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
    //     $companyId = (int) $request->user()->company_id;

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
    //     | First validate selected disposition
    //     |--------------------------------------------------------------------------
    //     |
    //     | Global dispositions (company_id NULL) ya current company ke
    //     | dispositions hi allow honge.
    //     |
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
    //                         ->where('is_active', true)
    //                         ->where(
    //                             function ($subQuery) use ($companyId) {
    //                                 $subQuery
    //                                     ->whereNull('company_id')
    //                                     ->orWhere(
    //                                         'company_id',
    //                                         $companyId
    //                                     );
    //                             }
    //                         );
    //                 }
    //             ),
    //         ],
    //     ]);

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Load disposition settings
    //     |--------------------------------------------------------------------------
    //     */

    //     $disposition = CallDisposition::query()
    //         ->whereKey(
    //             (int) $request->call_disposition_id
    //         )
    //         ->where('is_active', true)
    //         ->where(
    //             function (Builder $query) use ($companyId) {
    //                 $query
    //                     ->whereNull('company_id')
    //                     ->orWhere(
    //                         'company_id',
    //                         $companyId
    //                     );
    //             }
    //         )
    //         ->firstOrFail();

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Dynamic Validation
    //     |--------------------------------------------------------------------------
    //     |
    //     | requires_remarks = true
    //     |     => Remarks required
    //     |
    //     | requires_follow_up = true
    //     |     => Follow-up date/time required
    //     |
    //     */

    //     $validated = $request->validate([
    //         'duration_seconds' => [
    //             'nullable',
    //             'integer',
    //             'min:0',
    //         ],

    //         'remarks' => [
    //             $disposition->requires_remarks
    //                 ? 'required'
    //                 : 'nullable',
    //             'string',
    //             'max:3000',
    //         ],

    //         'follow_up_at' => [
    //             $disposition->requires_follow_up
    //                 ? 'required'
    //                 : 'nullable',
    //             'date',
    //             'after:now',
    //         ],
    //     ], [
    //         'remarks.required' =>
    //             "Remarks are required for {$disposition->name} disposition.",

    //         'follow_up_at.required' =>
    //             "Follow-up date and time are required for {$disposition->name} disposition.",

    //         'follow_up_at.after' =>
    //             'Follow-up date and time must be in the future.',
    //     ]);

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Normalize Values
    //     |--------------------------------------------------------------------------
    //     |
    //     | Agar disposition remarks/follow-up demand nahi karta to browser se
    //     | accidentally aayi stale values ko ignore karenge.
    //     |
    //     */

    //     $remarks = $disposition->requires_remarks
    //         ? trim((string) ($validated['remarks'] ?? ''))
    //         : null;

    //     $followUpAt = $disposition->requires_follow_up
    //         ? ($validated['follow_up_at'] ?? null)
    //         : null;

    //     $durationSeconds =
    //         (int) ($validated['duration_seconds'] ?? 0);

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Create Call Log
    //     |--------------------------------------------------------------------------
    //     */

    //     CallLog::create([
    //         'company_id' =>
    //             $companyId,

    //         'lead_id' =>
    //             $lead->id,

    //         'user_id' =>
    //             $request->user()->id,

    //         'call_disposition_id' =>
    //             $disposition->id,

    //         'started_at' =>
    //             now()->subSeconds(
    //                 $durationSeconds
    //             ),

    //         'ended_at' =>
    //             now(),

    //         'duration_seconds' =>
    //             $durationSeconds,

    //         'remarks' =>
    //             $remarks,
    //     ]);

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Update Lead Last Contact
    //     |--------------------------------------------------------------------------
    //     */

    //     $lead->update([
    //         'last_contact_at' => now(),
    //     ]);

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Create Follow-up Only When Disposition Requires It
    //     |--------------------------------------------------------------------------
    //     */

    //     if (
    //         $disposition->requires_follow_up
    //         &&
    //         !empty($followUpAt)
    //     ) {
    //         FollowUp::create([
    //             'company_id' =>
    //                 $companyId,

    //             'lead_id' =>
    //                 $lead->id,

    //             'assigned_to' =>
    //                 $lead->assigned_to
    //                 ?: $request->user()->id,

    //             'created_by' =>
    //                 $request->user()->id,

    //             'scheduled_at' =>
    //                 $followUpAt,

    //             'notes' =>
    //                 $remarks
    //                 ?: "Follow-up created from {$disposition->name} call disposition.",

    //             'status' =>
    //                 'pending',
    //         ]);

    //         $lead->update([
    //             'next_follow_up_at' =>
    //                 $followUpAt,
    //         ]);
    //     } else {
    //         /*
    //         |--------------------------------------------------------------------------
    //         | Non-follow-up disposition
    //         |--------------------------------------------------------------------------
    //         |
    //         | Existing future follow-up history ko delete nahi karenge.
    //         | Sirf current call se naya follow-up create nahi hoga.
    //         |
    //         */
    //     }

    //     return back()->with(
    //         'success',
    //         $disposition->requires_follow_up
    //             ? 'Call result and follow-up saved successfully.'
    //             : 'Call result saved successfully.'
    //     );
    // }



    // public function store(
    // Request $request,
    // Lead $lead
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
    //     | First Validate Selected Disposition
    //     |--------------------------------------------------------------------------
    //     |
    //     | Global disposition:
    //     | company_id = NULL
    //     |
    //     | OR
    //     |
    //     | Current company's disposition.
    //     |
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
    //                             function ($subQuery) use (
    //                                 $companyId
    //                             ) {

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


    //         /*
    //         |--------------------------------------------------------------------------
    //         | Special Flag From Demo Send Button
    //         |--------------------------------------------------------------------------
    //         */

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
    //                 function (Builder $query) use (
    //                     $companyId
    //                 ) {

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
    //     | Is This Demo Send Request?
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
    //     |
    //     | Frontend hidden input badla ja sakta hai.
    //     |
    //     | Isliye agar mark_demo_send=1 hai to backend par bhi verify karenge
    //     | ki selected disposition ka naam actually "demo" hi hai.
    //     |
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
    //     |
    //     | Normal Call:
    //     | requires_remarks ke according remarks required.
    //     |
    //     | Demo:
    //     | remark backend khud "Demo Sent" set karega.
    //     |
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
    //     |
    //     | DEMO SEND:
    //     | always "Demo Sent"
    //     |
    //     | Normal required remarks:
    //     | user-entered remarks
    //     |
    //     | Non-required disposition:
    //     | auto_remarks use kar sakte hain agar disposition me configured hai.
    //     |
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
    //                     $validated['remarks']
    //                     ?? ''
    //                 )
    //             );

    //     } else {

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Non-required Remarks
    //         |--------------------------------------------------------------------------
    //         |
    //         | User ke stale form data ko ignore karenge.
    //         | Agar disposition me auto_remarks configured hai to wahi save hoga.
    //         |
    //         */

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
    //     |
    //     | CallLog + Lead + FollowUp sab ek saath successful honge.
    //     |
    //     | Beech me error aaya to sab rollback.
    //     |
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
    //             | Demo button se aaya:
    //             |
    //             | call_logs:
    //             | disposition = Demo
    //             |
    //             | leads:
    //             | demo_send = 1
    //             | demo_sent_at = current datetime
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
    //             | Create Follow-up
    //             |--------------------------------------------------------------------------
    //             |
    //             | Sirf disposition explicitly follow-up require kare tab.
    //             |
    //             */

    //             if (
    //                 $disposition->requires_follow_up
    //                 &&
    //                 !empty($followUpAt)
    //             ) {

    //                 FollowUp::create([

    //                     'company_id' =>
    //                         $companyId,

    //                     'lead_id' =>
    //                         $lead->id,

    //                     'assigned_to' =>
    //                         $lead->assigned_to
    //                         ?: $request->user()->id,

    //                     'created_by' =>
    //                         $request->user()->id,

    //                     'scheduled_at' =>
    //                         $followUpAt,

    //                     'notes' =>
    //                         $remarks
    //                         ?: "Follow-up created from {$disposition->name} call disposition.",

    //                     'status' =>
    //                         'pending',

    //                 ]);


    //                 /*
    //                 |--------------------------------------------------------------------------
    //                 | Update Lead Follow-up
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
    //             'Demo Sent successfully. Demo disposition and call log have also been saved.'
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
    | Demo Send Request
    |--------------------------------------------------------------------------
    */

    $markDemoSend =
        $request->boolean(
            'mark_demo_send'
        );


    /*
    |--------------------------------------------------------------------------
    | Demo Security Check
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
    | Normalize Remarks
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

    } elseif ($disposition->requires_remarks) {

        $remarks =
            trim(
                (string) (
                    $validated[
                        'remarks'
                    ] ?? ''
                )
            );

    } else {

        $remarks =
            filled(
                $disposition->auto_remarks
            )
                ? trim(
                    (string)
                    $disposition->auto_remarks
                )
                : null;
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Follow-up
    |--------------------------------------------------------------------------
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
            $markDemoSend
        ) {


            /*
            |--------------------------------------------------------------------------
            | Create Call Log
            |--------------------------------------------------------------------------
            |
            | Har call par naya call log create hoga.
            |
            | Demo dubara send hua to bhi naya call log create hoga.
            |
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
            | Demo Send
            |--------------------------------------------------------------------------
            |
            | Important:
            |
            | Agar Demo first time send hua:
            | demo_send = 1
            | demo_sent_at = current time
            |
            | Agar Demo dubara send hua:
            | demo_send already 1 rahega
            | demo_sent_at dobara current/latest time se update hoga
            |
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
            | Update Lead
            |--------------------------------------------------------------------------
            */

            $lead->update(
                $leadUpdateData
            );


            /*
            |--------------------------------------------------------------------------
            | Follow-up Create OR Update
            |--------------------------------------------------------------------------
            |
            | Same lead ka pending follow-up already hai:
            | => NEW follow-up create nahi hoga
            | => existing pending follow-up update hoga
            |
            | Pending follow-up nahi hai:
            | => new follow-up create hoga
            |
            */

            if (
                $disposition->requires_follow_up
                &&
                !empty($followUpAt)
            ) {


                /*
                |--------------------------------------------------------------------------
                | Existing Pending Follow-up
                |--------------------------------------------------------------------------
                */

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


                /*
                |--------------------------------------------------------------------------
                | Existing Follow-up Found
                |--------------------------------------------------------------------------
                */

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

                    ]);


                /*
                |--------------------------------------------------------------------------
                | No Existing Follow-up
                |--------------------------------------------------------------------------
                */

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
                | Update Lead Next Follow-up Time
                |--------------------------------------------------------------------------
                */

                $lead->update([

                    'next_follow_up_at' =>
                        $followUpAt,

                ]);
            }

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
