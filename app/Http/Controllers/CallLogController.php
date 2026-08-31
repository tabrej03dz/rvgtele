<?php


namespace App\Http\Controllers;

use App\Models\CallDisposition;
use App\Models\CallLog;
use App\Models\FollowUp;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CallLogController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Call Log Listing
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $companyId = (int) $request->user()->company_id;

        $calls = CallLog::query()
            ->with([
                'lead',
                'user',
                'disposition',
            ])
            ->where('company_id', $companyId)
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('calls.index', compact('calls'));
    }

    /*
    |--------------------------------------------------------------------------
    | Save Call Result
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | Remarks aur Follow-up ki requirement selected Call Disposition ke
    | requires_remarks / requires_follow_up flags se decide hogi.
    |
    */

    public function store(
        Request $request,
        Lead $lead
    ): RedirectResponse {
        $companyId = (int) $request->user()->company_id;

        /*
        |--------------------------------------------------------------------------
        | Company Guard
        |--------------------------------------------------------------------------
        */

        abort_unless(
            (int) $lead->company_id === $companyId,
            403,
            'Unauthorized lead access.'
        );

        /*
        |--------------------------------------------------------------------------
        | First validate selected disposition
        |--------------------------------------------------------------------------
        |
        | Global dispositions (company_id NULL) ya current company ke
        | dispositions hi allow honge.
        |
        */

        $request->validate([
            'call_disposition_id' => [
                'required',
                'integer',

                Rule::exists(
                    'call_dispositions',
                    'id'
                )->where(
                    function ($query) use ($companyId) {
                        $query
                            ->where('is_active', true)
                            ->where(
                                function ($subQuery) use ($companyId) {
                                    $subQuery
                                        ->whereNull('company_id')
                                        ->orWhere(
                                            'company_id',
                                            $companyId
                                        );
                                }
                            );
                    }
                ),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Load disposition settings
        |--------------------------------------------------------------------------
        */

        $disposition = CallDisposition::query()
            ->whereKey(
                (int) $request->call_disposition_id
            )
            ->where('is_active', true)
            ->where(
                function (Builder $query) use ($companyId) {
                    $query
                        ->whereNull('company_id')
                        ->orWhere(
                            'company_id',
                            $companyId
                        );
                }
            )
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Dynamic Validation
        |--------------------------------------------------------------------------
        |
        | requires_remarks = true
        |     => Remarks required
        |
        | requires_follow_up = true
        |     => Follow-up date/time required
        |
        */

        $validated = $request->validate([
            'duration_seconds' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'remarks' => [
                $disposition->requires_remarks
                    ? 'required'
                    : 'nullable',
                'string',
                'max:3000',
            ],

            'follow_up_at' => [
                $disposition->requires_follow_up
                    ? 'required'
                    : 'nullable',
                'date',
                'after:now',
            ],
        ], [
            'remarks.required' =>
                "Remarks are required for {$disposition->name} disposition.",

            'follow_up_at.required' =>
                "Follow-up date and time are required for {$disposition->name} disposition.",

            'follow_up_at.after' =>
                'Follow-up date and time must be in the future.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Normalize Values
        |--------------------------------------------------------------------------
        |
        | Agar disposition remarks/follow-up demand nahi karta to browser se
        | accidentally aayi stale values ko ignore karenge.
        |
        */

        $remarks = $disposition->requires_remarks
            ? trim((string) ($validated['remarks'] ?? ''))
            : null;

        $followUpAt = $disposition->requires_follow_up
            ? ($validated['follow_up_at'] ?? null)
            : null;

        $durationSeconds =
            (int) ($validated['duration_seconds'] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | Create Call Log
        |--------------------------------------------------------------------------
        */

        CallLog::create([
            'company_id' =>
                $companyId,

            'lead_id' =>
                $lead->id,

            'user_id' =>
                $request->user()->id,

            'call_disposition_id' =>
                $disposition->id,

            'started_at' =>
                now()->subSeconds(
                    $durationSeconds
                ),

            'ended_at' =>
                now(),

            'duration_seconds' =>
                $durationSeconds,

            'remarks' =>
                $remarks,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Lead Last Contact
        |--------------------------------------------------------------------------
        */

        $lead->update([
            'last_contact_at' => now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Create Follow-up Only When Disposition Requires It
        |--------------------------------------------------------------------------
        */

        if (
            $disposition->requires_follow_up
            &&
            !empty($followUpAt)
        ) {
            FollowUp::create([
                'company_id' =>
                    $companyId,

                'lead_id' =>
                    $lead->id,

                'assigned_to' =>
                    $lead->assigned_to
                    ?: $request->user()->id,

                'created_by' =>
                    $request->user()->id,

                'scheduled_at' =>
                    $followUpAt,

                'notes' =>
                    $remarks
                    ?: "Follow-up created from {$disposition->name} call disposition.",

                'status' =>
                    'pending',
            ]);

            $lead->update([
                'next_follow_up_at' =>
                    $followUpAt,
            ]);
        } else {
            /*
            |--------------------------------------------------------------------------
            | Non-follow-up disposition
            |--------------------------------------------------------------------------
            |
            | Existing future follow-up history ko delete nahi karenge.
            | Sirf current call se naya follow-up create nahi hoga.
            |
            */
        }

        return back()->with(
            'success',
            $disposition->requires_follow_up
                ? 'Call result and follow-up saved successfully.'
                : 'Call result saved successfully.'
        );
    }
}
