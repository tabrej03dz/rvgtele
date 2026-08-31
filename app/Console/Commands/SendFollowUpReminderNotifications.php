<?php

namespace App\Console\Commands;

use App\Models\FollowUp;
use App\Services\FirebasePushService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendFollowUpReminderNotifications extends Command
{
    protected $signature =
        'followups:send-reminder-notifications {--force-id=}';

    protected $description =
        'Send Firebase notifications for due follow-ups';

    public function handle(
        FirebasePushService $firebase
    ): int {
        Log::info('FOLLOW-UP REMINDER COMMAND STARTED', [
            'time' => now()->toDateTimeString(),
            'timezone' => config('app.timezone'),
            'force_id' => $this->option('force-id'),
        ]);

        $this->info(
            'Command started at: '
            . now()->toDateTimeString()
            . ' | Timezone: '
            . config('app.timezone')
        );

        try {
            $now = now();

            /*
            |--------------------------------------------------------------------------
            | Notification Window
            |--------------------------------------------------------------------------
            |
            | Follow-up के समय से 5 मिनट पहले notification भेजेगा।
            | पुराने overdue pending follow-ups भी शामिल होंगे।
            |
            */

            $notificationUntil = $now
                ->copy()
                ->addMinutes(5);

            $query = FollowUp::query()
                ->with([
                    'lead',
                    'assignedUser',
                ])
                ->where('status', 'pending')
                ->whereNotNull('scheduled_at')
                ->whereNull('reminder_notified_at');

            /*
            |--------------------------------------------------------------------------
            | Manual Testing
            |--------------------------------------------------------------------------
            */

            if ($this->option('force-id')) {
                $query->where(
                    'id',
                    (int) $this->option('force-id')
                );
            } else {
                $query->where(
                    'scheduled_at',
                    '<=',
                    $notificationUntil
                );
            }

            $eligibleCount =
                (clone $query)->count();

            $this->info(
                "Eligible follow-ups: {$eligibleCount}"
            );

            Log::info('FOLLOW-UP REMINDER ELIGIBLE COUNT', [
                'eligible_count' => $eligibleCount,
                'current_time' =>
                    $now->toDateTimeString(),
                'notification_until' =>
                    $notificationUntil->toDateTimeString(),
            ]);

            if ($eligibleCount === 0) {
                $this->warn(
                    'No eligible pending follow-up found.'
                );

                Log::warning(
                    'NO ELIGIBLE FOLLOW-UP FOUND',
                    [
                        'conditions' => [
                            'status' => 'pending',
                            'reminder_notified_at' => null,
                            'scheduled_at_before' =>
                                $notificationUntil
                                    ->toDateTimeString(),
                        ],
                    ]
                );

                return self::SUCCESS;
            }

            $query
                ->orderBy('id')
                ->chunkById(
                    100,
                    function ($followUps) use (
                        $firebase,
                        $now
                    ) {
                        foreach ($followUps as $followUp) {
                            $this->sendReminder(
                                followUp: $followUp,
                                firebase: $firebase,
                                now: $now
                            );
                        }
                    }
                );

            Log::info(
                'FOLLOW-UP REMINDER COMMAND FINISHED',
                [
                    'time' => now()->toDateTimeString(),
                ]
            );

            $this->info(
                'Follow-up reminder command finished.'
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
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
                        $exception->getTraceAsString(),
                ]
            );

            $this->error(
                'Command failed: '
                . $exception->getMessage()
            );

            return self::FAILURE;
        }
    }

    private function sendReminder(
        FollowUp $followUp,
        FirebasePushService $firebase,
        Carbon $now
    ): void {
        $this->line(
            "Checking follow-up #{$followUp->id}"
        );

        Log::info('PROCESSING FOLLOW-UP', [
            'follow_up_id' => $followUp->id,
            'lead_id' => $followUp->lead_id,
            'assigned_to' => $followUp->assigned_to,
            'status' => $followUp->status,
            'scheduled_at' =>
                optional($followUp->scheduled_at)
                    ->toDateTimeString(),
            'reminder_notified_at' =>
                optional($followUp->reminder_notified_at)
                    ->toDateTimeString(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Follow-up को दोबारा check करें
        |--------------------------------------------------------------------------
        */

        $lockedFollowUp = FollowUp::query()
            ->whereKey($followUp->id)
            ->where('status', 'pending')
            ->whereNull('reminder_notified_at')
            ->first();

        if (!$lockedFollowUp) {
            Log::warning(
                'FOLLOW-UP SKIPPED AFTER RECHECK',
                [
                    'follow_up_id' => $followUp->id,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Assigned User Check
        |--------------------------------------------------------------------------
        */

        if (!$followUp->assigned_to) {
            Log::warning(
                'FOLLOW-UP HAS NO ASSIGNED USER',
                [
                    'follow_up_id' => $followUp->id,
                ]
            );

            $this->warn(
                "Follow-up #{$followUp->id}: "
                . 'assigned_to is empty.'
            );

            return;
        }

        if (!$followUp->assignedUser) {
            Log::warning(
                'FOLLOW-UP ASSIGNED USER NOT FOUND',
                [
                    'follow_up_id' => $followUp->id,
                    'assigned_to' =>
                        $followUp->assigned_to,
                ]
            );

            $this->warn(
                "Follow-up #{$followUp->id}: "
                . 'assigned user not found.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Lead Details
        |--------------------------------------------------------------------------
        */

        $leadName =
            $followUp->lead?->name
            ?: 'Customer';

        $mobile =
            $followUp->lead?->mobile
            ?: $followUp->lead?->alternate_mobile
            ?: '';

        /*
        |--------------------------------------------------------------------------
        | Scheduled Time
        |--------------------------------------------------------------------------
        */

        $scheduledAt =
            $followUp->scheduled_at instanceof Carbon
                ? $followUp->scheduled_at
                : Carbon::parse(
                    $followUp->scheduled_at,
                    config('app.timezone')
                );

        $isOverdue =
            $scheduledAt->lt($now);

        $scheduledTime =
            $scheduledAt->format(
                'd M Y, h:i A'
            );

        $title = $isOverdue
            ? "Overdue Follow-up: {$leadName}"
            : "Follow-up Reminder: {$leadName}";

        $body = $isOverdue
            ? "Follow-up overdue hai. Customer: {$leadName}"
            : "Follow-up {$scheduledTime} par scheduled hai.";

        if ($mobile !== '') {
            $body .= " Mobile: {$mobile}";
        }

        /*
        |--------------------------------------------------------------------------
        | Send Firebase Notification
        |--------------------------------------------------------------------------
        */

        $result = $firebase->sendToUser(
            userId: (int) $followUp->assigned_to,

            title: $title,

            body: $body,

            data: [
                'type' =>
                    'follow_up_reminder',

                'action' =>
                    'open_follow_up',

                'follow_up_id' =>
                    (string) $followUp->id,

                'lead_id' =>
                    (string) (
                        $followUp->lead_id ?? ''
                    ),

                'lead_name' =>
                    (string) $leadName,

                'mobile' =>
                    (string) $mobile,

                'scheduled_at' =>
                    $scheduledAt
                        ->toIso8601String(),

                'is_overdue' =>
                    $isOverdue ? '1' : '0',

                'screen' =>
                    'follow_up_detail',

                'click_action' =>
                    'FLUTTER_NOTIFICATION_CLICK',

                'sent_at' =>
                    now()->toIso8601String(),
            ]
        );

        Log::info(
            'FOLLOW-UP FIREBASE RESULT',
            [
                'follow_up_id' => $followUp->id,
                'assigned_to' =>
                    $followUp->assigned_to,
                'result' => $result,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Mark Notification Sent
        |--------------------------------------------------------------------------
        */

        if (($result['sent'] ?? 0) > 0) {
            $lockedFollowUp->update([
                'reminder_notified_at' => now(),
            ]);

            $this->info(
                "Reminder sent for follow-up "
                . "#{$followUp->id}"
            );

            Log::info(
                'FOLLOW-UP REMINDER SENT SUCCESSFULLY',
                [
                    'follow_up_id' =>
                        $followUp->id,

                    'assigned_to' =>
                        $followUp->assigned_to,

                    'sent_devices' =>
                        $result['sent'] ?? 0,
                ]
            );

            return;
        }

        $this->error(
            "Follow-up #{$followUp->id}: "
            . 'notification failed. '
            . 'Total tokens: '
            . ($result['total_tokens'] ?? 0)
        );

        Log::error(
            'FOLLOW-UP REMINDER NOT DELIVERED',
            [
                'follow_up_id' =>
                    $followUp->id,

                'assigned_to' =>
                    $followUp->assigned_to,

                'result' =>
                    $result,
            ]
        );
    }
}
