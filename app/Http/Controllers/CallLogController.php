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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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







// public function store(
//     Request $request,
//     Lead $lead
// ): RedirectResponse {

//     $companyId =
//         (int) $request->user()->company_id;


//     /*
//     |--------------------------------------------------------------------------
//     | Company Guard
//     |--------------------------------------------------------------------------
//     */

//     abort_unless(
//         (int) $lead->company_id === $companyId,
//         403,
//         'Unauthorized lead access.'
//     );


//     /*
//     |--------------------------------------------------------------------------
//     | First Validation
//     |--------------------------------------------------------------------------
//     */

//     $request->validate([

//         'call_disposition_id' => [
//             'required',
//             'integer',

//             Rule::exists(
//                 'call_dispositions',
//                 'id'
//             )->where(
//                 function ($query) use ($companyId) {

//                     $query
//                         ->where(
//                             'is_active',
//                             true
//                         )
//                         ->where(
//                             function ($subQuery) use ($companyId) {

//                                 $subQuery
//                                     ->whereNull(
//                                         'company_id'
//                                     )
//                                     ->orWhere(
//                                         'company_id',
//                                         $companyId
//                                     );
//                             }
//                         );
//                 }
//             ),
//         ],

//         'mark_demo_send' => [
//             'nullable',
//             'boolean',
//         ],

//     ]);


//     /*
//     |--------------------------------------------------------------------------
//     | Load Disposition
//     |--------------------------------------------------------------------------
//     */

//     $disposition =
//         CallDisposition::query()
//             ->whereKey(
//                 (int) $request->input(
//                     'call_disposition_id'
//                 )
//             )
//             ->where(
//                 'is_active',
//                 true
//             )
//             ->where(
//                 function (Builder $query) use ($companyId) {

//                     $query
//                         ->whereNull(
//                             'company_id'
//                         )
//                         ->orWhere(
//                             'company_id',
//                             $companyId
//                         );
//                 }
//             )
//             ->firstOrFail();


//     /*
//     |--------------------------------------------------------------------------
//     | Demo Send Request
//     |--------------------------------------------------------------------------
//     */

//     $markDemoSend =
//         $request->boolean(
//             'mark_demo_send'
//         );


//     /*
//     |--------------------------------------------------------------------------
//     | Demo Security Check
//     |--------------------------------------------------------------------------
//     */

//     if ($markDemoSend) {

//         $normalizedDispositionName =
//             strtolower(
//                 trim(
//                     (string) $disposition->name
//                 )
//             );

//         if ($normalizedDispositionName !== 'demo') {

//             throw ValidationException::withMessages([

//                 'call_disposition_id' =>
//                     'Demo Send action ke liye Demo disposition required hai.',

//             ]);
//         }
//     }


//     /*
//     |--------------------------------------------------------------------------
//     | Dynamic Validation
//     |--------------------------------------------------------------------------
//     */

//     $validated =
//         $request->validate([

//             'duration_seconds' => [
//                 'nullable',
//                 'integer',
//                 'min:0',
//             ],

//             'remarks' => [

//                 (
//                     $disposition->requires_remarks
//                     &&
//                     !$markDemoSend
//                 )
//                     ? 'required'
//                     : 'nullable',

//                 'string',
//                 'max:3000',
//             ],

//             'follow_up_at' => [

//                 $disposition->requires_follow_up
//                     ? 'required'
//                     : 'nullable',

//                 'date',
//                 'after:now',
//             ],

//         ], [

//             'remarks.required' =>
//                 "Remarks are required for {$disposition->name} disposition.",

//             'follow_up_at.required' =>
//                 "Follow-up date and time are required for {$disposition->name} disposition.",

//             'follow_up_at.after' =>
//                 'Follow-up date and time must be in the future.',

//         ]);


//     /*
//     |--------------------------------------------------------------------------
//     | Duration
//     |--------------------------------------------------------------------------
//     */

//     $durationSeconds =
//         max(
//             0,
//             (int) (
//                 $validated[
//                     'duration_seconds'
//                 ] ?? 0
//             )
//         );


//     /*
//     |--------------------------------------------------------------------------
//     | Normalize Remarks
//     |--------------------------------------------------------------------------
//     */

//     if ($markDemoSend) {

//         $remarks =
//             trim(
//                 (string) (
//                     $disposition->auto_remarks
//                     ?: 'Demo Sent'
//                 )
//             );

//     } elseif ($disposition->requires_remarks) {

