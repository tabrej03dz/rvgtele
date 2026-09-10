<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Data;
use App\Models\Lead;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class DataController extends Controller
{
    /**
     * Display listing of data.
     */

    // public function index(Request $request)
    // {
    //     /*
    //     |--------------------------------------------------------------------------
    //     | Main listing query
    //     |--------------------------------------------------------------------------
    //     */
    //     $query = Data::query()->with([
    //         'company',
    //         'lead',
    //         'categoryInfo',
    //     ]);

    //     $this->applyIndexFilters($query, $request, true);

    //     $data = $query
    //         ->latest('id')
    //         ->paginate(25)
    //         ->withQueryString();

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Dynamic category tabs + counts
    //     |--------------------------------------------------------------------------
    //     | Category count same active filters ko respect karega, bas category_id ko
    //     | ignore karega. Isliye tab change karte waqt count stable aur correct rahega.
    //     */
    //     $countQuery = Data::query();
    //     $this->applyIndexFilters($countQuery, $request, false);

    //     $allCategoryCount = (clone $countQuery)->count();

    //     $categoryCounts = (clone $countQuery)
    //         ->select('category_id', DB::raw('COUNT(*) as total'))
    //         ->groupBy('category_id')
    //         ->pluck('total', 'category_id');

    //     $companies = Company::query()
    //         ->orderBy('name')
    //         ->get();

    //     $categories = Category::query()
    //         ->orderBy('name')
    //         ->get();

    //     return view('data.index', compact(
    //         'data',
    //         'companies',
    //         'categories',
    //         'categoryCounts',
    //         'allCategoryCount'
    //     ));
    // }

    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Pagination
        |--------------------------------------------------------------------------
        */

        $allowedPerPage = [25, 50, 100, 250, 500];

        $perPage = (int) $request->get('per_page', 25);

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 25;
        }


        /*
        |--------------------------------------------------------------------------
        | Main listing query
        |--------------------------------------------------------------------------
        */

        $query = Data::query()->with([
            'company',
            'lead',
            'categoryInfo',
        ]);

        $this->applyIndexFilters($query, $request, true);

        $data = $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Dynamic category tabs + counts
        |--------------------------------------------------------------------------
        |
        | Category count same active filters ko respect karega,
        | bas category_id ko ignore karega.
        |
        */

        $countQuery = Data::query();

        $this->applyIndexFilters($countQuery, $request, false);

        $allCategoryCount = (clone $countQuery)->count();

        $categoryCounts = (clone $countQuery)
            ->select('category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('category_id')
            ->pluck('total', 'category_id');


        /*
        |--------------------------------------------------------------------------
        | Companies
        |--------------------------------------------------------------------------
        */

        $companies = Company::query()
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        $categories = Category::query()
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Cities
        |--------------------------------------------------------------------------
        |
        | Sirf existing unique cities dropdown me dikhengi.
        | Null / blank city ko remove kar diya gaya hai.
        |
        */

        $cities = Data::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->select('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');


        return view('data.index', compact(
            'data',
            'companies',
            'categories',
            'categoryCounts',
            'allCategoryCount',
            'cities',
            'perPage'
        ));
    }

    /**
     * Apply filters used by Data index page.
     *
     * $includeCategory = false category tab counts ke liye use hota hai,
     * taaki selected category ka filter count query ko restrict na kare.
     */
    // private function applyIndexFilters($query, Request $request, bool $includeCategory = true): void
    // {
    //     if ($request->get('show') === 'deleted') {
    //         $query->onlyTrashed();
    //     }

    //     if ($request->filled('company_id')) {
    //         $query->where('company_id', $request->integer('company_id'));
    //     }

    //     if ($includeCategory && $request->filled('category_id')) {
    //         $query->where('category_id', $request->integer('category_id'));
    //     }

    //     if ($request->filled('q')) {
    //         $search = trim((string) $request->q);

    //         $query->where(function ($q) use ($search) {
    //             $q->where('name', 'like', "%{$search}%")
    //                 ->orWhere('company_name', 'like', "%{$search}%")
    //                 ->orWhere('mobile', 'like', "%{$search}%")
    //                 ->orWhere('alternate_mobile', 'like', "%{$search}%")
    //                 ->orWhere('whatsapp_number', 'like', "%{$search}%")
    //                 ->orWhere('email', 'like', "%{$search}%")
    //                 ->orWhere('city', 'like', "%{$search}%")
    //                 ->orWhere('district', 'like', "%{$search}%")
    //                 ->orWhere('state', 'like', "%{$search}%")
    //                 ->orWhereHas('categoryInfo', function ($categoryQuery) use ($search) {
    //                     $categoryQuery->where('name', 'like', "%{$search}%");
    //                 });
    //         });
    //     }

    //     if ($request->filled('converted')) {
    //         if ($request->converted === '1') {
    //             $query->where('converted', true);
    //         } elseif ($request->converted === '0') {
    //             $query->where('converted', false);
    //         }
    //     }
    // }

    private function applyIndexFilters(
    $query,
    Request $request,
    bool $includeCategory = true
): void {

    /*
    |--------------------------------------------------------------------------
    | Deleted
    |--------------------------------------------------------------------------
    */

    if ($request->get('show') === 'deleted') {
        $query->onlyTrashed();
    }


    /*
    |--------------------------------------------------------------------------
    | Company Filter
    |--------------------------------------------------------------------------
    */

    if ($request->filled('company_id')) {
        $query->where(
            'company_id',
            $request->integer('company_id')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Category Filter
    |--------------------------------------------------------------------------
    */

    if (
        $includeCategory &&
        $request->filled('category_id')
    ) {
        $query->where(
            'category_id',
            $request->integer('category_id')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | City Filter
    |--------------------------------------------------------------------------
    */

    if ($request->filled('city')) {

        $city = trim((string) $request->city);

        $query->where('city', $city);
    }


    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    if ($request->filled('q')) {

        $search = trim((string) $request->q);

        $query->where(function ($q) use ($search) {

            $q->where(
                'name',
                'like',
                "%{$search}%"
            )
            ->orWhere(
                'company_name',
                'like',
                "%{$search}%"
            )
            ->orWhere(
                'mobile',
                'like',
                "%{$search}%"
            )
            ->orWhere(
                'alternate_mobile',
                'like',
                "%{$search}%"
            )
            ->orWhere(
                'whatsapp_number',
                'like',
                "%{$search}%"
            )
            ->orWhere(
                'email',
                'like',
                "%{$search}%"
            )
            ->orWhere(
                'city',
                'like',
                "%{$search}%"
            )
            ->orWhere(
                'district',
                'like',
                "%{$search}%"
            )
            ->orWhere(
                'state',
                'like',
                "%{$search}%"
            )
            ->orWhereHas(
                'categoryInfo',
                function ($categoryQuery) use ($search) {
                    $categoryQuery->where(
                        'name',
                        'like',
                        "%{$search}%"
                    );
                }
            );
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Conversion Filter
    |--------------------------------------------------------------------------
    */

    if ($request->filled('converted')) {

        if ($request->converted === '1') {

            $query->where('converted', true);

        } elseif ($request->converted === '0') {

            $query->where('converted', false);
        }
    }
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
                'category_id' => $data->category_id,

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
                    'category_id' => $data->category_id,

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

    // public function importCreate()
    // {
    //     $companies = Company::orderBy('name')->get();

    //     return view('data.import', compact('companies'));
    // }

    public function importCreate()
    {
        $companies = Company::orderBy('name')->get();

        $categories = Category::orderBy('name')->get();

        return view('data.import', compact(
            'companies',
            'categories'
        ));
    }


    public function importStore(Request $request)
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $file = $request->file('file');

        if (!$file || !$file->isValid()) {
            return back()->withInput()->with('error', 'Invalid import file.');
        }

        DB::beginTransaction();

        try {
            $sheets = Excel::toArray([], $file);
            $rows = $sheets[0] ?? [];

            if (empty($rows)) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Import file is empty.');
            }

            $originalHeader = array_shift($rows);

            if (!is_array($originalHeader) || empty($originalHeader)) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Unable to read Excel headings.');
            }

            $header = array_map(
                fn ($heading) => $this->normalizeDataImportHeading($heading),
                $originalHeader
            );

            $inserted = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    $skipped++;
                    continue;
                }

                $hasValue = collect($row)->contains(
                    fn ($value) => trim((string) $value) !== ''
                );

                if (!$hasValue) {
                    continue;
                }

                if (count($row) < count($header)) {
                    $row = array_pad($row, count($header), null);
                }

                if (count($row) > count($header)) {
                    $row = array_slice($row, 0, count($header));
                }

                $excelData = [];

                foreach ($header as $index => $heading) {
                    if ($heading === '') {
                        continue;
                    }

                    $excelData[$heading] = $row[$index] ?? null;
                }

                $data = [
                    'name' => $this->dataImportField($excelData, 'name'),
                    'company_name' => $this->dataImportField($excelData, 'company_name'),
                    'mobile' => $this->cleanDataImportPhone($this->dataImportField($excelData, 'mobile')),
                    'alternate_mobile' => $this->cleanDataImportPhone($this->dataImportField($excelData, 'alternate_mobile')),
                    'whatsapp_number' => $this->cleanDataImportPhone($this->dataImportField($excelData, 'whatsapp_number')),
                    'email' => $this->dataImportField($excelData, 'email'),
                    'category' => $this->dataImportField($excelData, 'category'),
                    'lead_source' => $this->dataImportField($excelData, 'lead_source'),
                    'campaign' => $this->dataImportField($excelData, 'campaign'),
                    'address' => $this->dataImportField($excelData, 'address'),
                    'city' => $this->dataImportField($excelData, 'city'),
                    'district' => $this->dataImportField($excelData, 'district'),
                    'state' => $this->dataImportField($excelData, 'state'),
                    'pincode' => $this->dataImportField($excelData, 'pincode'),
                    'industry' => $this->dataImportField($excelData, 'industry'),
                    'required_product' => $this->dataImportField($excelData, 'required_product'),
                    'preferred_language' => $this->dataImportField($excelData, 'preferred_language'),
                    'estimated_budget' => $this->cleanDataImportNumber($this->dataImportField($excelData, 'estimated_budget')),
                    'remarks' => $this->dataImportField($excelData, 'remarks'),
                    'company_id' => (int) $validated['company_id'],
                    'category_id' => (int) $validated['category_id'],
                    'raw_data' => $excelData,
                ];

                if (
                    empty($data['name']) &&
                    empty($data['mobile']) &&
                    empty($data['company_name']) &&
                    empty($data['email'])
                ) {
                    $skipped++;
                    continue;
                }

                Data::create($data);
                $inserted++;
            }

            DB::commit();

            return redirect()
                ->route('data.index')
                ->with('success', "{$inserted} data records imported successfully. {$skipped} rows skipped.");

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    private const DATA_IMPORT_HEADING_ALIASES = [
        'name' => [
            'name', 'lead name', 'customer name', 'client name', 'customer', 'client',
            'person name', 'contact person', 'contact person name', 'full name',
        ],
        'company_name' => [
            'company', 'company name', 'business', 'business name', 'firm', 'firm name',
            'shop', 'shop name', 'organisation', 'organization', 'organisation name',
            'organization name', 'store name', 'establishment name',
        ],
        'mobile' => [
            'mobile', 'mobile no', 'mobile number', 'phone', 'phone no', 'phone number',
            'contact', 'contact no', 'contact number', 'primary mobile', 'primary phone',
            'primary contact', 'telephone', 'tel',
        ],
        'alternate_mobile' => [
            'alternate mobile', 'alternate mobile no', 'alternate mobile number',
            'alternate phone', 'alternate phone no', 'alternate phone number',
            'alt mobile', 'alt mobile no', 'alt phone', 'secondary mobile',
            'secondary phone', 'other mobile', 'other phone', 'mobile 2', 'phone 2',
        ],
        'whatsapp_number' => [
            'whatsapp', 'whatsapp no', 'whatsapp number', 'whats app',
            'whats app no', 'whats app number', 'wa number', 'wa no',
        ],
        'email' => ['email', 'email id', 'email address', 'mail', 'mail id', 'e mail'],
        'category' => ['category', 'category name', 'lead category', 'business category', 'business type', 'type'],
        'lead_source' => ['lead source', 'source', 'source name', 'lead source name'],
        'campaign' => ['campaign', 'campaign name', 'lead campaign'],
        'address' => ['address', 'full address', 'business address', 'office address', 'shop address', 'location address'],
        'city' => ['city', 'city name', 'town', 'location', 'place'],
        'district' => ['district', 'district name', 'dist'],
        'state' => ['state', 'state name', 'province'],
        'pincode' => ['pincode', 'pin code', 'postal code', 'zip', 'zip code', 'zipcode'],
        'industry' => ['industry', 'industry name', 'business industry', 'sector'],
        'required_product' => ['required product', 'product', 'product required', 'interested product', 'interest', 'requirement'],
        'preferred_language' => ['preferred language', 'language', 'customer language', 'lead language'],
        'estimated_budget' => ['estimated budget', 'budget', 'lead budget', 'customer budget'],
        'remarks' => ['remarks', 'remark', 'notes', 'note', 'comments', 'comment', 'feedback', 'description'],
    ];

    private function dataImportField(array $row, string $field): ?string
    {
        $aliases = self::DATA_IMPORT_HEADING_ALIASES[$field] ?? [$field];

        foreach ($aliases as $alias) {
            $key = $this->normalizeDataImportHeading($alias);

            if (!array_key_exists($key, $row)) {
                continue;
            }

            $value = trim((string) ($row[$key] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        foreach ($aliases as $alias) {
            $aliasKey = $this->normalizeDataImportHeading($alias);

            if (strlen($aliasKey) < 5) {
                continue;
            }

            foreach ($row as $heading => $value) {
                $headingKey = $this->normalizeDataImportHeading($heading);

                if (
                    str_contains($headingKey, $aliasKey) ||
                    str_contains($aliasKey, $headingKey)
                ) {
                    $value = trim((string) ($value ?? ''));

                    if ($value !== '') {
                        return $value;
                    }
                }
            }
        }

        return null;
    }

    private function normalizeDataImportHeading(mixed $heading): string
    {
        $heading = (string) ($heading ?? '');
        $heading = preg_replace('/^\xEF\xBB\xBF/', '', $heading);
        $heading = mb_strtolower(trim($heading));

        return preg_replace('/[^a-z0-9]+/u', '', $heading) ?? '';
    }

    private function cleanDataImportPhone(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        if (is_numeric($value) && str_contains(strtolower($value), 'e')) {
            $value = sprintf('%.0f', (float) $value);
        }

        $value = preg_replace('/[^0-9+]/', '', $value);

        return $value ?: null;
    }

    private function cleanDataImportNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = str_replace([',', '₹', 'Rs.', 'Rs', ' '], '', (string) $value);

        return is_numeric($value) ? (float) $value : null;
    }

}