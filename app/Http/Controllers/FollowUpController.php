<?php

// namespace App\Http\Controllers;

// use App\Models\FollowUp;
// use Illuminate\Http\Request;

// class FollowUpController extends Controller
// {
//     public function index(Request $request)
//     {
//         $companyId = $request->user()->company_id;

//         /*
//         |--------------------------------------------------------------------------
//         | Base Query
//         |--------------------------------------------------------------------------
//         */

//         $query = FollowUp::query()
//             ->with([
//                 'lead',
//                 'assignedUser',
//             ])
//             ->where('company_id', $companyId);

//         /*
//         |--------------------------------------------------------------------------
//         | Status Filter
//         |--------------------------------------------------------------------------
//         */

//         if ($request->status === 'overdue') {

//             $query
//                 ->where('status', 'pending')
//                 ->whereNotNull('scheduled_at')
//                 ->where('scheduled_at', '<', now());

//         } elseif ($request->status === 'due_soon') {

//             $query
//                 ->where('status', 'pending')
//                 ->whereNotNull('scheduled_at')
//                 ->whereBetween('scheduled_at', [
//                     now(),
//                     now()->addMinutes(30),
//                 ]);

//         } elseif ($request->filled('status')) {

//             $query->where('status', $request->status);
//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Follow-ups
//         |--------------------------------------------------------------------------
//         */

//         $followups = $query
//             ->orderByRaw("
//                 CASE
//                     WHEN status = 'pending'
//                     AND scheduled_at IS NOT NULL
//                     AND scheduled_at < NOW()
//                     THEN 0

//                     WHEN status = 'pending'
//                     AND scheduled_at IS NOT NULL
//                     THEN 1

//                     ELSE 2
//                 END
//             ")
//             ->orderBy('scheduled_at')
//             ->paginate(25)
//             ->withQueryString();

//         /*
//         |--------------------------------------------------------------------------
//         | Summary Cards
//         |--------------------------------------------------------------------------
//         */

//         $base = FollowUp::query()
//             ->where('company_id', $companyId);

//         $totalCount = (clone $base)->count();

//         $pendingCount = (clone $base)
//             ->where('status', 'pending')
//             ->count();

//         $dueSoonCount = (clone $base)
//             ->where('status', 'pending')
//             ->whereNotNull('scheduled_at')
//             ->whereBetween('scheduled_at', [
//                 now(),
//                 now()->addMinutes(30),
//             ])
//             ->count();

//         $overdueCount = (clone $base)
//             ->where('status', 'pending')
//             ->whereNotNull('scheduled_at')
//             ->where('scheduled_at', '<', now())
//             ->count();

//         $completedCount = (clone $base)
//             ->where('status', 'completed')
//             ->count();

//         return view('followups.index', compact(
//             'followups',
//             'totalCount',
//             'pendingCount',
//             'dueSoonCount',
//             'overdueCount',
//             'completedCount'
//         ));
//     }


//     public function complete(Request $request, FollowUp $followUp)
//     {
//         abort_unless(
//             $followUp->company_id === $request->user()->company_id,
//             403
//         );

//         $followUp->update([
//             'status'       => 'completed',
//             'completed_at' => now(),
//         ]);

//         return back()->with(
//             'success',
//             'Follow-up completed successfully.'
//         );
//     }


//     public function destroy(Request $request, FollowUp $followUp)
//     {
//         abort_unless(
//             $followUp->company_id === $request->user()->company_id,
//             403
//         );

//         $followUp->delete();

//         return back()->with(
//             'success',
//             'Follow-up deleted successfully.'
//         );
//     }
// }


