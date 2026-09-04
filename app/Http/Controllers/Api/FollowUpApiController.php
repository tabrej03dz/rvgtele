<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FollowUp;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;



class FollowUpApiController extends Controller
{


public function index(Request $request): JsonResponse
{
    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    $validated = $request->validate([
        'search' => [
            'nullable',
            'string',
            'max:255',
        ],

        'status' => [
            'nullable',
            Rule::in([
                'all',
                'pending',
                'completed',
                'cancelled',
                'overdue',
                'due_soon',
                'today',
                'upcoming',
            ]),
        ],

        'assigned_to' => [
            'nullable',
            'integer',
        ],

        'date_from' => [
            'nullable',
            'date_format:Y-m-d',
        ],

        'date_to' => [
            'nullable',
            'date_format:Y-m-d',
            'after_or_equal:date_from',
        ],

        'per_page' => [
            'nullable',
            'integer',
            Rule::in([
                10,
                20,
                25,
                50,
                100,
            ]),
        ],

        'page' => [
            'nullable',
            'integer',
            'min:1',
        ],
    ]);

    /*
    |--------------------------------------------------------------------------
    | Accessible Follow-up Query
    |--------------------------------------------------------------------------
    |
    | accessibleQuery() के हिसाब से:
    |
    | Super Admin/Admin = पूरी company
    | Manager/Team Leader = अपनी team
    | Employee = केवल अपने follow-ups
    |
    */

    $query = $this->accessibleQuery($request)
        ->with([
            'lead',
            'assignedUser:id,name,email,employee_code,team_id',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Search By Lead Name Or Mobile
    |--------------------------------------------------------------------------
    */

    if (!empty($validated['search'])) {
        $search = trim((string) $validated['search']);

        $query->whereHas(
            'lead',
            function (Builder $leadQuery) use ($search) {
                $leadQuery->where(
                    function (Builder $builder) use ($search) {
                        $builder
                            ->where(
                                'name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'mobile',
                                'like',
                                "%{$search}%"
                            );
                    }
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Status Filter
    |--------------------------------------------------------------------------
    */

    $status = $validated['status'] ?? 'all';

    switch ($status) {
        case 'pending':
            $query->where('status', 'pending');
            break;

        case 'completed':
            $query->where('status', 'completed');
            break;

        case 'cancelled':
            $query->where('status', 'cancelled');
            break;

        case 'overdue':
            $query
                ->where('status', 'pending')
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<', now());
            break;

        case 'due_soon':
            $query
                ->where('status', 'pending')
                ->whereNotNull('scheduled_at')
                ->whereBetween('scheduled_at', [
                    now(),
                    now()->copy()->addMinutes(30),
                ]);
            break;

        case 'today':
            $query
                ->whereNotNull('scheduled_at')
                ->whereBetween('scheduled_at', [
                    now()->copy()->startOfDay(),
                    now()->copy()->endOfDay(),
                ]);
            break;

        case 'upcoming':
            $query
                ->where('status', 'pending')
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '>', now());
            break;

        case 'all':
        default:
            /*
             * कोई status condition नहीं लगेगी।
             */
            break;
    }

    /*
    |--------------------------------------------------------------------------
    | Date From Filter
    |--------------------------------------------------------------------------
    */

    if (!empty($validated['date_from'])) {
        $dateFrom = Carbon::createFromFormat(
            'Y-m-d',
            $validated['date_from'],
            config('app.timezone')
        )->startOfDay();

        $query->where(
            'scheduled_at',
            '>=',
            $dateFrom
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Date To Filter
    |--------------------------------------------------------------------------
    */

    if (!empty($validated['date_to'])) {
        $dateTo = Carbon::createFromFormat(
            'Y-m-d',
            $validated['date_to'],
            config('app.timezone')
        )->endOfDay();

        $query->where(
            'scheduled_at',
            '<=',
            $dateTo
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Assigned Employee Filter
    |--------------------------------------------------------------------------
    |
    | accessibleQuery() पहले ही access restrict कर चुका है।
    | इसलिए manager केवल अपनी team के user को filter कर पाएगा।
    |
    */

    if (!empty($validated['assigned_to'])) {
        $query->where(
            'assigned_to',
            (int) $validated['assigned_to']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Pagination And Sorting
    |--------------------------------------------------------------------------
    */

    $perPage = (int) ($validated['per_page'] ?? 25);

    $followUps = $query
        ->orderByRaw("
            CASE
                WHEN status = 'pending'
                    AND scheduled_at IS NOT NULL
                    AND scheduled_at < NOW()
                THEN 0

                WHEN status = 'pending'
                    AND scheduled_at IS NOT NULL
                THEN 1

                ELSE 2
            END
        ")
        ->orderBy('scheduled_at')
        ->orderByDesc('id')
        ->paginate($perPage)
        ->withQueryString();

    /*
    |--------------------------------------------------------------------------
    | Clean Flutter Response
    |--------------------------------------------------------------------------
    */

    $followUps->getCollection()->transform(
        fn (FollowUp $followUp) =>
            $this->formatFollowUp($followUp)
    );

    return response()->json([
        'status' => true,

        'message' =>
            'Follow-ups fetched successfully.',

        'filters' => [
            'search' =>
                $validated['search'] ?? null,

            'status' =>
                $status,

            'assigned_to' =>
                isset($validated['assigned_to'])
                    ? (int) $validated['assigned_to']
                    : null,

            'date_from' =>
                $validated['date_from'] ?? null,

            'date_to' =>
                $validated['date_to'] ?? null,
        ],

        'data' => $followUps,
    ]);
}

    /**
     * Dashboard और tabs के लिए follow-up counts.
     */
    public function summary(Request $request): JsonResponse
    {
        $baseQuery = $this->accessibleQuery($request);

        $total = (clone $baseQuery)->count();

        $pending = (clone $baseQuery)
            ->where('status', 'pending')
            ->count();

        $completed = (clone $baseQuery)
            ->where('status', 'completed')
            ->count();

        $cancelled = (clone $baseQuery)
            ->where('status', 'cancelled')
            ->count();

        $overdue = (clone $baseQuery)
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now())
            ->count();

        $dueSoon = (clone $baseQuery)
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [
                now(),
                now()->copy()->addMinutes(30),
            ])
            ->count();

        $today = (clone $baseQuery)
            ->whereNotNull('scheduled_at')
            ->whereDate('scheduled_at', today())
            ->count();

        $upcoming = (clone $baseQuery)
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now())
            ->count();

        return response()->json([
            'status' => true,
            'message' => 'Follow-up summary fetched successfully.',

            'data' => [
                'total' => $total,
                'pending' => $pending,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'overdue' => $overdue,
                'due_soon' => $dueSoon,
                'today' => $today,
                'upcoming' => $upcoming,
            ],
        ]);
    }

    /**
     * Single follow-up details.
     */
    public function show(
        Request $request,
        FollowUp $followUp
    ): JsonResponse {
        $this->authorizeFollowUp($request, $followUp);

        $followUp->load([
            'lead',
            'assignedUser:id,name,email,employee_code',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Follow-up fetched successfully.',
            'data' => $this->formatFollowUp($followUp),
        ]);
    }

    /**
     * Due और overdue reminders.
     */
    public function reminders(Request $request): JsonResponse
    {
        $followUps = $this->accessibleQuery($request)
            ->with([
                'lead',
                'assignedUser:id,name,email,employee_code',
            ])
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where(
                'scheduled_at',
                '<=',
                now()->copy()->addMinute()
            )
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get()
            ->map(
                fn (FollowUp $followUp) =>
                    $this->formatFollowUp($followUp)
            )
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Follow-up reminders fetched successfully.',
            'count' => $followUps->count(),
            'server_time' => now()->toIso8601String(),
            'data' => $followUps,
        ]);
    }

    /**
     * Nearest pending follow-up.
     */
    public function nearest(Request $request): JsonResponse
    {
        $followUp = $this->accessibleQuery($request)
            ->with([
                'lead',
                'assignedUser:id,name,email,employee_code',
            ])
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->orderByRaw("
                ABS(
                    TIMESTAMPDIFF(
                        SECOND,
                        NOW(),
                        scheduled_at
                    )
                ) ASC
            ")
            ->first();

        return response()->json([
            'status' => true,
            'message' => $followUp
                ? 'Nearest follow-up fetched successfully.'
                : 'No pending follow-up found.',

            'data' => $followUp
                ? $this->formatFollowUp($followUp)
                : null,
        ]);
    }

    /**
     * Complete follow-up.
     */
    public function complete(
        Request $request,
        FollowUp $followUp
    ): JsonResponse {
        $this->authorizeFollowUp($request, $followUp);

        if ($followUp->status === 'completed') {
            return response()->json([
                'status' => true,
                'message' => 'Follow-up is already completed.',
                'data' => $this->formatFollowUp($followUp),
            ]);
        }

        $followUp->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $followUp->load([
            'lead',
            'assignedUser:id,name,email,employee_code',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Follow-up completed successfully.',
            'data' => $this->formatFollowUp($followUp),
        ]);
    }

    /**
     * Follow-up snooze.
     */
    public function snooze(
        Request $request,
        FollowUp $followUp
    ): JsonResponse {
        $this->authorizeFollowUp($request, $followUp);

        $validated = $request->validate([
            'minutes' => [
                'required',
                'integer',
                Rule::in([
                    5,
                    10,
                    15,
                    20,
                    30,
                    60,
                ]),
            ],
        ]);

        if ($followUp->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Only pending follow-ups can be snoozed.',
            ], 422);
        }

        $scheduledAt = now()
            ->copy()
            ->addMinutes((int) $validated['minutes']);

        $followUp->update([
            'scheduled_at' => $scheduledAt,
            'reminder_notified_at' => null,
        ]);

        $this->updateLeadNextFollowUp($followUp, $scheduledAt);

        $followUp->load([
            'lead',
            'assignedUser:id,name,email,employee_code',
        ]);

        return response()->json([
            'status' => true,
            'message' => "Follow-up snoozed for {$validated['minutes']} minutes.",
            'data' => $this->formatFollowUp($followUp),
        ]);
    }

    /**
     * Follow-up reschedule.
     */
    public function reschedule(
        Request $request,
        FollowUp $followUp
    ): JsonResponse {
        $this->authorizeFollowUp($request, $followUp);

        $validated = $request->validate([
            'scheduled_at' => [
                'required',
                'date',
                'after:now',
            ],
        ], [
            'scheduled_at.required' =>
                'Please select new follow-up date and time.',

            'scheduled_at.date' =>
                'Please enter a valid follow-up date and time.',

            'scheduled_at.after' =>
                'New follow-up time must be in the future.',
        ]);

        if ($followUp->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Only pending follow-ups can be rescheduled.',
            ], 422);
        }

        $scheduledAt = Carbon::parse(
            $validated['scheduled_at'],
            config('app.timezone')
        );

        $followUp->update([
            'scheduled_at' => $scheduledAt,
            'completed_at' => null,
            'reminder_notified_at' => null,
        ]);

        $this->updateLeadNextFollowUp($followUp, $scheduledAt);

        $followUp->load([
            'lead',
            'assignedUser:id,name,email,employee_code',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Follow-up rescheduled successfully.',
            'data' => $this->formatFollowUp($followUp),
        ]);
    }

    /**
     * Cancel follow-up.
     */
    public function cancel(
        Request $request,
        FollowUp $followUp
    ): JsonResponse {
        $this->authorizeFollowUp($request, $followUp);

        if ($followUp->status === 'cancelled') {
            return response()->json([
                'status' => true,
                'message' => 'Follow-up is already cancelled.',
                'data' => $this->formatFollowUp($followUp),
            ]);
        }

        if ($followUp->status === 'completed') {
            return response()->json([
                'status' => false,
                'message' => 'Completed follow-up cannot be cancelled.',
            ], 422);
        }

        $followUp->update([
            'status' => 'cancelled',
            'completed_at' => null,
            'reminder_notified_at' => null,
        ]);

        $followUp->load([
            'lead',
            'assignedUser:id,name,email,employee_code',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Follow-up cancelled successfully.',
            'data' => $this->formatFollowUp($followUp),
        ]);
    }

    /**
     * Company और user access वाली base query.
     */
    private function accessibleQuery(Request $request): Builder
    {
        $user = $request->user();
        $companyId = (int) $user->company_id;

        $query = FollowUp::query()
            ->where('company_id', $companyId);

        /*
         * Normal user केवल अपने follow-ups देखेगा।
         */
        if (!$this->hasFullAccess($request)) {
            $query->where(
                'assigned_to',
                (int) $user->id
            );
        }

        return $query;
    }

    /**
     * Follow-up action authorization.
     */
    private function authorizeFollowUp(
        Request $request,
        FollowUp $followUp
    ): void {
        $user = $request->user();

        abort_unless(
            (int) $followUp->company_id ===
            (int) $user->company_id,
            403,
            'You are not allowed to access this follow-up.'
        );

        /*
         * Admin/Super Admin same company में सब access कर सकते हैं।
         */
        if ($this->hasFullAccess($request)) {
            return;
        }

        abort_unless(
            (int) $followUp->assigned_to ===
            (int) $user->id,
            403,
            'This follow-up is not assigned to you.'
        );
    }

    /**
     * Admin access check.
     */
    private function hasFullAccess(Request $request): bool
    {
        return $request->user()->hasAnyRole([
            'super_admin',
            'admin',
        ]);
    }

    /**
     * Lead table का next_follow_up_at sync रखता है।
     */
    private function updateLeadNextFollowUp(
        FollowUp $followUp,
        Carbon $scheduledAt
    ): void {
        $followUp->loadMissing('lead');

        if ($followUp->lead) {
            $followUp->lead->update([
                'next_follow_up_at' => $scheduledAt,
            ]);
        }
    }

    /**
     * Flutter के लिए clean response.
     */
    private function formatFollowUp(FollowUp $followUp): array
    {
        $scheduledAt = $followUp->scheduled_at
            ? Carbon::parse($followUp->scheduled_at)
            : null;

        $mobile = $followUp->lead?->mobile
            ?? $followUp->lead?->phone
            ?? $followUp->lead?->phone_number;

        return [
            'id' => (int) $followUp->id,
            'company_id' => (int) $followUp->company_id,
            'lead_id' => $followUp->lead_id
                ? (int) $followUp->lead_id
                : null,

            'assigned_to' => $followUp->assigned_to
                ? (int) $followUp->assigned_to
                : null,

            'created_by' => $followUp->created_by
                ? (int) $followUp->created_by
                : null,

            'type' => $followUp->type,
            'priority' => $followUp->priority,
            'status' => $followUp->status,
            'notes' => $followUp->notes,

            'scheduled_at' => $scheduledAt?->toIso8601String(),

            'scheduled_at_formatted' => $scheduledAt?->format(
                'd M Y, h:i A'
            ),

            'is_overdue' => $scheduledAt
                ? (
                    $followUp->status === 'pending'
                    && $scheduledAt->isPast()
                )
                : false,

            'remaining_seconds' => $scheduledAt
                ? now()->diffInSeconds($scheduledAt, false)
                : null,

            'completed_at' => $followUp->completed_at
                ? Carbon::parse($followUp->completed_at)->toIso8601String()
                : null,

            'lead' => [
                'id' => $followUp->lead?->id,
                'name' => $followUp->lead?->name,
                'business_name' => $followUp->lead?->business_name,
                'mobile' => $mobile,
                'city' => $followUp->lead?->city,
            ],

            'assigned_user' => [
                'id' => $followUp->assignedUser?->id,
                'name' => $followUp->assignedUser?->name,
                'email' => $followUp->assignedUser?->email,
                'employee_code' =>
                    $followUp->assignedUser?->employee_code,
            ],

            'created_at' => $followUp->created_at
                ? Carbon::parse($followUp->created_at)->toIso8601String()
                : null,

            'updated_at' => $followUp->updated_at
                ? Carbon::parse($followUp->updated_at)->toIso8601String()
                : null,
        ];
    }
}
