<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallLog;
use App\Models\CallingSetting;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeviceCallLogController extends Controller
{
    public function store(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'mobile' => [
                'required',
                'string',
                'max:30',
            ],

            'direction' => [
                'required',
                Rule::in(['outgoing']),
            ],

            'started_at' => [
                'required',
                'date_format:Y-m-d H:i:s',
            ],

            'ended_at' => [
                'nullable',
                'date_format:Y-m-d H:i:s',
                'after_or_equal:started_at',
            ],

            'duration_seconds' => [
                'required',
                'integer',
                'min:0',
                'max:86400',
            ],

            'sim_slot' => [
                'required',
                'integer',
                'min:0',
                'max:2',
            ],

            'subscription_id' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'carrier_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'phone_account_id' => [
                'nullable',
                'string',
                'max:191',
            ],

            'device_call_key' => [
                'required',
                'string',
                'max:191',
            ],
        ]);

        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Company ownership verification
        |--------------------------------------------------------------------------
        */

        if (
            !$user->company_id ||
            (int) $lead->company_id !== (int) $user->company_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Lead does not belong to your company.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Employee assignment verification
        |--------------------------------------------------------------------------
        |
        | अगर आपके leads table में assigned_to की जगह assigned_user_id है,
        | तो यहां column name बदल दें।
        |
        */

        if ((int) $lead->assigned_to !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'This lead is not assigned to the logged-in employee.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Mobile verification
        |--------------------------------------------------------------------------
        */

        if (!$this->mobileNumbersMatch(
            $validated['mobile'],
            (string) $lead->mobile
        )) {
            throw ValidationException::withMessages([
                'mobile' => [
                    'Request mobile does not match the assigned lead mobile.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Saved Work SIM verification
        |--------------------------------------------------------------------------
        */

        $callingSetting = CallingSetting::query()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$callingSetting) {
            return response()->json([
                'success' => false,
                'message' => 'Please select and save the Work SIM first.',
            ], 422);
        }

        if (
            (int) $callingSetting->sim_slot !==
            (int) $validated['sim_slot']
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Call was not made using the selected Work SIM.',
                'errors' => [
                    'sim_slot' => [
                        'Selected Work SIM slot does not match.',
                    ],
                ],
            ], 422);
        }

        if (
            $callingSetting->subscription_id !== null &&
            isset($validated['subscription_id']) &&
            (int) $callingSetting->subscription_id !==
            (int) $validated['subscription_id']
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Call subscription does not match the selected Work SIM.',
            ], 422);
        }

        if (
            filled($callingSetting->phone_account_id) &&
            filled($validated['phone_account_id'] ?? null) &&
            $callingSetting->phone_account_id !==
            $validated['phone_account_id']
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Phone account does not match the selected Work SIM.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate check
        |--------------------------------------------------------------------------
        */

        $existingCallLog = CallLog::query()
            ->where('device_call_key', $validated['device_call_key'])
            ->first();

        if ($existingCallLog) {
            if (
                (int) $existingCallLog->company_id ===
                    (int) $user->company_id &&
                (int) $existingCallLog->user_id ===
                    (int) $user->id &&
                (int) $existingCallLog->lead_id ===
                    (int) $lead->id
            ) {
                return response()->json([
                    'success' => true,
                    'duplicate' => true,
                    'message' => 'This device call was already synced.',
                    'data' => $this->formatCallLog($existingCallLog),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'This device call key has already been used.',
            ], 409);
        }

        try {
            $callLog = DB::transaction(function () use (
                $validated,
                $user,
                $lead
            ) {
                return CallLog::query()->create([
                    'company_id' => $user->company_id,
                    'user_id' => $user->id,
                    'lead_id' => $lead->id,
                    'mobile' => $this->normalizeMobile(
                        $validated['mobile']
                    ),
                    'direction' => 'outgoing',
                    'started_at' => Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        $validated['started_at'],
                        config('app.timezone')
                    ),
                    'ended_at' => isset($validated['ended_at'])
                        ? Carbon::createFromFormat(
                            'Y-m-d H:i:s',
                            $validated['ended_at'],
                            config('app.timezone')
                        )
                        : null,
                    'duration_seconds' =>
                        $validated['duration_seconds'],
                    'sim_slot' => $validated['sim_slot'],
                    'subscription_id' =>
                        $validated['subscription_id'] ?? null,
                    'carrier_name' =>
                        $validated['carrier_name'] ?? null,
                    'phone_account_id' =>
                        $validated['phone_account_id'] ?? null,
                    'device_call_key' =>
                        $validated['device_call_key'],
                ]);
            });
        } catch (QueryException $exception) {
            /*
             * दो simultaneous requests में unique constraint race होने पर।
             */
            if ($this->isUniqueConstraintException($exception)) {
                $existingCallLog = CallLog::query()
                    ->where(
                        'device_call_key',
                        $validated['device_call_key']
                    )
                    ->first();

                return response()->json([
                    'success' => true,
                    'duplicate' => true,
                    'message' => 'This device call was already synced.',
                    'data' => $existingCallLog
                        ? $this->formatCallLog($existingCallLog)
                        : null,
                ]);
            }

            throw $exception;
        }

        $callLog->load([
            'lead:id,name,mobile',
            'user:id,name',
            'disposition',
        ]);

        return response()->json([
            'success' => true,
            'duplicate' => false,
            'message' => 'Device call log synced successfully.',
            'data' => $this->formatCallLog($callLog),
        ], 201);
    }

    public function update(
        Request $request,
        CallLog $callLog
    ): JsonResponse {
        $validated = $request->validate([
            'remarks' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'call_disposition_id' => [
                'nullable',
                'integer',
                'exists:call_dispositions,id',
            ],

            'next_followup_at' => [
                'nullable',
                'date_format:Y-m-d H:i:s',
                'after:now',
            ],
        ]);

        $user = $request->user();

        if (
            (int) $callLog->company_id !==
            (int) $user->company_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot update this call log.',
            ], 403);
        }

        /*
         * Employee केवल अपना call log update कर सकता है।
         * Admin override चाहिए तो permission check यहां add कर सकते हैं।
         */
        if ((int) $callLog->user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can update only your own call log.',
            ], 403);
        }

        DB::transaction(function () use (
            $callLog,
            $validated
        ) {
            $callLog->update([
                'remarks' => array_key_exists('remarks', $validated)
                    ? $validated['remarks']
                    : $callLog->remarks,

                'call_disposition_id' => array_key_exists(
                    'call_disposition_id',
                    $validated
                )
                    ? $validated['call_disposition_id']
                    : $callLog->call_disposition_id,

                'next_followup_at' => array_key_exists(
                    'next_followup_at',
                    $validated
                )
                    ? $validated['next_followup_at']
                    : $callLog->next_followup_at,
            ]);

            /*
             * आपके existing FollowUp service/controller की logic यहां call करें।
             *
             * Example:
             *
             * if (!empty($validated['next_followup_at'])) {
             *     app(FollowUpService::class)->createFromCallLog(
             *         $callLog,
             *         $validated['next_followup_at']
             *     );
             * }
             */
        });

        $callLog->refresh()->load([
            'lead:id,name,mobile',
            'user:id,name',
            'disposition',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Call result updated successfully.',
            'data' => $this->formatCallLog($callLog),
        ]);
    }

    public function history(
        Request $request,
        Lead $lead
    ): JsonResponse {
        $user = $request->user();

        if (
            !$user->company_id ||
            (int) $lead->company_id !== (int) $user->company_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Lead does not belong to your company.',
            ], 403);
        }

        /*
         * Employee को केवल assigned lead की history मिलेगी।
         *
         * अगर admin/manager को team history दिखानी है तो यहां permission
         * या hierarchy condition add की जा सकती है।
         */
        if ((int) $lead->assigned_to !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'This lead is not assigned to you.',
            ], 403);
        }

        $perPage = min(
            max((int) $request->input('per_page', 20), 1),
            100
        );

        $callLogs = CallLog::query()
            ->with([
                'user:id,name',
                'disposition',
            ])
            ->where('company_id', $user->company_id)
            ->where('lead_id', $lead->id)
            ->whereNotNull('device_call_key')
            ->latest('started_at')
            ->paginate($perPage);

        $callLogs->getCollection()->transform(
            fn (CallLog $callLog) =>
                $this->formatCallLog($callLog)
        );

        return response()->json([
            'success' => true,
            'message' => 'Lead call history fetched successfully.',
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'mobile' => $lead->mobile,
            ],
            'data' => $callLogs,
        ]);
    }

    private function normalizeMobile(?string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile);

        if (strlen($digits) > 10) {
            return substr($digits, -10);
        }

        return $digits;
    }

    private function mobileNumbersMatch(
        ?string $requestMobile,
        ?string $leadMobile
    ): bool {
        $requestNumber = $this->normalizeMobile($requestMobile);
        $leadNumber = $this->normalizeMobile($leadMobile);

        return $requestNumber !== '' &&
            $leadNumber !== '' &&
            hash_equals($leadNumber, $requestNumber);
    }

    private function isUniqueConstraintException(
        QueryException $exception
    ): bool {
        return in_array(
            (string) $exception->getCode(),
            ['23000', '23505'],
            true
        );
    }

    private function formatCallLog(CallLog $callLog): array
    {
        return [
            'id' => $callLog->id,
            'lead_id' => $callLog->lead_id,
            'user_id' => $callLog->user_id,
            'employee_name' => $callLog->user?->name,
            'mobile' => $callLog->mobile,
            'direction' => $callLog->direction,

            'started_at' => $callLog->started_at?->format(
                'Y-m-d H:i:s'
            ),

            'ended_at' => $callLog->ended_at?->format(
                'Y-m-d H:i:s'
            ),

            'duration_seconds' => $callLog->duration_seconds,
            'duration_formatted' => $this->formatDuration(
                (int) $callLog->duration_seconds
            ),

            'sim_slot' => $callLog->sim_slot,
            'subscription_id' => $callLog->subscription_id,
            'carrier_name' => $callLog->carrier_name,
            'phone_account_id' => $callLog->phone_account_id,

            'remarks' => $callLog->remarks,

            'call_disposition' => $callLog->disposition
                ? [
                    'id' => $callLog->disposition->id,
                    'name' => $callLog->disposition->name,
                ]
                : null,

            'next_followup_at' =>
                $callLog->next_followup_at?->format(
                    'Y-m-d H:i:s'
                ),

            'recording_url' => $callLog->recording_url,
            'device_call_key' => $callLog->device_call_key,
            'created_at' => $callLog->created_at?->format(
                'Y-m-d H:i:s'
            ),
        ];
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf(
                '%02d:%02d:%02d',
                $hours,
                $minutes,
                $remainingSeconds
            );
        }

        return sprintf(
            '%02d:%02d',
            $minutes,
            $remainingSeconds
        );
    }
}
