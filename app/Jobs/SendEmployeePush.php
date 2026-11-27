<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Services\FirebasePushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SendEmployeePush implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public int $notificationId,
        public int $deviceId,
        public int $userId,
    ) {}

    public function handle(FirebasePushService $firebase): void
    {
        $device = DeviceToken::query()
            ->whereKey($this->deviceId)
            ->where('user_id', $this->userId)
            ->whereIn('platform', ['android', 'ios'])
            ->first();

        if (!$device || blank($device->token)) {
            return;
        }

        $notification = DB::table('employee_push_notifications as n')
            ->join('employee_push_recipients as r', 'r.notification_id', '=', 'n.id')
            ->where('n.id', $this->notificationId)
            ->where('r.user_id', $this->userId)
            ->first(['n.message', 'n.image_path', 'n.video_path']);

        if (!$notification) {
            return;
        }

        $body = $notification->message;

        if ($body === '') {
            $body = match (true) {
                $notification->image_path && $notification->video_path =>
                    'New image and video. Tap to open.',
                (bool) $notification->video_path => 'New video. Tap to open.',
                default => 'New image. Tap to open.',
            };
        }

        $result = $firebase->sendToToken(
            deviceToken: $device->token,
            title: 'TeleCRM',
            body: $body,
            data: [
                'type' => 'employee_notification',
                'notification_id' => (string) $this->notificationId,
            ],
            androidChannel: 'employee_notifications',
            androidClickAction: null,
        );

        if (!($result['success'] ?? false) && !($result['invalid_token'] ?? false)) {
            throw new RuntimeException(sprintf(
                'Employee notification %d failed for device %d. FCM status: %s.',
                $this->notificationId,
                $this->deviceId,
                $result['status'] ?? 'unknown',
            ));
        }
    }
}