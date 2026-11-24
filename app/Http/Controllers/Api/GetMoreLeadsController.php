<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallHistory;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetMoreLeadsController extends Controller
{
    private const BATCH_SIZE = 15;

    /*
    |--------------------------------------------------------------------------
    | COMPANY / USER CHECK
    |--------------------------------------------------------------------------
    */

    private function companyId(Request $request): int
    {
        $user = $request->user();

        abort_unless($user, 401, 'Unauthenticated.');

        abort_unless(
            (bool) $user->is_active,
            403,
            'Your account is inactive.'
        );

        $companyId = (int) $user->company_id;

        abort_if(
            $companyId <= 0,
            403,
            'Company not assigned.'
        );

        return $companyId;
    }

    /*
    |--------------------------------------------------------------------------
    | AVAILABLE LEADS
    |--------------------------------------------------------------------------
    |
    | Only leads:
    | - from logged-in user's company
    | - not assigned to anyone
    | - pending
    | - with mobile, state and city
    | - not marked do-not-call
    |--------------------------------------------------------------------------
    */

    // private function availableLeads(int $companyId)
    // {
    //     return Lead::query()
    //         ->where('company_id', $companyId)
    //         ->whereNull('assigned_to')
    //         ->whereRaw(
    //             "LOWER(TRIM(COALESCE(call_status, ''))) = ?",
    //             ['pending']
    //         )
    //         ->where(function ($query) {
    //             $query->where('do_not_call', false)
    //                 ->orWhereNull('do_not_call');
    //         })
    //         ->whereRaw(
    //             "TRIM(COALESCE(mobile, '')) <> ''"
    //         )
    //         ->whereRaw(
    //             "TRIM(COALESCE(state, '')) <> ''"
    //         )
    //         ->whereRaw(
    //             "TRIM(COALESCE(city, '')) <> ''"
    //         );
    // }


    private function availableLeads(int $companyId)
{
    return Lead::query()

        // Only current company leads
        ->where('leads.company_id', $companyId)

        // Only unassigned leads
        ->whereNull('leads.assigned_to')

        // Exclude deleted leads
        ->whereNull('leads.deleted_at')

        // Only pending leads
        ->whereRaw(
            "LOWER(TRIM(COALESCE(leads.call_status, ''))) = ?",
            ['pending']
        )

        // Exclude Do Not Call leads
        ->where(function ($query) {
            $query->where('leads.do_not_call', false)
                ->orWhereNull('leads.do_not_call');
        })

        // Mobile number must be available
        ->whereRaw(
            "TRIM(COALESCE(leads.mobile, '')) <> ''"
        );
}
    /*
    |--------------------------------------------------------------------------
    | GET STATES
    | GET /api/get-more-leads/states
    |--------------------------------------------------------------------------
    */

    public function states(Request $request)
    {
        $companyId = $this->companyId($request);
        $userId = (int) $request->user()->id;

        $available = $this->availableLeads($companyId)
            ->selectRaw('TRIM(state) AS state_name')
            ->distinct()
            ->pluck('state_name');

        /*
         | Include states where user already has
         | an existing batch.
         */

        $previous = DB::table('get_more_lead_batches')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->pluck('state');

        $states = $available
            ->merge($previous)
            ->filter(fn ($v) => trim((string) $v) !== '')
            ->unique(
                fn ($v) => mb_strtolower(trim($v))
            )
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'States fetched successfully.',
            'data' => $states,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET MORE LEADS
    | POST /api/get-more-leads
    | Body: {"state":"Bihar"}
    |--------------------------------------------------------------------------
    */

    // public function store(Request $request)
    // {
    //     $input = $request->validate([
    //         'state' => [
    //             'required',
    //             'string',
    //             'max:120',
    //         ],
    //     ]);

    //     $state = trim($input['state']);

    //     if ($state === '') {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Please select a state.',
    //         ], 422);
    //     }

    //     $companyId = $this->companyId($request);
    //     $userId = (int) $request->user()->id;

    //     return DB::transaction(function () use (
    //         $companyId,
    //         $userId,
    //         $state
    //     ) {

    //         /*
    //          | One assignment operation at a time
    //          | for this company.
    //          */

    //         DB::table('companies')
    //             ->where('id', $companyId)
    //             ->lockForUpdate()
    //             ->firstOrFail();

    //         /*
    //          | Existing active batch from ANY state
    //          | must be completed before a new batch.
    //          */

    //         $activeBatches = DB::table(
    //             'get_more_lead_batches'
    //         )
    //             ->where('company_id', $companyId)
    //             ->where('user_id', $userId)
    //             ->whereNull('history_end_id')
    //             ->orderBy('id')
    //             ->get();

    //         foreach ($activeBatches as $activeBatch) {

    //             $remaining = $this->pendingBatchLeads(
    //                 $activeBatch,
    //                 $companyId,
    //                 $userId
    //             );

    //             if ($remaining->isNotEmpty()) {
    //                 return $this->batchResponse(
    //                     'in_progress',
    //                     $activeBatch->id,
    //                     $remaining,
    //                     'Complete previous batch calls first.'
    //                 );
    //             }

    //             /*
    //              | Close completed batch and freeze
    //              | its history boundary.
    //              */

    //             $historyEndId = max(
    //                 (int) $activeBatch->history_start_id,
    //                 (int) (
    //                     $this->batchHistory($activeBatch)
    //                         ->max('id') ?? 0
    //                 )
    //             );

    //             DB::table('get_more_lead_batches')
    //                 ->where('id', $activeBatch->id)
    //                 ->whereNull('history_end_id')
    //                 ->update([
    //                     'history_end_id' => $historyEndId,
    //                     'updated_at' => now(),
    //                 ]);
    //         }

    //         /*
    //          | Latest batch for the selected state.
    //          */

    //         $previous = DB::table(
    //             'get_more_lead_batches'
    //         )
    //             ->where('company_id', $companyId)
    //             ->where('user_id', $userId)
    //             ->whereRaw(
    //                 'LOWER(TRIM(state)) = ?',
    //                 [mb_strtolower($state)]
    //             )
    //             ->orderByDesc('id')
    //             ->first();

    //         /*
    //          | Base pool for this state.
    //          */

    //         $pool = $this->availableLeads($companyId)
    //             ->whereRaw(
    //                 'LOWER(TRIM(state)) = ?',
    //                 [mb_strtolower($state)]
    //             );

    //         /*
    //          | First batch: all cities allowed.
    //          |
    //          | Next batch: only cities where
    //          | demo_sent occurred in the previous
    //          | completed batch.
    //          */

    //         if ($previous) {

    //             $demoCities = $this->demoCities(
    //                 $previous
    //             );

    //             if ($demoCities->isEmpty()) {

    //                 return $this->batchResponse(
    //                     'no_demo_city',
    //                     $previous->id,
    //                     collect(),
    //                     'No demo-sent city found in previous batch.'
    //                 );
    //             }

    //             $pool->whereIn(
    //                 DB::raw('LOWER(TRIM(city))'),
    //                 $demoCities->all()
    //             );
    //         }

    //         /*
    //          | Select up to 15 leads.
    //          | Prefer different cities.
    //          */

    //         $selected = $this->pickLeads($pool);

    //         if ($selected->isEmpty()) {

    //             return $this->batchResponse(
    //                 'no_stock',
    //                 $previous?->id,
    //                 collect(),
    //                 'No eligible unassigned leads available.'
    //             );
    //         }

    //         /*
    //          | The history boundary is the user's
    //          | latest history ID before this batch.
    //          */

    //         $historyStartId = (int) (
    //             CallHistory::query()
    //                 ->where('user_id', $userId)
    //                 ->max('id') ?? 0
    //         );

    //         $now = now();

    //         /*
    //          | Create batch.
    //          */

    //         $batchId = DB::table(
    //             'get_more_lead_batches'
    //         )->insertGetId([
    //             'company_id' => $companyId,
    //             'user_id' => $userId,
    //             'state' => $state,
    //             'history_start_id' => $historyStartId,
    //             'history_end_id' => null,
    //             'created_at' => $now,
    //             'updated_at' => $now,
    //         ]);

    //         /*
    //          | Assign leads to current user.
    //          |
    //          | All updates happen inside this
    //          | transaction under company lock.
    //          */

    //         $assigned = collect();

    //         foreach ($selected as $lead) {

    //             $updated = DB::table('leads')
    //                 ->where('id', $lead->id)
    //                 ->where('company_id', $companyId)
    //                 ->whereNull('assigned_to')
    //                 ->whereNull('deleted_at')
    //                 ->where('call_status', 'pending')
    //                 ->update([
    //                     'assigned_to' => $userId,
    //                     'updated_at' => $now,
    //                 ]);

    //             if ($updated !== 1) {
    //                 continue;
    //             }

    //             DB::table(
    //                 'get_more_lead_items'
    //             )->insert([
    //                 'batch_id' => $batchId,
    //                 'lead_id' => $lead->id,
    //                 'city' => mb_strtolower(
    //                     trim((string) $lead->city)
    //                 ),
    //             ]);

    //             DB::table(
    //                 'lead_assignments'
    //             )->insert([
    //                 'company_id' => $companyId,
    //                 'lead_id' => $lead->id,
    //                 'previous_user_id' => null,
    //                 'new_user_id' => $userId,
    //                 'assigned_by' => $userId,
    //                 'reason' => 'Get More Leads',
    //                 'assigned_at' => $now,
    //                 'created_at' => $now,
    //                 'updated_at' => $now,
    //             ]);

    //             $lead->assigned_to = $userId;

    //             $assigned->push($lead);
    //         }

    //         if ($assigned->isEmpty()) {

    //             DB::table('get_more_lead_batches')
    //                 ->where('id', $batchId)
    //                 ->delete();

    //             return $this->batchResponse(
    //                 'no_stock',
    //                 $previous?->id,
    //                 collect(),
    //                 'Leads were not available for assignment.'
    //             );
    //         }

    //         return $this->batchResponse(
    //             'assigned',
    //             $batchId,
    //             $assigned,
    //             'Leads assigned successfully.'
    //         );

    //     }, 3);
    // }


    // public function store(Request $request)
    // {
    //     $input = $request->validate([
    //         'state' => ['required', 'string', 'max:120'],
    //     ]);

    //     $state = trim($input['state']);

    //     if ($state === '') {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Please select a state.',
    //         ], 422);
    //     }

    //     $companyId = $this->companyId($request);
    //     $userId = (int) $request->user()->id;
    //     $stateKey = mb_strtolower($state);

    //     return DB::transaction(function () use ($companyId, $userId, $state, $stateKey) {
    //         DB::table('companies')
    //             ->where('id', $companyId)
    //             ->lockForUpdate()
    //             ->firstOrFail();

    //         $previous = DB::table('get_more_lead_batches')
    //             ->where('company_id', $companyId)
    //             ->where('user_id', $userId)
    //             ->whereRaw('LOWER(TRIM(state)) = ?', [$stateKey])
    //             ->orderByDesc('id')
    //             ->first();

    //         $pool = $this->availableLeads($companyId)
    //             ->whereRaw('LOWER(TRIM(state)) = ?', [$stateKey]);

    //         if ($previous) {
    //             $demoCities = $this->demoCities($previous);

    //             if ($demoCities->isEmpty()) {
    //                 return $this->batchResponse(
    //                     'no_demo_city',
    //                     $previous->id,
    //                     collect(),
    //                     'No demo-sent city found in previous batch.'
    //                 );
    //             }

    //             $pool->whereIn(
    //                 DB::raw('LOWER(TRIM(city))'),
    //                 $demoCities->all()
    //             );
    //         }

    //         // Lock fresh leads before releasing the user's current assignments.
    //         $selected = $this->pickLeads($pool);

    //         if ($selected->isEmpty()) {
    //             return $this->batchResponse(
    //                 'no_stock',
    //                 $previous?->id,
    //                 collect(),
    //                 'No eligible unassigned leads available.'
    //             );
    //         }

    //         $now = now();
    //         $pendingStatus = "LOWER(TRIM(COALESCE(call_status, ''))) = ?";

    //         DB::table('leads')
    //             ->where('company_id', $companyId)
    //             ->where('assigned_to', $userId)
    //             ->whereNull('deleted_at')
    //             ->whereRaw($pendingStatus, ['pending'])
    //             ->update([
    //                 'assigned_to' => null,
    //                 'updated_at' => $now,
    //             ]);

    //         $activeBatches = DB::table('get_more_lead_batches')
    //             ->where('company_id', $companyId)
    //             ->where('user_id', $userId)
    //             ->whereNull('history_end_id')
    //             ->orderBy('id')
    //             ->get();

    //         foreach ($activeBatches as $activeBatch) {
    //             $historyEndId = max(
    //                 (int) $activeBatch->history_start_id,
    //                 (int) ($this->batchHistory($activeBatch)->max('id') ?? 0)
    //             );

    //             DB::table('get_more_lead_batches')
    //                 ->where('id', $activeBatch->id)
    //                 ->whereNull('history_end_id')
    //                 ->update([
    //                     'history_end_id' => $historyEndId,
    //                     'updated_at' => $now,
    //                 ]);
    //         }

    //         $historyStartId = (int) (
    //             CallHistory::query()
    //                 ->where('user_id', $userId)
    //                 ->max('id') ?? 0
    //         );

    //         $batchId = DB::table('get_more_lead_batches')->insertGetId([
    //             'company_id' => $companyId,
    //             'user_id' => $userId,
    //             'state' => $state,
    //             'history_start_id' => $historyStartId,
    //             'history_end_id' => null,
    //             'created_at' => $now,
    //             'updated_at' => $now,
    //         ]);

    //         $updated = DB::table('leads')
    //             ->where('company_id', $companyId)
    //             ->whereIn('id', $selected->pluck('id')->all())
    //             ->whereNull('assigned_to')
    //             ->whereNull('deleted_at')
    //             ->whereRaw($pendingStatus, ['pending'])
    //             ->update([
    //                 'assigned_to' => $userId,
    //                 'updated_at' => $now,
    //             ]);

    //         if ($updated !== $selected->count()) {
    //             throw new \RuntimeException(
    //                 'Could not assign all selected leads.'
    //             );
    //         }

    //         foreach ($selected as $lead) {
    //             DB::table('get_more_lead_items')->insert([
    //                 'batch_id' => $batchId,
    //                 'lead_id' => $lead->id,
    //                 'city' => mb_strtolower(trim((string) $lead->city)),
    //             ]);

    //             DB::table('lead_assignments')->insert([
    //                 'company_id' => $companyId,
    //                 'lead_id' => $lead->id,
    //                 'previous_user_id' => null,
    //                 'new_user_id' => $userId,
    //                 'assigned_by' => $userId,
    //                 'reason' => 'Get More Leads',
    //                 'assigned_at' => $now,
    //                 'created_at' => $now,
    //                 'updated_at' => $now,
    //             ]);

    //             $lead->assigned_to = $userId;
    //         }

    //         return $this->batchResponse(
    //             'assigned',
    //             $batchId,
    //             $selected,
    //             'Leads assigned successfully.'
    //         );
    //     }, 3);
    // }


//     public function store(Request $request)
// {
//     $input = $request->validate([
//         'state' => ['nullable', 'string', 'max:120'],
//         'category' => ['nullable', 'string', 'max:120'],
//     ]);

//     // State and category both optional
//     $state = trim((string) ($input['state'] ?? ''));
//     $category = trim((string) ($input['category'] ?? ''));

//     $state = $state !== '' ? $state : null;
//     $category = $category !== '' ? $category : null;

//     $companyId = $this->companyId($request);
//     $userId = (int) $request->user()->id;

//     $stateKey = $state !== null
//         ? mb_strtolower($state)
//         : null;

//     $categoryKey = $category !== null
//         ? mb_strtolower($category)
//         : null;

//     return DB::transaction(function () use (
//         $companyId,
//         $userId,
//         $state,
//         $stateKey,
//         $category,
//         $categoryKey
//     ) {

//         // Lock company to prevent duplicate assignments
//         DB::table('companies')
//             ->where('id', $companyId)
//             ->lockForUpdate()
//             ->firstOrFail();

//         /*
//         |--------------------------------------------------------------------------
//         | Find previous batch with same filters
//         |--------------------------------------------------------------------------
//         */

//         $previousQuery = DB::table('get_more_lead_batches')
//             ->where('company_id', $companyId)
//             ->where('user_id', $userId);

//         // Match previous state filter
//         if ($stateKey !== null) {
//             $previousQuery->whereRaw(
//                 'LOWER(TRIM(state)) = ?',
//                 [$stateKey]
//             );
//         } else {
//             $previousQuery->where(function ($query) {
//                 $query->whereNull('state')
//                     ->orWhereRaw("TRIM(state) = ''");
//             });
//         }

//         // Match previous category filter
//         if ($categoryKey !== null) {
//             $previousQuery->whereRaw(
//                 'LOWER(TRIM(category)) = ?',
//                 [$categoryKey]
//             );
//         } else {
//             $previousQuery->where(function ($query) {
//                 $query->whereNull('category')
//                     ->orWhereRaw("TRIM(category) = ''");
//             });
//         }

//         $previous = $previousQuery
//             ->orderByDesc('id')
//             ->first();

//         /*
//         |--------------------------------------------------------------------------
//         | Available leads pool
//         |--------------------------------------------------------------------------
//         */

//         $pool = $this->availableLeads($companyId);

//         // Apply state filter only when provided
//         if ($stateKey !== null) {
//             $pool->whereRaw(
//                 'LOWER(TRIM(state)) = ?',
//                 [$stateKey]
//             );
//         }

//         // Apply category filter only when provided
//         if ($categoryKey !== null) {
//             $pool->whereRaw(
//                 'LOWER(TRIM(category)) = ?',
//                 [$categoryKey]
//             );
//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Previous batch demo-sent city logic
//         |--------------------------------------------------------------------------
//         */

//         if ($previous) {

//             $demoCities = $this->demoCities($previous);

//             if ($demoCities->isEmpty()) {
//                 return $this->batchResponse(
//                     'no_demo_city',
//                     $previous->id,
//                     collect(),
//                     'No demo-sent city found in previous batch.'
//                 );
//             }

//             $pool->whereIn(
//                 DB::raw('LOWER(TRIM(city))'),
//                 $demoCities->all()
//             );
//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Select fresh leads
//         |--------------------------------------------------------------------------
//         */

//         $selected = $this->pickLeads($pool);

//         if ($selected->isEmpty()) {
//             return $this->batchResponse(
//                 'no_stock',
//                 $previous?->id,
//                 collect(),
//                 'No eligible unassigned leads available.'
//             );
//         }

//         $now = now();

//         $pendingStatus =
//             "LOWER(TRIM(COALESCE(call_status, ''))) = ?";

//         /*
//         |--------------------------------------------------------------------------
//         | Release user's existing pending leads
//         |--------------------------------------------------------------------------
//         */

//         DB::table('leads')
//             ->where('company_id', $companyId)
//             ->where('assigned_to', $userId)
//             ->whereNull('deleted_at')
//             ->whereRaw($pendingStatus, ['pending'])
//             ->update([
//                 'assigned_to' => null,
//                 'updated_at' => $now,
//             ]);

//         /*
//         |--------------------------------------------------------------------------
//         | Close existing active batches
//         |--------------------------------------------------------------------------
//         */

//         $activeBatches = DB::table('get_more_lead_batches')
//             ->where('company_id', $companyId)
//             ->where('user_id', $userId)
//             ->whereNull('history_end_id')
//             ->orderBy('id')
//             ->get();

//         foreach ($activeBatches as $activeBatch) {

//             $historyEndId = max(
//                 (int) $activeBatch->history_start_id,
//                 (int) (
//                     $this->batchHistory($activeBatch)
//                         ->max('id') ?? 0
//                 )
//             );

//             DB::table('get_more_lead_batches')
//                 ->where('id', $activeBatch->id)
//                 ->whereNull('history_end_id')
//                 ->update([
//                     'history_end_id' => $historyEndId,
//                     'updated_at' => $now,
//                 ]);
//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Get current call history ID
//         |--------------------------------------------------------------------------
//         */

//         $historyStartId = (int) (
//             CallHistory::query()
//                 ->where('user_id', $userId)
//                 ->max('id') ?? 0
//         );

//         /*
//         |--------------------------------------------------------------------------
//         | Create new lead batch
//         |--------------------------------------------------------------------------
//         */

//         $batchId = DB::table('get_more_lead_batches')
//             ->insertGetId([
//                 'company_id' => $companyId,
//                 'user_id' => $userId,

//                 // Both values can be null
//                 'state' => $state,
//                 'category' => $category,

//                 'history_start_id' => $historyStartId,
//                 'history_end_id' => null,
//                 'created_at' => $now,
//                 'updated_at' => $now,
//             ]);

//         /*
//         |--------------------------------------------------------------------------
//         | Assign selected leads
//         |--------------------------------------------------------------------------
//         */

//         $updated = DB::table('leads')
//             ->where('company_id', $companyId)
//             ->whereIn(
//                 'id',
//                 $selected->pluck('id')->all()
//             )
//             ->whereNull('assigned_to')
//             ->whereNull('deleted_at')
//             ->whereRaw($pendingStatus, ['pending'])
//             ->update([
//                 'assigned_to' => $userId,
//                 'updated_at' => $now,
//             ]);

//         // Rollback if all leads could not be assigned
//         if ($updated !== $selected->count()) {
//             throw new \RuntimeException(
//                 'Could not assign all selected leads.'
//             );
//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Save batch items and assignment history
//         |--------------------------------------------------------------------------
//         */

//         foreach ($selected as $lead) {

//             DB::table('get_more_lead_items')->insert([
//                 'batch_id' => $batchId,
//                 'lead_id' => $lead->id,
//                 'city' => mb_strtolower(
//                     trim((string) $lead->city)
//                 ),
//             ]);

//             DB::table('lead_assignments')->insert([
//                 'company_id' => $companyId,
//                 'lead_id' => $lead->id,
//                 'previous_user_id' => null,
//                 'new_user_id' => $userId,
//                 'assigned_by' => $userId,
//                 'reason' => 'Get More Leads',
//                 'assigned_at' => $now,
//                 'created_at' => $now,
//                 'updated_at' => $now,
//             ]);

//             $lead->assigned_to = $userId;
//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Return success response
//         |--------------------------------------------------------------------------
//         */

//         return $this->batchResponse(
//             'assigned',
//             $batchId,
//             $selected,
//             'Leads assigned successfully.'
//         );

//     }, 3);
// }


// public function store(Request $request)
// {
//     /*
//     |--------------------------------------------------------------------------
//     | Normalize empty inputs
//     |--------------------------------------------------------------------------
//     */

//     if (
//         is_string($request->input('category')) &&
//         trim($request->input('category')) === ''
//     ) {
//         $request->merge([
//             'category' => null,
//         ]);
//     }

//     /*
//     |--------------------------------------------------------------------------
//     | Validate request
//     |--------------------------------------------------------------------------
//     */

//     $input = $request->validate([
//         'state' => ['nullable', 'string', 'max:120'],
//         'category' => ['nullable', 'integer', 'min:1'],
//     ]);

//     /*
//     |--------------------------------------------------------------------------
//     | State and category both optional
//     |--------------------------------------------------------------------------
//     */

//     $state = trim((string) ($input['state'] ?? ''));

//     $state = $state !== ''
//         ? $state
//         : null;

//     // Category parameter contains category ID
//     $categoryId = isset($input['category'])
//         ? (int) $input['category']
//         : null;

//     $companyId = $this->companyId($request);
//     $userId = (int) $request->user()->id;

//     $stateKey = $state !== null
//         ? mb_strtolower($state)
//         : null;

//     return DB::transaction(function () use (
//         $companyId,
//         $userId,
//         $state,
//         $stateKey,
//         $categoryId
//     ) {

//         /*
//         |--------------------------------------------------------------------------
//         | Lock company to prevent duplicate assignments
//         |--------------------------------------------------------------------------
//         */

//         DB::table('companies')
//             ->where('id', $companyId)
//             ->lockForUpdate()
//             ->firstOrFail();

//         /*
//         |--------------------------------------------------------------------------
//         | Find previous batch with same filters
//         |--------------------------------------------------------------------------
//         */

//         $previousQuery = DB::table('get_more_lead_batches')
//             ->where('company_id', $companyId)
//             ->where('user_id', $userId);

//         // Match previous state filter
//         if ($stateKey !== null) {

//             $previousQuery->whereRaw(
//                 'LOWER(TRIM(state)) = ?',
//                 [$stateKey]
//             );

//         } else {

//             $previousQuery->where(function ($query) {

//                 $query->whereNull('state')
//                     ->orWhereRaw("TRIM(state) = ''");

//             });
//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Match previous category ID
//         |--------------------------------------------------------------------------
//         |
//         | Existing batch table has category column.
//         | Store and compare category ID as string in that column.
//         |
//         */

//         if ($categoryId !== null) {

//             $previousQuery->where(
//                 'category',
//                 (string) $categoryId
//             );

//         } else {

//             $previousQuery->where(function ($query) {

//                 $query->whereNull('category')
//                     ->orWhereRaw("TRIM(category) = ''");

//             });
//         }

//         $previous = $previousQuery
//             ->orderByDesc('id')
//             ->first();

//         /*
//         |--------------------------------------------------------------------------
//         | Available leads pool
//         |--------------------------------------------------------------------------
//         */

//         $pool = $this->availableLeads($companyId);

//         /*
//         |--------------------------------------------------------------------------
//         | Apply state filter only when provided
//         |--------------------------------------------------------------------------
//         */

//         if ($stateKey !== null) {

//             $pool->whereRaw(
//                 'LOWER(TRIM(state)) = ?',
//                 [$stateKey]
//             );

//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Apply category ID filter only when provided
//         |--------------------------------------------------------------------------
//         |
//         | Match request category ID with leads.category_id.
//         | Do not match category name.
//         |
//         */

//         if ($categoryId !== null) {

//             $pool->where(
//                 'leads.category_id',
//                 $categoryId
//             );

//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Demo city priority (NOT REQUIRED)
//         |--------------------------------------------------------------------------
//         */

//         $selected = collect();

//         if ($previous) {

//             $demoCities = $this->demoCities($previous);

//             // Demo cities available
//             if ($demoCities->isNotEmpty()) {

//                 // Clone original filtered pool
//                 $demoPool = clone $pool;

//                 $demoPool->whereIn(
//                     DB::raw('LOWER(TRIM(city))'),
//                     $demoCities->all()
//                 );

//                 // First try assigning demo city leads
//                 $selected = $this->pickLeads($demoPool);
//             }
//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Fallback: Assign normal eligible leads
//         |--------------------------------------------------------------------------
//         |
//         | 1. Previous batch not available
//         | 2. Demo city not available
//         | 3. Demo city stock not available
//         |
//         | State and category ID filters remain applied.
//         |
//         */

//         if ($selected->isEmpty()) {

//             $selected = $this->pickLeads($pool);

//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Check stock availability
//         |--------------------------------------------------------------------------
//         */

//         if ($selected->isEmpty()) {

//             return $this->batchResponse(
//                 'no_stock',
//                 $previous?->id,
//                 collect(),
//                 'No eligible unassigned leads available.'
//             );

//         }

//         $now = now();

//         $pendingStatus =
//             "LOWER(TRIM(COALESCE(call_status, ''))) = ?";

//         /*
//         |--------------------------------------------------------------------------
//         | Release user's existing pending leads
//         |--------------------------------------------------------------------------
//         |
//         | Old pending leads will only be released when
//         | fresh leads are successfully selected.
//         |
//         */

//         DB::table('leads')
//             ->where('company_id', $companyId)
//             ->where('assigned_to', $userId)
//             ->whereNull('deleted_at')
//             ->whereRaw($pendingStatus, ['pending'])
//             ->update([
//                 'assigned_to' => null,
//                 'updated_at' => $now,
//             ]);

//         /*
//         |--------------------------------------------------------------------------
//         | Close existing active batches
//         |--------------------------------------------------------------------------
//         */

//         $activeBatches = DB::table('get_more_lead_batches')
//             ->where('company_id', $companyId)
//             ->where('user_id', $userId)
//             ->whereNull('history_end_id')
//             ->orderBy('id')
//             ->get();

//         foreach ($activeBatches as $activeBatch) {

//             $historyEndId = max(
//                 (int) $activeBatch->history_start_id,
//                 (int) (
//                     $this->batchHistory($activeBatch)
//                         ->max('id') ?? 0
//                 )
//             );

//             DB::table('get_more_lead_batches')
//                 ->where('id', $activeBatch->id)
//                 ->whereNull('history_end_id')
//                 ->update([
//                     'history_end_id' => $historyEndId,
//                     'updated_at' => $now,
//                 ]);
//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Get current call history ID
//         |--------------------------------------------------------------------------
//         */

//         $historyStartId = (int) (
//             CallHistory::query()
//                 ->where('user_id', $userId)
//                 ->max('id') ?? 0
//         );

//         /*
//         |--------------------------------------------------------------------------
//         | Create new lead batch
//         |--------------------------------------------------------------------------
//         */

//         $batchId = DB::table('get_more_lead_batches')
//             ->insertGetId([
//                 'company_id' => $companyId,
//                 'user_id' => $userId,

//                 // Optional state
//                 'state' => $state,

//                 // Store category ID in existing category column
//                 'category' => $categoryId !== null
//                     ? (string) $categoryId
//                     : null,

//                 'history_start_id' => $historyStartId,
//                 'history_end_id' => null,
//                 'created_at' => $now,
//                 'updated_at' => $now,
//             ]);

//         /*
//         |--------------------------------------------------------------------------
//         | Prepare assignment query
//         |--------------------------------------------------------------------------
//         */

//         $assignQuery = DB::table('leads')
//             ->where('company_id', $companyId)
//             ->whereIn(
//                 'id',
//                 $selected->pluck('id')->all()
//             )
//             ->whereNull('assigned_to')
//             ->whereNull('deleted_at')
//             ->whereRaw($pendingStatus, ['pending']);

//         /*
//         |--------------------------------------------------------------------------
//         | Recheck state before final assignment
//         |--------------------------------------------------------------------------
//         */

//         if ($stateKey !== null) {

//             $assignQuery->whereRaw(
//                 'LOWER(TRIM(state)) = ?',
//                 [$stateKey]
//             );

//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Recheck category ID before final assignment
//         |--------------------------------------------------------------------------
//         */

//         if ($categoryId !== null) {

//             $assignQuery->where(
//                 'category_id',
//                 $categoryId
//             );

//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Assign selected leads
//         |--------------------------------------------------------------------------
//         */

//         $updated = $assignQuery->update([
//             'assigned_to' => $userId,
//             'updated_at' => $now,
//         ]);

//         /*
//         |--------------------------------------------------------------------------
//         | Rollback if all selected leads could not be assigned
//         |--------------------------------------------------------------------------
//         */

//         if ($updated !== $selected->count()) {

//             throw new \RuntimeException(
//                 'Could not assign all selected leads.'
//             );

//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Save batch items and assignment history
//         |--------------------------------------------------------------------------
//         */

//         foreach ($selected as $lead) {

//             DB::table('get_more_lead_items')->insert([
//                 'batch_id' => $batchId,
//                 'lead_id' => $lead->id,
//                 'city' => mb_strtolower(
//                     trim((string) $lead->city)
//                 ),
//             ]);

//             DB::table('lead_assignments')->insert([
//                 'company_id' => $companyId,
//                 'lead_id' => $lead->id,
//                 'previous_user_id' => null,
//                 'new_user_id' => $userId,
//                 'assigned_by' => $userId,
//                 'reason' => 'Get More Leads',
//                 'assigned_at' => $now,
//                 'created_at' => $now,
//                 'updated_at' => $now,
//             ]);

//             $lead->assigned_to = $userId;
//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Return success response
//         |--------------------------------------------------------------------------
//         */

//         return $this->batchResponse(
//             'assigned',
//             $batchId,
//             $selected,
//             'Leads assigned successfully.'
//         );

//     }, 3);
// }


public function store(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | Normalize category input
    |--------------------------------------------------------------------------
    */

    $requestCategory = $request->input('category');

    if (
        $requestCategory !== null &&
        !is_string($requestCategory) &&
        !is_int($requestCategory)
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Category must be a valid ID or name.',
        ], 422);
    }

    $requestCategory = trim(
        (string) ($requestCategory ?? '')
    );

    $request->merge([
        'category' => $requestCategory !== ''
            ? $requestCategory
            : null,
    ]);

    /*
    |--------------------------------------------------------------------------
    | Validate request
    |--------------------------------------------------------------------------
    */

    $input = $request->validate([
        'state' => ['nullable', 'string', 'max:120'],
        'category' => ['nullable', 'string', 'max:120'],
    ]);

    /*
    |--------------------------------------------------------------------------
    | Normalize state and category
    |--------------------------------------------------------------------------
    */

    $state = trim((string) ($input['state'] ?? ''));

    $state = $state !== ''
        ? $state
        : null;

    $category = trim((string) ($input['category'] ?? ''));

    $category = $category !== ''
        ? $category
        : null;

    $companyId = $this->companyId($request);
    $userId = (int) $request->user()->id;

    $stateKey = $state !== null
        ? mb_strtolower($state)
        : null;

    /*
    |--------------------------------------------------------------------------
    | Detect category ID or category name
    |--------------------------------------------------------------------------
    */

    $categoryId = null;
    $categoryName = null;

    if ($category !== null) {

        if (ctype_digit($category)) {

            $categoryId = (int) $category;

            if (
                $categoryId < 1 ||
                (string) $categoryId !== ltrim($category, '0')
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid category ID.',
                ], 422);
            }

        } else {

            $categoryName = mb_strtolower(
                trim($category)
            );

        }
    }

    /*
    |--------------------------------------------------------------------------
    | Start database transaction
    |--------------------------------------------------------------------------
    */

    return DB::transaction(function () use (
        $companyId,
        $userId,
        $state,
        $stateKey,
        $categoryId,
        $categoryName
    ) {

        /*
        |--------------------------------------------------------------------------
        | Lock company
        |--------------------------------------------------------------------------
        */

        DB::table('companies')
            ->where('id', $companyId)
            ->lockForUpdate()
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Resolve category name into category IDs
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | category = Expire Data
        |
        | Find associated category IDs from existing leads.
        |
        */

        $matchedCategoryIds = collect();

        if ($categoryId !== null) {

            $matchedCategoryIds = collect([
                $categoryId
            ]);

        } elseif ($categoryName !== null) {

            $matchedCategoryIds = DB::table('leads')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->whereNotNull('category_id')
                ->where('category_id', '>', 0)
                ->whereRaw(
                    "LOWER(TRIM(COALESCE(category, ''))) = ?",
                    [$categoryName]
                )
                ->distinct()
                ->pluck('category_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | Prepare category key for batch history
        |--------------------------------------------------------------------------
        */

        $categoryKey = $categoryId !== null
            ? (string) $categoryId
            : $categoryName;

        /*
        |--------------------------------------------------------------------------
        | Common category filter
        |--------------------------------------------------------------------------
        |
        | If category ID:
        | Match directly with category_id.
        |
        | If category name:
        | Match category name OR associated category IDs.
        |
        */

        $applyCategoryFilter = function ($query) use (
            $categoryId,
            $categoryName,
            $matchedCategoryIds
        ) {

            if ($categoryId !== null) {

                $query->where(
                    'leads.category_id',
                    $categoryId
                );

            } elseif ($categoryName !== null) {

                $query->where(function ($subQuery) use (
                    $categoryName,
                    $matchedCategoryIds
                ) {

                    // Match category name
                    $subQuery->whereRaw(
                        "LOWER(TRIM(COALESCE(leads.category, ''))) = ?",
                        [$categoryName]
                    );

                    // Also match associated category IDs
                    if ($matchedCategoryIds->isNotEmpty()) {

                        $subQuery->orWhereIn(
                            'leads.category_id',
                            $matchedCategoryIds->all()
                        );

                    }
                });
            }

            return $query;
        };

        /*
        |--------------------------------------------------------------------------
        | Find previous batch
        |--------------------------------------------------------------------------
        */

        $previousQuery = DB::table('get_more_lead_batches')
            ->where('company_id', $companyId)
            ->where('user_id', $userId);

        /*
        |--------------------------------------------------------------------------
        | Previous batch state filter
        |--------------------------------------------------------------------------
        */

        if ($stateKey !== null) {

            $previousQuery->whereRaw(
                'LOWER(TRIM(state)) = ?',
                [$stateKey]
            );

        } else {

            $previousQuery->where(function ($query) {

                $query->whereNull('state')
                    ->orWhereRaw("TRIM(state) = ''");

            });
        }

        /*
        |--------------------------------------------------------------------------
        | Previous batch category filter
        |--------------------------------------------------------------------------
        */

        if ($categoryKey !== null) {

            $batchCategoryKeys = collect([
                $categoryKey
            ]);

            foreach ($matchedCategoryIds as $matchedId) {
                $batchCategoryKeys->push(
                    (string) $matchedId
                );
            }

            $previousQuery->whereIn(
                DB::raw('LOWER(TRIM(category))'),
                $batchCategoryKeys
                    ->unique()
                    ->values()
                    ->all()
            );

        } else {

            $previousQuery->where(function ($query) {

                $query->whereNull('category')
                    ->orWhereRaw("TRIM(category) = ''");

            });
        }

        $previous = $previousQuery
            ->orderByDesc('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Get available leads pool
        |--------------------------------------------------------------------------
        */

        $pool = $this->availableLeads($companyId);

        /*
        |--------------------------------------------------------------------------
        | Apply state filter
        |--------------------------------------------------------------------------
        */

        if ($stateKey !== null) {

            $pool->whereRaw(
                'LOWER(TRIM(leads.state)) = ?',
                [$stateKey]
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Apply category name or ID filter
        |--------------------------------------------------------------------------
        */

        $applyCategoryFilter($pool);

        /*
        |--------------------------------------------------------------------------
        | Demo city priority
        |--------------------------------------------------------------------------
        */

        $selected = collect();

        if ($previous) {

            $demoCities = $this->demoCities($previous);

            if ($demoCities->isNotEmpty()) {

                $demoPool = clone $pool;

                $demoPool->whereIn(
                    DB::raw('LOWER(TRIM(leads.city))'),
                    $demoCities->all()
                );

                $selected = $this->pickLeads($demoPool);

            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback to normal eligible leads
        |--------------------------------------------------------------------------
        |
        | Demo city is not mandatory.
        |
        */

        if ($selected->isEmpty()) {

            $selected = $this->pickLeads(
                clone $pool
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Check if stock available
        |--------------------------------------------------------------------------
        */

        if ($selected->isEmpty()) {

            return $this->batchResponse(
                'no_stock',
                $previous?->id,
                collect(),
                'No eligible unassigned leads available.'
            );

        }

        $now = now();

        $pendingStatus =
            "LOWER(TRIM(COALESCE(call_status, ''))) = ?";

        /*
        |--------------------------------------------------------------------------
        | Release user's old pending leads
        |--------------------------------------------------------------------------
        |
        | Only release old leads when fresh leads are found.
        |
        */

        DB::table('leads')
            ->where('company_id', $companyId)
            ->where('assigned_to', $userId)
            ->whereNull('deleted_at')
            ->whereRaw($pendingStatus, ['pending'])
            ->update([
                'assigned_to' => null,
                'updated_at' => $now,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Close previous active batches
        |--------------------------------------------------------------------------
        */

        $activeBatches = DB::table('get_more_lead_batches')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->whereNull('history_end_id')
            ->orderBy('id')
            ->get();

        foreach ($activeBatches as $activeBatch) {

            $historyEndId = max(
                (int) $activeBatch->history_start_id,
                (int) (
                    $this->batchHistory($activeBatch)
                        ->max('id') ?? 0
                )
            );

            DB::table('get_more_lead_batches')
                ->where('id', $activeBatch->id)
                ->whereNull('history_end_id')
                ->update([
                    'history_end_id' => $historyEndId,
                    'updated_at' => $now,
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Get current call history ID
        |--------------------------------------------------------------------------
        */

        $historyStartId = (int) (
            CallHistory::query()
                ->where('user_id', $userId)
                ->max('id') ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Create new lead batch
        |--------------------------------------------------------------------------
        */

        $batchId = DB::table('get_more_lead_batches')
            ->insertGetId([
                'company_id' => $companyId,
                'user_id' => $userId,
                'state' => $state,
                'category' => $categoryKey,
                'history_start_id' => $historyStartId,
                'history_end_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Prepare final assignment query
        |--------------------------------------------------------------------------
        */

        $assignQuery = DB::table('leads')
            ->where('company_id', $companyId)
            ->whereIn(
                'id',
                $selected->pluck('id')->all()
            )
            ->whereNull('assigned_to')
            ->whereNull('deleted_at')
            ->whereRaw($pendingStatus, ['pending']);

        /*
        |--------------------------------------------------------------------------
        | Recheck state before assignment
        |--------------------------------------------------------------------------
        */

        if ($stateKey !== null) {

            $assignQuery->whereRaw(
                'LOWER(TRIM(leads.state)) = ?',
                [$stateKey]
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Recheck category before assignment
        |--------------------------------------------------------------------------
        */

        $applyCategoryFilter($assignQuery);

        /*
        |--------------------------------------------------------------------------
        | Assign leads to current user
        |--------------------------------------------------------------------------
        */

        $updated = $assignQuery->update([
            'assigned_to' => $userId,
            'updated_at' => $now,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Rollback if assignment is incomplete
        |--------------------------------------------------------------------------
        */

        if ($updated !== $selected->count()) {

            throw new \RuntimeException(
                'Could not assign all selected leads.'
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Save batch items and assignment history
        |--------------------------------------------------------------------------
        */

        foreach ($selected as $lead) {

            DB::table('get_more_lead_items')->insert([
                'batch_id' => $batchId,
                'lead_id' => $lead->id,
                'city' => mb_strtolower(
                    trim((string) $lead->city)
                ),
            ]);

            DB::table('lead_assignments')->insert([
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'previous_user_id' => null,
                'new_user_id' => $userId,
                'assigned_by' => $userId,
                'reason' => 'Get More Leads',
                'assigned_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $lead->assigned_to = $userId;
        }

        /*
        |--------------------------------------------------------------------------
        | Return successful response
        |--------------------------------------------------------------------------
        */

        return $this->batchResponse(
            'assigned',
            $batchId,
            $selected,
            'Leads assigned successfully.'
        );

    }, 3);
}
    /*
    |--------------------------------------------------------------------------
    | SELECT LEADS FROM DIFFERENT CITIES
    |--------------------------------------------------------------------------
    |
    | Round-robin:
    | City A -> 1 lead
    | City B -> 1 lead
    | City C -> 1 lead
    |
    | Then next round until 15 leads.
    |--------------------------------------------------------------------------
    */

    private function pickLeads($pool): Collection
    {
        $cities = (clone $pool)
            ->selectRaw(
                'LOWER(TRIM(city)) AS city_key'
            )
            ->selectRaw(
                'MIN(id) AS first_id'
            )
            ->groupByRaw('LOWER(TRIM(city))')
            ->orderBy('first_id')
            ->limit(self::BATCH_SIZE)
            ->pluck('city_key');

        $selected = collect();

        while (
            $cities->isNotEmpty() &&
            $selected->count() < self::BATCH_SIZE
        ) {

            $nextCities = collect();

            foreach ($cities as $city) {

                $lead = (clone $pool)
                    ->whereRaw(
                        'LOWER(TRIM(city)) = ?',
                        [$city]
                    )
                    ->whereNotIn(
                        'id',
                        $selected->pluck('id')->all()
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if (!$lead) {
                    continue;
                }

                $selected->push($lead);
                $nextCities->push($city);

                if (
                    $selected->count() >=
                    self::BATCH_SIZE
                ) {
                    break;
                }
            }

            $cities = $nextCities;
        }

        return $selected;
    }

    /*
    |--------------------------------------------------------------------------
    | BATCH HISTORY
    |--------------------------------------------------------------------------
    */

    private function batchHistory(object $batch)
    {
        $query = CallHistory::query()
            ->where('user_id', $batch->user_id)
            ->where(
                'id',
                '>',
                $batch->history_start_id
            )
            ->whereIn('lead_id', function ($query) use (
                $batch
            ) {

                $query->select('lead_id')
                    ->from('get_more_lead_items')
                    ->where('batch_id', $batch->id);
            });

        if ($batch->history_end_id !== null) {

            $query->where(
                'id',
                '<=',
                $batch->history_end_id
            );
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | PENDING LEADS FROM EXISTING BATCH
    |--------------------------------------------------------------------------
    */

    private function pendingBatchLeads(
        object $batch,
        int $companyId,
        int $userId
    ): Collection {

        $leadIds = DB::table(
            'get_more_lead_items'
        )
            ->where('batch_id', $batch->id)
            ->pluck('lead_id');

        if ($leadIds->isEmpty()) {
            return collect();
        }

        /*
         | A lead is considered processed when
         | there is a non-pending call history.
         */

        $completedIds = $this->batchHistory($batch)
            ->whereRaw(
                "LOWER(TRIM(COALESCE(call_status, ''))) <> ?",
                ['pending']
            )
            ->whereNotNull('call_status')
            ->distinct()
            ->pluck('lead_id');

        $pendingIds = $leadIds
            ->diff($completedIds)
            ->values();

        if ($pendingIds->isEmpty()) {
            return collect();
        }

        return Lead::query()
            ->where('company_id', $companyId)
            ->where('assigned_to', $userId)
            ->whereIn('id', $pendingIds)
            ->whereRaw(
                "LOWER(TRIM(COALESCE(call_status, ''))) = ?",
                ['pending']
            )
            ->where(function ($query) {
                $query->where('do_not_call', false)
                    ->orWhereNull('do_not_call');
            })
            ->orderBy('id')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | DEMO SENT CITIES
    |--------------------------------------------------------------------------
    */

    private function demoCities(object $batch): Collection
    {
        $demoLeadIds = $this->batchHistory($batch)
            ->whereRaw(
                "LOWER(TRIM(COALESCE(call_status, ''))) IN (?, ?)",
                ['demo_sent', 'demo send']
            )
            ->distinct()
            ->pluck('lead_id');

        if ($demoLeadIds->isEmpty()) {
            return collect();
        }

        return DB::table('get_more_lead_items')
            ->where('batch_id', $batch->id)
            ->whereIn('lead_id', $demoLeadIds)
            ->whereNotNull('city')
            ->selectRaw('LOWER(TRIM(city)) AS city_key')
            ->distinct()
            ->pluck('city_key')
            ->filter(fn ($city) => $city !== '')
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | GET CALL HISTORY
    | GET /api/get-more-leads/call-history
    |--------------------------------------------------------------------------
    */

    // public function history(Request $request)
    // {
    //     $filters = $request->validate([
    //         'batch_id' => [
    //             'nullable',
    //             'integer',
    //             'min:1',
    //         ],
    //         'lead_id' => [
    //             'nullable',
    //             'integer',
    //             'min:1',
    //         ],
    //         'call_status' => [
    //             'nullable',
    //             'string',
    //             'max:255',
    //         ],
    //         'per_page' => [
    //             'nullable',
    //             'integer',
    //             'between:1,100',
    //         ],
    //     ]);

    //     $companyId = $this->companyId($request);
    //     $userId = (int) $request->user()->id;

    //     if (!empty($filters['batch_id'])) {

    //         $batch = DB::table(
    //             'get_more_lead_batches'
    //         )
    //             ->where(
    //                 'company_id',
    //                 $companyId
    //             )
    //             ->where(
    //                 'user_id',
    //                 $userId
    //             )
    //             ->where(
    //                 'id',
    //                 $filters['batch_id']
    //             )
    //             ->first();

    //         if (!$batch) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Batch not found.',
    //             ], 404);
    //         }

    //         $query = $this->batchHistory($batch);

    //     } else {

    //         $query = CallHistory::query()
    //             ->where('user_id', $userId);
    //     }

    //     /*
    //      | Only leads belonging to user's company.
    //      */

    //     $query->whereHas(
    //         'lead',
    //         fn ($q) => $q->withTrashed()
    //             ->where('company_id', $companyId)
    //     );

    //     $query->with([
    //         'lead' => fn ($q) => $q->withTrashed()
    //             ->select(
    //                 'id',
    //                 'name',
    //                 'mobile',
    //                 'state',
    //                 'city'
    //             ),
    //     ]);

    //     if (!empty($filters['lead_id'])) {

    //         $query->where(
    //             'lead_id',
    //             $filters['lead_id']
    //         );
    //     }

    //     if (!empty($filters['call_status'])) {

    //         $query->where(
    //             'call_status',
    //             $filters['call_status']
    //         );
    //     }

    //     $history = $query
    //         ->orderByDesc('call_at')
    //         ->orderByDesc('id')
    //         ->paginate(
    //             $filters['per_page'] ?? 25
    //         )
    //         ->withQueryString();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Call history fetched.',
    //         'data' => $history,
    //     ]);
    // }


    public function history(Request $request)
    {
        $filters = $request->validate([
            'batch_id' => 'nullable|integer|min:1',
            'lead_id' => 'nullable|integer|min:1',
            'call_status' => 'nullable|string|max:255',
            'direction' => 'nullable|in:incoming,outgoing',
            'call_type' => 'nullable|in:incoming,outgoing,missed,rejected',
            'mobile' => 'nullable|string|max:30',
            'per_page' => 'nullable|integer|between:1,100',
        ]);

        $companyId = $this->companyId($request);
        $userId = (int) $request->user()->id;

        // पुराना batch filter बरकरार
        if (!empty($filters['batch_id'])) {

            $batch = DB::table('get_more_lead_batches')
                ->where('company_id', $companyId)
                ->where('user_id', $userId)
                ->where('id', $filters['batch_id'])
                ->first();

            if (!$batch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch not found.',
                ], 404);
            }

            $query = $this->batchHistory($batch);

        } else {

            // Employee की सभी call histories
            $query = CallHistory::query()
                ->where('user_id', $userId);
        }

        // Lead नहीं है, तब भी history दिखनी चाहिए
        $query->where(function ($q) use ($companyId) {

            $q->whereNull('lead_id')

            ->orWhereHas('lead', function ($leadQuery) use ($companyId) {

                $leadQuery->withTrashed()
                    ->where('company_id', $companyId);

            });
        });

        // Lead की जानकारी
        $query->with([
            'lead' => fn ($q) => $q
                ->withTrashed()
                ->select(
                    'id',
                    'name',
                    'mobile',
                    'state',
                    'city'
                ),
        ]);

        // Existing + new filters
        foreach ([
            'lead_id',
            'call_status',
            'direction',
            'call_type'
        ] as $field) {

            if (
                isset($filters[$field]) &&
                $filters[$field] !== ''
            ) {
                $query->where($field, $filters[$field]);
            }
        }

        // Mobile filter
        if (!empty($filters['mobile'])) {

            $digits = preg_replace(
                '/\D+/',
                '',
                $filters['mobile']
            );

            if (
                strlen($digits) === 12 &&
                str_starts_with($digits, '91')
            ) {
                $digits = substr($digits, 2);
            } elseif (
                strlen($digits) === 11 &&
                str_starts_with($digits, '0')
            ) {
                $digits = substr($digits, 1);
            }

            if (!preg_match('/^[6-9][0-9]{9}$/', $digits)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid mobile number.',
                ], 422);
            }

            $query->where('mobile', $digits);
        }

        $history = $query
            ->orderByDesc('call_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();

        // Response में lead name और has_lead जोड़ना
        $history->getCollection()->transform(
            function (CallHistory $item) {

                $data = $item->toArray();

                $data['has_lead'] =
                    $item->lead_id !== null;

                $data['lead_name'] =
                    $item->lead?->name;

                return $data;
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'Call history fetched.',
            'data' => $history,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | COMMON API RESPONSE
    |--------------------------------------------------------------------------
    */

    private function batchResponse(
        string $status,
        ?int $batchId,
        Collection $leads,
        string $message
    ) {
        return response()->json([
            'success' => true,
            'message' => $message,
            'batch_status' => $status,
            'batch_id' => $batchId,
            'count' => $leads->count(),
            'data' => $leads->values(),
        ]);
    }
}
