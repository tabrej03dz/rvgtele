<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CallDisposition;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    /**
     * Company list
     */
    public function index(Request $request)
    {
        $this->authorizePermission($request, 'companies.view');

        $query = Company::query()
            ->withCount([
                'branches',
                'teams',
                'users',
                'leads',
            ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            }

            if ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $companies = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('companies.index', compact('companies'));
    }

    /**
     * Create company form
     */
    public function create(Request $request)
    {
        $this->authorizePermission($request, 'companies.create');

        return view('companies.create');
    }

    /**
     * Store new company
     */
    public function store(Request $request)
    {
        $this->authorizePermission($request, 'companies.create');

        $data = $request->validate([
            /*
            |--------------------------------------------------------------------------
            | Company
            |--------------------------------------------------------------------------
            */
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                'unique:companies,code',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Head Office
            |--------------------------------------------------------------------------
            */
            'branch_name' => [
                'required',
                'string',
                'max:255',
            ],

            'branch_code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
            ],

            'branch_phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'branch_address' => [
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Company Owner
            |--------------------------------------------------------------------------
            */
            'owner_name' => [
                'required',
                'string',
                'max:255',
            ],

            'owner_email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'owner_phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'owner_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        DB::transaction(function () use ($data) {
            /*
            |--------------------------------------------------------------------------
            | 1. Company
            |--------------------------------------------------------------------------
            */

            $company = Company::create([
                'name' => trim($data['name']),
                'code' => strtoupper(trim($data['code'])),
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'is_active' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 2. Head Office Branch
            |--------------------------------------------------------------------------
            */

            $branch = Branch::create([
                'company_id' => $company->id,
                'name' => trim($data['branch_name']),
                'code' => strtoupper(trim($data['branch_code'])),
                'phone' => $data['branch_phone'] ?? $data['phone'] ?? null,
                'address' => $data['branch_address'] ?? $data['address'] ?? null,
                'is_active' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 3. Company Owner
            |--------------------------------------------------------------------------
            */

            $owner = User::create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'team_id' => null,

                'name' => trim($data['owner_name']),
                'email' => strtolower(trim($data['owner_email'])),
                'phone' => $data['owner_phone'] ?? null,

                'employee_code' => $this->generateOwnerEmployeeCode(
                    $company
                ),

                'password' => Hash::make(
                    $data['owner_password']
                ),

                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 4. Assign Company Owner Role
            |--------------------------------------------------------------------------
            */

            $owner->syncRoles([
                'company_owner',
            ]);

            /*
            |--------------------------------------------------------------------------
            | 5. Default Sales Team
            |--------------------------------------------------------------------------
            */

            $team = Team::create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'name' => 'Sales Team A',
                'leader_id' => $owner->id,
                'is_active' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Owner ko default team me add karo
            |--------------------------------------------------------------------------
            */

            $owner->update([
                'team_id' => $team->id,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 6. Default CRM Setup
            |--------------------------------------------------------------------------
            */

            $this->createDefaultCrmSetup($company);
        });

        return redirect()
            ->route('companies.index')
            ->with(
                'success',
                'Company, Head Office, Company Owner aur default CRM setup successfully create ho gaya.'
            );
    }

    /**
     * Edit company
     */
    public function edit(Request $request, Company $company)
    {
        $this->authorizePermission($request, 'companies.update');

        $company->load([
            'branches',
            'users.roles',
        ]);

        return view(
            'companies.edit',
            compact('company')
        );
    }

    /**
     * Update company
     */
    public function update(
        Request $request,
        Company $company
    ) {
        $this->authorizePermission($request, 'companies.update');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',

                Rule::unique(
                    'companies',
                    'code'
                )->ignore($company->id),
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $company->update([
            'name' => trim($data['name']),
            'code' => strtoupper(
                trim($data['code'])
            ),
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'is_active' => $request->boolean(
                'is_active'
            ),
        ]);

        return redirect()
            ->route('companies.index')
            ->with(
                'success',
                'Company successfully updated.'
            );
    }

    /**
     * Disable/delete company
     *
     * Company soft delete hogi.
     */
    public function destroy(
        Request $request,
        Company $company
    ) {
        $this->authorizePermission($request, 'companies.delete');

        /*
        |--------------------------------------------------------------------------
        | Apni currently attached company delete mat hone do
        |--------------------------------------------------------------------------
        */

        if (
            (int) $request->user()->company_id
            ===
            (int) $company->id
        ) {
            return back()->with(
                'error',
                'Aap jis company se currently attached hain, use delete nahi kar sakte.'
            );
        }

        DB::transaction(function () use ($company) {
            /*
            |--------------------------------------------------------------------------
            | Users ko inactive karo
            |--------------------------------------------------------------------------
            */

            User::where(
                'company_id',
                $company->id
            )->update([
                'is_active' => false,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Company inactive + soft delete
            |--------------------------------------------------------------------------
            */

            $company->update([
                'is_active' => false,
            ]);

            $company->delete();
        });

        return redirect()
            ->route('companies.index')
            ->with(
                'success',
                'Company successfully removed.'
            );
    }

    /**
     * Permission check. Super Admin is automatically allowed by Gate::before().
     */
    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless(
            $request->user() && $request->user()->can($permission),
            403,
            'You do not have permission to perform this company action.'
        );
    }

    /**
     * Unique owner employee code
     */
    private function generateOwnerEmployeeCode(
        Company $company
    ): string {
        $base = strtoupper(
            preg_replace(
                '/[^A-Za-z0-9]/',
                '',
                $company->code
            )
        );

        $base = substr(
            $base ?: 'COMP',
            0,
            8
        );

        $counter = 1;

        do {
            $code = sprintf(
                '%s-OWN-%03d',
                $base,
                $counter
            );

            $exists = User::where(
                'employee_code',
                $code
            )->exists();

            $counter++;
        } while ($exists);

        return $code;
    }

    /**
     * Default CRM configuration
     */
    private function createDefaultCrmSetup(
        Company $company
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Lead Sources
        |--------------------------------------------------------------------------
        */

        $leadSources = [
            'Manual Entry',
            'Excel Import',
            'Website',
            'Facebook',
            'Google Ads',
            'WhatsApp',
            'Referral',
            'IndiaMART',
            'Justdial',
        ];

        foreach ($leadSources as $source) {
            LeadSource::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => $source,
                ],
                [
                    'is_active' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lead Statuses
        |--------------------------------------------------------------------------
        */

        $leadStatuses = [
            [
                'New',
                '#2563eb',
            ],
            [
                'Assigned',
                '#7c3aed',
            ],
            [
                'Attempted',
                '#64748b',
            ],
            [
                'Connected',
                '#0891b2',
            ],
            [
                'Follow-up Required',
                '#f59e0b',
            ],
            [
                'Interested',
                '#16a34a',
            ],
            [
                'Qualified',
                '#15803d',
            ],
            [
                'Converted',
                '#059669',
            ],
            [
                'Not Interested',
                '#dc2626',
            ],
            [
                'Invalid Number',
                '#991b1b',
            ],
            [
                'Lost',
                '#475569',
            ],
        ];

        foreach (
            $leadStatuses as $index => $status
        ) {
            $name = $status[0];
            $color = $status[1];

            LeadStatus::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'slug' => Str::slug($name),
                ],
                [
                    'name' => $name,
                    'color' => $color,
                    'sort_order' => $index,
                    'is_converted' => $name === 'Converted',
                    'is_lost' => $name === 'Lost',
                    'is_active' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Call Dispositions
        |--------------------------------------------------------------------------
        */

        $dispositions = [
            [
                'Interested',
                'connected',
                true,
            ],
            [
                'Follow-up',
                'connected',
                true,
            ],
            [
                'Send Details',
                'connected',
                false,
            ],
            [
                'Converted',
                'connected',
                false,
            ],
            [
                'Not Interested',
                'connected',
                false,
            ],
            [
                'No Answer',
                'not_connected',
                true,
            ],
            [
                'Busy',
                'not_connected',
                true,
            ],
            [
                'Switched Off',
                'not_connected',
                true,
            ],
            [
                'Wrong Number',
                'not_connected',
                false,
            ],
            [
                'Do Not Call',
                'other',
                false,
            ],
        ];

        foreach ($dispositions as $disposition) {
            CallDisposition::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => $disposition[0],
                ],
                [
                    'type' => $disposition[1],
                    'requires_follow_up' => $disposition[2],
                    'requires_remarks' => true,
                    'is_active' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Default Pipeline
        |--------------------------------------------------------------------------
        */

        $pipeline = Pipeline::firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Default Sales Pipeline',
            ],
            [
                'is_default' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Pipeline Stages
        |--------------------------------------------------------------------------
        */

        $stages = [
            [
                'New Lead',
                '#2563eb',
                10,
            ],
            [
                'Contacted',
                '#0891b2',
                20,
            ],
            [
                'Interested',
                '#16a34a',
                40,
            ],
            [
                'Qualified',
                '#15803d',
                60,
            ],
            [
                'Demo / Meeting',
                '#7c3aed',
                70,
            ],
            [
                'Quotation',
                '#d97706',
                80,
            ],
            [
                'Negotiation',
                '#ea580c',
                90,
            ],
            [
                'Won',
                '#059669',
                100,
            ],
            [
                'Lost',
                '#dc2626',
                0,
            ],
        ];

        foreach (
            $stages as $index => $stage
        ) {
            PipelineStage::firstOrCreate(
                [
                    'pipeline_id' => $pipeline->id,
                    'name' => $stage[0],
                ],
                [
                    'color' => $stage[1],
                    'probability' => $stage[2],
                    'sort_order' => $index,
                    'is_won' => $stage[0] === 'Won',
                    'is_lost' => $stage[0] === 'Lost',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Default Product
        |--------------------------------------------------------------------------
        */

        Product::firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'CRM-001',
            ],
            [
                'name' => 'Default Product / Service',
                'description' => 'Default CRM product/service',
                'base_price' => 0,
                'tax_percent' => 0,
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Default Campaign
        |--------------------------------------------------------------------------
        */

        Campaign::firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'CMP001',
            ],
            [
                'name' => 'Default Calling Campaign',
                'status' => 'active',
                'description' => 'Default campaign',
            ]
        );
    }
}