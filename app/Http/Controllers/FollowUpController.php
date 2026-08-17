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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    /**
     * Follow-ups listing.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $companyId = (int) $user->company_id;

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        */

        $query = FollowUp::query()
            ->with([
                'lead',
                'assignedUser',
            ])
            ->where('company_id', $companyId);

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

            $query->where('status', $request->status);
        }

        /*
        |--------------------------------------------------------------------------
        | Follow-ups
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
            ->paginate(25)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Summary Cards
        |--------------------------------------------------------------------------
        */

        $base = FollowUp::query()
            ->where('company_id', $companyId);

        $totalCount = (clone $base)
            ->count();

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
     * Get follow-ups which need reminder.
     *
     * Reminder starts 5 minutes before scheduled time.
     * Only currently logged-in user's assigned follow-ups are returned.
     */
    public function reminders(Request $request): JsonResponse
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | User Must Belong To Company
        |--------------------------------------------------------------------------
        */

        if (!$user->company_id) {
            return response()->json([
                'success' => true,
                'reminders' => [],
                'server_time' => now()->toIso8601String(),
            ]);
        }

        $companyId = (int) $user->company_id;
        $userId = (int) $user->id;

        /*
        |--------------------------------------------------------------------------
        | Reminder Window
        |--------------------------------------------------------------------------
        |
        | Current time se lekar next 5 minutes tak ke follow-ups.
        |
        | Example:
        |
        | Follow-up = 4:30 PM
        | Reminder start = 4:25 PM
        |
        |--------------------------------------------------------------------------
        */

        $now = now();

        $fiveMinutesLater = $now->copy()->addMinutes(5);

        /*
        |--------------------------------------------------------------------------
        | Fetch Reminders
        |--------------------------------------------------------------------------
        */

        $followUps = FollowUp::query()
            ->with([
                'lead:id,name,mobile',
                'assignedUser:id,name',
            ])
            ->where('company_id', $companyId)
            ->where('assigned_to', $userId)
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [
                $now,
                $fiveMinutesLater,
            ])
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Format Response
        |--------------------------------------------------------------------------
        */

        $reminders = $followUps->map(function (FollowUp $followUp) use ($now) {

            $scheduledAt = $followUp->scheduled_at;

            $secondsRemaining = max(
                0,
                $now->diffInSeconds($scheduledAt, false)
            );

            return [
                'id' => $followUp->id,

                'lead_id' => $followUp->lead_id,

                'lead_name' => $followUp->lead?->name
                    ?? 'Unknown Lead',

                'mobile' => $followUp->lead?->mobile,

                'assigned_user' => $followUp->assignedUser?->name,

                'type' => $followUp->type,

                'priority' => $followUp->priority,

                'notes' => $followUp->notes,

                'scheduled_at' => $scheduledAt?->toIso8601String(),

                'scheduled_at_formatted' => $scheduledAt
                    ? $scheduledAt->format('d M Y, h:i A')
                    : null,

                'seconds_remaining' => $secondsRemaining,

                'minutes_remaining' => max(
                    0,
                    (int) ceil($secondsRemaining / 60)
                ),

                'lead_url' => $followUp->lead
                    ? route('leads.show', $followUp->lead)
                    : null,

                'complete_url' => route(
                    'followups.complete',
                    $followUp
                ),
            ];
        });

        return response()->json([
            'success' => true,
            'reminders' => $reminders,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Complete follow-up.
     */
    public function complete(
        Request $request,
        FollowUp $followUp
    ): RedirectResponse|JsonResponse {

        $user = $request->user();

        abort_unless(
            (int) $followUp->company_id === (int) $user->company_id,
            403
        );

        /*
        |--------------------------------------------------------------------------
        | Assigned User Security
        |--------------------------------------------------------------------------
        |
        | followups.manage permission wala user kisi visible follow-up ko
        | complete kar sakta hai.
        |
        | Normal employee sirf apna follow-up complete karega.
        |--------------------------------------------------------------------------
        */

        if (
            !$user->can('followups.manage')
            && (int) $followUp->assigned_to !== (int) $user->id
        ) {
            abort(403);
        }

        $followUp->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | AJAX / Popup Request
        |--------------------------------------------------------------------------
        */

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Follow-up completed successfully.',
                'follow_up_id' => $followUp->id,
            ]);
        }

        return back()->with(
            'success',
            'Follow-up completed successfully.'
        );
    }

    /**
     * Delete follow-up.
     */
    public function destroy(
        Request $request,
        FollowUp $followUp
    ): RedirectResponse {

        abort_unless(
            (int) $followUp->company_id ===
            (int) $request->user()->company_id,
            403
        );

        $followUp->delete();

        return back()->with(
            'success',
            'Follow-up deleted successfully.'
        );
    }
}