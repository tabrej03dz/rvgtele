<?php

namespace App\Http\Controllers;

use App\Models\Data;
use App\Models\Lead;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DataController extends Controller
{
    /**
     * Display listing of data.
     */
    public function index(Request $request)
    {
        $query = Data::query()
            ->with(['company', 'lead']);

        /*
        |--------------------------------------------------------------------------
        | Company Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if ($request->filled('q')) {
            $search = trim($request->q);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('alternate_mobile', 'like', "%{$search}%")
                    ->orWhere('whatsapp_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('district', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Category Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        /*
        |--------------------------------------------------------------------------
        | Converted Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('converted')) {
            if ($request->converted === '1') {
                $query->where('converted', true);
            }

            if ($request->converted === '0') {
                $query->where('converted', false);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Deleted Filter
        |--------------------------------------------------------------------------
        */
        if ($request->get('show') === 'deleted') {
            $query->onlyTrashed();
        }

        $data = $query
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $companies = Company::orderBy('name')->get();

        $categories = Data::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('data.index', compact(
            'data',
            'companies',
            'categories'
        ));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $companies = Company::orderBy('name')->get();

        return view('data.create', compact('companies'));
    }

    /**
     * Store new data.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => [
                'required',
                'integer',
                'exists:companies,id',
            ],

            'name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'company_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:20',
            ],

            'alternate_mobile' => [
                'nullable',
                'string',
                'max:20',
            ],

            'whatsapp_number' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'category' => [
                'nullable',
                'string',
                'max:255',
            ],

            'lead_source' => [
                'nullable',
                'string',
                'max:255',
            ],

            'campaign' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'city' => [
                'nullable',
                'string',
                'max:255',
            ],

            'district' => [
                'nullable',
                'string',
                'max:255',
            ],

            'state' => [
                'nullable',
                'string',
                'max:255',
            ],

            'pincode' => [
                'nullable',
                'string',
                'max:10',
            ],

            'industry' => [
                'nullable',
                'string',
                'max:255',
            ],

            'required_product' => [
                'nullable',
                'string',
                'max:255',
            ],

            'preferred_language' => [
                'nullable',
                'string',
                'max:255',
            ],

            'estimated_budget' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],
        ]);

        Data::create($validated);

        return redirect()
            ->route('data.index')
            ->with('success', 'Data successfully added.');
    }

    /**
     * Display single record.
     */
    public function show(Data $data)
    {
        $data->load([
            'company',
            'lead',
        ]);

        return view('data.show', compact('data'));
    }

    /**
     * Show edit form.
     */
    public function edit(Data $data)
    {
        $companies = Company::orderBy('name')->get();

        return view('data.edit', compact(
            'data',
            'companies'
        ));
    }

    /**
     * Update existing data.
     */
    public function update(Request $request, Data $data)
    {
        $validated = $request->validate([
            'company_id' => [
                'required',
                'integer',
                'exists:companies,id',
            ],

            'name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'company_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:20',
            ],

            'alternate_mobile' => [
                'nullable',
                'string',
                'max:20',
            ],

            'whatsapp_number' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'category' => [
                'nullable',
                'string',
                'max:255',
            ],

            'lead_source' => [
                'nullable',
                'string',
                'max:255',
            ],

            'campaign' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'city' => [
                'nullable',
                'string',
                'max:255',
            ],

            'district' => [
                'nullable',
                'string',
                'max:255',
            ],

            'state' => [
                'nullable',
                'string',
                'max:255',
            ],

            'pincode' => [
                'nullable',
                'string',
                'max:10',
            ],

            'industry' => [
                'nullable',
                'string',
                'max:255',
            ],

            'required_product' => [
                'nullable',
                'string',
                'max:255',
            ],

            'preferred_language' => [
                'nullable',
                'string',
                'max:255',
            ],

            'estimated_budget' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],
        ]);

        $data->update($validated);

        return redirect()
            ->route('data.index')
            ->with('success', 'Data successfully updated.');
    }

    /**
     * Soft delete.
     */
    public function destroy(Data $data)
    {
        $data->delete();

        return redirect()
            ->route('data.index')
            ->with('success', 'Data deleted successfully.');
    }

    /**
     * Restore deleted data.
     */
    public function restore($id)
    {
        $data = Data::onlyTrashed()
            ->findOrFail($id);

        $data->restore();

        return redirect()
            ->route('data.index')
            ->with('success', 'Data restored successfully.');
    }

    /**
     * Permanently delete data.
     */
    public function forceDelete($id)
    {
        $data = Data::onlyTrashed()
            ->findOrFail($id);

        $data->forceDelete();

        return redirect()
            ->route('data.index')
            ->with('success', 'Data permanently deleted.');
    }

    /**
     * Convert data record to lead.
     */
    public function convertToLead(Request $request, Data $data)
    {
        if ($data->converted) {
            return back()->with(
                'error',
                'This data has already been converted into a lead.'
            );
        }

        if (empty($data->mobile)) {
            return back()->with(
                'error',
                'Mobile number is required to convert data into lead.'
            );
        }

        if (empty($data->name)) {
            return back()->with(
                'error',
                'Name is required to convert data into lead.'
            );
        }

        $request->validate([
            'branch_id' => [
                'nullable',
                'integer',
                'exists:branches,id',
            ],

            'team_id' => [
                'nullable',
                'integer',
                'exists:teams,id',
            ],

            'assigned_to' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'lead_source_id' => [
                'nullable',
                'integer',
                'exists:lead_sources,id',
            ],

            'lead_status_id' => [
                'nullable',
                'integer',
                'exists:lead_statuses,id',
            ],

            'campaign_id' => [
                'nullable',
                'integer',
                'exists:campaigns,id',
            ],

            'pipeline_stage_id' => [
                'nullable',
                'integer',
                'exists:pipeline_stages,id',
            ],

            'priority' => [
                'nullable',
                Rule::in([
                    'low',
                    'normal',
                    'high',
                    'urgent',
                    'hot',
                ]),
            ],

            'temperature' => [
                'nullable',
                Rule::in([
                    'cold',
                    'warm',
                    'hot',
                ]),
            ],
        ]);

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Duplicate Check
            |--------------------------------------------------------------------------
            */
            $existingLead = Lead::where('company_id', $data->company_id)
                ->where('mobile', $data->mobile)
                ->first();

            if ($existingLead) {
                DB::rollBack();

                return back()->with(
                    'error',
                    'This mobile number already exists in leads.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Create Lead
            |--------------------------------------------------------------------------
            */
            $lead = Lead::create([
                'company_id' => $data->company_id,

                'branch_id' => $request->branch_id,
                'team_id' => $request->team_id,
                'assigned_to' => $request->assigned_to,

                'created_by' => Auth::id(),

                'lead_source_id' => $request->lead_source_id,
                'lead_status_id' => $request->lead_status_id,
                'campaign_id' => $request->campaign_id,
                'pipeline_stage_id' => $request->pipeline_stage_id,

                'name' => $data->name,
                'company_name' => $data->company_name,

                'mobile' => $data->mobile,
                'alternate_mobile' => $data->alternate_mobile,
                'whatsapp_number' => $data->whatsapp_number,
                'email' => $data->email,

                'preferred_language' => $data->preferred_language,

                'address' => $data->address,
                'city' => $data->city,
                'district' => $data->district,
                'state' => $data->state,
                'pincode' => $data->pincode,

                'industry' => $data->industry,
                'required_product' => $data->required_product,

                'estimated_budget' => $data->estimated_budget,

                'priority' => $request->priority ?? 'normal',

                'temperature' => $request->temperature ?? 'cold',

                /*
                |--------------------------------------------------------------------------
                | Original Data Information
                |--------------------------------------------------------------------------
                */
                'custom_data' => [
                    'data_id' => $data->id,
                    'category' => $data->category,
                    'lead_source_text' => $data->lead_source,
                    'campaign_text' => $data->campaign,
                    'remarks' => $data->remarks,
                    'raw_data' => $data->raw_data,
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Mark Data Converted
            |--------------------------------------------------------------------------
            */
            $data->update([
                'converted' => true,
                'lead_id' => $lead->id,
                'converted_at' => now(),
            ]);

            DB::commit();

            return redirect()
                ->route('data.index')
                ->with(
                    'success',
                    'Data successfully converted into lead.'
                );

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return back()->with(
                'error',
                'Lead conversion failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Bulk delete selected data.
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => [
                'required',
                'array',
            ],

            'ids.*' => [
                'required',
                'integer',
                'exists:data,id',
            ],
        ]);

        Data::whereIn('id', $validated['ids'])
            ->delete();

        return back()->with(
            'success',
            'Selected data deleted successfully.'
        );
    }

    /**
     * Bulk convert selected data to leads.
     */
    public function bulkConvertToLead(Request $request)
    {
        $validated = $request->validate([
            'ids' => [
                'required',
                'array',
            ],

            'ids.*' => [
                'required',
                'integer',
                'exists:data,id',
            ],

            'assigned_to' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'branch_id' => [
                'nullable',
                'integer',
                'exists:branches,id',
            ],

            'team_id' => [
                'nullable',
                'integer',
                'exists:teams,id',
            ],

            'lead_source_id' => [
                'nullable',
                'integer',
                'exists:lead_sources,id',
            ],

            'lead_status_id' => [
                'nullable',
                'integer',
                'exists:lead_statuses,id',
            ],

            'campaign_id' => [
                'nullable',
                'integer',
                'exists:campaigns,id',
            ],

            'pipeline_stage_id' => [
                'nullable',
                'integer',
                'exists:pipeline_stages,id',
            ],
        ]);

        $records = Data::whereIn('id', $validated['ids'])
            ->where('converted', false)
            ->get();

        if ($records->isEmpty()) {
            return back()->with(
                'error',
                'No unconverted data found.'
            );
        }

        $convertedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();

        try {

            foreach ($records as $data) {

                /*
                |--------------------------------------------------------------------------
                | Required Fields Check
                |--------------------------------------------------------------------------
                */
                if (empty($data->name) || empty($data->mobile)) {
                    $skippedCount++;
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Duplicate Lead Check
                |--------------------------------------------------------------------------
                */
                $exists = Lead::where('company_id', $data->company_id)
                    ->where('mobile', $data->mobile)
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Create Lead
                |--------------------------------------------------------------------------
                */
                $lead = Lead::create([
                    'company_id' => $data->company_id,

                    'branch_id' => $request->branch_id,
                    'team_id' => $request->team_id,
                    'assigned_to' => $request->assigned_to,

                    'created_by' => Auth::id(),

                    'lead_source_id' => $request->lead_source_id,
                    'lead_status_id' => $request->lead_status_id,
                    'campaign_id' => $request->campaign_id,
                    'pipeline_stage_id' => $request->pipeline_stage_id,

                    'name' => $data->name,
                    'company_name' => $data->company_name,

                    'mobile' => $data->mobile,
                    'alternate_mobile' => $data->alternate_mobile,
                    'whatsapp_number' => $data->whatsapp_number,
                    'email' => $data->email,

                    'preferred_language' => $data->preferred_language,

                    'address' => $data->address,
                    'city' => $data->city,
                    'district' => $data->district,
                    'state' => $data->state,
                    'pincode' => $data->pincode,

                    'industry' => $data->industry,
                    'required_product' => $data->required_product,

                    'estimated_budget' => $data->estimated_budget,

                    'priority' => 'normal',
                    'temperature' => 'cold',

                    'custom_data' => [
                        'data_id' => $data->id,
                        'category' => $data->category,
                        'lead_source_text' => $data->lead_source,
                        'campaign_text' => $data->campaign,
                        'remarks' => $data->remarks,
                        'raw_data' => $data->raw_data,
                    ],
                ]);

                /*
                |--------------------------------------------------------------------------
                | Update Original Data
                |--------------------------------------------------------------------------
                */
                $data->update([
                    'converted' => true,
                    'lead_id' => $lead->id,
                    'converted_at' => now(),
                ]);

                $convertedCount++;
            }

            DB::commit();

            return back()->with(
                'success',
                "{$convertedCount} records converted into leads. {$skippedCount} records skipped."
            );

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return back()->with(
                'error',
                'Bulk conversion failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Mark converted record again as unconverted.
     *
     * Note:
     * This does NOT delete the associated lead.
     */
    public function markUnconverted(Data $data)
    {
        $data->update([
            'converted' => false,
            'lead_id' => null,
            'converted_at' => null,
        ]);

        return back()->with(
            'success',
            'Data marked as unconverted.'
        );
    }
}