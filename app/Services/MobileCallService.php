<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use RuntimeException;

class MobileCallService
{
    protected $messaging;

    public function __construct()
    {
        /*
        |--------------------------------------------------------------------------
        | Firebase Credentials
        |--------------------------------------------------------------------------
        */

        $credentials = config(
            'services.firebase.credentials'
        );

        if (!$credentials) {
            throw new RuntimeException(
                'Firebase credentials path is not configured.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Relative Path Support
        |--------------------------------------------------------------------------
        */

        if (!str_starts_with($credentials, '/') &&
            !preg_match('/^[A-Za-z]:\\\\/', $credentials)) {

            $credentials = base_path($credentials);
        }

        if (!file_exists($credentials)) {
            throw new RuntimeException(
                'Firebase service account file not found: ' . $credentials
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create Firebase Messaging
        |--------------------------------------------------------------------------
        */

        $factory = (new Factory)
            ->withServiceAccount($credentials);

        $projectId = config(
            'services.firebase.project_id'
        );

        if ($projectId) {
            $factory = $factory->withProjectId(
                $projectId
            );
        }

        $this->messaging =
            $factory->createMessaging();
    }

    public function send(
        User $user,
        Lead $lead
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Lead Mobile
        |--------------------------------------------------------------------------
        */

        $mobile = trim(
            (string) $lead->mobile
        );

        if ($mobile === '') {
            throw new RuntimeException(
                'This lead does not have a mobile number.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Get User Device
        |--------------------------------------------------------------------------
        */

        $device = DeviceToken::query()
            ->where(
                'user_id',
                $user->id
            )
            ->whereNotNull('token')
            ->where(
                'token',
                '<>',
                ''
            )
            ->orderByRaw(
                'last_used_at IS NULL'
            )
            ->orderByDesc(
                'last_used_at'
            )
            ->orderByDesc('id')
            ->first();

        if (!$device) {
            throw new RuntimeException(
                'No mobile device is registered for this user.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Unique Request
        |--------------------------------------------------------------------------
        */

        $requestId =
            (string) Str::uuid();

        /*
        |--------------------------------------------------------------------------
        | Firebase Message
        |--------------------------------------------------------------------------
        */

        $message = CloudMessage::new()
            ->withToken(
                $device->token
            )
            ->withNotification(
                Notification::create(
                    'Call ' . $lead->name,
                    $mobile
                )
            )
            ->withData([
                'type' =>
                    'call_lead',

                'action' =>
                    'open_lead_and_dial',

                'lead_id' =>
                    (string) $lead->id,

                'lead_name' =>
                    (string) $lead->name,

                'mobile' =>
                    $mobile,

                'request_id' =>
                    $requestId,

                'sent_at' =>
                    now()->toIso8601String(),
            ]);

        /*
        |--------------------------------------------------------------------------
        | Send FCM
        |--------------------------------------------------------------------------
        */

        $this->messaging->send(
            $message
        );

        /*
        |--------------------------------------------------------------------------
        | Device Last Used
        |--------------------------------------------------------------------------
        */

        $device->update([
            'last_used_at' => now(),
        ]);

        return [
            'device_id' =>
                $device->id,

            'platform' =>
                $device->platform,

            'mobile' =>
                $mobile,

            'request_id' =>
                $requestId,
        ];
    }
}
