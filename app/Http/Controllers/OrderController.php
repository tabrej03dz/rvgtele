<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $this->companyId();

        $query = Order::query()
            ->with([
                'customer:id,name,mobile',
                'lead:id,name,mobile',
            ])
            ->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('order_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('payment_status', 'like', "%{$search}%")
                    ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                        $customerQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    })
                    ->orWhereHas('lead', function (Builder $leadQuery) use ($search) {
                        $leadQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    });
            });
        }

        $items = $query
            ->latest('order_date')
            ->paginate(20)
            ->withQueryString();

        return view('modules.index', [
            'items' => $items,
            'title' => 'Orders',
            'routeName' => 'orders',

            'columns' => [
                'order_number',
                'customer.name',
                'lead.name',
                'order_date',
                'total_amount',
                'paid_amount',
                'payment_status',
                'status',
            ],

            'columnLabels' => [
                'order_number' => 'Order Number',
                'customer.name' => 'Customer',
                'lead.name' => 'Lead',
                'order_date' => 'Order Date',
                'total_amount' => 'Total Amount',
                'paid_amount' => 'Paid Amount',
                'payment_status' => 'Payment Status',
                'status' => 'Order Status',
            ],
        ]);
    }

    public function create(): View
    {
        return view('modules.form', [
            'item' => new Order(),
            'title' => 'Create Order',
            'routeName' => 'orders',
            'fields' => $this->formFields(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $data['company_id'] = $this->companyId();

        if (empty($data['order_number'])) {
            $data['order_number'] = $this->generateOrderNumber();
        }

        $data['net_amount'] = $data['total_amount'];
        $data['pending_amount'] = max(
            0,
            (float) $data['total_amount'] - (float) $data['paid_amount']
        );

        $data['payment_status'] = $this->resolvePaymentStatus(
            (float) $data['total_amount'],
            (float) $data['paid_amount']
        );

        Order::create($data);

        return redirect()
            ->route('orders.index')
            ->with('success', 'Order created successfully.');
    }

    public function edit(Order $item): View
    {
        $this->authorizeCompanyRecord($item);

        return view('modules.form', [
            'item' => $item,
            'title' => 'Edit Order',
            'routeName' => 'orders',
            'fields' => $this->formFields(),
        ]);
    }

    public function update(
        Request $request,
        Order $item
    ): RedirectResponse {
        $this->authorizeCompanyRecord($item);

        $data = $this->validated($request, $item);

        $data['net_amount'] = $data['total_amount'];
        $data['pending_amount'] = max(
            0,
            (float) $data['total_amount'] - (float) $data['paid_amount']
        );

        $data['payment_status'] = $this->resolvePaymentStatus(
            (float) $data['total_amount'],
            (float) $data['paid_amount']
        );

        $item->update($data);

        return redirect()
            ->route('orders.index')
            ->with('success', 'Order updated successfully.');
    }

    public function destroy(Order $item): RedirectResponse
    {
        $this->authorizeCompanyRecord($item);

        if ($item->payments()->exists()) {
            return back()->with(
                'error',
                'Order cannot be deleted because payments are associated with it.'
            );
        }

        $item->delete();

        return back()->with('success', 'Order deleted successfully.');
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

        $leads = Lead::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'mobile',
                'company_name',
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

            'lead_id' => [
                'type' => 'select',
                'label' => 'Related Lead',
                'empty_label' => 'Select Related Lead',
                'options' => $leads,
                'option_label' => function (Lead $lead): string {
                    $parts = [$lead->name];

                    if ($lead->company_name) {
                        $parts[] = $lead->company_name;
                    }

                    if ($lead->mobile) {
                        $parts[] = $lead->mobile;
                    }

                    return implode(' - ', $parts);
                },
            ],

            'order_number' => [
                'type' => 'text',
                'label' => 'Order Number',
                'placeholder' => 'Leave empty for automatic order number',
                'help' => 'System automatically generates an order number when left empty.',
            ],

            'order_date' => [
                'type' => 'date',
                'label' => 'Order Date',
                'required' => true,
            ],

            'total_amount' => [
                'type' => 'number',
                'label' => 'Total Amount',
                'required' => true,
                'min' => 0,
                'step' => '0.01',
            ],

            'paid_amount' => [
                'type' => 'number',
                'label' => 'Paid Amount',
                'required' => true,
                'min' => 0,
                'step' => '0.01',
            ],

            'status' => [
                'type' => 'select',
                'label' => 'Order Status',
                'required' => true,
                'empty_label' => 'Select Order Status',
                'options' => [
                    ['id' => 'pending', 'name' => 'Pending'],
                    ['id' => 'confirmed', 'name' => 'Confirmed'],
                    ['id' => 'processing', 'name' => 'Processing'],
                    ['id' => 'completed', 'name' => 'Completed'],
                    ['id' => 'cancelled', 'name' => 'Cancelled'],
                    ['id' => 'refunded', 'name' => 'Refunded'],
                ],
            ],

            'notes' => [
                'type' => 'textarea',
                'label' => 'Order Notes',
                'placeholder' => 'Enter order notes',
            ],
        ];
    }

    private function validated(
        Request $request,
        ?Order $order = null
    ): array {
        $companyId = $this->companyId();

        return $request->validate([
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')
                    ->where(fn ($query) => $query
                        ->where('company_id', $companyId)),
            ],

            'lead_id' => [
                'nullable',
                'integer',
                Rule::exists('leads', 'id')
                    ->where(fn ($query) => $query
                        ->where('company_id', $companyId)
                        ->whereNull('deleted_at')),
            ],

            'order_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('orders', 'order_number')
                    ->where(fn ($query) => $query
                        ->where('company_id', $companyId))
                    ->ignore($order?->id),
            ],

            'order_date' => [
                'required',
                'date',
            ],

            'total_amount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'paid_amount' => [
                'required',
                'numeric',
                'min:0',
                'lte:total_amount',
            ],

            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'confirmed',
                    'processing',
                    'completed',
                    'cancelled',
                    'refunded',
                ]),
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);
    }

    private function generateOrderNumber(): string
    {
        $prefix = 'ORD-' . now()->format('Ym') . '-';

        $lastOrder = Order::query()
            ->where('company_id', $this->companyId())
            ->where('order_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        $lastSequence = 0;

        if ($lastOrder) {
            $lastSequence = (int) str_replace(
                $prefix,
                '',
                $lastOrder->order_number
            );
        }

        return $prefix . str_pad(
            (string) ($lastSequence + 1),
            5,
            '0',
            STR_PAD_LEFT
        );
    }

    private function resolvePaymentStatus(
        float $totalAmount,
        float $paidAmount
    ): string {
        if ($paidAmount <= 0) {
            return 'unpaid';
        }

        if ($paidAmount >= $totalAmount) {
            return 'paid';
        }

        return 'partially_paid';
    }

    private function companyId(): int
    {
        return (int) Auth::user()->company_id;
    }

    private function authorizeCompanyRecord(Order $order): void
    {
        abort_unless(
            (int) $order->company_id === $this->companyId(),
            403,
            'Unauthorized order access.'
        );
    }
}