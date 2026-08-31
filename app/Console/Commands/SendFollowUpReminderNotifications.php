<?php

namespace App\Console\Commands;

use App\Models\FollowUp;
use App\Services\FirebasePushService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendFollowUpReminderNotifications extends Command
{
    /**
     * Command:
     *
     * Normal:
     * php artisan followups:send-reminder-notifications
     *
     * Specific follow-up testing:
     * php artisan followups:send-reminder-notifications --force-id=217
     */
    protected $signature =
        'followups:send-reminder-notifications
        {--force-id= : Send reminder for a specific follow-up ID}';

    protected $description =
        'Send Firebase notifications for due follow-ups';

    /**
     * Command execute करें।
     */
    public function handle(
        FirebasePushService $firebase
    ): int {
        $currentTime = now();

        $forceId = $this->option('force-id');

        /*
        |--------------------------------------------------------------------------
        | Command Start Log
        |--------------------------------------------------------------------------
        */

        $this->info(
            'Command started at: '
            . $currentTime->toDateTimeString()
            . ' | Timezone: '
            . config('app.timezone')
        );

        Log::info(
            'FOLLOW-UP REMINDER COMMAND STARTED',
            [
                'current_time' =>
                    $currentTime->toDateTimeString(),

                'timezone' =>
                    config('app.timezone'),

                'force_id' =>
                    $forceId,
            ]
        );

        try {
            /*
            |--------------------------------------------------------------------------
            | Reminder Window
            |--------------------------------------------------------------------------
            |
            | Follow-up के scheduled time से 5 minute पहले notification जाएगा।
            |
            | Example:
            | Scheduled: 04:00 PM
            | Notification: 03:55 PM से eligible
            |
            | पुराने overdue pending follow-ups भी eligible रहेंगे।
            |
            */

            $notificationUntil =
                $currentTime
                    ->copy()
                    ->addMinutes(5);

            /*
            |--------------------------------------------------------------------------
            | Base Follow-up Query
            |--------------------------------------------------------------------------
            */

            $query = FollowUp::query()
                ->with([
                    'lead',
                    'assignedUser',
                ])
                ->where(
                    'status',
                    'pending'
                )
                ->whereNotNull(
                    'scheduled_at'
                )
                ->whereNull(
                    'reminder_notified_at'
                );

            /*
            |--------------------------------------------------------------------------
            | Force Test अथवा Normal Scheduled Query
            |--------------------------------------------------------------------------
            */

            if (
                $forceId !== null
                && $forceId !== ''
            ) {
                $query->whereKey(
                    (int) $forceId
                );
            } else {
                $query->where(
                    'scheduled_at',
                    '<=',
                    $notificationUntil
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Eligible Count
            |--------------------------------------------------------------------------
            */

            $eligibleCount =
                (clone $query)->count();

            $this->info(
                "Eligible follow-ups: {$eligibleCount}"
            );

            Log::info(
                'FOLLOW-UP REMINDER ELIGIBLE COUNT',
                [
                    'eligible_count' =>
                        $eligibleCount,

                    'current_time' =>
                        $currentTime
                            ->toDateTimeString(),

                    'notification_until' =>
                        $notificationUntil
                            ->toDateTimeString(),

                    'force_id' =>
                        $forceId,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Nothing Found
            |--------------------------------------------------------------------------
            */

            if ($eligibleCount === 0) {
                $this->warn(
                    'No eligible pending follow-up found.'
                );

                Log::warning(
                    'NO ELIGIBLE FOLLOW-UP FOUND',
                    [
                        'status' =>
                            'pending',

                        'reminder_notified_at' =>
                            null,

                        'scheduled_at_before' =>
                            $notificationUntil
                                ->toDateTimeString(),

                        'force_id' =>
                            $forceId,
                    ]
                );

                return self::SUCCESS;
            }

            /*
            |--------------------------------------------------------------------------
            | Process Follow-ups
            |--------------------------------------------------------------------------
            */

            $query
                ->orderBy('id')
                ->chunkById(
                    100,
                    function (
                        $followUps
                    ) use (
                        $firebase,
                        $currentTime
                    ) {
                        foreach (
                            $followUps
                            as
                            $followUp
                        ) {
                            $this->sendReminder(
                                followUp:
                                    $followUp,

                                firebase:
                                    $firebase,

                                currentTime:
                                    $currentTime
                            );
                        }
                    }
                );

            /*
            |--------------------------------------------------------------------------
            | Command Complete
            |--------------------------------------------------------------------------
            */

            $this->info(
                'Follow-up reminder command finished.'
            );

            Log::info(
                'FOLLOW-UP REMINDER COMMAND FINISHED',
                [
                    'finished_at' =>
                        now()->toDateTimeString(),
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            /*
            |--------------------------------------------------------------------------
            | Complete Error Log
            |--------------------------------------------------------------------------
            */

            $this->error(
                'Command failed: '
                . $exception->getMessage()
            );

            Log::error(
                'FOLLOW-UP REMINDER COMMAND FAILED',
                [
                    'exception' =>
                        get_class($exception),

                    'message' =>
                        $exception->getMessage(),

                    'file' =>
                        $exception->getFile(),

                    'line' =>
                        $exception->getLine(),

                    'trace' =>
                        $exception
                            ->getTraceAsString(),
                ]
            );

            return self::FAILURE;
        }
    }

    /**
     * Single follow-up reminder भेजें।
     *
     * CarbonInterface इस्तेमाल किया गया है क्योंकि Laravel 13 में
     * now() CarbonImmutable return कर सकता है।
     */
    private function sendReminder(
        FollowUp $followUp,
        FirebasePushService $firebase,
        CarbonInterface $currentTime
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Follow-up Processing Information
        |--------------------------------------------------------------------------
        */

        $this->line(
            "Processing follow-up #{$followUp->id}"
        );

        Log::info(
            'PROCESSING FOLLOW-UP REMINDER',
            [
                'follow_up_id' =>
                    $followUp->id,

                'lead_id' =>
                    $followUp->lead_id,

                'assigned_to' =>
                    $followUp->assigned_to,

                'status' =>
                    $followUp->status,

                'scheduled_at' =>
                    $followUp->scheduled_at
                        ? $followUp
                            ->scheduled_at
                            ->toDateTimeString()
                        : null,

                'reminder_notified_at' =>
                    $followUp
                        ->reminder_notified_at
                        ? $followUp
                            ->reminder_notified_at
                            ->toDateTimeString()
                        : null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Recheck Database Record
        |--------------------------------------------------------------------------
        |
        | अगर किसी दूसरी process ने notification भेज दिया है तो दोबारा
        | notification नहीं जाएगा।
        |
        */

        $freshFollowUp = FollowUp::query()
            ->with([
                'lead',
                'assignedUser',
            ])
            ->whereKey(
                $followUp->id
            )
            ->where(
                'status',
                'pending'
            )
            ->whereNull(
                'reminder_notified_at'
            )
            ->first();

        if (!$freshFollowUp) {
            $this->warn(
                "Follow-up #{$followUp->id} "
                . 'was already processed or cancelled.'
            );

            Log::warning(
                'FOLLOW-UP SKIPPED AFTER RECHECK',
                [
                    'follow_up_id' =>
                        $followUp->id,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Assigned User Check
        |--------------------------------------------------------------------------
        */

        if (empty($freshFollowUp->assigned_to)) {
            $this->error(
                "Follow-up #{$freshFollowUp->id}: "
                . 'assigned_to is empty.'
            );

            Log::error(
                'FOLLOW-UP HAS NO ASSIGNED USER',
                [
                    'follow_up_id' =>
                        $freshFollowUp->id,

                    'lead_id' =>
                        $freshFollowUp->lead_id,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Assigned User Exists Check
        |--------------------------------------------------------------------------
        */

        if (!$freshFollowUp->assignedUser) {
            $this->error(
                "Follow-up #{$freshFollowUp->id}: "
                . 'assigned user does not exist.'
            );

            Log::error(
                'FOLLOW-UP ASSIGNED USER NOT FOUND',
                [
                    'follow_up_id' =>
                        $freshFollowUp->id,

                    'assigned_to' =>
                        $freshFollowUp
                            ->assigned_to,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Scheduled Date Parse
        |--------------------------------------------------------------------------
        */

        $scheduledAt =
            $freshFollowUp->scheduled_at;

        if (
            !$scheduledAt instanceof
            CarbonInterface
        ) {
            $scheduledAt = Carbon::parse(
                $freshFollowUp->scheduled_at,
                config(
                    'app.timezone',
                    'Asia/Kolkata'
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lead Information
        |--------------------------------------------------------------------------
        */

        $leadName =
            $freshFollowUp->lead?->name
            ?: 'Customer';

        $mobile =
            $freshFollowUp->lead?->mobile
            ?: $freshFollowUp
                ->lead
                ?->alternate_mobile
            ?: '';

        /*
        |--------------------------------------------------------------------------
        | Overdue Check
        |--------------------------------------------------------------------------
        */

        $isOverdue =
            $scheduledAt->lt(
                $currentTime
            );

        $scheduledTime =
            $scheduledAt->format(
                'd M Y, h:i A'
            );

        /*
        |--------------------------------------------------------------------------
        | Notification Title
        |--------------------------------------------------------------------------
        */

        $title = $isOverdue
            ? "Overdue Follow-up: {$leadName}"
            : "Follow-up Reminder: {$leadName}";

        /*
        |--------------------------------------------------------------------------
        | Notification Body
        |--------------------------------------------------------------------------
        */

        if ($isOverdue) {
            $body =
                "Follow-up overdue hai. "
                . "Customer: {$leadName}";
        } else {
            $body =
                "Follow-up {$scheduledTime} "
                . 'par scheduled hai.';
        }

        if ($mobile !== '') {
            $body .= " Mobile: {$mobile}";
        }

        /*
        |--------------------------------------------------------------------------
        | Firebase Notification
        |--------------------------------------------------------------------------
        */

        $result = $firebase->sendToUser(
            userId:
                (int) $freshFollowUp
                    ->assigned_to,

            title:
                $title,

            body:
                $body,

            data: [
                'type' =>
                    'follow_up_reminder',

                'action' =>
                    'open_follow_up',

                'screen' =>
                    'follow_up_detail',

                'follow_up_id' =>
                    (string) $freshFollowUp
                        ->id,

                'lead_id' =>
                    (string) (
                        $freshFollowUp
                            ->lead_id
                        ?? ''
                    ),

                'lead_name' =>
                    (string) $leadName,

                'mobile' =>
                    (string) $mobile,

                'scheduled_at' =>
                    $scheduledAt
                        ->toIso8601String(),

                'is_overdue' =>
                    $isOverdue
                        ? '1'
                        : '0',

                'click_action' =>
                    'FLUTTER_NOTIFICATION_CLICK',

                'sent_at' =>
                    now()->toIso8601String(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Firebase Result Log
        |--------------------------------------------------------------------------
        */

        Log::info(
            'FOLLOW-UP FIREBASE RESULT',
            [
                'follow_up_id' =>
                    $freshFollowUp->id,

                'assigned_to' =>
                    $freshFollowUp
                        ->assigned_to,

                'total_tokens' =>
                    $result['total_tokens']
                    ?? 0,

                'sent' =>
                    $result['sent']
                    ?? 0,

                'failed' =>
                    $result['failed']
                    ?? 0,

                'result' =>
                    $result,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Notification Successfully Sent
        |--------------------------------------------------------------------------
        */

        if (
            (int) (
                $result['sent']
                ?? 0
            ) > 0
        ) {
            /*
             * Conditional update duplicate notification से बचाता है।
             */
            $updated = FollowUp::query()
                ->whereKey(
                    $freshFollowUp->id
                )
                ->whereNull(
                    'reminder_notified_at'
                )
                ->update([
                    'reminder_notified_at' =>
                        now(),
                ]);

            if ($updated === 1) {
                $this->info(
                    'Reminder sent for follow-up '
                    . "#{$freshFollowUp->id}"
                );

                Log::info(
                    'FOLLOW-UP REMINDER SENT SUCCESSFULLY',
                    [
                        'follow_up_id' =>
                            $freshFollowUp->id,

                        'assigned_to' =>
                            $freshFollowUp
                                ->assigned_to,

                        'sent_devices' =>
                            $result['sent'],

                        'notified_at' =>
                            now()
                                ->toDateTimeString(),
                    ]
                );
            } else {
                $this->warn(
                    "Follow-up #{$freshFollowUp->id}: "
                    . 'notification sent but record '
                    . 'was already marked.'
                );
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Notification Failed
        |--------------------------------------------------------------------------
        */

        $totalTokens =
            (int) (
                $result['total_tokens']
                ?? 0
            );

        $failedTokens =
            (int) (
                $result['failed']
                ?? 0
            );

        $this->error(
            "Follow-up #{$freshFollowUp->id}: "
            . 'notification failed. '
            . "Total tokens: {$totalTokens}, "
            . "Failed: {$failedTokens}"
        );

        Log::error(
            'FOLLOW-UP REMINDER NOT DELIVERED',
            [
                'follow_up_id' =>
                    $freshFollowUp->id,

                'lead_id' =>
                    $freshFollowUp->lead_id,

                'assigned_to' =>
                    $freshFollowUp
                        ->assigned_to,

                'result' =>
                    $result,
            ]
        );
    }
}
