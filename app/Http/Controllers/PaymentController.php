<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $this->companyId();

        $query = Payment::query()
            ->with([
                'customer:id,name,mobile',
                'order:id,order_number,total_amount,paid_amount',
            ])
            ->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('transaction_reference', 'like', "%{$search}%")
                    ->orWhere('method', 'like', "%{$search}%")
                    ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                        $customerQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    })
                    ->orWhereHas('order', function (Builder $orderQuery) use ($search) {
                        $orderQuery->where(
                            'order_number',
                            'like',
                            "%{$search}%"
                        );
                    });
            });
        }

        $items = $query
            ->latest('payment_date')
            ->paginate(20)
            ->withQueryString();

        return view('modules.index', [
            'items' => $items,
            'title' => 'Payments',
            'routeName' => 'payments',

            'columns' => [
                'customer.name',
                'order.order_number',
                'amount',
                'payment_date',
                'method',
                'transaction_reference',
            ],

            'columnLabels' => [
                'customer.name' => 'Customer',
                'order.order_number' => 'Order Number',
                'amount' => 'Payment Amount',
                'payment_date' => 'Payment Date',
                'method' => 'Payment Method',
                'transaction_reference' => 'Transaction Reference',
            ],
        ]);
    }

    public function create(): View
    {
        return view('modules.form', [
            'item' => new Payment(),
            'title' => 'Record Payment',
            'routeName' => 'payments',
            'fields' => $this->formFields(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['company_id'] = $this->companyId();

        DB::transaction(function () use ($data) {
            Payment::create($data);

            $this->refreshOrderPaymentTotals(
                (int) $data['order_id']
            );
        });

        return redirect()
            ->route('payments.index')
            ->with('success', 'Payment recorded successfully.');
    }

    public function edit(Payment $item): View
    {
        $this->authorizeCompanyRecord($item);

        return view('modules.form', [
            'item' => $item,
            'title' => 'Edit Payment',
            'routeName' => 'payments',
            'fields' => $this->formFields(),
        ]);
    }

    public function update(
        Request $request,
        Payment $item
    ): RedirectResponse {
        $this->authorizeCompanyRecord($item);

        $oldOrderId = (int) $item->order_id;
        $data = $this->validated($request, $item);

        DB::transaction(function () use (
            $item,
            $data,
            $oldOrderId
        ) {
            $item->update($data);

            $this->refreshOrderPaymentTotals($oldOrderId);

            if ($oldOrderId !== (int) $data['order_id']) {
                $this->refreshOrderPaymentTotals(
                    (int) $data['order_id']
                );
            }
        });

        return redirect()
            ->route('payments.index')
            ->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $item): RedirectResponse
    {
        $this->authorizeCompanyRecord($item);

        $orderId = (int) $item->order_id;

        DB::transaction(function () use ($item, $orderId) {
            $item->delete();

            $this->refreshOrderPaymentTotals($orderId);
        });

        return back()->with('success', 'Payment deleted successfully.');
    }

    private function formFields(): array
    {
        $companyId = $this->companyId();

        $customers = Customer::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'mobile',
                'company_name',
            ]);

        $orders = Order::query()
            ->with('customer:id,name')
            ->where('company_id', $companyId)
            ->whereNotIn('status', [
                'cancelled',
                'refunded',
            ])
            ->latest('order_date')
            ->get([
                'id',
                'customer_id',
                'order_number',
                'total_amount',
                'paid_amount',
                'payment_status',
            ]);

        return [
            'customer_id' => [
                'type' => 'select',
                'label' => 'Customer',
                'required' => true,
                'empty_label' => 'Select Customer',
                'options' => $customers,
                'option_label' => function (Customer $customer): string {
                    $parts = [$customer->name];

                    if ($customer->company_name) {
                        $parts[] = $customer->company_name;
                    }

                    if ($customer->mobile) {
                        $parts[] = $customer->mobile;
                    }

                    return implode(' - ', $parts);
                },
            ],

            'order_id' => [
                'type' => 'select',
                'label' => 'Order',
                'required' => true,
                'empty_label' => 'Select Order',
                'options' => $orders,
                'option_label' => function (Order $order): string {
                    $customerName = $order->customer?->name
                        ?? 'Unknown Customer';

                    $pendingAmount = max(
                        0,
                        (float) $order->total_amount
                        - (float) $order->paid_amount
                    );

                    return sprintf(
                        '%s - %s - Pending ₹%s',
                        $order->order_number,
                        $customerName,
                        number_format($pendingAmount, 2)
                    );
                },
            ],

            'amount' => [
                'type' => 'number',
                'label' => 'Payment Amount',
                'required' => true,
                'min' => 0.01,
                'step' => '0.01',
            ],

            'payment_date' => [
                'type' => 'date',
                'label' => 'Payment Date',
                'required' => true,
            ],

            'method' => [
                'type' => 'select',
                'label' => 'Payment Method',
                'required' => true,
                'empty_label' => 'Select Payment Method',
                'options' => [
                    ['id' => 'cash', 'name' => 'Cash'],
                    ['id' => 'upi', 'name' => 'UPI'],
                    ['id' => 'card', 'name' => 'Card'],
                    ['id' => 'bank_transfer', 'name' => 'Bank Transfer'],
                    ['id' => 'cheque', 'name' => 'Cheque'],
                    ['id' => 'payment_gateway', 'name' => 'Payment Gateway'],
                    ['id' => 'other', 'name' => 'Other'],
                ],
            ],

            'transaction_reference' => [
                'type' => 'text',
                'label' => 'Transaction Reference',
                'placeholder' => 'UPI reference, cheque number or transaction ID',
            ],

            'remarks' => [
                'type' => 'textarea',
                'label' => 'Payment Remarks',
                'placeholder' => 'Enter payment remarks',
            ],
        ];
    }

    private function validated(
        Request $request,
        ?Payment $payment = null
    ): array {
        $companyId = $this->companyId();

        $data = $request->validate([
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')
                    ->where(fn ($query) => $query
                        ->where('company_id', $companyId)),
            ],

            'order_id' => [
                'required',
                'integer',
                Rule::exists('orders', 'id')
                    ->where(fn ($query) => $query
                        ->where('company_id', $companyId)),
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'payment_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'method' => [
                'required',
                Rule::in([
                    'cash',
                    'upi',
                    'card',
                    'bank_transfer',
                    'cheque',
                    'payment_gateway',
                    'other',
                ]),
            ],

            'transaction_reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $order = Order::query()
            ->where('company_id', $companyId)
            ->findOrFail($data['order_id']);

        if ((int) $order->customer_id !== (int) $data['customer_id']) {
            abort(
                422,
                'The selected order does not belong to the selected customer.'
            );
        }

        $existingOtherPayments = Payment::query()
            ->where('company_id', $companyId)
            ->where('order_id', $order->id)
            ->when(
                $payment,
                fn ($query) => $query->where('id', '!=', $payment->id)
            )
            ->sum('amount');

        $remainingAmount = max(
            0,
            (float) $order->total_amount
            - (float) $existingOtherPayments
        );

        if ((float) $data['amount'] > $remainingAmount) {
            abort(
                422,
                'Payment amount cannot be greater than the pending order amount of ₹'
                . number_format($remainingAmount, 2)
                . '.'
            );
        }

        return $data;
    }

    private function refreshOrderPaymentTotals(int $orderId): void
    {
        $order = Order::query()
            ->where('company_id', $this->companyId())
            ->find($orderId);

        if (!$order) {
            return;
        }

        $paidAmount = (float) Payment::query()
            ->where('company_id', $this->companyId())
            ->where('order_id', $order->id)
            ->sum('amount');

        $totalAmount = (float) $order->total_amount;
        $pendingAmount = max(0, $totalAmount - $paidAmount);

        $paymentStatus = match (true) {
            $paidAmount <= 0 => 'unpaid',
            $paidAmount >= $totalAmount => 'paid',
            default => 'partially_paid',
        };

        $order->update([
            'paid_amount' => $paidAmount,
            'pending_amount' => $pendingAmount,
            'payment_status' => $paymentStatus,
        ]);
    }

    private function companyId(): int
    {
        return (int) Auth::user()->company_id;
    }

    private function authorizeCompanyRecord(Payment $payment): void
    {
        abort_unless(
            (int) $payment->company_id === $this->companyId(),
            403,
            'Unauthorized payment access.'
        );
    }
}