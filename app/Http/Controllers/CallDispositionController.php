<?php

namespace App\Http\Controllers;

use App\Models\CallDisposition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CallDispositionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $companyId = $this->companyId();

        $query = CallDisposition::query()
            ->where('company_id', $companyId);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(function ($query) use ($search) {
                $query
                    ->where(
                        'name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'type',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'auto_remarks',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $items = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('modules.index', [
            'items' => $items,

            'title' => 'Call Dispositions',

            'routeName' => 'crm-settings.call-dispositions',

            'columns' => [
                'name',
                'type',
                'requires_follow_up',
                'requires_remarks',
                'auto_remarks',
                'next_followup',
                'is_active',
            ],

            'columnLabels' => [
                'name' => 'Disposition Name',
                'type' => 'Type',
                'requires_follow_up' => 'Follow-up Required',
                'requires_remarks' => 'Remarks Required',
                'auto_remarks' => 'Auto Remarks',
                'next_followup' => 'Next Follow-up',
                'is_active' => 'Active',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(): View
    {
        return view('modules.form', [
            'item' => new CallDisposition(),

            'title' => 'Create Call Disposition',

            'routeName' => 'crm-settings.call-dispositions',

            'fields' => $this->formFields(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */

        $data = $this->validated(
            $request
        );

        /*
        |--------------------------------------------------------------------------
        | Company
        |--------------------------------------------------------------------------
        */

        $data['company_id'] =
            $this->companyId();

        /*
        |--------------------------------------------------------------------------
        | Boolean Fields
        |--------------------------------------------------------------------------
        */

        $this->normalizeBooleans(
            $request,
            $data
        );

        /*
        |--------------------------------------------------------------------------
        | Normalize Nullable Fields
        |--------------------------------------------------------------------------
        */

        $data['auto_remarks'] = $request->filled('auto_remarks')
            ? trim((string) $request->input('auto_remarks'))
            : null;

        $data['next_followup'] = $request->filled('next_followup')
            ? (int) $request->input('next_followup')
            : null;

        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        */

        CallDisposition::create(
            $data
        );

        return redirect()
            ->route(
                'crm-settings.call-dispositions.index'
            )
            ->with(
                'success',
                'Call Disposition created successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(
        CallDisposition $item
    ): View {
        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        */

        $this->guardCompany(
            $item
        );

        return view('modules.form', [
            'item' => $item,

            'title' => 'Edit Call Disposition',

            'routeName' => 'crm-settings.call-dispositions',

            'fields' => $this->formFields(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        CallDisposition $item
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        */

        $this->guardCompany(
            $item
        );

        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */

        $data = $this->validated(
            $request
        );

        /*
        |--------------------------------------------------------------------------
        | Boolean Fields
        |--------------------------------------------------------------------------
        */

        $this->normalizeBooleans(
            $request,
            $data
        );

        /*
        |--------------------------------------------------------------------------
        | Normalize Nullable Fields
        |--------------------------------------------------------------------------
        */

        $data['auto_remarks'] = $request->filled('auto_remarks')
            ? trim((string) $request->input('auto_remarks'))
            : null;

        $data['next_followup'] = $request->filled('next_followup')
            ? (int) $request->input('next_followup')
            : null;

        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        $item->update(
            $data
        );

        return redirect()
            ->route(
                'crm-settings.call-dispositions.index'
            )
            ->with(
                'success',
                'Call Disposition updated successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(
        CallDisposition $item
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        */

        $this->guardCompany(
            $item
        );

        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */

        $item->delete();

        return redirect()
            ->route(
                'crm-settings.call-dispositions.index'
            )
            ->with(
                'success',
                'Call Disposition deleted successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Form Fields
    |--------------------------------------------------------------------------
    */

    private function formFields(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Disposition Name
            |--------------------------------------------------------------------------
            */

            'name' => [
                'type' => 'text',

                'label' =>
                    'Disposition Name',

                'required' =>
                    true,

                'placeholder' =>
                    'Enter disposition name',

                'help' =>
                    'Example: Interested, No Answer, Call Later, Converted etc.',
            ],

            /*
            |--------------------------------------------------------------------------
            | Type
            |--------------------------------------------------------------------------
            */

            'type' => [
                'type' => 'text',

                'label' =>
                    'Type',

                'required' =>
                    false,

                'placeholder' =>
                    'Enter type',

                'help' =>
                    'Optional custom type. Aap apne hisaab se koi bhi type enter kar sakte hain.',
            ],

            /*
            |--------------------------------------------------------------------------
            | Requires Follow Up
            |--------------------------------------------------------------------------
            */

            'requires_follow_up' => [
                'type' =>
                    'checkbox',

                'label' =>
                    'Requires Follow-up',

                'help' =>
                    'Enable karein agar is disposition par follow-up required hona chahiye.',
            ],

            /*
            |--------------------------------------------------------------------------
            | Requires Remarks
            |--------------------------------------------------------------------------
            */

            'requires_remarks' => [
                'type' =>
                    'checkbox',

                'label' =>
                    'Requires Remarks',

                'help' =>
                    'Enable karein agar is disposition par remarks required hone chahiye.',
            ],

            /*
            |--------------------------------------------------------------------------
            | Auto Remarks
            |--------------------------------------------------------------------------
            */

            'auto_remarks' => [
                'type' =>
                    'textarea',

                'label' =>
                    'Auto Remarks',

                'required' =>
                    false,

                'placeholder' =>
                    'Enter automatic remarks',

                'rows' =>
                    4,

                'help' =>
                    'Disposition select hone par default remarks ke roop me use ki ja sakti hain.',
            ],

            /*
            |--------------------------------------------------------------------------
            | Next Follow-up
            |--------------------------------------------------------------------------
            |
            | Value Minutes me store hogi.
            |
            | Example:
            | 30 = 30 Minutes
            | 60 = 1 Hour
            | 1440 = 1 Day
            |
            */

            'next_followup' => [
                'type' =>
                    'number',

                'label' =>
                    'Next Follow-up (Minutes)',

                'required' =>
                    false,

                'placeholder' =>
                    'Example: 30, 60, 1440',

                'min' =>
                    0,

                'step' =>
                    1,

                'help' =>
                    'Follow-up kitne minutes baad automatically schedule hona chahiye. Example: 60 = 1 hour, 1440 = 1 day.',
            ],

            /*
            |--------------------------------------------------------------------------
            | Active
            |--------------------------------------------------------------------------
            */

            'is_active' => [
                'type' =>
                    'checkbox',

                'label' =>
                    'Active',

                'help' =>
                    'Active hone par ye disposition calling screen par available rahegi.',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    private function validated(
        Request $request
    ): array {
        return $request->validate(
            [
                /*
                |--------------------------------------------------------------------------
                | Name
                |--------------------------------------------------------------------------
                */

                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                /*
                |--------------------------------------------------------------------------
                | Type
                |--------------------------------------------------------------------------
                */

                'type' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                /*
                |--------------------------------------------------------------------------
                | Follow Up
                |--------------------------------------------------------------------------
                */

                'requires_follow_up' => [
                    'nullable',
                    'boolean',
                ],

                /*
                |--------------------------------------------------------------------------
                | Remarks Required
                |--------------------------------------------------------------------------
                */

                'requires_remarks' => [
                    'nullable',
                    'boolean',
                ],

                /*
                |--------------------------------------------------------------------------
                | Auto Remarks
                |--------------------------------------------------------------------------
                */

                'auto_remarks' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],

                /*
                |--------------------------------------------------------------------------
                | Next Follow-up
                |--------------------------------------------------------------------------
                */

                'next_followup' => [
                    'nullable',
                    'integer',
                    'min:0',
                    'max:525600',
                ],

                /*
                |--------------------------------------------------------------------------
                | Active
                |--------------------------------------------------------------------------
                */

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ],
            [
                /*
                |--------------------------------------------------------------------------
                | Validation Messages
                |--------------------------------------------------------------------------
                */

                'name.required' =>
                    'Disposition name is required.',

                'name.max' =>
                    'Disposition name cannot be longer than 255 characters.',

                'type.max' =>
                    'Type cannot be longer than 100 characters.',

                'auto_remarks.max' =>
                    'Auto remarks cannot be longer than 5000 characters.',

                'next_followup.integer' =>
                    'Next follow-up must be a valid number.',

                'next_followup.min' =>
                    'Next follow-up cannot be negative.',

                'next_followup.max' =>
                    'Next follow-up duration is too large.',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Boolean Fields
    |--------------------------------------------------------------------------
    */

    private function normalizeBooleans(
        Request $request,
        array &$data
    ): void {
        $data['requires_follow_up'] =
            $request->boolean(
                'requires_follow_up'
            );

        $data['requires_remarks'] =
            $request->boolean(
                'requires_remarks'
            );

        $data['is_active'] =
            $request->boolean(
                'is_active'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Company ID
    |--------------------------------------------------------------------------
    */

    private function companyId(): int
    {
        return (int)
            Auth::user()->company_id;
    }

    /*
    |--------------------------------------------------------------------------
    | Company Security
    |--------------------------------------------------------------------------
    */

    private function guardCompany(
        CallDisposition $item
    ): void {
        abort_unless(
            (int) $item->company_id ===
                $this->companyId(),

            403,

            'You are not authorized to manage this call disposition.'
        );
    }
}