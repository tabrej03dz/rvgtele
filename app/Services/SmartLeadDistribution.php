<?php

namespace App\Services;

use App\Models\CallHistory;
use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\User;
use App\Support\DistributionValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SmartLeadDistribution
{
    public const SIZE = 20;

    public const OUTCOMES = [
        'not_answer',
        'not_interested',
        'interested',
        'demo_sent',
        'payment_done',
        'wrong_number',
    ];

    /**
     * All changes made by this service use the same company lock.
     */
    private function lockCompany(int $companyId): void
    {
        DB::table('companies')
            ->where('id', $companyId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Register imported leads and historical callers.
     *
     * Run once during installation, then after every upload.
     */
    public function sync(int $companyId): array
    {
        $counts = [
            'indexed' => 0,
            'invalid' => 0,
            'duplicates' => 0,
            'conflicts' => 0,
        ];

        // Normalize all unindexed rows first so duplicate formats join together.
        Lead::withTrashed()
            ->where('company_id', $companyId)
            ->where('distribution_indexed', false)
            ->orderBy('id')
            ->chunkById(500, function ($leads) use (&$counts) {
                foreach ($leads as $lead) {
                    $phone = DistributionValue::phone($lead->mobile);

                    DB::table('leads')
                        ->where('id', $lead->id)
                        ->update([
                            'distribution_phone' => $phone,
                            'distribution_indexed' => true,
                        ]);

                    $counts['indexed']++;

                    if (!$phone) {
                        $counts['invalid']++;
                    }
                }
            });

        // Existing registry records are not reset on reruns.
        DB::table('leads')
            ->where('company_id', $companyId)
            ->whereNotNull('distribution_phone')
            ->whereNotExists(function ($query) use ($companyId) {
                $query->selectRaw('1')
                    ->from('distribution_numbers as numbers')
                    ->where('numbers.company_id', $companyId)
                    ->whereColumn('numbers.phone', 'leads.distribution_phone');
            })
            ->select('distribution_phone')
            ->distinct()
            ->orderBy('distribution_phone')
            ->chunk(500, function ($phones) use ($companyId, &$counts) {
                // This query's result shrinks as rows are registered.
                // Registration itself is performed below after collecting keys.
            });

        // Cursor avoids offset pagination over a shrinking candidate set.
        $phones = DB::table('leads')
            ->where('company_id', $companyId)
            ->whereNotNull('distribution_phone')
            ->select('distribution_phone')
            ->distinct()
            ->orderBy('distribution_phone')
            ->pluck('distribution_phone');

        foreach ($phones as $phone) {
            DB::transaction(function () use ($companyId, $phone, &$counts) {
                $this->lockCompany($companyId);

                $number = DB::table('distribution_numbers')
                    ->where('company_id', $companyId)
                    ->where('phone', $phone)
                    ->first();

                $copies = Lead::withTrashed()
                    ->where('company_id', $companyId)
                    ->where('distribution_phone', $phone)
                    ->orderBy('id')
                    ->get();

                if ($copies->isEmpty()) {
                    return;
                }

                $history = CallHistory::query()
                    ->whereIn('lead_id', $copies->pluck('id'));

                if (!$number) {
                    $live = $copies->whereNull('deleted_at');
                    $canonical = $live->first() ?? $copies->first();

                    $owners = $live->pluck('assigned_to')
                        ->filter()
                        ->unique();

                    $latest = (clone $history)
                        ->orderByDesc('call_at')
                        ->orderByDesc('id')
                        ->first();

                    $status = DistributionValue::text(
                        $latest?->call_status ?? $canonical->call_status
                    );

                    $status = str_replace(' ', '_', $status);

                    $status = match ($status) {
                        'no_answer', 'not_answered', 'no_answered' => 'not_answer',
                        'demo_send' => 'demo_sent',
                        default => $status,
                    };

                    $owner = $owners->first();
                    $state = 'available';
                    $availableAt = null;

                    if (in_array($status, ['not_answer', 'not_interested'], true)) {
                        $state = 'cooldown';
                        $owner = null;

                        $availableAt = \Carbon\Carbon::parse(
                            $latest?->call_at ?? $canonical->updated_at
                        )->addDays(7);
                    } elseif (in_array($status, ['demo_sent', 'interested'], true)) {
                        $state = 'retained';
                        $owner = $owner ?? $latest?->user_id;
                    } elseif ($status === 'payment_done') {
                        $state = 'converted';
                    } elseif ($status === 'wrong_number') {
                        $state = 'blocked';
                    } elseif ($owner) {
                        // Previously assigned leads stay with their current owner.
                        $state = 'retained';
                    }

                    if (
                        $live->isEmpty()
                        || $live->contains(fn ($lead) => (bool) $lead->do_not_call)
                    ) {
                        $state = 'blocked';
                    }

                    if ($owners->count() > 1) {
                        $state = 'conflict';
                        $counts['conflicts']++;
                    }

                    $id = DB::table('distribution_numbers')->insertGetId([
                        'company_id' => $companyId,
                        'phone' => $phone,
                        'lead_id' => $canonical->id,
                        'status' => $state,
                        'owner_id' => $owner,
                        'available_at' => $availableAt,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $number = DB::table('distribution_numbers')->find($id);

                    if ($state === 'cooldown') {
                        DB::table('leads')
                            ->whereIn('id', $copies->pluck('id'))
                            ->update(['assigned_to' => null]);
                    }

                    $counts['duplicates'] += max(0, $copies->count() - 1);
                }

                // Preserve all historic callers, including duplicate lead rows.
                $callers = (clone $history)
                    ->whereNotNull('user_id')
                    ->select('user_id')
                    ->selectRaw('MIN(call_at) AS first_called_at')
                    ->groupBy('user_id')
                    ->get();

                foreach ($callers as $caller) {
                    DB::table('distribution_visits')->insertOrIgnore([
                        'company_id' => $companyId,
                        'number_id' => $number->id,
                        'user_id' => $caller->user_id,
                        'first_called_at' => $caller->first_called_at,
                    ]);
                }
            }, 3);
        }

        return $counts;
    }

    public function preference(User $user, int $categoryId, string $state): void
    {
        $companyId = (int) $user->company_id;
        $state = DistributionValue::state($state);

        DB::transaction(function () use ($user, $companyId, $categoryId, $state) {
            $this->lockCompany($companyId);

            $active = DB::table('distribution_batches')
                ->where('company_id', $companyId)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (
                $active
                && (
                    (int) $active->category_id !== $categoryId
                    || $active->state !== $state
                )
            ) {
                throw ValidationException::withMessages([
                    'state' => 'Complete your current batch before changing category or state.',
                ]);
            }

            $exists = DB::table('distribution_preferences')
                ->where('company_id', $companyId)
                ->where('user_id', $user->id)
                ->exists();

            $values = [
                'category_id' => $categoryId,
                'state' => $state,
                'enabled' => true,
                'updated_at' => now(),
            ];

            if (!$exists) {
                $values['created_at'] = now();
            }

            DB::table('distribution_preferences')->updateOrInsert([
                'company_id' => $companyId,
                'user_id' => $user->id,
            ], $values);
        }, 3);
    }

    /**
     * Eligible stock for one employee.
     */
    private function pool(object $preference)
    {
        return DB::table('distribution_numbers as numbers')
            ->join('leads', 'leads.id', '=', 'numbers.lead_id')
            ->where('numbers.company_id', $preference->company_id)
            ->where('leads.company_id', $preference->company_id)
            ->where('leads.category_id', $preference->category_id)
            ->whereRaw('LOWER(TRIM(leads.state)) = ?', [$preference->state])
            ->whereNull('leads.deleted_at')
            ->where(function ($query) {
                $query->where('leads.do_not_call', false)
                    ->orWhereNull('leads.do_not_call');
            })
            ->whereRaw("TRIM(COALESCE(leads.city, '')) <> ''")
            ->where(function ($query) {
                $query->where('numbers.status', 'available')
                    ->orWhere(function ($cooldown) {
                        $cooldown->where('numbers.status', 'cooldown')
                            ->where('numbers.available_at', '<=', now());
                    });
            })
            ->whereNotExists(function ($query) use ($preference) {
                $query->selectRaw('1')
                    ->from('distribution_visits as visits')
                    ->whereColumn('visits.number_id', 'numbers.id')
                    ->where('visits.user_id', $preference->user_id);
            });
    }

    /**
     * Build one 20-slot city plan.
     */
    private function cityPlan(object $preference): array
    {
        $cities = $this->pool($preference)
            ->selectRaw('LOWER(TRIM(leads.city)) AS city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->all();

        if (!$cities) {
            return [];
        }

        $completed = DB::table('distribution_items as items')
            ->join('distribution_batches as batches', 'batches.id', '=', 'items.batch_id')
            ->where('batches.company_id', $preference->company_id)
            ->where('batches.user_id', $preference->user_id)
            ->where('batches.category_id', $preference->category_id)
            ->where('batches.state', $preference->state)
            ->whereNotNull('items.completed_at')
            ->whereIn('items.outcome', self::OUTCOMES);

        $recent = (clone $completed)
            ->orderByDesc('items.completed_at')
            ->orderByDesc('items.id')
            ->limit(20)
            ->get(['items.city', 'items.outcome']);

        if ($recent->count() === 20 && !$recent->contains('outcome', 'demo_sent')) {
            $otherCities = array_values(
                array_diff($cities, $recent->pluck('city')->all())
            );

            if ($otherCities) {
                $cities = $otherCities;
            }
        }

        $lastUsed = (clone $completed)
            ->select('items.city')
            ->selectRaw('MAX(items.completed_at) AS last_used')
            ->groupBy('items.city')
            ->pluck('last_used', 'city')
            ->all();

        usort($cities, fn ($a, $b) =>
            strcmp($lastUsed[$a] ?? '', $lastUsed[$b] ?? '')
            ?: strcmp($a, $b)
        );

        $demos = (clone $completed)
            ->where('items.outcome', 'demo_sent')
            ->where('items.completed_at', '>=', now()->subDays(30))
            ->whereIn('items.city', $cities)
            ->select('items.city')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('items.city')
            ->pluck('total', 'city')
            ->all();

        $plan = [];
        $sum = array_sum($demos);
        $assigned = array_fill_keys($cities, 0);

        // Deterministic weighted distribution of the first 16 slots.
        for ($slot = 0; $slot < 16; $slot++) {
            $bestCity = $cities[$slot % count($cities)];

            if ($sum > 0) {
                $bestScore = -INF;

                foreach ($cities as $city) {
                    $weight = (int) ($demos[$city] ?? 0);
                    $score = (($slot + 1) * $weight / $sum) - $assigned[$city];

                    if ($weight > 0 && $score > $bestScore) {
                        $bestScore = $score;
                        $bestCity = $city;
                    }
                }
            }

            $plan[] = $bestCity;
            $assigned[$bestCity]++;
        }

        // Four exploration slots, starting with least recently used cities.
        for ($slot = 0; $slot < 4; $slot++) {
            $plan[] = $cities[$slot % count($cities)];
        }

        return $plan;
    }

    /**
     * Give all currently waiting employees one number per round.
     */
    public function refill(int $companyId): void
    {
        DB::transaction(function () use ($companyId) {
            $this->lockCompany($companyId);

            $this->settleUnavailableItems($companyId);

            $preferences = DB::table('distribution_preferences as preferences')
                ->join('users', 'users.id', '=', 'preferences.user_id')
                ->where('preferences.company_id', $companyId)
                ->where('users.company_id', $companyId)
                ->where('users.is_active', true)
                ->where('preferences.enabled', true)
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('distribution_batches as batches')
                        ->whereColumn('batches.user_id', 'preferences.user_id')
                        ->whereColumn('batches.company_id', 'preferences.company_id')
                        ->where('batches.status', 'active');
                })
                ->select('preferences.*')
                ->orderBy('preferences.last_allocated_at')
                ->orderBy('preferences.id')
                ->get();

            $plans = [];
            $batchIds = [];

            foreach ($preferences as $preference) {
                $plans[$preference->id] = $this->cityPlan($preference);
            }

            for ($slot = 0; $slot < self::SIZE; $slot++) {
                foreach ($preferences as $preference) {
                    $plan = $plans[$preference->id];

                    if (!$plan) {
                        continue;
                    }

                    $city = $plan[$slot];

                    $number = $this->pool($preference)
                        ->orderByRaw(
                            'CASE WHEN LOWER(TRIM(leads.city)) = ? THEN 0 ELSE 1 END',
                            [$city]
                        )
                        ->orderBy('numbers.id')
                        ->select('numbers.*', 'leads.city')
                        ->lockForUpdate()
                        ->first();

                    if (!$number) {
                        continue;
                    }

                    if (!isset($batchIds[$preference->id])) {
                        $batchIds[$preference->id] =
                            DB::table('distribution_batches')->insertGetId([
                                'company_id' => $companyId,
                                'user_id' => $preference->user_id,
                                'category_id' => $preference->category_id,
                                'state' => $preference->state,
                                'status' => 'active',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }

                    DB::table('distribution_items')->insert([
                        'batch_id' => $batchIds[$preference->id],
                        'number_id' => $number->id,
                        'lead_id' => $number->lead_id,
                        'city' => DistributionValue::text($number->city),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('distribution_numbers')
                        ->where('id', $number->id)
                        ->update([
                            'status' => 'assigned',
                            'owner_id' => $preference->user_id,
                            'available_at' => null,
                            'updated_at' => now(),
                        ]);

                    DB::table('leads')
                        ->where('id', $number->lead_id)
                        ->update([
                            'assigned_to' => $preference->user_id,
                            'call_status' => 'pending',
                            'updated_at' => now(),
                        ]);

                    LeadAssignment::create([
                        'company_id' => $companyId,
                        'lead_id' => $number->lead_id,
                        'previous_user_id' => $number->owner_id,
                        'new_user_id' => $preference->user_id,
                        'assigned_by' => $preference->user_id,
                        'reason' => 'Automatic smart distribution',
                        'assigned_at' => now(),
                    ]);
                }
            }

            foreach (array_keys($batchIds) as $preferenceId) {
                DB::table('distribution_preferences')
                    ->where('id', $preferenceId)
                    ->update([
                        'last_allocated_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        }, 3);
    }

    private function settleUnavailableItems(int $companyId): void
    {
        $items = DB::table('distribution_items as items')
            ->join('distribution_batches as batches', 'batches.id', '=', 'items.batch_id')
            ->leftJoin('leads', 'leads.id', '=', 'items.lead_id')
            ->where('batches.company_id', $companyId)
            ->where('batches.status', 'active')
            ->whereNull('items.completed_at')
            ->where(function ($query) {
                $query->whereNull('leads.id')
                    ->orWhereNotNull('leads.deleted_at')
                    ->orWhere('leads.do_not_call', true);
            })
            ->select('items.id', 'items.number_id', 'items.batch_id')
            ->get();

        foreach ($items as $item) {
            DB::table('distribution_items')->where('id', $item->id)->update([
                'outcome' => 'unavailable',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('distribution_numbers')->where('id', $item->number_id)->update([
                'status' => 'blocked',
                'updated_at' => now(),
            ]);

            $this->closeBatch($item->batch_id);
        }
    }

    private function closeBatch(int $batchId): void
    {
        $pending = DB::table('distribution_items')
            ->where('batch_id', $batchId)
            ->whereNull('completed_at')
            ->exists();

        if (!$pending) {
            DB::table('distribution_batches')
                ->where('id', $batchId)
                ->where('status', 'active')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);
        }
    }

    public function result(User $user, int $leadId, array $input): CallHistory
    {
        return DB::transaction(function () use ($user, $leadId, $input) {
            $companyId = (int) $user->company_id;
            $this->lockCompany($companyId);

            $existing = CallHistory::query()
                ->where('user_id', $user->id)
                ->where('client_call_id', $input['client_call_id'])
                ->first();

            if ($existing) {
                abort_unless(
                    (int) $existing->lead_id === $leadId
                    && $existing->call_status === $input['call_status'],
                    409,
                    'This client_call_id was already used for another result.'
                );

                return $existing;
            }

            $lead = Lead::query()
                ->where('company_id', $companyId)
                ->whereKey($leadId)
                ->lockForUpdate()
                ->firstOrFail();

            $number = DB::table('distribution_numbers')
                ->where('company_id', $companyId)
                ->where('phone', $lead->distribution_phone)
                ->lockForUpdate()
                ->first();

            abort_unless($number, 422, 'Lead is not indexed for distribution.');

            abort_unless(
                (int) $lead->assigned_to === (int) $user->id
                && (int) $number->owner_id === (int) $user->id,
                403,
                'This number is not assigned to you.'
            );

            abort_if(
                in_array($number->status, ['blocked', 'conflict', 'converted'], true),
                409,
                'This number is closed for calling.'
            );

            $item = null;

            if (!empty($input['batch_item_id'])) {
                $item = DB::table('distribution_items as items')
                    ->join('distribution_batches as batches', 'batches.id', '=', 'items.batch_id')
                    ->where('items.id', $input['batch_item_id'])
                    ->where('items.lead_id', $leadId)
                    ->where('items.number_id', $number->id)
                    ->where('batches.company_id', $companyId)
                    ->where('batches.user_id', $user->id)
                    ->select('items.*')
                    ->first();

                abort_unless($item, 422, 'Invalid batch item.');
            }

            if ($number->status === 'assigned') {
                abort_unless(
                    $item && $item->completed_at === null,
                    422,
                    'An active batch_item_id is required.'
                );
            }

            $history = CallHistory::create([
                'lead_id' => $leadId,
                'user_id' => $user->id,
                'client_call_id' => $input['client_call_id'],
                'distribution_item_id' => $item?->id,
                'call_status' => $input['call_status'],
                'call_at' => now(),
                'remarks' => $input['remarks'] ?? null,
                'call_duration' => $input['call_duration'] ?? 0,
                'recording' => $input['recording'] ?? null,
            ]);

            DB::table('distribution_visits')->insertOrIgnore([
                'company_id' => $companyId,
                'number_id' => $number->id,
                'user_id' => $user->id,
                'first_called_at' => now(),
            ]);

            $status = $input['call_status'];
            $owner = $user->id;
            $availableAt = null;

            $distributionStatus = match ($status) {
                'not_answer', 'not_interested' => 'cooldown',
                'demo_sent', 'interested' => 'retained',
                'payment_done' => 'converted',
                'wrong_number' => 'blocked',
            };

            if ($distributionStatus === 'cooldown') {
                $owner = null;
                $availableAt = now()->addDays(7);
            }

            DB::table('distribution_numbers')
                ->where('id', $number->id)
                ->update([
                    'status' => $distributionStatus,
                    'owner_id' => $owner,
                    'available_at' => $availableAt,
                    'updated_at' => now(),
                ]);

            // Update all copies of the same phone to avoid stale duplicate owners.
            $leadValues = [
                'call_status' => $status,
                'assigned_to' => $owner,
                'updated_at' => now(),
            ];

            if ($status === 'wrong_number') {
                $leadValues['do_not_call'] = true;
            }

            DB::table('leads')
                ->where('company_id', $companyId)
                ->where('distribution_phone', $number->phone)
                ->update($leadValues);

            if ($item && $item->completed_at === null) {
                DB::table('distribution_items')->where('id', $item->id)->update([
                    'outcome' => $status,
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->closeBatch($item->batch_id);
            }

            return $history;
        }, 3);
    }

    public function current(User $user): array
    {
        $batch = DB::table('distribution_batches')
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if (!$batch) {
            return [
                'success' => true,
                'batch_status' => 'waiting_for_data',
                'batch_id' => null,
                'count' => 0,
                'data' => [],
            ];
        }

        $items = DB::table('distribution_items as items')
            ->join('leads', 'leads.id', '=', 'items.lead_id')
            ->where('items.batch_id', $batch->id)
            ->whereNull('items.completed_at')
            ->whereNull('leads.deleted_at')
            ->where('leads.assigned_to', $user->id)
            ->where(function ($query) {
                $query->where('leads.do_not_call', false)
                    ->orWhereNull('leads.do_not_call');
            })
            ->orderBy('items.id')
            ->select(
                'leads.*',
                'items.id as batch_item_id'
            )
            ->get();

        $total = DB::table('distribution_items')
            ->where('batch_id', $batch->id)
            ->count();

        return [
            'success' => true,
            'batch_status' => 'active',
            'batch_id' => $batch->id,
            'category_id' => $batch->category_id,
            'state' => $batch->state,
            'assigned_count' => $total,
            'count' => $items->count(),
            'partial' => $total < self::SIZE,
            'data' => $items,
        ];
    }

    public function cityStatus(User $user): array
    {
        $preference = DB::table('distribution_preferences')
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$preference) {
            return [];
        }

        $cities = Lead::query()
            ->where('company_id', $user->company_id)
            ->where('category_id', $preference->category_id)
            ->whereRaw('LOWER(TRIM(state)) = ?', [$preference->state])
            ->whereRaw("TRIM(COALESCE(city, '')) <> ''")
            ->selectRaw('LOWER(TRIM(city)) AS city_name')
            ->distinct()
            ->orderBy('city_name')
            ->pluck('city_name');

        $available = $this->pool($preference)
            ->selectRaw('LOWER(TRIM(leads.city)) AS city_name, COUNT(*) AS total')
            ->groupByRaw('LOWER(TRIM(leads.city))')
            ->pluck('total', 'city_name');

        return $cities->map(function ($city) use ($available) {
            $count = (int) ($available[$city] ?? 0);

            return [
                'city' => $city,
                'available_count' => $count,
                'status' => $count > 0 ? 'available' : 'data_not_available',
                'title' => 'City Data Status',
                'message' => $count > 0
                    ? 'Data Available'
                    : 'इस City में अभी कोई डेटा उपलब्ध नहीं है। कृपया दूसरी City का डेटा आने का इंतजार करें।',
            ];
        })->all();
    }
}