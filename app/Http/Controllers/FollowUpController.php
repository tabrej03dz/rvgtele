<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Models\CallDisposition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    /**
     * ============================================================
     * FOLLOW-UP LIST
     * ============================================================
     *
     * Important:
     * Logged-in user ko sirf uske assigned follow-ups hi milenge.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $companyId = (int) $user->company_id;
        $userId = (int) $user->id;

        /*
        |--------------------------------------------------------------------------
        | Base Follow-up Query
        |--------------------------------------------------------------------------
        |
        | SECURITY:
        | 1. Same company
        | 2. Follow-up current user ko assigned hona chahiye
        |
        */

        $query = FollowUp::query()
            ->with([
                'lead',
                'assignedUser',
            ])
            ->where('company_id', $companyId)
            ->where('assigned_to', $userId);

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

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

            $allowedStatuses = [
                'pending',
                'completed',
                'cancelled',
            ];

            if (in_array($request->status, $allowedStatuses, true)) {
                $query->where('status', $request->status);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Follow-up List
        |--------------------------------------------------------------------------
        */

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
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Summary Base Query
        |--------------------------------------------------------------------------
        |
        | Ye bhi current user ke hisaab se scoped hai.
        |
        */

        $base = FollowUp::query()
            ->where('company_id', $companyId)
            ->where('assigned_to', $userId);

        /*
        |--------------------------------------------------------------------------
        | Total
        |--------------------------------------------------------------------------
        */

        $totalCount = (clone $base)->count();

        /*
        |--------------------------------------------------------------------------
        | Pending
        |--------------------------------------------------------------------------
        */

        $pendingCount = (clone $base)
            ->where('status', 'pending')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Due Soon
        |--------------------------------------------------------------------------
        */

        $dueSoonCount = (clone $base)
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [
                now(),
                now()->copy()->addMinutes(30),
            ])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Overdue
        |--------------------------------------------------------------------------
        */

        $overdueCount = (clone $base)
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now())
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Completed
        |--------------------------------------------------------------------------
        */

        $completedCount = (clone $base)
            ->where('status', 'completed')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

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
     * ============================================================
     * LIVE REMINDERS
     * ============================================================
     *
     * Scheduled time se 1 minute pehle reminder dikhega.
     * Overdue pending follow-ups bhi return honge.
     *
     * Sirf logged-in user ke follow-ups.
     */
    public function reminders(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {

            return response()->json([
                'success' => true,
                'reminders' => [],
                'count' => 0,
                'server_time' => now()->toIso8601String(),
            ]);
        }

        $companyId = (int) $user->company_id;
        $userId = (int) $user->id;

        $now = now();

        $oneMinuteLater = $now
            ->copy()
            ->addMinute();

        /*
        |--------------------------------------------------------------------------
        | Only Current User Follow-ups
        |--------------------------------------------------------------------------
        */

        $followUps = FollowUp::query()
            ->with([
                'lead',
                'assignedUser',
            ])
            ->where('company_id', $companyId)
            ->where('assigned_to', $userId)
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $oneMinuteLater)
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Prepare Reminder Response
        |--------------------------------------------------------------------------
        */

        $reminders = $followUps
            ->map(function (FollowUp $followUp) use ($now) {

                $scheduledAt = $followUp->scheduled_at
                    ? Carbon::parse($followUp->scheduled_at)
                    : null;

                if (!$scheduledAt) {
                    return null;
                }

                /*
                |--------------------------------------------------------------------------
                | Remaining Time
                |--------------------------------------------------------------------------
                */

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

                /*
                |--------------------------------------------------------------------------
                | Lead Mobile
                |--------------------------------------------------------------------------
                */

                $mobile =
                    $followUp->lead?->mobile
                    ?? $followUp->lead?->phone
                    ?? $followUp->lead?->phone_number
                    ?? null;

                /*
                |--------------------------------------------------------------------------
                | Response
                |--------------------------------------------------------------------------
                */

                return [
                    'id' => (int) $followUp->id,

                    'lead_id' => $followUp->lead_id,

                    'lead_name' =>
                        $followUp->lead?->name
                        ?? 'Unknown Lead',

                    'mobile' => $mobile,

                    'assigned_user' =>
                        $followUp->assignedUser?->name,

                    'type' =>
                        $followUp->type
                        ?: 'Follow-up',

                    'priority' =>
                        $followUp->priority
                        ?: 'normal',

                    'notes' =>
                        $followUp->notes,

                    'scheduled_at' =>
                        $scheduledAt->toIso8601String(),

                    'scheduled_at_formatted' =>
                        $scheduledAt->format(
                            'd M Y, h:i A'
                        ),

                    'seconds_remaining' =>
                        $secondsRemaining,

                    'minutes_remaining' =>
                        $minutesRemaining,

                    'is_overdue' =>
                        $isOverdue,

                    'overdue_minutes' =>
                        $overdueMinutes,

                    /*
                    |--------------------------------------------------------------------------
                    | URLs
                    |--------------------------------------------------------------------------
                    */

                    'lead_url' =>
                        $followUp->lead
                            ? route(
                                'leads.show',
                                $followUp->lead
                            )
                            : null,

                    'call_store_url' =>
                        $followUp->lead
                            ? route(
                                'calls.store',
                                $followUp->lead
                            )
                            : null,

                    'complete_url' =>
                        route(
                            'followups.complete',
                            $followUp
                        ),

                    'snooze_url' =>
                        route(
                            'followups.snooze',
                            $followUp
                        ),

                    'reschedule_url' =>
                        route(
                            'followups.reschedule',
                            $followUp
                        ),

                    'cancel_url' =>
                        route(
                            'followups.cancel',
                            $followUp
                        ),
                ];
            })
            ->filter()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Call Dispositions For Reminder Popup
        |--------------------------------------------------------------------------
        |
        | Popup ke andar "Save Call Result" ke liye wahi active dispositions
        | bheje ja rahe hain jo lead page par use hote hain.
        |
        */

        $dispositions = CallDisposition::query()
            ->where(function (Builder $query) use ($companyId) {

                $query
                    ->whereNull('company_id')
                    ->orWhere(
                        'company_id',
                        $companyId
                    );
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'requires_remarks',
                'requires_follow_up',
            ])
            ->map(function (CallDisposition $disposition) {

                return [
                    'id' =>
                        (int) $disposition->id,

                    'name' =>
                        $disposition->name,

                    'requires_remarks' =>
                        (bool) $disposition->requires_remarks,

                    'requires_follow_up' =>
                        (bool) $disposition->requires_follow_up,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,

            'reminders' => $reminders,

            'dispositions' => $dispositions,

            'count' =>
                $reminders->count(),

            'server_time' =>
                now()->toIso8601String(),
        ]);
    }


    /**
     * ============================================================
     * NEAREST FOLLOW-UP
     * ============================================================
     *
     * Sidebar timer ke liye nearest pending follow-up.
     *
     * Sirf current user ka.
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

        $companyId = (int) $user->company_id;
        $userId = (int) $user->id;

        /*
        |--------------------------------------------------------------------------
        | Find Nearest Current User Follow-up
        |--------------------------------------------------------------------------
        */

        $followUp = FollowUp::query()
            ->where('company_id', $companyId)
            ->where('assigned_to', $userId)
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

                'id' =>
                    (int) $followUp->id,

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


    /**
     * ============================================================
     * COMPLETE FOLLOW-UP
     * ============================================================
     */
    public function complete(
        Request $request,
        FollowUp $followUp
    ): RedirectResponse|JsonResponse {

        /*
        |--------------------------------------------------------------------------
        | Security Check
        |--------------------------------------------------------------------------
        */

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


    /**
     * ============================================================
     * SNOOZE FOLLOW-UP
     * ============================================================
     */
    public function snooze(
        Request $request,
        FollowUp $followUp
    ): JsonResponse|RedirectResponse {

        $this->authorizeFollowUpAction(
            $request,
            $followUp
        );

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'minutes' => [
                'required',
                'integer',
                'in:5,10,15,30,60',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Status Check
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | New Time
        |--------------------------------------------------------------------------
        */

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
                'scheduled_at' =>
                    $newScheduledAt->toIso8601String(),

                'scheduled_at_formatted' =>
                    $newScheduledAt->format(
                        'd M Y, h:i A'
                    ),
            ]
        );
    }


    /**
     * ============================================================
     * RESCHEDULE FOLLOW-UP
     * ============================================================
     */
    public function reschedule(
        Request $request,
        FollowUp $followUp
    ): JsonResponse|RedirectResponse {

        $this->authorizeFollowUpAction(
            $request,
            $followUp
        );

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'scheduled_at' => [
                'required',
                'date',
                'after:now',
            ],
        ], [
            'scheduled_at.required' =>
                'Please select new date and time.',

            'scheduled_at.date' =>
                'Please select a valid date and time.',

            'scheduled_at.after' =>
                'New follow-up time must be in the future.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Status Check
        |--------------------------------------------------------------------------
        */

        if ($followUp->status !== 'pending') {

            return $this->actionResponse(
                $request,
                false,
                'Only pending follow-ups can be rescheduled.',
                $followUp,
                422
            );
        }

        /*
        |--------------------------------------------------------------------------
        | New Scheduled Date
        |--------------------------------------------------------------------------
        */

        $newScheduledAt = Carbon::parse(
            $validated['scheduled_at'],
            config('app.timezone')
        );

        $followUp->update([
            'scheduled_at' =>
                $newScheduledAt,

            'completed_at' =>
                null,
        ]);

        return $this->actionResponse(
            $request,
            true,
            'Follow-up rescheduled successfully.',
            $followUp,
            200,
            [
                'scheduled_at' =>
                    $newScheduledAt->toIso8601String(),

                'scheduled_at_formatted' =>
                    $newScheduledAt->format(
                        'd M Y, h:i A'
                    ),
            ]
        );
    }


    /**
     * ============================================================
     * CANCEL FOLLOW-UP
     * ============================================================
     */
    public function cancel(
        Request $request,
        FollowUp $followUp
    ): JsonResponse|RedirectResponse {

        $this->authorizeFollowUpAction(
            $request,
            $followUp
        );

        /*
        |--------------------------------------------------------------------------
        | Already Cancelled
        |--------------------------------------------------------------------------
        */

        if ($followUp->status === 'cancelled') {

            return $this->actionResponse(
                $request,
                true,
                'Follow-up is already cancelled.',
                $followUp
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Completed Cannot Be Cancelled
        |--------------------------------------------------------------------------
        */

        if ($followUp->status === 'completed') {

            return $this->actionResponse(
                $request,
                false,
                'Completed follow-up cannot be cancelled.',
                $followUp,
                422
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Cancel
        |--------------------------------------------------------------------------
        */

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


    /**
     * ============================================================
     * DELETE FOLLOW-UP
     * ============================================================
     */
    public function destroy(
        Request $request,
        FollowUp $followUp
    ): RedirectResponse {

        /*
        |--------------------------------------------------------------------------
        | Important
        |--------------------------------------------------------------------------
        |
        | Purane code me delete ke liye sirf company check tha.
        | Ab user assignment bhi check hoga.
        |
        */

        $this->authorizeFollowUpAction(
            $request,
            $followUp
        );

        $followUp->delete();

        return back()->with(
            'success',
            'Follow-up deleted successfully.'
        );
    }


    /**
     * ============================================================
     * SECURITY / AUTHORIZATION
     * ============================================================
     *
     * Kisi bhi follow-up action ke liye:
     *
     * 1. Same company hona mandatory.
     * 2. Follow-up logged-in user ko assigned hona mandatory.
     *
     * Permission hone se dusre user ke follow-up ka access nahi milega.
     */
    private function authorizeFollowUpAction(
        Request $request,
        FollowUp $followUp
    ): void {

        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Login Check
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $user,
            401
        );

        /*
        |--------------------------------------------------------------------------
        | Company Check
        |--------------------------------------------------------------------------
        */

        abort_unless(
            (int) $followUp->company_id
                ===
            (int) $user->company_id,
            403,
            'You are not allowed to access this follow-up.'
        );

        /*
        |--------------------------------------------------------------------------
        | Assigned User Check
        |--------------------------------------------------------------------------
        |
        | THIS IS IMPORTANT
        |
        | User dusre employee ka follow-up URL se bhi access nahi kar sakta.
        |
        */

        abort_unless(
            (int) $followUp->assigned_to
                ===
            (int) $user->id,
            403,
            'This follow-up is not assigned to you.'
        );
    }


    /**
     * ============================================================
     * COMMON ACTION RESPONSE
     * ============================================================
     *
     * AJAX/Popup = JSON
     * Normal Form = Redirect
     */
    private function actionResponse(
        Request $request,
        bool $success,
        string $message,
        FollowUp $followUp,
        int $statusCode = 200,
        array $extra = []
    ): JsonResponse|RedirectResponse {

        /*
        |--------------------------------------------------------------------------
        | AJAX / JSON
        |--------------------------------------------------------------------------
        */

        if (
            $request->expectsJson()
            || $request->ajax()
        ) {

            return response()->json(
                array_merge([
                    'success' =>
                        $success,

                    'message' =>
                        $message,

                    'follow_up_id' =>
                        $followUp->id,
                ], $extra),
                $statusCode
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Normal Request
        |--------------------------------------------------------------------------
        */

        return back()->with(
            $success
                ? 'success'
                : 'error',
            $message
        );
    }
}