<?php

namespace App\Console\Commands;

use App\Models\FollowUp;
use App\Services\FirebasePushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendFollowUpReminderNotifications extends Command
{
    protected $signature =
        'followups:send-reminder-notifications';

    protected $description =
        'Send Firebase notifications for due follow-ups';

    public function handle(
        FirebasePushService $firebase
    ): int {
        /*
         * Agar command overlap ho जाए तो duplicate push न जाए।
         */
        $lock = Cache::lock(
            'followups:send-reminder-notifications',
            55
        );

        if (!$lock->get()) {
            $this->info(
                'Another reminder process is already running.'
            );

            return self::SUCCESS;
        }

        try {
            $now = now();

            /*
             * Scheduled time से 1 minute पहले reminder.
             *
             * overdue pending follow-ups जिनका notification नहीं गया,
             * उनको भी notification मिलेगा।
             */
            $notificationUntil = $now
                ->copy()
                ->addMinute();

            FollowUp::query()
                ->with([
                    'lead:id,name,mobile,alternate_mobile',
                    'assignedUser:id,name,email',
                ])
                ->where('status', 'pending')
                ->whereNotNull('scheduled_at')
                ->whereNull('reminder_notified_at')
                ->where('scheduled_at', '<=', $notificationUntil)
                ->orderBy('id')
                ->chunkById(
                    100,
                    function ($followUps) use (
                        $firebase,
                        $now
                    ) {
                        foreach ($followUps as $followUp) {
                            $this->sendReminder(
                                $followUp,
                                $firebase,
                                $now
                            );
                        }
                    }
                );

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    private function sendReminder(
        FollowUp $followUp,
        FirebasePushService $firebase,
        $now
    ): void {
        /*
         * Notification भेजने से ठीक पहले database record lock.
         */
        $lockedFollowUp = FollowUp::query()
            ->whereKey($followUp->id)
            ->where('status', 'pending')
            ->whereNull('reminder_notified_at')
            ->first();

        if (!$lockedFollowUp) {
            return;
        }

        $leadName =
            $followUp->lead?->name
            ?? 'Customer';

        $mobile =
            $followUp->lead?->mobile
            ?? $followUp->lead?->alternate_mobile
            ?? '';

        $isOverdue =
            $followUp->scheduled_at->lt($now);

        $title = $isOverdue
            ? "Overdue Follow-up: {$leadName}"
            : "Follow-up Reminder: {$leadName}";

        $scheduledTime =
            $followUp->scheduled_at
                ->format('d M Y, h:i A');

        $body = $isOverdue
            ? "Follow-up overdue hai. Customer: {$leadName}"
            : "Follow-up {$scheduledTime} par scheduled hai.";

        if ($mobile) {
            $body .= " Mobile: {$mobile}";
        }

        $result = $firebase->sendToUser(
            userId: (int) $followUp->assigned_to,

            title: $title,

            body: $body,

            data: [
                'type' => 'follow_up_reminder',

                'follow_up_id' =>
                    (string) $followUp->id,

                'lead_id' =>
                    (string) ($followUp->lead_id ?? ''),

                'lead_name' => $leadName,

                'mobile' => $mobile,

                'scheduled_at' =>
                    $followUp->scheduled_at
                        ->toIso8601String(),

                'is_overdue' =>
                    $isOverdue ? '1' : '0',

                'screen' => 'follow_up_detail',

                'click_action' =>
                    'FLUTTER_NOTIFICATION_CLICK',
            ]
        );

        /*
         * कम से कम एक device पर notification send होने के बाद mark करें।
         */
        if (($result['sent'] ?? 0) > 0) {
            $lockedFollowUp->update([
                'reminder_notified_at' => now(),
            ]);

            $this->info(
                "Reminder sent for follow-up #{$followUp->id}"
            );

            return;
        }

        Log::warning(
            'Follow-up reminder was not delivered.',
            [
                'follow_up_id' => $followUp->id,
                'assigned_to' => $followUp->assigned_to,
                'result' => $result,
            ]
        );
    }
}