// <?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $companyId = (int) $user->company_id;

        $query = FollowUp::query()
            ->with([
                'lead',
                'assignedUser',
            ])
            ->where('company_id', $companyId);

        if ($request->status === 'overdue') {
            $query
                ->where('status', 'pending')
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<', now());

        } elseif ($request->status === 'due_soon') {
            $query
                ->where('status', 'pending')
                ->whereNotNull('scheduled_at')
                ->whereBetween('scheduled_at', [
                    now(),
                    now()->copy()->addMinutes(30),
                ]);

        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $followups = $query
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
            ->paginate(25)
            ->withQueryString();

        $base = FollowUp::query()
            ->where('company_id', $companyId);

        $totalCount = (clone $base)->count();

        $pendingCount = (clone $base)
            ->where('status', 'pending')
            ->count();

        $dueSoonCount = (clone $base)
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [
                now(),
                now()->copy()->addMinutes(30),
            ])
            ->count();

        $overdueCount = (clone $base)
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now())
            ->count();

        $completedCount = (clone $base)
            ->where('status', 'completed')
            ->count();

        return view('followups.index', compact(
            'followups',
            'totalCount',
            'pendingCount',
            'dueSoonCount',
            'overdueCount',
            'completedCount'
        ));
    }

    /**
     * Reminder starts 10 minutes before scheduled time.
     * Overdue pending follow-ups are also returned.
     */
    public function reminders(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return response()->json([
                'success' => true,
                'reminders' => [],
                'server_time' => now()->toIso8601String(),
            ]);
        }

        $companyId = (int) $user->company_id;
        $userId = (int) $user->id;

        $now = now();
        $tenMinutesLater = $now->copy()->addMinutes(10);

        $followUps = FollowUp::query()
            ->with([
                'lead',
                'assignedUser',
            ])
            ->where('company_id', $companyId)
            ->where('assigned_to', $userId)
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $tenMinutesLater)
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get();

        $reminders = $followUps
            ->map(function (FollowUp $followUp) use ($now) {
                $scheduledAt = $followUp->scheduled_at
                    ? Carbon::parse($followUp->scheduled_at)
                    : null;

                if (!$scheduledAt) {
                    return null;
                }

                $secondsRemaining = $now->diffInSeconds(
                    $scheduledAt,
                    false
                );

                $isOverdue = $scheduledAt->lt($now);

                $minutesRemaining = $isOverdue
                    ? 0
                    : max(
                        0,
                        (int) ceil($secondsRemaining / 60)
                    );

                $overdueMinutes = $isOverdue
                    ? (int) floor(
                        $scheduledAt->diffInSeconds($now) / 60
                    )
                    : 0;

                $mobile =
                    $followUp->lead?->mobile
                    ?? $followUp->lead?->phone
                    ?? $followUp->lead?->phone_number
                    ?? null;

                return [
                    'id' => (int) $followUp->id,
                    'lead_id' => $followUp->lead_id,
                    'lead_name' => $followUp->lead?->name ?? 'Unknown Lead',
                    'mobile' => $mobile,
                    'assigned_user' => $followUp->assignedUser?->name,
                    'type' => $followUp->type ?: 'Follow-up',
                    'priority' => $followUp->priority ?: 'normal',
                    'notes' => $followUp->notes,

                    'scheduled_at' => $scheduledAt->toIso8601String(),
                    'scheduled_at_formatted' => $scheduledAt->format(
                        'd M Y, h:i A'
                    ),

                    'seconds_remaining' => $secondsRemaining,
                    'minutes_remaining' => $minutesRemaining,
                    'is_overdue' => $isOverdue,
                    'overdue_minutes' => $overdueMinutes,

                    'lead_url' => $followUp->lead
                        ? route('leads.show', $followUp->lead)
                        : null,

                    'complete_url' => route(
                        'followups.complete',
                        $followUp
                    ),

                    'snooze_url' => route(
                        'followups.snooze',
                        $followUp
                    ),

                    'reschedule_url' => route(
                        'followups.reschedule',
                        $followUp
                    ),

                    'cancel_url' => route(
                        'followups.cancel',
                        $followUp
                    ),
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'reminders' => $reminders,
            'count' => $reminders->count(),
            'server_time' => now()->toIso8601String(),
        ]);
    }


    /**
     * Nearest pending/overdue follow-up for sidebar timer.
     */
    public function nearest(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return response()->json([
                'success' => true,
                'followup' => null,
            ]);
        }

        $followUp = FollowUp::query()
            ->where('company_id', (int) $user->company_id)
            ->where('assigned_to', (int) $user->id)
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

        if (!$followUp || !$followUp->scheduled_at) {
            return response()->json([
                'success' => true,
                'followup' => null,
            ]);
        }

        $scheduledAt = Carbon::parse(
            $followUp->scheduled_at
        );

        return response()->json([
            'success' => true,

            'followup' => [
                'id' => (int) $followUp->id,

                'scheduled_at' =>
                    $scheduledAt->toIso8601String(),

                'scheduled_at_formatted' =>
                    $scheduledAt->format(
                        'd M Y, h:i A'
                    ),

                'is_overdue' =>
                    $scheduledAt->isPast(),
            ],
        ]);
    }

    public function complete(
        Request $request,
        FollowUp $followUp
    ): RedirectResponse|JsonResponse {
        $this->authorizeFollowUpAction(
            $request,
            $followUp
        );

        if ($followUp->status === 'completed') {
            return $this->actionResponse(
                $request,
                true,
                'Follow-up is already completed.',
                $followUp
            );
        }

        $followUp->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return $this->actionResponse(
            $request,
            true,
            'Follow-up completed successfully.',
            $followUp
        );
    }

    public function snooze(
        Request $request,
        FollowUp $followUp
    ): JsonResponse|RedirectResponse {
        $this->authorizeFollowUpAction(
            $request,
            $followUp
        );

        $validated = $request->validate([
            'minutes' => [
                'required',
                'integer',
                'in:5,10,15,30,60',
            ],
        ]);

        if ($followUp->status !== 'pending') {
            return $this->actionResponse(
                $request,
                false,
                'Only pending follow-ups can be snoozed.',
                $followUp,
                422
            );
        }

        $minutes = (int) $validated['minutes'];

        $newScheduledAt = now()
            ->copy()
            ->addMinutes($minutes);

        $followUp->update([
            'scheduled_at' => $newScheduledAt,
        ]);

        return $this->actionResponse(
            $request,
            true,
            "Follow-up snoozed for {$minutes} minutes.",
            $followUp,
            200,
            [
                'scheduled_at' => $newScheduledAt->toIso8601String(),
                'scheduled_at_formatted' => $newScheduledAt->format(
                    'd M Y, h:i A'
                ),
            ]
        );
    }

    /**
     * Set an exact new date/time for a pending follow-up.
     */
    public function reschedule(
        Request $request,
        FollowUp $followUp
    ): JsonResponse|RedirectResponse {
        $this->authorizeFollowUpAction(
            $request,
            $followUp
        );

        $validated = $request->validate([
            'scheduled_at' => [
                'required',
                'date',
                'after:now',
            ],
        ], [
            'scheduled_at.required' => 'Please select new date and time.',
            'scheduled_at.date' => 'Please select a valid date and time.',
            'scheduled_at.after' => 'New follow-up time must be in the future.',
        ]);

        if ($followUp->status !== 'pending') {
            return $this->actionResponse(
                $request,
                false,
                'Only pending follow-ups can be rescheduled.',
                $followUp,
                422
            );
        }

        $newScheduledAt = Carbon::parse(
            $validated['scheduled_at'],
            config('app.timezone')
        );

        $followUp->update([
            'scheduled_at' => $newScheduledAt,
            'completed_at' => null,
        ]);

        return $this->actionResponse(
            $request,
            true,
            'Follow-up rescheduled successfully.',
            $followUp,
            200,
            [
                'scheduled_at' => $newScheduledAt->toIso8601String(),
                'scheduled_at_formatted' => $newScheduledAt->format(
                    'd M Y, h:i A'
                ),
            ]
        );
    }

    /**
     * Cancel a pending follow-up without deleting its history.
     */
    public function cancel(
        Request $request,
        FollowUp $followUp
    ): JsonResponse|RedirectResponse {
        $this->authorizeFollowUpAction(
            $request,
            $followUp
        );

        if ($followUp->status === 'cancelled') {
            return $this->actionResponse(
                $request,
                true,
                'Follow-up is already cancelled.',
                $followUp
            );
        }

        if ($followUp->status === 'completed') {
            return $this->actionResponse(
                $request,
                false,
                'Completed follow-up cannot be cancelled.',
                $followUp,
                422
            );
        }

        $followUp->update([
            'status' => 'cancelled',
            'completed_at' => null,
        ]);

        return $this->actionResponse(
            $request,
            true,
            'Follow-up cancelled successfully.',
            $followUp
        );
    }

    public function destroy(
        Request $request,
        FollowUp $followUp
    ): RedirectResponse {
        abort_unless(
            (int) $followUp->company_id
                === (int) $request->user()->company_id,
            403
        );

        $followUp->delete();

        return back()->with(
            'success',
            'Follow-up deleted successfully.'
        );
    }

    /**
     * Company + assigned-user permission check.
     */
    private function authorizeFollowUpAction(
        Request $request,
        FollowUp $followUp
    ): void {
        $user = $request->user();

        abort_unless(
            (int) $followUp->company_id
                === (int) $user->company_id,
            403
        );

        if (
            !$user->can('followups.manage')
            && (int) $followUp->assigned_to
                !== (int) $user->id
        ) {
            abort(403);
        }
    }

    /**
     * Return JSON for popup/AJAX and redirect for normal form requests.
     */
    private function actionResponse(
        Request $request,
        bool $success,
        string $message,
        FollowUp $followUp,
        int $statusCode = 200,
        array $extra = []
    ): JsonResponse|RedirectResponse {
        if (
            $request->expectsJson()
            || $request->ajax()
        ) {
            return response()->json(
                array_merge([
                    'success' => $success,
                    'message' => $message,
                    'follow_up_id' => $followUp->id,
                ], $extra),
                $statusCode
            );
        }

        return back()->with(
            $success ? 'success' : 'error',
            $message
        );
    }
}