//         $remarks =
//             trim(
//                 (string) (
//                     $validated[
//                         'remarks'
//                     ] ?? ''
//                 )
//             );

//     } else {

//         $remarks =
//             filled(
//                 $disposition->auto_remarks
//             )
//                 ? trim(
//                     (string)
//                     $disposition->auto_remarks
//                 )
//                 : null;
//     }


//     /*
//     |--------------------------------------------------------------------------
//     | Normalize Follow-up
//     |--------------------------------------------------------------------------
//     */

//     $followUpAt =
//         $disposition->requires_follow_up
//             ? (
//                 $validated[
//                     'follow_up_at'
//                 ] ?? null
//             )
//             : null;


//     /*
//     |--------------------------------------------------------------------------
//     | Database Transaction
//     |--------------------------------------------------------------------------
//     */

//     DB::transaction(
//         function () use (
//             $request,
//             $lead,
//             $companyId,
//             $disposition,
//             $durationSeconds,
//             $remarks,
//             $followUpAt,
//             $markDemoSend
//         ) {


//             /*
//             |--------------------------------------------------------------------------
//             | Create Call Log
//             |--------------------------------------------------------------------------
//             |
//             | Har call par naya call log create hoga.
//             |
//             | Demo dubara send hua to bhi naya call log create hoga.
//             |
//             */

//             CallLog::create([

//                 'company_id' =>
//                     $companyId,

//                 'lead_id' =>
//                     $lead->id,

//                 'user_id' =>
//                     $request->user()->id,

//                 'call_disposition_id' =>
//                     $disposition->id,

//                 'started_at' =>
//                     now()->subSeconds(
//                         $durationSeconds
//                     ),

//                 'ended_at' =>
//                     now(),

//                 'duration_seconds' =>
//                     $durationSeconds,

//                 'remarks' =>
//                     $remarks,

//             ]);


//             /*
//             |--------------------------------------------------------------------------
//             | Lead Update Data
//             |--------------------------------------------------------------------------
//             */

//             $leadUpdateData = [

//                 'last_contact_at' =>
//                     now(),

//             ];


//             /*
//             |--------------------------------------------------------------------------
//             | Demo Send
//             |--------------------------------------------------------------------------
//             |
//             | Important:
//             |
//             | Agar Demo first time send hua:
//             | demo_send = 1
//             | demo_sent_at = current time
//             |
//             | Agar Demo dubara send hua:
//             | demo_send already 1 rahega
//             | demo_sent_at dobara current/latest time se update hoga
//             |
//             */

//             if ($markDemoSend) {

//                 $leadUpdateData[
//                     'demo_send'
//                 ] = true;

//                 $leadUpdateData[
//                     'demo_sent_at'
//                 ] = now();
//             }


//             /*
//             |--------------------------------------------------------------------------
//             | Update Lead
//             |--------------------------------------------------------------------------
//             */

//             $lead->update(
//                 $leadUpdateData
//             );


//             /*
//             |--------------------------------------------------------------------------
//             | Follow-up Create OR Update
//             |--------------------------------------------------------------------------
//             |
//             | Same lead ka pending follow-up already hai:
//             | => NEW follow-up create nahi hoga
//             | => existing pending follow-up update hoga
//             |
//             | Pending follow-up nahi hai:
//             | => new follow-up create hoga
//             |
//             */

//             if (
//                 $disposition->requires_follow_up
//                 &&
//                 !empty($followUpAt)
//             ) {


//                 /*
//                 |--------------------------------------------------------------------------
//                 | Existing Pending Follow-up
//                 |--------------------------------------------------------------------------
//                 */

//                 $existingFollowUp =
//                     FollowUp::query()
//                         ->where(
//                             'company_id',
//                             $companyId
//                         )
//                         ->where(
//                             'lead_id',
//                             $lead->id
//                         )
//                         ->where(
//                             'status',
//                             'pending'
//                         )
//                         ->latest(
//                             'id'
//                         )
//                         ->first();


//                 /*
//                 |--------------------------------------------------------------------------
//                 | Existing Follow-up Found
//                 |--------------------------------------------------------------------------
//                 */

//                 if ($existingFollowUp) {

//                     $existingFollowUp->update([

//                         'assigned_to' =>
//                             $lead->assigned_to
//                             ?: $request->user()->id,

//                         'scheduled_at' =>
//                             $followUpAt,

