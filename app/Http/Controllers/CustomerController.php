<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $this->companyId();

        $query = Customer::query()
            ->with('accountManager:id,name')
            ->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('accountManager', function (Builder $userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $items = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('modules.index', [
            'items' => $items,
            'title' => 'Customers',
            'routeName' => 'customers',

            'columns' => [
                'name',
                'company_name',
                'mobile',
                'email',
                'category',
                'accountManager.name',
            ],

            'columnLabels' => [
                'name' => 'Customer Name',
                'company_name' => 'Business Name',
                'mobile' => 'Mobile',
                'email' => 'Email',
                'category' => 'Category',
                'accountManager.name' => 'Account Manager',
            ],
        ]);
    }

    public function create(): View
    {
        return view('modules.form', [
            'item' => new Customer(),
            'title' => 'Create Customer',
            'routeName' => 'customers',
            'fields' => $this->formFields(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['company_id'] = $this->companyId();

        Customer::create($data);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function edit(Customer $item): View
    {
        $this->authorizeCompanyRecord($item);

        return view('modules.form', [
            'item' => $item,
            'title' => 'Edit Customer',
            'routeName' => 'customers',
            'fields' => $this->formFields(),
        ]);
    }

    public function update(
        Request $request,
        Customer $item
    ): RedirectResponse {
        $this->authorizeCompanyRecord($item);

        $item->update($this->validated($request));

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $item): RedirectResponse
    {
        $this->authorizeCompanyRecord($item);

        if ($item->orders()->exists()) {
            return back()->with(
                'error',
                'Customer cannot be deleted because orders are associated with this customer.'
            );
        }

        $item->delete();

        return back()->with('success', 'Customer deleted successfully.');
    }

    private function formFields(): array
    {
        $accountManagers = User::query()
            ->where('company_id', $this->companyId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'employee_code',
            ]);

        return [
            'name' => [
                'type' => 'text',
                'label' => 'Customer Name',
                'required' => true,
                'placeholder' => 'Enter customer name',
            ],

            'company_name' => [
                'type' => 'text',
                'label' => 'Business Name',
                'placeholder' => 'Enter business or company name',
            ],

            'mobile' => [
                'type' => 'text',
                'label' => 'Mobile Number',
                'required' => true,
                'placeholder' => 'Enter mobile number',
            ],

            'email' => [
                'type' => 'email',
                'label' => 'Email Address',
                'placeholder' => 'Enter email address',
            ],

            'category' => [
                'type' => 'select',
                'label' => 'Customer Category',
                'required' => true,
                'empty_label' => 'Select Customer Category',
                'options' => [
                    ['id' => 'new', 'name' => 'New Customer'],
                    ['id' => 'active', 'name' => 'Active Customer'],
                    ['id' => 'repeat', 'name' => 'Repeat Customer'],
                    ['id' => 'premium', 'name' => 'Premium Customer'],
                    ['id' => 'inactive', 'name' => 'Inactive Customer'],
                    ['id' => 'blacklisted', 'name' => 'Blacklisted Customer'],
                ],
            ],

            'account_manager_id' => [
                'type' => 'select',
                'label' => 'Account Manager',
                'empty_label' => 'Select Account Manager',
                'options' => $accountManagers,
                'option_label' => function (User $user): string {
                    return $user->employee_code
                        ? "{$user->name} ({$user->employee_code})"
                        : $user->name;
                },
            ],

            'address' => [
                'type' => 'textarea',
                'label' => 'Complete Address',
                'placeholder' => 'Enter complete customer address',
                'rows' => 4,
            ],
        ];
    }

    private function validated(Request $request): array
    {
        $companyId = $this->companyId();

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'company_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'mobile' => [
                'required',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'category' => [
                'required',
                Rule::in([
                    'new',
                    'active',
                    'repeat',
                    'premium',
                    'inactive',
                    'blacklisted',
                ]),
            ],

            'account_manager_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where(fn ($query) => $query
                        ->where('company_id', $companyId)
                        ->where('is_active', true)),
            ],
        ]);
    }

    private function companyId(): int
    {
        return (int) Auth::user()->company_id;
    }

    private function authorizeCompanyRecord(Customer $customer): void
    {
        abort_unless(
            (int) $customer->company_id === $this->companyId(),
            403,
            'Unauthorized customer access.'
        );
    }
}