<?php

namespace App\Services;

use App\Models\DeviceToken;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FirebasePushService
{
    private const FCM_SCOPE =
        'https://www.googleapis.com/auth/firebase.messaging';

    /**
     * Single device पर notification भेजें।
     */
    public function sendToToken(
        string $deviceToken,
        string $title,
        string $body,
        array $data = []
    ): array {
        try {
            $projectId = $this->projectId();
            $accessToken = $this->accessToken();

            $data = collect($data)
                ->mapWithKeys(function ($value, $key) {
                    if (is_bool($value)) {
                        $value = $value ? '1' : '0';
                    } elseif (is_array($value) || is_object($value)) {
                        $value = json_encode($value);
                    } elseif ($value === null) {
                        $value = '';
                    }

                    return [
                        (string) $key => (string) $value,
                    ];
                })
                ->all();

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(30)
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    [
                        'message' => [
                            'token' => $deviceToken,

                            'notification' => [
                                'title' => $title,
                                'body' => $body,
                            ],

                            'data' => $data,

                            'android' => [
                                'priority' => 'high',

                                'notification' => [
                                    'channel_id' =>
                                        'follow_up_reminders',

                                    'sound' => 'default',

                                    'click_action' =>
                                        'FLUTTER_NOTIFICATION_CLICK',

                                    'default_sound' => true,

                                    'default_vibrate_timings' => true,
                                ],
                            ],

                            'apns' => [
                                'headers' => [
                                    'apns-priority' => '10',
                                ],

                                'payload' => [
                                    'aps' => [
                                        'sound' => 'default',
                                        'content-available' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ]
                );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'response' => $response->json(),
                ];
            }

            $errorCode = data_get(
                $response->json(),
                'error.details.0.errorCode'
            );

            $isInvalidToken = in_array(
                $errorCode,
                [
                    'UNREGISTERED',
                    'INVALID_ARGUMENT',
                    'SENDER_ID_MISMATCH',
                ],
                true
            );

            if ($isInvalidToken) {
                DeviceToken::query()
                    ->where('token', $deviceToken)
                    ->delete();
            }

            Log::warning('FCM notification failed.', [
                'status' => $response->status(),
                'response' => $response->json(),
                'invalid_token' => $isInvalidToken,
            ]);

            return [
                'success' => false,
                'invalid_token' => $isInvalidToken,
                'status' => $response->status(),
                'response' => $response->json(),
            ];
        } catch (Throwable $exception) {
            Log::error('FCM notification exception.', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * User के सभी devices पर notification भेजें।
     */
    public function sendToUser(
        int $userId,
        string $title,
        string $body,
        array $data = []
    ): array {
        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->pluck('token');

        $sent = 0;
        $failed = 0;

        foreach ($tokens as $token) {
            $result = $this->sendToToken(
                deviceToken: $token,
                title: $title,
                body: $body,
                data: $data
            );

            if ($result['success'] ?? false) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'total_tokens' => $tokens->count(),
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    private function accessToken(): string
    {
        $credentialsPath =
            config('services.firebase.credentials');

        if (!$credentialsPath) {
            throw new RuntimeException(
                'Firebase credentials path is missing.'
            );
        }

        if (!is_file($credentialsPath)) {
            throw new RuntimeException(
                "Firebase credentials file not found: {$credentialsPath}"
            );
        }

        $credentials = new ServiceAccountCredentials(
            self::FCM_SCOPE,
            $credentialsPath
        );

        $tokenData = $credentials->fetchAuthToken();

        $accessToken = $tokenData['access_token'] ?? null;

        if (!$accessToken) {
            throw new RuntimeException(
                'Unable to generate Firebase access token.'
            );
        }

        return $accessToken;
    }

    private function projectId(): string
    {
        $projectId = config('services.firebase.project_id');

        if ($projectId) {
            return $projectId;
        }

        $credentialsPath =
            config('services.firebase.credentials');

        if (
            !$credentialsPath
            || !is_file($credentialsPath)
        ) {
            throw new RuntimeException(
                'Firebase project ID is missing.'
            );
        }

        $credentials = json_decode(
            file_get_contents($credentialsPath),
            true
        );

        $projectId = $credentials['project_id'] ?? null;

        if (!$projectId) {
            throw new RuntimeException(
                'Firebase project ID not found in credentials.'
            );
        }

        return $projectId;
    }



    public function healthCheck(): array
{
    try {
        $projectId = $this->projectId();
        $accessToken = $this->accessToken();

        return [
            'success' => true,
            'project_id' => $projectId,
            'credentials_file_exists' => true,
            'access_token_generated' => !empty($accessToken),
        ];
    } catch (\Throwable $exception) {
        return [
            'success' => false,
            'project_id' =>
                config('services.firebase.project_id'),

            'credentials_path' =>
                config('services.firebase.credentials'),

            'credentials_file_exists' => is_file(
                config('services.firebase.credentials')
            ),

            'access_token_generated' => false,
            'error' => $exception->getMessage(),
        ];
    }
}
}