//                         'notes' =>
//                             $remarks
//                             ?: "Follow-up updated from {$disposition->name} call disposition.",

//                         'status' =>
//                             'pending',

//                     ]);


//                 /*
//                 |--------------------------------------------------------------------------
//                 | No Existing Follow-up
//                 |--------------------------------------------------------------------------
//                 */

//                 } else {

//                     FollowUp::create([

//                         'company_id' =>
//                             $companyId,

//                         'lead_id' =>
//                             $lead->id,

//                         'assigned_to' =>
//                             $lead->assigned_to
//                             ?: $request->user()->id,

//                         'created_by' =>
//                             $request->user()->id,

//                         'scheduled_at' =>
//                             $followUpAt,

//                         'notes' =>
//                             $remarks
//                             ?: "Follow-up created from {$disposition->name} call disposition.",

//                         'status' =>
//                             'pending',

//                     ]);
//                 }


//                 /*
//                 |--------------------------------------------------------------------------
//                 | Update Lead Next Follow-up Time
//                 |--------------------------------------------------------------------------
//                 */

//                 $lead->update([

//                     'next_follow_up_at' =>
//                         $followUpAt,

//                 ]);
//             }

//         }
//     );


//     /*
//     |--------------------------------------------------------------------------
//     | Success Response
//     |--------------------------------------------------------------------------
//     */

//     if ($markDemoSend) {

//         return back()->with(
//             'success',
//             'Demo Sent successfully. Latest demo sent time and call log have been saved.'
//         );
//     }


//     if ($disposition->requires_follow_up) {

//         return back()->with(
//             'success',
//             'Call result and follow-up saved successfully.'
//         );
//     }


//     return back()->with(
//         'success',
//         'Call result saved successfully.'
//     );
// }

