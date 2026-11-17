<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallHistory;
use App\Models\Lead;
use App\Models\LeadAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetMoreLeadsController extends Controller
{
    private const BATCH_SIZE = 15;

    /**
     * GET /api/get-more-leads/states
     */
    public function states(Request $request)
    {
        $companyId = $this->companyId($request);
        $userId = (int) $request->user()->id;

        $availableStates = $this->availableLeads($companyId)
            ->selectRaw('DISTINCT TRIM(state) AS state_name')
            ->pluck('state_name');

        // अगर सारी unassigned leads खत्म हैं, तब भी user
        // अपना पहले से शुरू किया हुआ batch खोल सकता है।
        $startedStates = DB::table('get_more_lead_batches')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->distinct()
            ->pluck('state');

        $states = $availableStates
            ->merge($startedStates)
            ->unique(fn ($state) => mb_strtolower(trim($state)))
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $states,
        ]);
    }

    /**
     * POST /api/get-more-leads
     * Body: {"state":"Bihar"}
     */
    public function store(Request $request)
    {
        $input = $request->validate([
            'state' => ['required', 'string', 'max:120'],
        ]);

        $state = trim($input['state']);
        $companyId = $this->companyId($request);
        $userId = (int) $request->user()->id;

        return DB::transaction(function () use ($companyId, $userId, $state) {
            // इसी API पर एक साथ आए requests को company-wise serialize करें।
            DB::table('companies')
                ->where('id', $companyId)
                ->lockForUpdate()
                ->firstOrFail();

            $previous = DB::table('get_more_lead_batches')
                ->where('company_id', $companyId)
                ->where('user_id', $userId)
                ->whereRaw('LOWER(state) = LOWER(?)', [$state])
                ->latest('id')
                ->first();

            $pool = $this->availableLeads($companyId)
                ->whereRaw('LOWER(TRIM(state)) = LOWER(?)', [$state]);

            if ($previous) {
                $items = DB::table('get_more_lead_items')
                    ->where('batch_id', $previous->id)
                    ->orderBy('id')
                    ->get(['lead_id', 'city']);

                $history = $this->batchHistory($previous)
                    ->where('call_status', '<>', 'pending');

                if ($previous->history_end_id === null) {
                    $calledIds = (clone $history)
                        ->distinct()
                        ->pluck('lead_id');

                    $pendingIds = $items->pluck('lead_id')->diff($calledIds);

                    $remaining = Lead::query()
                        ->where('company_id', $companyId)
                        ->where('assigned_to', $userId)
                        ->whereIn('id', $pendingIds)
                        ->where(function ($q) {
                            $q->where('do_not_call', false)
                                ->orWhereNull('do_not_call');
                        })
                        ->orderBy('id')
                        ->get();

                    if ($remaining->isNotEmpty()) {
                        return $this->batchResponse(
                            'in_progress',
                            $previous->id,
                            $remaining
                        );
                    }

                    // Deleted, reassigned या do-not-call leads को
                    // mobile में वापस नहीं भेजेंगे।
                    $endId = max(
                        (int) $previous->history_start_id,
                        (int) ((clone $history)->max('id') ?? 0)
                    );

                    DB::table('get_more_lead_batches')
                        ->where('id', $previous->id)
                        ->update([
                            'history_end_id' => $endId,
                            'updated_at' => now(),
                        ]);

                    $history->where('id', '<=', $endId);
                }

                $demoLeadIds = (clone $history)
                    ->where('call_status', 'demo_sent')
                    ->distinct()
                    ->pluck('lead_id');

                $demoCities = $items
                    ->whereIn('lead_id', $demoLeadIds)
                    ->pluck('city')
                    ->unique()
                    ->values();

                if ($demoCities->isEmpty()) {
                    return $this->batchResponse(
                        'no_demo_city',
                        $previous->id,
                        collect()
                    );
                }

                $pool->whereIn(DB::raw('LOWER(TRIM(city))'), $demoCities);
            }

            $selected = $this->pickFromDifferentCities($pool);

            if ($selected->isEmpty()) {
                return $this->batchResponse(
                    'no_stock',
                    $previous?->id,
                    collect()
                );
            }

            // इन leads की पुरानी history नए batch में count नहीं होगी।
            $historyStartId = (int) (
                CallHistory::query()
                    ->where('user_id', $userId)
                    ->whereIn('lead_id', $selected->pluck('id'))
                    ->max('id') ?? 0
            );

            $now = now();

            $batchId = DB::table('get_more_lead_batches')->insertGetId([
                'company_id' => $companyId,
                'user_id' => $userId,
                'state' => $state,
                'history_start_id' => $historyStartId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($selected as $lead) {
                $lead->update([
                    'assigned_to' => $userId,
                ]);

                DB::table('get_more_lead_items')->insert([
                    'batch_id' => $batchId,
                    'lead_id' => $lead->id,
                    'city' => mb_strtolower(trim($lead->city)),
                ]);

                LeadAssignment::create([
                    'company_id' => $companyId,
                    'lead_id' => $lead->id,
                    'previous_user_id' => null,
                    'new_user_id' => $userId,
                    'assigned_by' => $userId,
                    'reason' => 'Get More Leads',
                    'assigned_at' => $now,
                ]);
            }

            return $this->batchResponse('assigned', $batchId, $selected);
        }, 3);
    }

    /**
     * GET /api/get-more-leads/call-history
     */
    public function history(Request $request)
    {
        $filters = $request->validate([
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'lead_id' => ['nullable', 'integer', 'min:1'],
            'call_status' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $companyId = $this->companyId($request);
        $userId = (int) $request->user()->id;

        if (isset($filters['batch_id'])) {
            $batch = DB::table('get_more_lead_batches')
                ->where('company_id', $companyId)
                ->where('user_id', $userId)
                ->where('id', $filters['batch_id'])
                ->firstOrFail();

            $query = $this->batchHistory($batch);
        } else {
            $query = CallHistory::query()->where('user_id', $userId);
        }

        $query->whereHas('lead', fn ($q) => $q->withTrashed()
            ->where('company_id', $companyId));

        $query->with([
            'lead' => fn ($q) => $q->withTrashed()
                ->select('id', 'name', 'mobile', 'city', 'state'),
        ]);

        if (isset($filters['lead_id'])) {
            $query->where('lead_id', $filters['lead_id']);
        }

        if (isset($filters['call_status'])) {
            $query->where('call_status', $filters['call_status']);
        }

        $history = $query
            ->orderByDesc('call_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    private function availableLeads(int $companyId)
    {
        return Lead::query()
            ->where('company_id', $companyId)
            ->whereNull('assigned_to')
            ->where('call_status', 'pending')
            ->where(function ($q) {
                $q->where('do_not_call', false)
                    ->orWhereNull('do_not_call');
            })
            ->whereRaw("TRIM(COALESCE(mobile, '')) <> ''")
            ->whereRaw("TRIM(COALESCE(state, '')) <> ''")
            ->whereRaw("TRIM(COALESCE(city, '')) <> ''");
    }

    private function pickFromDifferentCities($pool): Collection
    {
        $cities = (clone $pool)
            ->selectRaw('LOWER(TRIM(city)) AS city_key, MIN(id) AS first_id')
            ->groupByRaw('LOWER(TRIM(city))')
            ->orderBy('first_id')
            ->limit(self::BATCH_SIZE)
            ->pluck('city_key');

        $selected = collect();

        while ($cities->isNotEmpty() && $selected->count() < self::BATCH_SIZE) {
            $nextRound = collect();

            foreach ($cities as $city) {
                $lead = (clone $pool)
                    ->whereRaw('LOWER(TRIM(city)) = ?', [$city])
                    ->whereNotIn('id', $selected->pluck('id'))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if (!$lead) {
                    continue;
                }

                $selected->push($lead);
                $nextRound->push($city);

                if ($selected->count() === self::BATCH_SIZE) {
                    break;
                }
            }

            $cities = $nextRound;
        }

        return $selected;
    }

    private function batchHistory(object $batch)
    {
        $query = CallHistory::query()
            ->where('user_id', $batch->user_id)
            ->where('id', '>', $batch->history_start_id)
            ->whereIn('lead_id', function ($q) use ($batch) {
                $q->select('lead_id')
                    ->from('get_more_lead_items')
                    ->where('batch_id', $batch->id);
            });

        if ($batch->history_end_id !== null) {
            $query->where('id', '<=', $batch->history_end_id);
        }

        return $query;
    }

    private function companyId(Request $request): int
    {
        $companyId = (int) $request->user()->company_id;

        abort_if($companyId < 1, 403, 'No company assigned to this user.');

        return $companyId;
    }

    private function batchResponse(string $status, ?int $batchId, Collection $leads)
    {
        return response()->json([
            'success' => true,
            'batch_status' => $status,
            'batch_id' => $batchId,
            'count' => $leads->count(),
            'data' => $leads->values(),
        ]);
    }
}