<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

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
                    now()->addMinutes(30),
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

        $totalCount = (clone $base)->count();

        $pendingCount = (clone $base)
            ->where('status', 'pending')
            ->count();

        $dueSoonCount = (clone $base)
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [
                now(),
                now()->addMinutes(30),
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


    public function complete(Request $request, FollowUp $followUp)
    {
        abort_unless(
            $followUp->company_id === $request->user()->company_id,
            403
        );

        $followUp->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with(
            'success',
            'Follow-up completed successfully.'
        );
    }


    public function destroy(Request $request, FollowUp $followUp)
    {
        abort_unless(
            $followUp->company_id === $request->user()->company_id,
            403
        );

        $followUp->delete();

        return back()->with(
            'success',
            'Follow-up deleted successfully.'
        );
    }
}
