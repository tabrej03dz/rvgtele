<?php

namespace App\Http\Controllers;
use App\Models\CallDisposition;
use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Note;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Models\LeadLabel;
use App\Services\MobileCallService;
use Illuminate\Http\JsonResponse;
use Throwable;
use App\Models\CallLog;
use Illuminate\Support\Facades\Schema;

class LeadController extends Controller
{




public function callOnMobile(
    Request $request,
    Lead $lead,
    MobileCallService $mobileCallService
): JsonResponse {

    /*
    |--------------------------------------------------------------------------
    | Existing Lead Security
    |--------------------------------------------------------------------------
    |
    | Aapke current controller ka wahi guard reuse hoga.
    |
    */

    $this->guard(
        $request,
        $lead
    );

    if (blank($lead->mobile)) {
        return response()->json([
            'status' => false,
            'message' => 'Lead mobile number is missing.',
        ], 422);
    }

    try {

        $result = $mobileCallService->send(
            $request->user(),
            $lead
        );

        return response()->json([
            'status' => true,

            'message' =>
                'Call sent to mobile successfully.',

            'data' => [
                'lead_id' => $lead->id,
                'lead_name' => $lead->name,
                'mobile' => $lead->mobile,
                'device' => $result,
            ],
        ]);

    } catch (Throwable $e) {

        report($e);

        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}





    /*
    |--------------------------------------------------------------------------
    | FULL ACCESS ROLES
    |--------------------------------------------------------------------------
    |
    | In roles ko company ki saari leads dikhengi.
    |
    */

    private array $fullAccessRoles = [
        'super_admin',
        'admin',
    ];



    // public function index(Request $request): View
    // {
    //     /*
    //     |--------------------------------------------------------------------------
    //     | Default Call Disposition
    //     |--------------------------------------------------------------------------
    //     |
    //     | Normal /leads open hone par NO CALL YET default rahega.
    //     |
    //     | IMPORTANT:
    //     | call_disposition=all explicitly diya gaya ho to ALL leads dikhenge.
    //     |
    //     */

    //     if (!$request->has('call_disposition')) {
    //         $request->merge([
    //             'call_disposition' => 'no_call',
    //         ]);
    //     }

    //     $companyId = $this->companyId($request);
    //     $hasFullAccess = $this->hasFullAccess($request);

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Team Leader Access
    //     |--------------------------------------------------------------------------
    //     */

    //     $leaderTeamIds = $this->leaderTeamIds($request);

    //     $isTeamLeader =
    //         !$hasFullAccess &&
    //         !empty($leaderTeamIds);

    //     $canFilterByEmployee =
    //         $hasFullAccess || $isTeamLeader;

    //     $canFilterByTeam =
    //         $hasFullAccess || $isTeamLeader;

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Call Disposition
    //     |--------------------------------------------------------------------------
    //     */

    //     $callDisposition = (string) $request->input(
    //         'call_disposition',
    //         'no_call'
    //     );

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Prepare Request For Existing Lead Filters
    //     |--------------------------------------------------------------------------
    //     |
    //     | filteredLeadQuery() ke andar agar pehle se call_disposition filtering
    //     | hai to usse duplicate/conflict na ho, isliye clone request se
    //     | call_disposition hata rahe hain.
    //     |
    //     | Call disposition ka complete filter niche manually handle hoga.
    //     |
    //     */

    //     $filterRequest = clone $request;

    //     $filterRequest->query->remove('call_disposition');
    //     $filterRequest->request->remove('call_disposition');

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Base Lead Query
    //     |--------------------------------------------------------------------------
    //     |
    //     | Existing permissions + search + source + employee + team + category
    //     | etc. filteredLeadQuery() se hi rahenge.
    //     |
    //     */

    //     $query = $this->filteredLeadQuery($filterRequest)
    //         ->with([
    //             'assignedUser:id,name,employee_code,team_id',
    //             'source:id,name',
    //             'status:id,name,color',
    //             'team:id,name',
    //             'stage:id,name,color',
    //             'labels:id,company_id,name,color',
    //         ])
    //         ->addSelect([
    //             /*
    //             |--------------------------------------------------------------------------
    //             | Latest Note
    //             |--------------------------------------------------------------------------
    //             */

    //             'latest_note_body' => Note::query()
    //                 ->select('body')
    //                 ->whereColumn(
    //                     'notes.lead_id',
    //                     'leads.id'
    //                 )
    //                 ->latest('notes.id')
    //                 ->limit(1),

    //             /*
    //             |--------------------------------------------------------------------------
    //             | Latest Note Date
    //             |--------------------------------------------------------------------------
    //             */

    //             'latest_note_created_at' => Note::query()
    //                 ->select('created_at')
    //                 ->whereColumn(
    //                     'notes.lead_id',
    //                     'leads.id'
    //                 )
    //                 ->latest('notes.id')
    //                 ->limit(1),

    //             /*
    //             |--------------------------------------------------------------------------
    //             | Latest Note User
    //             |--------------------------------------------------------------------------
    //             */

    //             'latest_note_user_name' => Note::query()
    //                 ->leftJoin(
    //                     'users',
    //                     'users.id',
    //                     '=',
    //                     'notes.user_id'
    //                 )
    //                 ->select('users.name')
    //                 ->whereColumn(
    //                     'notes.lead_id',
    //                     'leads.id'
    //                 )
    //                 ->latest('notes.id')
    //                 ->limit(1),
    //         ]);

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Call Disposition Filter
    //     |--------------------------------------------------------------------------
    //     */

    //     if ($callDisposition === 'no_call') {

    //         /*
    //         |--------------------------------------------------------------------------
    //         | NO CALL YET
    //         |--------------------------------------------------------------------------
    //         |
    //         | Lead ke against abhi tak ek bhi call log nahi hona chahiye.
    //         |
    //         */

    //         $query->whereDoesntHave('calls');

    //     } elseif (
    //         $callDisposition !== '' &&
    //         $callDisposition !== 'all'
    //     ) {

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Specific Latest Call Disposition
    //         |--------------------------------------------------------------------------
    //         |
    //         | IMPORTANT:
    //         |
    //         | Sirf kisi bhi purani call ka disposition match nahi karenge.
    //         | Lead ki LATEST call ka disposition hi selected disposition hona
    //         | chahiye.
    //         |
    //         */

    //         $dispositionId = (int) $callDisposition;

    //         $query->whereHas('calls', function (Builder $callQuery) use ($dispositionId) {

    //             $callQuery
    //                 ->where(
    //                     'call_disposition_id',
    //                     $dispositionId
    //                 )
    //                 ->where(
    //                     'call_logs.id',
    //                     '=',
    //                     function ($subQuery) {
    //                         $subQuery
    //                             ->selectRaw('MAX(cl2.id)')
    //                             ->from('call_logs as cl2')
    //                             ->whereColumn(
    //                                 'cl2.lead_id',
    //                                 'call_logs.lead_id'
    //                             );
    //                     }
    //                 );
    //         });
    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | ALL
    //     |--------------------------------------------------------------------------
    //     |
    //     | call_disposition=all par intentionally koi call filter nahi lagega.
    //     |
    //     | Isliye:
    //     |
    //     | /leads?call_disposition=all
    //     |
    //     | = all accessible leads.
    //     |
    //     */

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Per Page
    //     |--------------------------------------------------------------------------
    //     */

    //     $allowedPerPage = [
    //         25,
    //         50,
    //         100,
    //         200,
    //     ];

    //     $perPage = (int) $request->input(
    //         'per_page',
    //         25
    //     );

    //     if (!in_array(
    //         $perPage,
    //         $allowedPerPage,
    //         true
    //     )) {
    //         $perPage = 25;
    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Leads Pagination
    //     |--------------------------------------------------------------------------
    //     */

    //     $leads = $query
    //         ->latest('leads.id')
    //         ->paginate($perPage)
    //         ->withQueryString();

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Statuses
    //     |--------------------------------------------------------------------------
    //     */

    //     $statuses = LeadStatus::query()
    //         ->where(function (Builder $query) use ($companyId) {
    //             $query
    //                 ->whereNull('company_id')
    //                 ->orWhere(
    //                     'company_id',
    //                     $companyId
    //                 );
    //         })
    //         ->where('is_active', true)
    //         ->orderBy('sort_order')
    //         ->orderBy('name')
    //         ->get();

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Call Dispositions
    //     |--------------------------------------------------------------------------
    //     */

    //     $dispositions = CallDisposition::query()
    //         ->where(function (Builder $query) use ($companyId) {
    //             $query
    //                 ->whereNull('company_id')
    //                 ->orWhere(
    //                     'company_id',
    //                     $companyId
    //                 );
    //         })
    //         ->where('is_active', true)
    //         ->orderBy('name')
    //         ->get();

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Sources
    //     |--------------------------------------------------------------------------
    //     */

    //     $sources = LeadSource::query()
    //         ->where(function (Builder $query) use ($companyId) {
    //             $query
    //                 ->whereNull('company_id')
    //                 ->orWhere(
    //                     'company_id',
    //                     $companyId
    //                 );
    //         })
    //         ->orderBy('name')
    //         ->get();

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Categories
    //     |--------------------------------------------------------------------------
    //     |
    //     | Separate category table nahi hai.
    //     | Leads table ke category column se distinct category niklegi.
    //     |
    //     */

    //     $categories = Lead::query()
    //         ->where(
    //             'company_id',
    //             $companyId
    //         )
    //         ->whereNotNull('category')
    //         ->where(
    //             'category',
    //             '<>',
    //             ''
    //         )
    //         ->select('category')
    //         ->distinct()
    //         ->orderBy('category')
    //         ->pluck('category');

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Employees For Filter
    //     |--------------------------------------------------------------------------
    //     */

    //     if ($hasFullAccess) {

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Admin / Full Access
    //         |--------------------------------------------------------------------------
    //         */

    //         $users = User::query()
    //             ->where(
    //                 'company_id',
    //                 $companyId
    //             )
    //             ->where(
    //                 'is_active',
    //                 true
    //             )
    //             ->orderBy('name')
    //             ->get([
    //                 'id',
    //                 'name',
    //                 'employee_code',
    //                 'team_id',
    //             ]);

    //     } elseif ($isTeamLeader) {

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Team Leader
    //         |--------------------------------------------------------------------------
    //         |
    //         | Team leader ko:
    //         |
    //         | - khud ka user
    //         | - apni teams ke employees
    //         |
    //         | filter me milenge.
    //         |
    //         */

    //         $users = User::query()
    //             ->where(
    //                 'company_id',
    //                 $companyId
    //             )
    //             ->where(
    //                 'is_active',
    //                 true
    //             )
    //             ->where(function (Builder $query) use (
    //                 $request,
    //                 $leaderTeamIds
    //             ) {
    //                 $query
    //                     ->whereKey(
    //                         $request->user()->id
    //                     )
    //                     ->orWhereIn(
    //                         'team_id',
    //                         $leaderTeamIds
    //                     );
    //             })
    //             ->orderBy('name')
    //             ->get([
    //                 'id',
    //                 'name',
    //                 'employee_code',
    //                 'team_id',
    //             ]);

    //     } else {

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Normal Employee
    //         |--------------------------------------------------------------------------
    //         */

    //         $users = collect();
    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Teams For Filter
    //     |--------------------------------------------------------------------------
    //     */

    //     if ($hasFullAccess) {

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Full Access
    //         |--------------------------------------------------------------------------
    //         */

    //         $teams = Team::query()
    //             ->where(
    //                 'company_id',
    //                 $companyId
    //             )
    //             ->orderBy('name')
    //             ->get([
    //                 'id',
    //                 'name',
    //             ]);

    //     } elseif ($isTeamLeader) {

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Team Leader Teams
    //         |--------------------------------------------------------------------------
    //         */

    //         $teams = Team::query()
    //             ->where(
    //                 'company_id',
    //                 $companyId
    //             )
    //             ->whereIn(
    //                 'id',
    //                 $leaderTeamIds
    //             )
    //             ->orderBy('name')
    //             ->get([
    //                 'id',
    //                 'name',
    //             ]);

    //     } else {

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Normal Employee
    //         |--------------------------------------------------------------------------
    //         */

    //         $teams = collect();
    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Labels
    //     |--------------------------------------------------------------------------
    //     */

    //     $labels = LeadLabel::query()
    //         ->where(
    //             'company_id',
    //             $companyId
    //         )
    //         ->withCount('leads')
    //         ->orderBy('name')
    //         ->get();

    //     /*
    //     |--------------------------------------------------------------------------
    //     | View
    //     |--------------------------------------------------------------------------
    //     */

    //     return view('leads.index', [

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Main Data
    //         |--------------------------------------------------------------------------
    //         */

    //         'leads' => $leads,

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Filters
    //         |--------------------------------------------------------------------------
    //         */

    //         'statuses' => $statuses,
    //         'dispositions' => $dispositions,
    //         'sources' => $sources,
    //         'categories' => $categories,

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Pagination
    //         |--------------------------------------------------------------------------
    //         */

    //         'perPage' => $perPage,

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Users / Teams
    //         |--------------------------------------------------------------------------
    //         */

    //         'users' => $users,
    //         'teams' => $teams,

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Labels
    //         |--------------------------------------------------------------------------
    //         */

    //         'labels' => $labels,

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Blade Access Control
    //         |--------------------------------------------------------------------------
    //         */

    //         'hasFullAccess' => $hasFullAccess,
    //         'isTeamLeader' => $isTeamLeader,
    //         'canFilterByEmployee' => $canFilterByEmployee,
    //         'canFilterByTeam' => $canFilterByTeam,
    //     ]);
    // }


public function index(Request $request): View
{
    $companyId = $this->companyId($request);
    $hasFullAccess = $this->hasFullAccess($request);

    /*
    |--------------------------------------------------------------------------
    | Team Leader Access
    |--------------------------------------------------------------------------
    */

    $leaderTeamIds = $this->leaderTeamIds($request);

    $isTeamLeader =
        !$hasFullAccess &&
        !empty($leaderTeamIds);

    $canFilterByEmployee =
        $hasFullAccess || $isTeamLeader;

    $canFilterByTeam =
        $hasFullAccess || $isTeamLeader;


    /*
    |--------------------------------------------------------------------------
    | Dashboard Quick Metric Filter
    |--------------------------------------------------------------------------
    |
    | Top KPI card se aane wala filter. Ye server/database level par apply hoga,
    | sirf browser me already-loaded cards ko hide/show nahi karega.
    |
    */

    $quickMetric = (string) $request->input('quick_metric', '');

    $allowedQuickMetrics = [
        'calls_today',
        'connected_today',
        'employee_total_calls',
        'unique_connected',
        'follow_up',
        'demo_today',
        'total_demo',
    ];

    if (!in_array($quickMetric, $allowedQuickMetrics, true)) {
        $quickMetric = '';
    }


    /*
    |--------------------------------------------------------------------------
    | Remove Board Specific Filters From Existing filteredLeadQuery()
    |--------------------------------------------------------------------------
    |
    | Global search etc. chalega.
    |
    | Lekin:
    | new_category
    | dialed_category
    | connected_category
    |
    | wagairah filteredLeadQuery() ko nahi bhejne hain.
    |
    */

    $baseRequest = clone $request;

    $boardFilterKeys = $this->boardFilterKeys();

    foreach ($boardFilterKeys as $key) {
        $baseRequest->query->remove($key);
        $baseRequest->request->remove($key);
    }

    $baseRequest->query->remove('call_disposition');
    $baseRequest->request->remove('call_disposition');

    // Quick metric ko filteredLeadQuery() me nahi bhejna hai.
    // Isko niche tino board queries par explicitly apply karenge.
    $baseRequest->query->remove('quick_metric');
    $baseRequest->request->remove('quick_metric');

    /*
    |--------------------------------------------------------------------------
    | Base Accessible Query
    |--------------------------------------------------------------------------
    |
    | Existing Admin / Team Leader / Employee permission logic rahega.
    |
    */

    $baseQuery = $this->filteredLeadQuery($baseRequest);


    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    $relations = [
        'assignedUser:id,name,employee_code,team_id',
        'source:id,name',
        'status:id,name,color',
        'team:id,name',
        'stage:id,name,color',
        'labels:id,company_id,name,color',

        'latestCall' => function ($query) {
            $query->with([
                'disposition',
                'user:id,name',
            ]);
        },

        'latestNote.user:id,name',

        'latestFollowUp' => function ($query) {
            $query->with([
                'assignedUser:id,name',
            ]);
        },
    ];


    /*
    |--------------------------------------------------------------------------
    | Common Card Data
    |--------------------------------------------------------------------------
    */

    $prepareQuery = function (Builder $query) use ($relations) {

        return $query
            ->with($relations)
            // Existing global calls_count preserve rahega for compatibility.
            ->withCount('calls')
            ->addSelect([

                /*
                |--------------------------------------------------------------------------
                | Latest Call ID
                |--------------------------------------------------------------------------
                |
                | Dialed / Connected columns ko latest call activity ke basis
                | par descending order me dikhane ke liye.
                |
                */

                'latest_call_id' => CallLog::query()
                    ->select('id')
                    ->whereColumn('call_logs.lead_id', 'leads.id')
                    ->where(function (Builder $callScope) {
                        $callScope
                            ->where(function (Builder $assigned) {
                                $assigned
                                    ->whereNotNull('leads.assigned_to')
                                    ->whereColumn('call_logs.user_id', 'leads.assigned_to')
                                    ->whereRaw(
                                        'call_logs.created_at >= COALESCE((
'
                                        . 'SELECT MAX(la.assigned_at) FROM lead_assignments la '
                                        . 'WHERE la.lead_id = leads.id '
                                        . 'AND la.new_user_id = leads.assigned_to
'
                                        . '), leads.created_at)'
                                    );
                            })
                            ->orWhere(function (Builder $unassigned) {
                                // Admin ke unassigned leads ka old/global behavior preserve.
                                $unassigned->whereNull('leads.assigned_to');
                            });
                    })
                    ->latest('call_logs.id')
                    ->limit(1),

                /*
                |--------------------------------------------------------------------------
                | Latest Note Body
                |--------------------------------------------------------------------------
                */

                'latest_note_body' => Note::query()
                    ->select('body')
                    ->whereColumn(
                        'notes.lead_id',
                        'leads.id'
                    )
                    ->latest('notes.id')
                    ->limit(1),


                /*
                |--------------------------------------------------------------------------
                | Latest Note Date
                |--------------------------------------------------------------------------
                */

                'latest_note_created_at' => Note::query()
                    ->select('created_at')
                    ->whereColumn(
                        'notes.lead_id',
                        'leads.id'
                    )
                    ->latest('notes.id')
                    ->limit(1),


                /*
                |--------------------------------------------------------------------------
                | Latest Note User
                |--------------------------------------------------------------------------
                */

                'latest_note_user_name' => Note::query()
                    ->leftJoin(
                        'users',
                        'users.id',
                        '=',
                        'notes.user_id'
                    )
                    ->select('users.name')
                    ->whereColumn(
                        'notes.lead_id',
                        'leads.id'
                    )
                    ->latest('notes.id')
                    ->limit(1),
            ]);
    };


    /*
    |--------------------------------------------------------------------------
    | NEW CALL
    |--------------------------------------------------------------------------
    |
    | Ek bhi call log nahi.
    |
    */

    $newQuery = clone $baseQuery;

    // IMPORTANT: New/Dialed/Connected ab current assignment ke hisab se manage honge.
    // Purane employee ki call history delete/change nahi hogi.
    $this->applyCurrentAssignmentCallState($newQuery, 'new');

    $this->applyBoardFilters(
        $newQuery,
        $request,
        'new',
        $companyId
    );

    $this->applyQuickMetricFilter(
        $newQuery,
        $quickMetric,
        'new'
    );


    /*
    |--------------------------------------------------------------------------
    | DIALED CALL
    |--------------------------------------------------------------------------
    |
    | Kam se kam ek baar dial hua.
    | Disposition kuch bhi ho.
    |
    */

    $dialedQuery = clone $baseQuery;

    $this->applyCurrentAssignmentCallState($dialedQuery, 'dialed');

    $this->applyBoardFilters(
        $dialedQuery,
        $request,
        'dialed',
        $companyId
    );

    $this->applyQuickMetricFilter(
        $dialedQuery,
        $quickMetric,
        'dialed'
    );


    /*
    |--------------------------------------------------------------------------
    | CONNECTED CALL
    |--------------------------------------------------------------------------
    |
    | Call hona chahiye
    |
    | AND
    |
    | Call remarks / auto remarks / notes me data hona chahiye.
    |
    */

    $connectedQuery = clone $baseQuery;

    $this->applyCurrentAssignmentCallState($connectedQuery, 'connected');

    $this->applyBoardFilters(
        $connectedQuery,
        $request,
        'connected',
        $companyId
    );

    $this->applyQuickMetricFilter(
        $connectedQuery,
        $quickMetric,
        'connected'
    );


    /*
    |--------------------------------------------------------------------------
    | Counts
    |--------------------------------------------------------------------------
    */

    $newCount =
        (clone $newQuery)->count();

    $dialedCount =
        (clone $dialedQuery)->count();

    $connectedCount =
        (clone $connectedQuery)->count();


    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Har column ki apni pagination.
    |
    */

    $boardLimit = 10;

    $newLeads = $prepareQuery(
        clone $newQuery
    )
        ->orderByDesc('leads.id')
        ->paginate(
            $boardLimit,
            ['*'],
            'new_page'
        )
        ->withQueryString();


    $dialedLeads = $prepareQuery(
        clone $dialedQuery
    )
        ->orderByDesc('latest_call_id')
        ->orderByDesc('leads.id')
        ->paginate(
            $boardLimit,
            ['*'],
            'dialed_page'
        )
        ->withQueryString();


    $connectedLeads = $prepareQuery(
        clone $connectedQuery
    )
        ->orderByDesc('latest_call_id')
        ->orderByDesc('leads.id')
        ->paginate(
            $boardLimit,
            ['*'],
            'connected_page'
        )
        ->withQueryString();


    /*
    |--------------------------------------------------------------------------
    | Dashboard Base Query
    |--------------------------------------------------------------------------
    */

    $statsRequest = clone $baseRequest;

    $statsRequest->query->remove('page');
    $statsRequest->request->remove('page');

    $accessibleStatsQuery =
        $this->filteredLeadQuery(
            $statsRequest
        );

    // Dashboard numbers quick_metric se independent rahenge.
    $connectedStatsQuery = clone $accessibleStatsQuery;

    $connectedStatsQuery
        ->whereHas('calls')
        ->where(function (Builder $connected) {
            $connected->whereHas('notes');

            if (Schema::hasColumn('call_logs', 'remarks')) {
                $connected->orWhereHas('calls', function (Builder $calls) {
                    $calls->whereNotNull('remarks')
                        ->whereRaw("TRIM(COALESCE(remarks, '')) <> ''");
                });
            }

            if (Schema::hasColumn('call_logs', 'remark')) {
                $connected->orWhereHas('calls', function (Builder $calls) {
                    $calls->whereNotNull('remark')
                        ->whereRaw("TRIM(COALESCE(remark, '')) <> ''");
                });
            }

            if (Schema::hasColumn('call_logs', 'auto_remarks')) {
                $connected->orWhereHas('calls', function (Builder $calls) {
                    $calls->whereNotNull('auto_remarks')
                        ->whereRaw("TRIM(COALESCE(auto_remarks, '')) <> ''");
                });
            }
        });


    /*
    |--------------------------------------------------------------------------
    | Total Leads
    |--------------------------------------------------------------------------
    */

    $totalLeads =
        (clone $accessibleStatsQuery)
            ->count();


    /*
    |--------------------------------------------------------------------------
    | Calls Today
    |--------------------------------------------------------------------------
    */

    $callsToday =
        DB::table('call_logs')
            ->join(
                'leads',
                'leads.id',
                '=',
                'call_logs.lead_id'
            )
            ->where(
                'leads.company_id',
                $companyId
            )
            ->whereDate(
                'call_logs.created_at',
                today()
            )
            ->count();


    /*
    |--------------------------------------------------------------------------
    | Connected Today
    |--------------------------------------------------------------------------
    */

    $connectedToday =
        (clone $connectedStatsQuery)
            ->whereHas(
                'calls',
                function (Builder $query) {

                    $query->whereDate(
                        'call_logs.created_at',
                        today()
                    );
                }
            )
            ->count();


    /*
    |--------------------------------------------------------------------------
    | Total Demo
    |--------------------------------------------------------------------------
    */

    $totalDemo =
        (clone $accessibleStatsQuery)
            ->where(
                'demo_send',
                true
            )
            ->count();


    /*
    |--------------------------------------------------------------------------
    | Demo Today
    |--------------------------------------------------------------------------
    */

    $demoToday =
        (clone $accessibleStatsQuery)
            ->where(
                'demo_send',
                true
            )
            ->whereNotNull(
                'demo_sent_at'
            )
            ->whereDate(
                'demo_sent_at',
                today()
            )
            ->count();


    /*
    |--------------------------------------------------------------------------
    | Follow Up
    |--------------------------------------------------------------------------
    */

    $followUpCount =
        (clone $accessibleStatsQuery)
            ->whereNotNull(
                'next_follow_up_at'
            )
            ->count();


    /*
    |--------------------------------------------------------------------------
    | Unique Connected
    |--------------------------------------------------------------------------
    */

    $uniqueConnected =
        (clone $connectedStatsQuery)
            ->count();


    /*
    |--------------------------------------------------------------------------
    | Total Calls
    |--------------------------------------------------------------------------
    */

    $employeeTotalCalls =
        DB::table('call_logs')
            ->join(
                'leads',
                'leads.id',
                '=',
                'call_logs.lead_id'
            )
            ->where(
                'leads.company_id',
                $companyId
            )
            ->count();


    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    */

    $statuses = LeadStatus::query()
        ->where(function (Builder $query) use ($companyId) {

            $query
                ->whereNull('company_id')
                ->orWhere(
                    'company_id',
                    $companyId
                );
        })
        ->where(
            'is_active',
            true
        )
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();


    /*
    |--------------------------------------------------------------------------
    | Dispositions
    |--------------------------------------------------------------------------
    */

    $dispositions =
        CallDisposition::query()
            ->where(
                function (Builder $query) use (
                    $companyId
                ) {

                    $query
                        ->whereNull('company_id')
                        ->orWhere(
                            'company_id',
                            $companyId
                        );
                }
            )
            ->where(
                'is_active',
                true
            )
            ->orderBy('name')
            ->get();


    /*
    |--------------------------------------------------------------------------
    | Sources
    |--------------------------------------------------------------------------
    */

    $sources =
        LeadSource::query()
            ->where(
                function (Builder $query) use (
                    $companyId
                ) {

                    $query
                        ->whereNull('company_id')
                        ->orWhere(
                            'company_id',
                            $companyId
                        );
                }
            )
            ->orderBy('name')
            ->get();


    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */

    $categories =
        Lead::query()
            ->where(
                'company_id',
                $companyId
            )
            ->whereNotNull('category')
            ->where(
                'category',
                '<>',
                ''
            )
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');


    /*
    |--------------------------------------------------------------------------
    | Cities
    |--------------------------------------------------------------------------
    */

    $cities =
        Lead::query()
            ->where(
                'company_id',
                $companyId
            )
            ->whereNotNull('city')
            ->where(
                'city',
                '<>',
                ''
            )
            ->select('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');


    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    if ($hasFullAccess) {

        $users =
            User::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'employee_code',
                    'team_id',
                ]);

    } elseif ($isTeamLeader) {

        $users =
            User::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    function (Builder $query) use (
                        $request,
                        $leaderTeamIds
                    ) {

                        $query
                            ->whereKey(
                                $request->user()->id
                            )
                            ->orWhereIn(
                                'team_id',
                                $leaderTeamIds
                            );
                    }
                )
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'employee_code',
                    'team_id',
                ]);

    } else {

        $users = collect([
            $request->user()
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Teams
    |--------------------------------------------------------------------------
    */

    if ($hasFullAccess) {

        $teams =
            Team::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                ]);

    } elseif ($isTeamLeader) {

        $teams =
            Team::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->whereIn(
                    'id',
                    $leaderTeamIds
                )
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                ]);

    } else {

        $teams = collect();
    }


    /*
    |--------------------------------------------------------------------------
    | Labels
    |--------------------------------------------------------------------------
    */

    $labels =
        LeadLabel::query()
            ->where(
                'company_id',
                $companyId
            )
            ->withCount('leads')
            ->orderBy('name')
            ->get();


    /*
    |--------------------------------------------------------------------------
    | Return View
    |--------------------------------------------------------------------------
    */

    return view(
        'leads.index',
        [
            'newLeads' =>
                $newLeads,

            'dialedLeads' =>
                $dialedLeads,

            'connectedLeads' =>
                $connectedLeads,

            'newCount' =>
                $newCount,

            'dialedCount' =>
                $dialedCount,

            'connectedCount' =>
                $connectedCount,

            'totalLeads' =>
                $totalLeads,

            'callsToday' =>
                $callsToday,

            'connectedToday' =>
                $connectedToday,

            'employeeTotalCalls' =>
                $employeeTotalCalls,

            'uniqueConnected' =>
                $uniqueConnected,

            'followUpCount' =>
                $followUpCount,

            'demoToday' =>
                $demoToday,

            'totalDemo' =>
                $totalDemo,

            'quickMetric' =>
                $quickMetric,

            'statuses' =>
                $statuses,

            'dispositions' =>
                $dispositions,

            'sources' =>
                $sources,

            'categories' =>
                $categories,

            'cities' =>
                $cities,

            'users' =>
                $users,

            'teams' =>
                $teams,

            'labels' =>
                $labels,

            'hasFullAccess' =>
                $hasFullAccess,

            'isTeamLeader' =>
                $isTeamLeader,

            'canFilterByEmployee' =>
                $canFilterByEmployee,

            'canFilterByTeam' =>
                $canFilterByTeam,
        ]
    );
}



    /*
    |--------------------------------------------------------------------------
    | Create Lead Form
    |--------------------------------------------------------------------------
    */

    public function create(Request $request): View
    {
        return view(
            'leads.form',
            $this->formData($request)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store Lead
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ): RedirectResponse {


        $companyId = $this->companyId($request);

        $validated = $this->validateData($request);


        /*
        |--------------------------------------------------------------------------
        | Normal Employee
        |--------------------------------------------------------------------------
        |
        | Employee agar Lead create karta hai to Lead automatically usi ko
        | assign hogi.
        |
        | Wo manually kisi aur employee ko assign nahi kar sakta.
        |
        */

        if (!$this->hasFullAccess($request)) {
            $validated['assigned_to'] =
                (int) $request->user()->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Default Pipeline Stage
        |--------------------------------------------------------------------------
        */

        if (empty($validated['pipeline_stage_id'])) {
            $validated['pipeline_stage_id'] =
                $this->defaultPipelineStageId(
                    $companyId
                );
        }

        $validated['company_id'] = $companyId;

        $validated['created_by'] =
            (int) $request->user()->id;

        $validated['priority'] =
            $validated['priority'] ?? 'normal';

        $validated['temperature'] =
            $validated['temperature'] ?? 'cold';

        $lead = DB::transaction(
            function () use (
                $validated,
                $request,
                $companyId
            ) {
                $lead = Lead::create(
                    $validated
                );

                /*
                |--------------------------------------------------------------------------
                | Assignment History
                |--------------------------------------------------------------------------
                */

                if (!empty($validated['assigned_to'])) {
                    $this->createAssignmentHistory(
                        lead: $lead,
                        previousUserId: null,
                        newUserId: (int) $validated['assigned_to'],
                        assignedBy: (int) $request->user()->id,
                        reason: $this->hasFullAccess($request)
                            ? 'Lead assigned during creation'
                            : 'Lead created by employee and automatically assigned to self',
                        companyId: $companyId
                    );
                }

                return $lead;
            }
        );

        return redirect()
            ->route(
                'leads.show',
                $lead
            )
            ->with(
                'success',
                'Lead created successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Lead Detail
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        Lead $lead
    ):
     View {


        /*
        | Employee doosre employee ki lead URL se open nahi kar sakta.
        */

        $this->guard(
            $request,
            $lead
        );

        $lead->load([
            'assignedUser',
            'source',
            'status',
            'stage',
            'team',

            'calls.user',
            'calls.disposition',

            'followUps.assignedUser',

            'notes.user',

            'assignments',
            'labels:id,company_id,name,color',
        ]);

        $companyId =
            $this->companyId($request);

        /*
        |--------------------------------------------------------------------------
        | Preserve Listing Filters
        |--------------------------------------------------------------------------
        |
        | Lead list se jo bhi GET filters aaye hain unko detail page, Previous,
        | Next aur Back navigation me preserve rakhenge. `page` intentionally
        | hata rahe hain, kyunki Previous/Next poore filtered result set par chalega.
        |
        */

        $navigationParams = $request->except([
            'page',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Previous / Next Lead Navigation
        |--------------------------------------------------------------------------
        |
        | Same filteredLeadQuery() reuse ho rahi hai, isliye navigation exactly
        | usi filtered result set ke andar rahegi jo Leads listing par tha.
        |
        | Listing order: latest('id') => ID DESC
        | Previous = current row se upar wali/newer lead = higher ID
        | Next     = current row se neeche wali/older lead = lower ID
        |
        */

        $navigationQuery =
            $this->filteredLeadQuery($request);

        $previousLead =
            (clone $navigationQuery)
            ->where('id', '>', $lead->id)
            ->orderBy('id')
            ->first([
                'id',
                'name',
            ]);

        $nextLead =
            (clone $navigationQuery)
            ->where('id', '<', $lead->id)
            ->orderByDesc('id')
            ->first([
                'id',
                'name',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Assignment Dropdown
        |--------------------------------------------------------------------------
        |
        | Sirf Admin/Super Admin ko employee list denge.
        |
        */

        $users = $this->hasFullAccess($request)
            ? User::query()
            ->where(
                'company_id',
                $companyId
            )
            ->where(
                'is_active',
                true
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'employee_code',
            ])
            : collect();

        /*
        |--------------------------------------------------------------------------
        | Dispositions
        |--------------------------------------------------------------------------
        */

        $dispositions =
            \App\Models\CallDisposition::query()
            ->where(
                function (
                    Builder $query
                ) use ($companyId) {
                    $query
                        ->whereNull(
                            'company_id'
                        )
                        ->orWhere(
                            'company_id',
                            $companyId
                        );
                }
            )
            ->where(
                'is_active',
                true
            )
            ->orderBy('name')
            ->get();

        $labels = LeadLabel::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'company_id', 'name', 'color']);

        return view('leads.show', [
            'lead' => $lead,

            'users' => $users,

            'dispositions' =>
            $dispositions,

            'hasFullAccess' =>
            $this->hasFullAccess($request),

            'previousLead' =>
            $previousLead,

            'nextLead' =>
            $nextLead,

            'navigationParams' =>
            $navigationParams,

            'labels' => $labels,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Edit Lead
    |--------------------------------------------------------------------------
    */

    public function edit(
        Request $request,
        Lead $lead
    ): View {
        $this->guard(
            $request,
            $lead
        );

        return view(
            'leads.form',
            array_merge(
                $this->formData($request),
                compact('lead')
            )
        );
    }



    public function update(
    Request $request,
    Lead $lead
): RedirectResponse {
    $this->guard(
        $request,
        $lead
    );

    /*
    |--------------------------------------------------------------------------
    | Demo Send Quick Update
    |--------------------------------------------------------------------------
    |
    | demo_send_only request aane par normal lead edit validation run nahi hogi.
    |
    | demo_send = 1:
    | - Lead ko demo sent mark karenge.
    | - Agar pehle demo_sent_at nahi hai to current timestamp save hoga.
    |
    | demo_send = 0:
    | - Demo Send mark remove hoga.
    | - demo_sent_at bhi NULL kar diya jayega.
    |
    */

    if ($request->boolean('demo_send_only')) {

        $demoValidated = $request->validate([
            'demo_send' => ['required', 'boolean'],
            'call_disposition_id' => ['nullable', 'integer'],
        ]);

        $isDemoSend = (bool) $demoValidated['demo_send'];

        DB::transaction(function () use (
            $lead,
            $request,
            $demoValidated,
            $isDemoSend
        ) {
            if ($isDemoSend) {
                $lead->demo_send = true;

                // First demo send date preserve rahegi. Resend is date ko overwrite nahi karega.
                if (empty($lead->demo_sent_at)) {
                    $lead->demo_sent_at = now();
                }

                $lead->save();

                // Har Send Demo / Resend Demo par ek fresh Demo disposition log.
                $demoDispositionId = !empty($demoValidated['call_disposition_id'])
                    ? (int) $demoValidated['call_disposition_id']
                    : $this->demoDispositionId($request);

                $this->saveDemoDispositionLog(
                    request: $request,
                    lead: $lead,
                    dispositionId: $demoDispositionId
                );
            } else {
                // Existing remove behavior preserve.
                $lead->demo_send = false;
                $lead->demo_sent_at = null;
                $lead->save();
            }
        });

        return back()->with(
            'success',
            $isDemoSend
                ? 'Demo sent successfully and Demo disposition saved.'
                : 'Demo Send mark removed.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Normal Lead Update
    |--------------------------------------------------------------------------
    */

    $validated = $this->validateData(
        $request,
        $lead
    );

    /*
    |--------------------------------------------------------------------------
    | Employee Cannot Change Owner
    |--------------------------------------------------------------------------
    */

    if (!$this->hasFullAccess($request)) {
        $validated['assigned_to'] =
            $lead->assigned_to;
    }

    $oldAssignedUserId =
        $lead->assigned_to;

    $newAssignedUserId =
        $validated['assigned_to'] ?? null;

    DB::transaction(
        function () use (
            $lead,
            $validated,
            $oldAssignedUserId,
            $newAssignedUserId,
            $request
        ) {
            /*
            |--------------------------------------------------------------------------
            | Update Lead
            |--------------------------------------------------------------------------
            */

            $lead->update(
                $validated
            );

            /*
            |--------------------------------------------------------------------------
            | Admin Changed Assignment From Edit Form
            |--------------------------------------------------------------------------
            */

            if (
                $this->hasFullAccess($request)
                &&
                (int) $oldAssignedUserId !==
                (int) $newAssignedUserId
                &&
                !empty($newAssignedUserId)
            ) {
                $this->createAssignmentHistory(
                    lead: $lead,

                    previousUserId:
                        $oldAssignedUserId
                            ? (int) $oldAssignedUserId
                            : null,

                    newUserId:
                        (int) $newAssignedUserId,

                    assignedBy:
                        (int) $request->user()->id,

                    reason:
                        'Lead owner changed from edit form',

                    companyId:
                        $this->companyId($request)
                );
            }
        }
    );

    return redirect()
        ->route(
            'leads.show',
            $lead
        )
        ->with(
            'success',
            'Lead updated successfully.'
        );
}




    /*
    |--------------------------------------------------------------------------
    | Lead Labels
    |--------------------------------------------------------------------------
    */

    public function storeLabel(Request $request): RedirectResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('lead_labels', 'name')->where(
                    fn ($query) => $query->where('company_id', $companyId)
                ),
            ],
            'color' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ]);

        LeadLabel::create([
            'company_id' => $companyId,
            'name' => trim($validated['name']),
            'color' => strtoupper($validated['color']),
            'created_by' => (int) $request->user()->id,
        ]);

        return back()->with('success', 'Label created successfully.');
    }

    public function addLabel(Request $request, Lead $lead): RedirectResponse
    {
        $this->guard($request, $lead);
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'label_id' => [
                'required',
                'integer',
                Rule::exists('lead_labels', 'id')->where(
                    fn ($query) => $query->where('company_id', $companyId)
                ),
            ],
        ]);

        $lead->labels()->syncWithoutDetaching([(int) $validated['label_id']]);

        return back()->with('success', 'Lead added to label successfully.');
    }

    public function removeLabel(
        Request $request,
        Lead $lead,
        LeadLabel $label
    ): RedirectResponse {
        $this->guard($request, $lead);

        abort_unless(
            (int) $label->company_id === $this->companyId($request),
            403
        );

        $lead->labels()->detach($label->id);

        return back()->with('success', 'Label removed from lead.');
    }

    public function bulkLabel(Request $request): RedirectResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1'],
            'lead_ids.*' => ['required', 'integer'],
            'label_id' => [
                'required',
                'integer',
                Rule::exists('lead_labels', 'id')->where(
                    fn ($query) => $query->where('company_id', $companyId)
                ),
            ],
            'label_action' => ['required', Rule::in(['add', 'remove'])],
        ]);

        // Always start from the current user's access scope.
        $accessRequest = new Request();
        $accessRequest->setUserResolver(fn () => $request->user());

        $leads = $this->filteredLeadQuery($accessRequest)
            ->whereIn('leads.id', $validated['lead_ids'])
            ->get(['leads.id']);

        if ($leads->isEmpty()) {
            return back()->with('error', 'No accessible leads selected.');
        }

        $labelId = (int) $validated['label_id'];
        $action = $validated['label_action'];

        DB::transaction(function () use ($leads, $labelId, $action) {
            foreach ($leads as $lead) {
                if ($action === 'add') {
                    $lead->labels()->syncWithoutDetaching([$labelId]);
                } else {
                    $lead->labels()->detach($labelId);
                }
            }
        });

        $count = $leads->count();

        return back()->with(
            'success',
            $action === 'add'
                ? "{$count} lead(s) added to label successfully."
                : "{$count} lead(s) removed from label successfully."
        );
    }

    public function destroyLabel(
        Request $request,
        LeadLabel $label
    ): RedirectResponse {
        $this->ensureFullAccess($request);

        abort_unless(
            (int) $label->company_id === $this->companyId($request),
            403
        );

        $label->delete();

        return back()->with('success', 'Label deleted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Lead
    |--------------------------------------------------------------------------
    |
    | Sirf Admin/Super Admin.
    |
    */

    public function destroy(
        Request $request,
        Lead $lead
    ): RedirectResponse {
        $this->ensureFullAccess(
            $request
        );

        $this->guardCompany(
            $request,
            $lead
        );

        $lead->delete();

        return redirect()
            ->route('leads.index')
            ->with(
                'success',
                'Lead moved to trash.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Single Assignment
    |--------------------------------------------------------------------------
    |
    | Sirf Admin/Super Admin.
    |
    */

    public function assign(
        Request $request,
        Lead $lead
    ): RedirectResponse {
        $this->ensureFullAccess(
            $request
        );

        $this->guardCompany(
            $request,
            $lead
        );

        $companyId =
            $this->companyId($request);

        $validated =
            $request->validate([
                'assigned_to' => [
                    'required',
                    'integer',

                    Rule::exists(
                        'users',
                        'id'
                    )->where(
                        function ($query) use (
                            $companyId
                        ) {
                            $query
                                ->where(
                                    'company_id',
                                    $companyId
                                )
                                ->where(
                                    'is_active',
                                    true
                                );
                        }
                    ),
                ],

                'reason' => [
                    'required',
                    'string',
                    'max:500',
                ],
            ]);

        $oldAssignedUserId =
            $lead->assigned_to;

        /*
        |--------------------------------------------------------------------------
        | Already same employee
        |--------------------------------------------------------------------------
        */

        if (
            (int) $oldAssignedUserId ===
            (int) $validated['assigned_to']
        ) {
            return back()->with(
                'error',
                'Lead is already assigned to this employee.'
            );
        }

        DB::transaction(
            function () use (
                $lead,
                $validated,
                $oldAssignedUserId,
                $request,
                $companyId
            ) {
                $lead->update([
                    'assigned_to' =>
                    $validated['assigned_to'],
                ]);

                $this->createAssignmentHistory(
                    lead: $lead,

                    previousUserId: $oldAssignedUserId
                        ? (int) $oldAssignedUserId
                        : null,

                    newUserId: (int) $validated['assigned_to'],

                    assignedBy: (int) $request->user()->id,

                    reason: $validated['reason'],

                    companyId: $companyId
                );
            }
        );

        return back()->with(
            'success',
            'Lead assigned successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Assignment
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | Employee direct POST request bhejkar bhi bulk assignment nahi kar sakta.
    |
    */

    public function bulkAssign(
        Request $request
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | ROLE SECURITY
        |--------------------------------------------------------------------------
        */

        $this->ensureFullAccess(
            $request
        );

        $companyId =
            $this->companyId($request);

        $validated =
            $request->validate([
                'bulk_action' => [
                    'required',
                    Rule::in([
                        'assign',
                        'unassign',
                    ]),
                ],

                'assignment_scope' => [
                    'required',
                    Rule::in([
                        'selected',
                        'filtered',
                    ]),
                ],

                'lead_ids' => [
                    'nullable',
                    'array',
                    'required_if:assignment_scope,selected',
                ],

                'lead_ids.*' => [
                    'integer',

                    Rule::exists(
                        'leads',
                        'id'
                    )->where(
                        function ($query) use (
                            $companyId
                        ) {
                            $query
                                ->where(
                                    'company_id',
                                    $companyId
                                )
                                ->whereNull(
                                    'deleted_at'
                                );
                        }
                    ),
                ],

                /*
                | Employee sirf ASSIGN action me required hai.
                | UNASSIGN me assigned_to null rahega.
                */
                'assigned_to' => [
                    'nullable',
                    'required_if:bulk_action,assign',
                    'integer',

                    Rule::exists(
                        'users',
                        'id'
                    )->where(
                        function ($query) use (
                            $companyId
                        ) {
                            $query
                                ->where(
                                    'company_id',
                                    $companyId
                                )
                                ->where(
                                    'is_active',
                                    true
                                );
                        }
                    ),
                ],

                'reason' => [
                    'required',
                    'string',
                    'max:500',
                ],

                /*
                |--------------------------------------------------------------------------
                | Current Filters
                |--------------------------------------------------------------------------
                */

                'search' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'status' => [
                    'nullable',
                    'integer',
                ],

                'source' => [
                    'nullable',
                    'integer',
                ],

                'filter_assigned_to' => [
                    'nullable',
                    'string',
                ],

                'team_id' => [
                    'nullable',
                    'integer',
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

                'call_disposition' => [
                    'nullable',
                    function (
                        string $attribute,
                        mixed $value,
                        \Closure $fail
                    ) use ($companyId) {
                        if ($value === 'no_call') {
                            return;
                        }

                        if (
                            !ctype_digit((string) $value)
                            ||
                            !CallDisposition::query()
                                ->whereKey((int) $value)
                                ->where(function (Builder $query) use ($companyId) {
                                    $query
                                        ->whereNull('company_id')
                                        ->orWhere('company_id', $companyId);
                                })
                                ->where('is_active', true)
                                ->exists()
                        ) {
                            $fail('The selected call disposition is invalid.');
                        }
                    },
                ],

                'demo_send' => [
                    'nullable',
                    'boolean',
                ],

                'per_page' => [
                    'nullable',
                    'integer',
                    Rule::in([
                        25,
                        50,
                        100,
                        200,
                    ]),
                ],

                'date_from' => [
                    'nullable',
                    'date',
                ],

                'date_to' => [
                    'nullable',
                    'date',
                    'after_or_equal:date_from',
                ],

                'label_id_filter' => [
                    'nullable',
                    'integer',
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | Selected Leads
        |--------------------------------------------------------------------------
        */

        if (
            $validated['assignment_scope'] ===
            'selected'
        ) {
            $targetQuery =
                Lead::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->whereIn(
                    'id',
                    $validated['lead_ids']
                        ?? []
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Filtered Leads
        |--------------------------------------------------------------------------
        */ else {
            $filterRequest =
                new Request([
                    'search' =>
                    $validated['search']
                        ?? null,

                    'status' =>
                    $validated['status']
                        ?? null,

                    'source' =>
                    $validated['source']
                        ?? null,

                    'assigned_to' =>
                    $validated['filter_assigned_to']
                        ?? null,

                    'team_id' =>
                    $validated['team_id']
                        ?? null,

                    'priority' =>
                    $validated['priority']
                        ?? null,

                    'temperature' =>
                    $validated['temperature']
                        ?? null,

                    'call_disposition' =>
                    $validated['call_disposition']
                        ?? null,

                    'demo_send' =>
                    $validated['demo_send']
                        ?? null,

                    'per_page' =>
                    $validated['per_page']
                        ?? null,

                    'date_from' =>
                    $validated['date_from']
                        ?? null,

                    'date_to' =>
                    $validated['date_to']
                        ?? null,

                    'label_id' =>
                    $validated['label_id_filter']
                        ?? null,
                ]);

            $filterRequest->setUserResolver(
                fn() =>
                $request->user()
            );

            $targetQuery =
                $this->filteredLeadQuery(
                    $filterRequest
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Count
        |--------------------------------------------------------------------------
        */

        $totalLeads =
            (clone $targetQuery)->count();

        if ($totalLeads < 1) {
            return back()->with(
                'error',
                $validated['bulk_action'] === 'unassign'
                    ? 'No leads found for bulk unassign.'
                    : 'No leads found for bulk assignment.'
            );
        }

        $bulkAction =
            $validated['bulk_action'];

        $assignedBy =
            (int) $request->user()->id;

        $reason =
            $validated['reason'];

        $updatedCount = 0;
        $skippedCount = 0;

        /*
        |--------------------------------------------------------------------------
        | BULK UNASSIGN
        |--------------------------------------------------------------------------
        |
        | Galat employee ko assigned leads ka owner remove kar denge.
        | assigned_to = NULL hone ke baad lead "Unassigned" ho jayegi.
        |
        | Existing LeadAssignment history helper new_user_id ko required int
        | maanta hai, isliye unassign par invalid history row create nahi karte.
        |
        */

        if ($bulkAction === 'unassign') {
            $targetQuery
                ->select([
                    'id',
                    'company_id',
                    'assigned_to',
                ])
                ->orderBy('id')
                ->chunkById(
                    200,
                    function ($leads) use (
                        &$updatedCount,
                        &$skippedCount
                    ) {
                        DB::transaction(
                            function () use (
                                $leads,
                                &$updatedCount,
                                &$skippedCount
                            ) {
                                foreach ($leads as $lead) {
                                    if (empty($lead->assigned_to)) {
                                        $skippedCount++;
                                        continue;
                                    }

                                    $lead->update([
                                        'assigned_to' => null,
                                    ]);

                                    $updatedCount++;
                                }
                            }
                        );
                    }
                );

            return back()->with(
                'success',
                "{$updatedCount} leads unassigned successfully. {$skippedCount} already unassigned leads skipped."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | BULK ASSIGN
        |--------------------------------------------------------------------------
        */

        $newUserId =
            (int) $validated['assigned_to'];

        $targetQuery
            ->select([
                'id',
                'company_id',
                'assigned_to',
            ])
            ->orderBy('id')
            ->chunkById(
                200,

                function ($leads) use (
                    $newUserId,
                    $assignedBy,
                    $reason,
                    $companyId,
                    &$updatedCount,
                    &$skippedCount
                ) {
                    DB::transaction(
                        function () use (
                            $leads,
                            $newUserId,
                            $assignedBy,
                            $reason,
                            $companyId,
                            &$updatedCount,
                            &$skippedCount
                        ) {
                            foreach (
                                $leads as $lead
                            ) {
                                $previousUserId =
                                    $lead->assigned_to;

                                /*
                                | Already same employee
                                */

                                if (
                                    (int) $previousUserId ===
                                    $newUserId
                                ) {
                                    $skippedCount++;

                                    continue;
                                }

                                $lead->update([
                                    'assigned_to' =>
                                    $newUserId,
                                ]);

                                $this->createAssignmentHistory(
                                    lead: $lead,

                                    previousUserId: $previousUserId
                                        ? (int) $previousUserId
                                        : null,

                                    newUserId: $newUserId,

                                    assignedBy: $assignedBy,

                                    reason: $reason,

                                    companyId: $companyId
                                );

                                $updatedCount++;
                            }
                        }
                    );
                }
            );

        return back()->with(
            'success',
            "{$updatedCount} leads assigned successfully. {$skippedCount} already assigned leads skipped."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Add Note
    |--------------------------------------------------------------------------
    |
    | Employee apni assigned Lead par note add kar sakta hai.
    |
    */

    public function note(
        Request $request,
        Lead $lead
    ): RedirectResponse {
        $this->guard(
            $request,
            $lead
        );

        $validated =
            $request->validate([
                'body' => [
                    'required',
                    'string',
                    'max:3000',
                ],
            ]);

        Note::create([
            'lead_id' =>
            $lead->id,

            'user_id' =>
            $request->user()->id,

            'body' =>
            trim($validated['body']),
        ]);

        return back()->with(
            'success',
            'Note added successfully.'
        );
    }



    private function filteredLeadQuery(
    Request $request
    ): Builder {
        $companyId = $this->companyId($request);
        $user = $request->user();
        $hasFullAccess = $this->hasFullAccess($request);

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        */

        $query = Lead::query()
            ->where(
                'company_id',
                $companyId
            );

        /*
        |--------------------------------------------------------------------------
        | ROLE BASED LEAD ACCESS
        |--------------------------------------------------------------------------
        |
        | Admin / Super Admin:
        | Company ki saari leads.
        |
        | Team Leader:
        | 1. Khud ko assigned leads
        | 2. Apni teams ke employees ko assigned leads.
        |
        | Employee:
        | Sirf khud ko assigned leads.
        |
        */

        $leaderTeamIds = [];

        if (!$hasFullAccess) {

            $leaderTeamIds =
                $this->leaderTeamIds($request);

            /*
            |--------------------------------------------------------------------------
            | Normal Employee
            |--------------------------------------------------------------------------
            */

            if (empty($leaderTeamIds)) {

                $query->where(
                    'assigned_to',
                    $user->id
                );

            } else {

                /*
                |--------------------------------------------------------------------------
                | Team Leader
                |--------------------------------------------------------------------------
                */

                $query->where(
                    function (Builder $accessQuery) use (
                        $user,
                        $companyId,
                        $leaderTeamIds
                    ) {
                        $accessQuery
                            ->where(
                                'assigned_to',
                                $user->id
                            )
                            ->orWhereIn(
                                'assigned_to',
                                User::query()
                                    ->select('id')
                                    ->where(
                                        'company_id',
                                        $companyId
                                    )
                                    ->whereIn(
                                        'team_id',
                                        $leaderTeamIds
                                    )
                            );
                    }
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim(
                (string) $request->input('search')
            );

            $query->where(
                function (Builder $subQuery) use ($search) {

                    $subQuery
                        ->where(
                            'name',
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
                            'company_name',
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
                            'category',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $query->where(
                'lead_status_id',
                $request->input('status')
            );
        }

        if ($request->filled('city')) {

            $query->where(
                'city',
                (string)$request->input('city')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Source
        |--------------------------------------------------------------------------
        */

        if ($request->filled('source')) {
            $query->where(
                'lead_source_id',
                $request->input('source')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        */

        if ($request->filled('category')) {

            $category = trim(
                (string) $request->input('category')
            );

            $query->where(
                'category',
                $category
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Assigned Employee Filter
        |--------------------------------------------------------------------------
        */

        $isTeamLeader =
            !$hasFullAccess &&
            !empty($leaderTeamIds);

        if (
            ($hasFullAccess || $isTeamLeader)
            &&
            $request->filled('assigned_to')
        ) {

            $assignedTo =
                (string) $request->input('assigned_to');

            /*
            |--------------------------------------------------------------------------
            | Unassigned
            |--------------------------------------------------------------------------
            */

            if ($assignedTo === 'unassigned') {

                /*
                * Unassigned lead sirf Admin/Super Admin ko accessible.
                */

                if ($hasFullAccess) {

                    $query->whereNull(
                        'assigned_to'
                    );

                } else {

                    /*
                    * Team Leader unassigned leads nahi dekh sakta.
                    */
                    $query->whereRaw('1 = 0');
                }

            } elseif (ctype_digit($assignedTo)) {

                /*
                |--------------------------------------------------------------------------
                | Specific Employee
                |--------------------------------------------------------------------------
                */

                $query->where(
                    'assigned_to',
                    (int) $assignedTo
                );

            } else {

                /*
                * Invalid assigned_to URL value.
                */

                $query->whereRaw('1 = 0');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Team Filter
        |--------------------------------------------------------------------------
        |
        | Lead par direct team_id ho ya assigned employee ke users.team_id se
        | relation mile, dono cases ko support karta hai.
        |
        */

        if ($request->filled('team_id')) {

            $teamId =
                (int) $request->input('team_id');

            $query->where(
                function (Builder $teamQuery) use (
                    $companyId,
                    $teamId
                ) {

                    $teamQuery
                        ->where(
                            'team_id',
                            $teamId
                        )
                        ->orWhereIn(
                            'assigned_to',
                            User::query()
                                ->select('id')
                                ->where(
                                    'company_id',
                                    $companyId
                                )
                                ->where(
                                    'team_id',
                                    $teamId
                                )
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Priority
        |--------------------------------------------------------------------------
        */

        if ($request->filled('priority')) {

            $priority =
                (string) $request->input('priority');

            if (in_array(
                $priority,
                [
                    'low',
                    'normal',
                    'high',
                    'urgent',
                    'hot',
                ],
                true
            )) {
                $query->where(
                    'priority',
                    $priority
                );
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Temperature
        |--------------------------------------------------------------------------
        */

        if ($request->filled('temperature')) {

            $temperature =
                (string) $request->input('temperature');

            if (in_array(
                $temperature,
                [
                    'cold',
                    'warm',
                    'hot',
                ],
                true
            )) {
                $query->where(
                    'temperature',
                    $temperature
                );
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Latest Call Disposition
        |--------------------------------------------------------------------------
        |
        | no_call:
        | Lead par abhi koi call nahi.
        |
        | Numeric ID:
        | Lead ka latest call selected disposition wala hona chahiye.
        |
        */

        if ($request->filled('call_disposition')) {

            $callDisposition =
                (string) $request->input(
                    'call_disposition'
                );

            /*
            |--------------------------------------------------------------------------
            | No Call Yet
            |--------------------------------------------------------------------------
            */

            if ($callDisposition === 'no_call') {

                $query->whereDoesntHave(
                    'calls'
                );

            } elseif (ctype_digit($callDisposition)) {

                /*
                |--------------------------------------------------------------------------
                | Selected Disposition
                |--------------------------------------------------------------------------
                */

                $dispositionId =
                    (int) $callDisposition;

                $validDisposition =
                    CallDisposition::query()
                        ->whereKey(
                            $dispositionId
                        )
                        ->where(
                            function (
                                Builder $dispositionQuery
                            ) use ($companyId) {

                                $dispositionQuery
                                    ->whereNull(
                                        'company_id'
                                    )
                                    ->orWhere(
                                        'company_id',
                                        $companyId
                                    );
                            }
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->exists();

                if ($validDisposition) {

                    $query->whereHas(
                        'calls',
                        function (
                            Builder $callQuery
                        ) use (
                            $dispositionId
                        ) {

                            $callQuery
                                ->where(
                                    'call_disposition_id',
                                    $dispositionId
                                )
                                ->whereRaw(
                                    'call_logs.id = (
                                        SELECT MAX(latest_call.id)
                                        FROM call_logs AS latest_call
                                        WHERE latest_call.lead_id = leads.id
                                    )'
                                );
                        }
                    );

                } else {

                    $query->whereRaw(
                        '1 = 0'
                    );
                }

            } else {

                $query->whereRaw(
                    '1 = 0'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Label Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('label_id')) {

            $labelId =
                (int) $request->input(
                    'label_id'
                );

            $validLabel =
                LeadLabel::query()
                    ->whereKey(
                        $labelId
                    )
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->exists();

            if ($validLabel) {

                $query->whereHas(
                    'labels',
                    function (
                        Builder $labelQuery
                    ) use (
                        $labelId
                    ) {

                        $labelQuery->where(
                            'lead_labels.id',
                            $labelId
                        );
                    }
                );

            } else {

                $query->whereRaw(
                    '1 = 0'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Demo Send Tab Filter
        |--------------------------------------------------------------------------
        |
        | Leads page par existing:
        |
        | ?demo_send=1
        |
        | tab ko same tarah kaam karne denge.
        |
        */

        if ($request->boolean('demo_send')) {

            $query->where(
                'demo_send',
                true
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Dashboard Lead Send Filter
        |--------------------------------------------------------------------------
        |
        | lead_send=today
        |   Sirf aaj Demo Send hui leads.
        |
        | lead_send=all
        |   Saari Demo Send leads.
        |
        */

        if ($request->filled('lead_send')) {

            $leadSend =
                (string) $request->input(
                    'lead_send'
                );

            /*
            |--------------------------------------------------------------------------
            | Today Lead Send
            |--------------------------------------------------------------------------
            */

            if ($leadSend === 'today') {

                $query
                    ->where(
                        'demo_send',
                        true
                    )
                    ->whereNotNull(
                        'demo_sent_at'
                    )
                    ->whereDate(
                        'demo_sent_at',
                        today()
                    );

            /*
            |--------------------------------------------------------------------------
            | Total Lead Send
            |--------------------------------------------------------------------------
            */

            } elseif ($leadSend === 'all') {

                $query->where(
                    'demo_send',
                    true
                );

            } else {

                /*
                * Invalid filter manually URL me bheja gaya.
                */
                $query->whereRaw(
                    '1 = 0'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | New Today Dashboard Filter
        |--------------------------------------------------------------------------
        |
        | Dashboard se:
        |
        | ?created_filter=today
        |
        */

        if (
            $request->input(
                'created_filter'
            ) === 'today'
        ) {
            $query->whereDate(
                'created_at',
                today()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Date From
        |--------------------------------------------------------------------------
        |
        | Ye lead created_at par filter hai.
        |
        */

        if ($request->filled('date_from')) {

            $query->whereDate(
                'created_at',
                '>=',
                $request->input(
                    'date_from'
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Date To
        |--------------------------------------------------------------------------
        */

        if ($request->filled('date_to')) {

            $query->whereDate(
                'created_at',
                '<=',
                $request->input(
                    'date_to'
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Final Query
        |--------------------------------------------------------------------------
        */

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Form Data
    |--------------------------------------------------------------------------
    */

    private function formData(
        Request $request
    ): array {
        $companyId =
            $this->companyId($request);

        $hasFullAccess =
            $this->hasFullAccess($request);

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        |
        | Normal employee ko doosra employee choose nahi karna hai.
        |
        */

        $users = $hasFullAccess
            ? User::query()
            ->where(
                'company_id',
                $companyId
            )
            ->where(
                'is_active',
                true
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'employee_code',
            ])
            : collect([
                $request->user(),
            ]);

        return [
            'sources' =>
            LeadSource::query()
                ->where(
                    function (
                        Builder $query
                    ) use ($companyId) {
                        $query
                            ->whereNull(
                                'company_id'
                            )
                            ->orWhere(
                                'company_id',
                                $companyId
                            );
                    }
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get(),

            'statuses' =>
            LeadStatus::query()
                ->where(
                    function (
                        Builder $query
                    ) use ($companyId) {
                        $query
                            ->whereNull(
                                'company_id'
                            )
                            ->orWhere(
                                'company_id',
                                $companyId
                            );
                    }
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'sort_order'
                )
                ->orderBy('name')
                ->get(),

            'users' =>
            $users,

            'teams' =>
            Team::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                ]),

            'stages' =>
            PipelineStage::query()
                ->whereHas(
                    'pipeline',
                    fn(
                        Builder $query
                    ) =>
                    $query->where(
                        'company_id',
                        $companyId
                    )
                )
                ->orderBy(
                    'sort_order'
                )
                ->get(),

            'hasFullAccess' =>
            $hasFullAccess,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    private function validateData(
        Request $request,
        ?Lead $lead = null
    ): array {
        $companyId =
            $this->companyId($request);

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'mobile' => [
                'required',
                'string',
                'max:20',

                Rule::unique(
                    'leads',
                    'mobile'
                )
                    ->where(
                        fn($query) =>
                        $query->where(
                            'company_id',
                            $companyId
                        )
                    )
                    ->ignore(
                        $lead?->id
                    ),
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

            'company_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'lead_source_id' => [
                'required',
                'integer',

                Rule::exists(
                    'lead_sources',
                    'id'
                )->where(
                    function ($query) use (
                        $companyId
                    ) {
                        $query->where(
                            function (
                                $subQuery
                            ) use ($companyId) {
                                $subQuery
                                    ->whereNull(
                                        'company_id'
                                    )
                                    ->orWhere(
                                        'company_id',
                                        $companyId
                                    );
                            }
                        );
                    }
                ),
            ],

            'lead_status_id' => [
                'required',
                'integer',

                Rule::exists(
                    'lead_statuses',
                    'id'
                )->where(
                    function ($query) use (
                        $companyId
                    ) {
                        $query->where(
                            function (
                                $subQuery
                            ) use ($companyId) {
                                $subQuery
                                    ->whereNull(
                                        'company_id'
                                    )
                                    ->orWhere(
                                        'company_id',
                                        $companyId
                                    );
                            }
                        );
                    }
                ),
            ],

            'assigned_to' => [
                'nullable',
                'integer',

                Rule::exists(
                    'users',
                    'id'
                )->where(
                    function ($query) use (
                        $companyId
                    ) {
                        $query
                            ->where(
                                'company_id',
                                $companyId
                            )
                            ->where(
                                'is_active',
                                true
                            );
                    }
                ),
            ],

            'team_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'teams',
                    'id'
                )->where(
                    fn($query) =>
                    $query->where(
                        'company_id',
                        $companyId
                    )
                ),
            ],

            'pipeline_stage_id' => [
                'nullable',
                'integer',
                'exists:pipeline_stages,id',
            ],

            'priority' => [
                'required',

                Rule::in([
                    'low',
                    'normal',
                    'high',
                    'urgent',
                    'hot',
                ]),
            ],

            'temperature' => [
                'required',

                Rule::in([
                    'cold',
                    'warm',
                    'hot',
                ]),
            ],

            'preferred_language' => [
                'nullable',
                'string',
                'max:50',
            ],

            'address' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'district' => [
                'nullable',
                'string',
                'max:100',
            ],

            'state' => [
                'nullable',
                'string',
                'max:100',
            ],

            'pincode' => [
                'nullable',
                'string',
                'max:10',
            ],

            'required_product' => [
                'nullable',
                'string',
                'max:255',
            ],

            'estimated_budget' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'expected_deal_value' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'expected_closing_date' => [
                'nullable',
                'date',
            ],

            'next_follow_up_at' => [
                'nullable',
                'date',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Default Pipeline Stage
    |--------------------------------------------------------------------------
    */

    private function defaultPipelineStageId(
        int $companyId
    ): ?int {
        $pipeline =
            Pipeline::query()
            ->where(
                'company_id',
                $companyId
            )
            ->orderByDesc(
                'is_default'
            )
            ->orderBy('id')
            ->first();

        if (!$pipeline) {
            return null;
        }

        return PipelineStage::query()
            ->where(
                'pipeline_id',
                $pipeline->id
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy('id')
            ->value('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Assignment History
    |--------------------------------------------------------------------------
    */

    private function createAssignmentHistory(
        Lead $lead,
        ?int $previousUserId,
        int $newUserId,
        int $assignedBy,
        string $reason,
        int $companyId
    ): void {
        LeadAssignment::create([
            'company_id' =>
            $companyId,

            'lead_id' =>
            $lead->id,

            'previous_user_id' =>
            $previousUserId,

            'new_user_id' =>
            $newUserId,

            'assigned_by' =>
            $assignedBy,

            'reason' =>
            $reason,

            'assigned_at' =>
            now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Company ID
    |--------------------------------------------------------------------------
    */

    private function companyId(
        Request $request
    ): int {
        return (int)
        $request->user()->company_id;
    }

    /*
    |--------------------------------------------------------------------------
    | Team Leader Team IDs
    |--------------------------------------------------------------------------
    |
    | Actual team leadership teams.leader_id se determine hogi.
    | Role name team_leader hona required nahi hai.
    |
    */

    private function leaderTeamIds(
        Request $request
    ): array {
        $companyId =
            $this->companyId($request);

        return Team::query()
            ->where(
                'company_id',
                $companyId
            )
            ->where(
                'leader_id',
                $request->user()->id
            )
            ->pluck('id')
            ->map(
                fn($id) => (int) $id
            )
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Team Leader Check
    |--------------------------------------------------------------------------
    */

    private function isTeamLeader(
        Request $request
    ): bool {
        return !empty($this->leaderTeamIds($request));
    }

    /*
    |--------------------------------------------------------------------------
    | Full Access Check
    |--------------------------------------------------------------------------
    */

    private function hasFullAccess(
        Request $request
    ): bool {
        return $request
            ->user()
            ->hasAnyRole(
                $this->fullAccessRoles
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Ensure Admin Access
    |--------------------------------------------------------------------------
    */

    private function ensureFullAccess(
        Request $request
    ): void {
        abort_unless(
            $this->hasFullAccess($request),
            403,
            'You do not have permission to manage lead assignments.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Company Guard
    |--------------------------------------------------------------------------
    */

    private function guardCompany(
        Request $request,
        Lead $lead
    ): void {
        abort_unless(
            (int) $lead->company_id ===
                $this->companyId($request),
            403,
            'Unauthorized company lead access.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Lead Access Guard
    |--------------------------------------------------------------------------
    |
    | Super Admin/Admin:
    | company ki lead open kar sakte hain.
    |
    | Employee:
    | sirf assigned lead open kar sakta hai.
    |
    */

    private function guard(
        Request $request,
        Lead $lead
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Company Check
        |--------------------------------------------------------------------------
        */

        $this->guardCompany(
            $request,
            $lead
        );

        /*
        |--------------------------------------------------------------------------
        | Admin / Super Admin
        |--------------------------------------------------------------------------
        */

        if ($this->hasFullAccess($request)) {
            return;
        }

        $user = $request->user();
        $companyId =
            $this->companyId($request);

        /*
        |--------------------------------------------------------------------------
        | Own Assigned Lead
        |--------------------------------------------------------------------------
        */

        if (
            (int) $lead->assigned_to ===
            (int) $user->id
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Team Leader Access
        |--------------------------------------------------------------------------
        |
        | Agar login user kisi team ka leader hai aur lead jis employee ko
        | assigned hai wo employee uski team me hai, to access allowed.
        |
        */

        $leaderTeamIds =
            $this->leaderTeamIds($request);

        if (!empty($leaderTeamIds)) {
            $assignedEmployeeIsInLeaderTeam =
                User::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->whereKey(
                    $lead->assigned_to
                )
                ->whereIn(
                    'team_id',
                    $leaderTeamIds
                )
                ->exists();

            if ($assignedEmployeeIsInLeaderTeam) {
                return;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | No Access
        |--------------------------------------------------------------------------
        */

        abort(
            403,
            'This lead is not assigned to you or your team.'
        );
    }


    private function requestWithoutBoardFilters(
    Request $request
    ): Request {

        $copy = clone $request;

        $remove = [
            'new_page',
            'dialed_page',
            'connected_page',
            'call_disposition',
        ];

        foreach ($remove as $key) {

            $copy->query->remove($key);
            $copy->request->remove($key);
        }

        return $copy;
    }


    /*
    |--------------------------------------------------------------------------
    | Current Assignment Activity Scope
    |--------------------------------------------------------------------------
    |
    | A lead can have old activity from Employee A and later be assigned to
    | Employee B. Board state must be fresh for B without deleting A's history.
    |
    | Assignment boundary = latest lead_assignments.assigned_at where
    | new_user_id = leads.assigned_to. If no history row exists, leads.created_at
    | is used as safe fallback.
    |
    */
    private function applyCurrentAssignmentCallState(
        Builder $query,
        string $state
    ): Builder {
        $assignmentBoundarySql =
            "COALESCE((SELECT MAX(la.assigned_at) " .
            "FROM lead_assignments la " .
            "WHERE la.lead_id = leads.id " .
            "AND la.new_user_id = leads.assigned_to), leads.created_at)";

        $currentCall = function (Builder $calls) use ($assignmentBoundarySql) {
            $calls
                ->whereColumn('call_logs.user_id', 'leads.assigned_to')
                ->whereRaw("call_logs.created_at >= {$assignmentBoundarySql}");
        };

        if ($state === 'new') {
            return $query->where(function (Builder $stateQuery) use ($currentCall) {
                $stateQuery
                    ->where(function (Builder $assigned) use ($currentCall) {
                        $assigned
                            ->whereNotNull('leads.assigned_to')
                            ->whereDoesntHave('calls', $currentCall);
                    })
                    ->orWhere(function (Builder $unassigned) {
                        // Unassigned admin view keeps existing/global behavior.
                        $unassigned
                            ->whereNull('leads.assigned_to')
                            ->whereDoesntHave('calls');
                    });
            });
        }

        if ($state === 'dialed') {
            return $query->where(function (Builder $stateQuery) use ($currentCall) {
                $stateQuery
                    ->where(function (Builder $assigned) use ($currentCall) {
                        $assigned
                            ->whereNotNull('leads.assigned_to')
                            ->whereHas('calls', $currentCall);
                    })
                    ->orWhere(function (Builder $unassigned) {
                        $unassigned
                            ->whereNull('leads.assigned_to')
                            ->whereHas('calls');
                    });
            });
        }

        if ($state === 'connected') {
            return $query->where(function (Builder $stateQuery) use ($assignmentBoundarySql) {
                $stateQuery
                    ->where(function (Builder $assigned) use ($assignmentBoundarySql) {
                        $assigned
                            ->whereNotNull('leads.assigned_to')
                            ->whereHas('calls', function (Builder $calls) use ($assignmentBoundarySql) {
                                $calls
                                    ->whereColumn('call_logs.user_id', 'leads.assigned_to')
                                    ->whereRaw("call_logs.created_at >= {$assignmentBoundarySql}");
                            })
                            ->where(function (Builder $connected) use ($assignmentBoundarySql) {
                                // Note must also belong to current assigned employee after reassignment.
                                $connected->whereHas('notes', function (Builder $notes) use ($assignmentBoundarySql) {
                                    $notes
                                        ->whereColumn('notes.user_id', 'leads.assigned_to')
                                        ->whereRaw("notes.created_at >= {$assignmentBoundarySql}");
                                });

                                if (Schema::hasColumn('call_logs', 'remarks')) {
                                    $connected->orWhereHas('calls', function (Builder $calls) use ($assignmentBoundarySql) {
                                        $calls
                                            ->whereColumn('call_logs.user_id', 'leads.assigned_to')
                                            ->whereRaw("call_logs.created_at >= {$assignmentBoundarySql}")
                                            ->whereNotNull('remarks')
                                            ->whereRaw("TRIM(COALESCE(remarks, '')) <> ''");
                                    });
                                }

                                if (Schema::hasColumn('call_logs', 'remark')) {
                                    $connected->orWhereHas('calls', function (Builder $calls) use ($assignmentBoundarySql) {
                                        $calls
                                            ->whereColumn('call_logs.user_id', 'leads.assigned_to')
                                            ->whereRaw("call_logs.created_at >= {$assignmentBoundarySql}")
                                            ->whereNotNull('remark')
                                            ->whereRaw("TRIM(COALESCE(remark, '')) <> ''");
                                    });
                                }

                                if (Schema::hasColumn('call_logs', 'auto_remarks')) {
                                    $connected->orWhereHas('calls', function (Builder $calls) use ($assignmentBoundarySql) {
                                        $calls
                                            ->whereColumn('call_logs.user_id', 'leads.assigned_to')
                                            ->whereRaw("call_logs.created_at >= {$assignmentBoundarySql}")
                                            ->whereNotNull('auto_remarks')
                                            ->whereRaw("TRIM(COALESCE(auto_remarks, '')) <> ''");
                                    });
                                }
                            });
                    })
                    ->orWhere(function (Builder $unassigned) {
                        // Existing/global connected behavior for unassigned admin leads.
                        $unassigned
                            ->whereNull('leads.assigned_to')
                            ->whereHas('calls')
                            ->where(function (Builder $connected) {
                                $connected->whereHas('notes');

                                if (Schema::hasColumn('call_logs', 'remarks')) {
                                    $connected->orWhereHas('calls', function (Builder $calls) {
                                        $calls->whereNotNull('remarks')
                                            ->whereRaw("TRIM(COALESCE(remarks, '')) <> ''");
                                    });
                                }

                                if (Schema::hasColumn('call_logs', 'remark')) {
                                    $connected->orWhereHas('calls', function (Builder $calls) {
                                        $calls->whereNotNull('remark')
                                            ->whereRaw("TRIM(COALESCE(remark, '')) <> ''");
                                    });
                                }

                                if (Schema::hasColumn('call_logs', 'auto_remarks')) {
                                    $connected->orWhereHas('calls', function (Builder $calls) {
                                        $calls->whereNotNull('auto_remarks')
                                            ->whereRaw("TRIM(COALESCE(auto_remarks, '')) <> ''");
                                    });
                                }
                            });
                    });
            });
        }

        return $query;
    }

    private function demoDispositionId(Request $request): int
    {
        $companyId = $this->companyId($request);

        $id = CallDisposition::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($companyId) {
                $query->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            })
            ->whereRaw("LOWER(TRIM(name)) = 'demo'")
            ->value('id');

        abort_if(
            empty($id),
            422,
            'Active "Demo" call disposition not found. Please create/enable Demo disposition first.'
        );

        return (int) $id;
    }

    private function saveDemoDispositionLog(
        Request $request,
        Lead $lead,
        int $dispositionId
    ): void {

        $companyId = $this->companyId($request);

        /*
        |--------------------------------------------------------------------------
        | Validate Demo Disposition
        |--------------------------------------------------------------------------
        */

        $validDisposition = CallDisposition::query()
            ->whereKey($dispositionId)
            ->where('is_active', true)
            ->where(function (Builder $query) use ($companyId) {

                $query
                    ->whereNull('company_id')
                    ->orWhere(
                        'company_id',
                        $companyId
                    );
            })
            ->exists();

        abort_unless(
            $validDisposition,
            422,
            'Invalid Demo call disposition.'
        );

        /*
        |--------------------------------------------------------------------------
        | Save Only Demo Disposition
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Yahan remarks / remark / auto_remarks kuch bhi save nahi hoga.
        | Sirf Demo disposition ka call log create hoga.
        |
        */

        $columns =
            array_flip(
                Schema::getColumnListing(
                    'call_logs'
                )
            );

        $payload = [];

        if (isset($columns['lead_id'])) {
            $payload['lead_id'] =
                $lead->id;
        }

        if (isset($columns['user_id'])) {
            $payload['user_id'] =
                (int) $request->user()->id;
        }

        if (isset($columns['company_id'])) {
            $payload['company_id'] =
                $companyId;
        }

        if (isset($columns['call_disposition_id'])) {
            $payload['call_disposition_id'] =
                $dispositionId;
        }

        if (isset($columns['created_at'])) {
            $payload['created_at'] =
                now();
        }

        if (isset($columns['updated_at'])) {
            $payload['updated_at'] =
                now();
        }

        DB::table('call_logs')
            ->insert($payload);
    }

    private function boardFilterKeys(): array
{
    $sections = [
        'new',
        'dialed',
        'connected',
    ];

    $fields = [
        'category',
        'source',
        'city',
        'priority',
        'assigned_to',
        'date_filter',
        'disposition_id',
        'demo_status',
        'label_id',
    ];

    $keys = [
        'new_page',
        'dialed_page',
        'connected_page',
    ];

    foreach ($sections as $section) {

        foreach ($fields as $field) {

            $keys[] =
                $section
                . '_'
                . $field;
        }
    }

    return $keys;
}


private function applyQuickMetricFilter(
    Builder $query,
    string $quickMetric,
    string $section
): Builder {

    if ($quickMetric === '') {
        return $query;
    }

    switch ($quickMetric) {

        case 'calls_today':
            // Aaj kam se kam ek call hui ho.
            $query->whereHas('calls', function (Builder $calls) {
                $calls->whereDate('call_logs.created_at', today());
            });
            break;

        case 'connected_today':
            // Sirf Connected column, aur call aaj ki ho.
            if ($section !== 'connected') {
                $query->whereRaw('1 = 0');
                break;
            }

            $query->whereHas('calls', function (Builder $calls) {
                $calls->whereDate('call_logs.created_at', today());
            });
            break;

        case 'employee_total_calls':
            // New Call me koi call hoti hi nahi. Dialed column me sab called leads.
            // Connected ko hide rakhte hain taaki same lead duplicate na dikhe.
            if ($section !== 'dialed') {
                $query->whereRaw('1 = 0');
            }
            break;

        case 'unique_connected':
            // Distinct connected leads sirf Connected column me.
            if ($section !== 'connected') {
                $query->whereRaw('1 = 0');
            }
            break;

        case 'follow_up':
            $query->whereNotNull('leads.next_follow_up_at');
            break;

        case 'demo_today':
            $query
                ->where('leads.demo_send', true)
                ->whereNotNull('leads.demo_sent_at')
                ->whereDate('leads.demo_sent_at', today());
            break;

        case 'total_demo':
            $query->where('leads.demo_send', true);
            break;
    }

    return $query;
}


private function applyBoardFilters(
    Builder $query,
    Request $request,
    string $section,
    int $companyId
): Builder {

    /*
    |--------------------------------------------------------------------------
    | Category
    |--------------------------------------------------------------------------
    */

    $categoryKey =
        $section . '_category';

    if ($request->filled($categoryKey)) {

        $query->where(
            'leads.category',
            (string) $request->input(
                $categoryKey
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Source
    |--------------------------------------------------------------------------
    */

    $sourceKey =
        $section . '_source';

    if ($request->filled($sourceKey)) {

        $sourceId =
            (int) $request->input(
                $sourceKey
            );

        $query->where(
            'leads.source_id',
            $sourceId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | City
    |--------------------------------------------------------------------------
    */

    $cityKey =
        $section . '_city';

    if ($request->filled($cityKey)) {

        $query->where(
            'leads.city',
            (string) $request->input(
                $cityKey
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Priority
    |--------------------------------------------------------------------------
    */

    $priorityKey =
        $section . '_priority';

    if ($request->filled($priorityKey)) {

        $priority =
            (string) $request->input(
                $priorityKey
            );

        if (
            in_array(
                $priority,
                [
                    'low',
                    'normal',
                    'high',
                    'urgent',
                    'hot',
                ],
                true
            )
        ) {

            $query->where(
                'leads.priority',
                $priority
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Assigned Employee
    |--------------------------------------------------------------------------
    */

    $assignedKey =
        $section . '_assigned_to';

    if ($request->filled($assignedKey)) {

        $assignedUserId =
            (int) $request->input(
                $assignedKey
            );

        $query->where(
            'leads.assigned_to',
            $assignedUserId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Date Added
    |--------------------------------------------------------------------------
    */

    $dateKey =
        $section . '_date_filter';

    if ($request->filled($dateKey)) {

        $dateFilter =
            (string) $request->input(
                $dateKey
            );

        switch ($dateFilter) {

            case 'today':

                $query->whereDate(
                    'leads.created_at',
                    today()
                );

                break;


            case 'yesterday':

                $query->whereDate(
                    'leads.created_at',
                    today()->subDay()
                );

                break;


            case 'week':

                $query->whereBetween(
                    'leads.created_at',
                    [
                        now()
                            ->startOfWeek(),

                        now()
                            ->endOfWeek(),
                    ]
                );

                break;


            case 'month':

                $query
                    ->whereYear(
                        'leads.created_at',
                        now()->year
                    )
                    ->whereMonth(
                        'leads.created_at',
                        now()->month
                    );

                break;
        }
    }



    /*
    |--------------------------------------------------------------------------
    | Latest Call Disposition
    |--------------------------------------------------------------------------
    |
    | "none" = lead ke against koi call log/disposition nahi.
    |
    | Numeric ID = lead ki LATEST call ka disposition selected ID hona chahiye.
    | Sirf kisi purani call ka disposition match nahi kiya jayega.
    |
    */

    $dispositionKey =
        $section . '_disposition_id';

    if ($request->filled($dispositionKey)) {

        $dispositionValue =
            (string) $request->input(
                $dispositionKey
            );

        if ($dispositionValue === 'none') {

            $this->applyCurrentAssignmentCallState($query, 'new');

        } elseif (
            ctype_digit($dispositionValue)
            &&
            (int) $dispositionValue > 0
        ) {

            $dispositionId =
                (int) $dispositionValue;

            $validDisposition =
                CallDisposition::query()
                    ->whereKey($dispositionId)
                    ->where('is_active', true)
                    ->where(
                        function (Builder $dispositionQuery) use (
                            $companyId
                        ) {
                            $dispositionQuery
                                ->whereNull('company_id')
                                ->orWhere(
                                    'company_id',
                                    $companyId
                                );
                        }
                    )
                    ->exists();

            if ($validDisposition) {

                $assignmentBoundarySql =
                    "COALESCE((SELECT MAX(la.assigned_at) FROM lead_assignments la " .
                    "WHERE la.lead_id = leads.id AND la.new_user_id = leads.assigned_to), leads.created_at)";

                $query->where(function (Builder $scope) use (
                    $dispositionId,
                    $assignmentBoundarySql
                ) {
                    $scope
                        ->where(function (Builder $assigned) use (
                            $dispositionId,
                            $assignmentBoundarySql
                        ) {
                            $assigned
                                ->whereNotNull('leads.assigned_to')
                                ->whereExists(function ($subQuery) use (
                                    $dispositionId,
                                    $assignmentBoundarySql
                                ) {
                                    $subQuery
                                        ->selectRaw('1')
                                        ->from('call_logs as cl')
                                        ->whereColumn('cl.lead_id', 'leads.id')
                                        ->whereColumn('cl.user_id', 'leads.assigned_to')
                                        ->whereRaw("cl.created_at >= {$assignmentBoundarySql}")
                                        ->where('cl.call_disposition_id', $dispositionId)
                                        ->whereRaw(
                                            'cl.id = (SELECT MAX(cl2.id) FROM call_logs cl2 '
                                            . 'WHERE cl2.lead_id = leads.id '
                                            . 'AND cl2.user_id = leads.assigned_to '
                                            . "AND cl2.created_at >= {$assignmentBoundarySql})"
                                        );
                                });
                        })
                        ->orWhere(function (Builder $unassigned) use ($dispositionId) {
                            $unassigned
                                ->whereNull('leads.assigned_to')
                                ->whereHas('calls', function (Builder $calls) use ($dispositionId) {
                                    $calls
                                        ->where('call_disposition_id', $dispositionId)
                                        ->whereRaw(
                                            'call_logs.id = (SELECT MAX(cl2.id) FROM call_logs cl2 '
                                            . 'WHERE cl2.lead_id = leads.id)'
                                        );
                                });
                        });
                });
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Demo Status
    |--------------------------------------------------------------------------
    */

    $demoKey =
        $section . '_demo_status';

    if ($request->filled($demoKey)) {

        $demoStatus =
            (string) $request->input(
                $demoKey
            );

        if ($demoStatus === 'sent') {

            $query->where(
                'leads.demo_send',
                true
            );

        } elseif ($demoStatus === 'not_sent') {

            $query->where(
                function (Builder $demoQuery) {

                    $demoQuery
                        ->where(
                            'leads.demo_send',
                            false
                        )
                        ->orWhereNull(
                            'leads.demo_send'
                        );
                }
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Label
    |--------------------------------------------------------------------------
    */

    $labelKey =
        $section . '_label_id';

    if ($request->filled($labelKey)) {

        $labelId =
            (int) $request->input(
                $labelKey
            );

        $validLabel =
            LeadLabel::query()
                ->whereKey($labelId)
                ->where(
                    'company_id',
                    $companyId
                )
                ->exists();

        if ($validLabel) {

            $query->whereHas(
                'labels',
                function (Builder $labelQuery) use (
                    $labelId
                ) {

                    $labelQuery->where(
                        'lead_labels.id',
                        $labelId
                    );
                }
            );
        }
    }


    return $query;
}
}