public function store(
    Request $request,
    Lead $lead
): RedirectResponse {

    $companyId =
        (int) $request->user()->company_id;


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
    | First Validation
    |--------------------------------------------------------------------------
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
                        ->where(
                            'is_active',
                            true
                        )
                        ->where(
                            function ($subQuery) use ($companyId) {

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

        'mark_demo_send' => [
            'nullable',
            'boolean',
        ],

        'from_followup_popup' => [
            'nullable',
            'boolean',
        ],

        /*
        |--------------------------------------------------------------------------
        | Current Follow-up ID
        |--------------------------------------------------------------------------
        | Auto reminder popup se exact current follow-up ID aayega.
        */
        'follow_up_id' => [
            'nullable',
            'integer',
        ],

    ]);


    /*
    |--------------------------------------------------------------------------
    | Load Disposition
    |--------------------------------------------------------------------------
    */

    $disposition =
        CallDisposition::query()
            ->whereKey(
                (int) $request->input(
                    'call_disposition_id'
                )
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                function (Builder $query) use ($companyId) {

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
            ->firstOrFail();


    /*
    |--------------------------------------------------------------------------
    | Request Flags
    |--------------------------------------------------------------------------
    */

    $markDemoSend =
        $request->boolean(
            'mark_demo_send'
        );

    $fromFollowupPopup =
        $request->boolean(
            'from_followup_popup'
        );

    $currentFollowUpId =
        $request->filled('follow_up_id')
            ? (int) $request->input('follow_up_id')
            : null;


    /*
    |--------------------------------------------------------------------------
    | Demo Security
    |--------------------------------------------------------------------------
    */

    if ($markDemoSend) {

        $normalizedDispositionName =
            strtolower(
                trim(
                    (string) $disposition->name
                )
            );

        if ($normalizedDispositionName !== 'demo') {

            throw ValidationException::withMessages([

                'call_disposition_id' =>
                    'Demo Send action ke liye Demo disposition required hai.',

            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Dynamic Validation
    |--------------------------------------------------------------------------
    */

    $validated =
        $request->validate([

            'duration_seconds' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'remarks' => [

                (
                    $disposition->requires_remarks
                    &&
                    !$markDemoSend
                )
                    ? 'required'
                    : 'nullable',

                'string',
                'max:3000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Follow-up Date & Time
            |--------------------------------------------------------------------------
            | requires_follow_up = true ho to popup ho ya normal lead page,
            | follow_up_at required hoga.
            */
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
    | Duration
    |--------------------------------------------------------------------------
    */

    $durationSeconds =
        max(
            0,
            (int) (
                $validated[
                    'duration_seconds'
                ] ?? 0
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Remarks
    |--------------------------------------------------------------------------
    */

    if ($markDemoSend) {

        $remarks =
            trim(
                (string) (
                    $disposition->auto_remarks
                    ?: 'Demo Sent'
                )
            );

    } else {

        /*
        |--------------------------------------------------------------------------
        | Manual Remark Priority
        |--------------------------------------------------------------------------
        */

        $manualRemarks =
            trim(
                (string) (
                    $validated[
                        'remarks'
                    ] ?? ''
                )
            );

        if ($manualRemarks !== '') {

            $remarks =
                $manualRemarks;

        } elseif (filled($disposition->auto_remarks)) {

            $remarks =
                trim(
                    (string)
                    $disposition->auto_remarks
                );

        } else {

            $remarks = null;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Follow-up Time
    |--------------------------------------------------------------------------
    | IMPORTANT:
    | Auto popup me bhi follow_up_at ko NULL nahi karna hai.
    */

    $followUpAt =
        $disposition->requires_follow_up
            ? (
                $validated[
                    'follow_up_at'
                ] ?? null
            )
            : null;


    /*
    |--------------------------------------------------------------------------
    | Database Transaction
    |--------------------------------------------------------------------------
    */

    DB::transaction(
        function () use (
            $request,
            $lead,
            $companyId,
            $disposition,
            $durationSeconds,
            $remarks,
            $followUpAt,
            $markDemoSend,
            $fromFollowupPopup,
            $currentFollowUpId
        ) {


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
            | Lead Update Data
            |--------------------------------------------------------------------------
            */

            $leadUpdateData = [

                'last_contact_at' =>
                    now(),

            ];


            /*
            |--------------------------------------------------------------------------
            | Demo
            |--------------------------------------------------------------------------
            */

            if ($markDemoSend) {

                $leadUpdateData[
                    'demo_send'
                ] = true;

                $leadUpdateData[
                    'demo_sent_at'
                ] = now();
            }


            /*
            |--------------------------------------------------------------------------
            | AUTO FOLLOW-UP POPUP
            |--------------------------------------------------------------------------
            |
            | Popup se feedback save karte waqt:
            |
            | 1. Naya FollowUp create NAHI hoga.
            | 2. Exact current follow_up_id update hoga.
            | 3. scheduled_at = selected/auto follow_up_at.
            | 4. notes = latest feedback.
            | 5. reminder_notified_at reset hoga.
            |
            */

            if ($fromFollowupPopup) {

                if (
                    $disposition->requires_follow_up
                    &&
                    !empty($followUpAt)
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Follow-up ID Mandatory
                    |--------------------------------------------------------------------------
                    */

                    if (!$currentFollowUpId) {

                        throw ValidationException::withMessages([

                            'follow_up_id' =>
                                'Current follow-up ID is missing from reminder popup.',

                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Find Exact Current Pending Follow-up
                    |--------------------------------------------------------------------------
                    */

                    $currentFollowUp =
                        FollowUp::query()
                            ->where(
                                'id',
                                $currentFollowUpId
                            )
                            ->where(
                                'company_id',
                                $companyId
                            )
                            ->where(
                                'lead_id',
                                $lead->id
                            )
                            ->where(
                                'status',
                                'pending'
                            )
                            ->first();


                    if (!$currentFollowUp) {

                        throw ValidationException::withMessages([

                            'follow_up_id' =>
                                'Current pending follow-up not found for this lead.',

                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Update Existing Current Follow-up
                    |--------------------------------------------------------------------------
                    */

                    $currentFollowUp->update([

                        'assigned_to' =>
                            $lead->assigned_to
                            ?: $request->user()->id,

                        'scheduled_at' =>
                            $followUpAt,

                        'notes' =>
                            $remarks
                            ?: "Follow-up updated from {$disposition->name} call disposition.",

                        'status' =>
                            'pending',

                        'completed_at' =>
                            null,

                        'reminder_notified_at' =>
                            null,

                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Sync Lead Next Follow-up
                    |--------------------------------------------------------------------------
                    */

                    $leadUpdateData[
                        'next_follow_up_at'
                    ] = $followUpAt;
                }


            /*
            |--------------------------------------------------------------------------
            | NORMAL LEAD FEEDBACK
            |--------------------------------------------------------------------------
            |
            | Normal lead page se:
            | pending follow-up hai => update
            | pending follow-up nahi => create
            |
            */

            } elseif (
                $disposition->requires_follow_up
                &&
                !empty($followUpAt)
            ) {

                $existingFollowUp =
                    FollowUp::query()
                        ->where(
                            'company_id',
                            $companyId
                        )
                        ->where(
                            'lead_id',
                            $lead->id
                        )
                        ->where(
                            'status',
                            'pending'
                        )
                        ->latest(
                            'id'
                        )
                        ->first();


                if ($existingFollowUp) {

                    $existingFollowUp->update([

                        'assigned_to' =>
                            $lead->assigned_to
                            ?: $request->user()->id,

                        'scheduled_at' =>
                            $followUpAt,

                        'notes' =>
                            $remarks
                            ?: "Follow-up updated from {$disposition->name} call disposition.",

                        'status' =>
                            'pending',

                        'completed_at' =>
                            null,

                        'reminder_notified_at' =>
                            null,

                    ]);

                } else {

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
                }


                /*
                |--------------------------------------------------------------------------
                | Sync Lead Next Follow-up
                |--------------------------------------------------------------------------
                */

                $leadUpdateData[
                    'next_follow_up_at'
                ] = $followUpAt;
            }


            /*
            |--------------------------------------------------------------------------
            | Update Lead Once
            |--------------------------------------------------------------------------
            */

            $lead->update(
                $leadUpdateData
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    if ($markDemoSend) {

        return back()->with(
            'success',
            'Demo Sent successfully. Latest demo sent time and call log have been saved.'
        );
    }


    if (
        $fromFollowupPopup
        &&
        $disposition->requires_follow_up
    ) {

        return back()->with(
            'success',
            'Feedback saved and current follow-up updated successfully.'
        );
    }


    if ($disposition->requires_follow_up) {

        return back()->with(
            'success',
            'Call result and follow-up saved successfully.'
        );
    }


    return back()->with(
        'success',
        'Call result saved successfully.'
    );
}


public function storeDemo(
    Request $request,
    Lead $lead
): RedirectResponse {

    $companyId =
        (int) $request->user()->company_id;


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
    | Find Demo Disposition
    |--------------------------------------------------------------------------
    |
    | Current company ka "demo" disposition pehle prefer karenge.
    | Agar company-specific nahi hai to global disposition use hoga.
    |
    */

    $demoDisposition =
        CallDisposition::query()
            ->where(
                'is_active',
                true
            )
            ->whereRaw(
                'LOWER(TRIM(name)) = ?',
                ['demo']
            )
            ->where(
                function (Builder $query) use (
                    $companyId
                ) {

                    $query
                        ->where(
                            'company_id',
                            $companyId
                        )
                        ->orWhereNull(
                            'company_id'
                        );
                }
            )
            ->orderByRaw(
                'CASE
                    WHEN company_id = ? THEN 0
                    ELSE 1
                END',
                [$companyId]
            )
            ->first();


    if (!$demoDisposition) {

        return back()
            ->withErrors([
                'demo' =>
                    'Demo disposition not found. Please create an active "demo" disposition first.',
            ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Do Not Save Duplicate Demo Again
    |--------------------------------------------------------------------------
    */

    if ($lead->demo_send) {

        return back()->with(
            'success',
            'Demo is already marked as sent for this lead.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Transaction
    |--------------------------------------------------------------------------
    */

    DB::transaction(
        function () use (
            $request,
            $lead,
            $companyId,
            $demoDisposition
        ) {

            $now = now();


            /*
            |--------------------------------------------------------------------------
            | Create Demo Call Log
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
                    $demoDisposition->id,

                'started_at' =>
                    $now,

                'ended_at' =>
                    $now,

                'duration_seconds' =>
                    0,

                /*
                |--------------------------------------------------------------------------
                | Important
                |--------------------------------------------------------------------------
                |
                | Demo disposition requires_remarks false ho tab bhi
                | Demo Sent remark save hoga.
                |
                */

                'remarks' =>
                    filled(
                        $demoDisposition->auto_remarks
                    )
                        ? trim(
                            (string)
                            $demoDisposition->auto_remarks
                        )
                        : 'Demo Sent',

            ]);


            /*
            |--------------------------------------------------------------------------
            | Mark Demo Sent On Lead
            |--------------------------------------------------------------------------
            */

            $lead->update([

                'demo_send' =>
                    true,

                'demo_sent_at' =>
                    $now,

                'last_contact_at' =>
                    $now,

            ]);

        }
    );


    return back()->with(
        'success',
        'Demo Sent successfully and Demo disposition saved in call log.'
    );
}
}
