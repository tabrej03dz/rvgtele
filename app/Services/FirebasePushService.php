<?php

namespace App\Services;

use App\Models\DeviceToken;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
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
            $deviceToken = trim($deviceToken);

            if ($deviceToken === '') {
                return [
                    'success' => false,
                    'message' => 'Device token is empty.',
                ];
            }

            $projectId  = $this->projectId();
            $accessToken = $this->accessToken();

            /*
            |--------------------------------------------------------------------------
            | FCM data values हमेशा string होनी चाहिए
            |--------------------------------------------------------------------------
            */

            $formattedData = collect($data)
                ->mapWithKeys(function ($value, $key) {
                    if (is_bool($value)) {
                        $value = $value ? '1' : '0';
                    } elseif (is_array($value) || is_object($value)) {
                        $value = json_encode(
                            $value,
                            JSON_UNESCAPED_UNICODE
                        );
                    } elseif ($value === null) {
                        $value = '';
                    }

                    return [
                        (string) $key => (string) $value,
                    ];
                })
                ->all();

            $url =
                "https://fcm.googleapis.com/v1/projects/"
                . "{$projectId}/messages:send";

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->asJson()
                ->connectTimeout(15)
                ->timeout(30)
                ->retry(
                    2,
                    500,
                    throw: false
                )
                ->post($url, [
                    'message' => [
                        'token' => $deviceToken,

                        /*
                        |--------------------------------------------------------------------------
                        | Visible Notification
                        |--------------------------------------------------------------------------
                        */

                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],

                        /*
                        |--------------------------------------------------------------------------
                        | Flutter Payload
                        |--------------------------------------------------------------------------
                        */

                        'data' => $formattedData,

                        /*
                        |--------------------------------------------------------------------------
                        | Android Configuration
                        |--------------------------------------------------------------------------
                        */

                        'android' => [
                            'priority' => 'high',

                            'notification' => [
                                'channel_id' =>
                                    'follow_up_reminders',

                                'sound' =>
                                    'default',

                                'click_action' =>
                                    'FLUTTER_NOTIFICATION_CLICK',

                                'default_sound' =>
                                    true,

                                'default_vibrate_timings' =>
                                    true,

                                'notification_priority' =>
                                    'PRIORITY_MAX',

                                'visibility' =>
                                    'PUBLIC',
                            ],
                        ],

                        /*
                        |--------------------------------------------------------------------------
                        | iOS Configuration
                        |--------------------------------------------------------------------------
                        */

                        'apns' => [
                            'headers' => [
                                'apns-priority' => '10',
                            ],

                            'payload' => [
                                'aps' => [
                                    'sound' => 'default',
                                    'badge' => 1,

                                    'content-available' => 1,
                                    'mutable-content' => 1,
                                ],
                            ],
                        ],
                    ],
                ]);

            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            if ($response->successful()) {
                Log::info('FCM notification sent successfully.', [
                    'project_id' => $projectId,
                    'message_name' => data_get(
                        $response->json(),
                        'name'
                    ),
                    'notification_type' =>
                        $formattedData['type'] ?? null,
                    'follow_up_id' =>
                        $formattedData['follow_up_id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Error Information
            |--------------------------------------------------------------------------
            */

            $responseJson = $response->json();

            $errorCode = data_get(
                $responseJson,
                'error.details.0.errorCode'
            );

            $errorStatus = data_get(
                $responseJson,
                'error.status'
            );

            $errorMessage = data_get(
                $responseJson,
                'error.message',
                'Firebase notification failed.'
            );

            /*
            |--------------------------------------------------------------------------
            | Invalid/Expired Device Token
            |--------------------------------------------------------------------------
            */

            $isInvalidToken = in_array(
                $errorCode,
                [
                    'UNREGISTERED',
                    'INVALID_ARGUMENT',
                    'SENDER_ID_MISMATCH',
                ],
                true
            ) || in_array(
                $errorStatus,
                [
                    'NOT_FOUND',
                    'INVALID_ARGUMENT',
                ],
                true
            );

            if ($isInvalidToken) {
                DeviceToken::query()
                    ->where('token', $deviceToken)
                    ->delete();

                Log::warning(
                    'Invalid FCM device token deleted.',
                    [
                        'error_code' => $errorCode,
                        'error_status' => $errorStatus,
                    ]
                );
            }

            Log::warning('FCM notification failed.', [
                'status' => $response->status(),
                'error_code' => $errorCode,
                'error_status' => $errorStatus,
                'error_message' => $errorMessage,
                'response' => $responseJson,
                'invalid_token' => $isInvalidToken,
                'notification_type' =>
                    $formattedData['type'] ?? null,
                'follow_up_id' =>
                    $formattedData['follow_up_id'] ?? null,
            ]);

            return [
                'success' => false,
                'invalid_token' => $isInvalidToken,
                'status' => $response->status(),
                'error_code' => $errorCode,
                'error_status' => $errorStatus,
                'message' => $errorMessage,
                'response' => $responseJson,
            ];
        } catch (Throwable $exception) {
            Log::error('FCM notification exception.', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'notification_type' => $data['type'] ?? null,
                'follow_up_id' => $data['follow_up_id'] ?? null,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * User के सभी registered devices पर notification भेजें।
     */
    public function sendToUser(
        int $userId,
        string $title,
        string $body,
        array $data = []
    ): array {
        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->whereNotNull('token')
            ->where('token', '<>', '')
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->get();

        $sent = 0;
        $failed = 0;
        $results = [];

        foreach ($tokens as $device) {
            $result = $this->sendToToken(
                deviceToken: (string) $device->token,
                title: $title,
                body: $body,
                data: $data
            );

            $results[] = [
                'device_id' => (int) $device->id,
                'platform' => $device->platform,
                'success' => (bool) (
                    $result['success'] ?? false
                ),
                'message' => $result['message'] ?? null,
                'status' => $result['status'] ?? null,
            ];

            if ($result['success'] ?? false) {
                $sent++;

                $device->forceFill([
                    'last_used_at' => now(),
                ])->save();
            } else {
                $failed++;
            }
        }

        if ($tokens->isEmpty()) {
            Log::warning(
                'Follow-up notification user has no device token.',
                [
                    'user_id' => $userId,
                    'notification_type' =>
                        $data['type'] ?? null,
                    'follow_up_id' =>
                        $data['follow_up_id'] ?? null,
                ]
            );
        }

        return [
            'total_tokens' => $tokens->count(),
            'sent' => $sent,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * Google OAuth access token generate करें।
     */
    private function accessToken(): string
    {
        /*
         * Access token लगभग 1 घंटे तक valid रहता है।
         * 50 मिनट cache रखने से हर notification पर
         * नया token generate नहीं होगा।
         */
        return Cache::remember(
            'firebase:fcm:access-token',
            now()->addMinutes(50),
            function (): string {
                $credentialsPath =
                    $this->credentialsPath();

                $credentials =
                    new ServiceAccountCredentials(
                        self::FCM_SCOPE,
                        $credentialsPath
                    );

                $tokenData =
                    $credentials->fetchAuthToken();

                $accessToken =
                    $tokenData['access_token'] ?? null;

                if (!$accessToken) {
                    throw new RuntimeException(
                        'Unable to generate Firebase access token.'
                    );
                }

                return (string) $accessToken;
            }
        );
    }

    /**
     * Firebase project ID प्राप्त करें।
     */
    private function projectId(): string
    {
        $configuredProjectId = trim(
            (string) config(
                'services.firebase.project_id'
            )
        );

        if ($configuredProjectId !== '') {
            return $configuredProjectId;
        }

        $credentialsPath =
            $this->credentialsPath();

        $credentialsContent =
            file_get_contents($credentialsPath);

        if ($credentialsContent === false) {
            throw new RuntimeException(
                'Unable to read Firebase credentials file.'
            );
        }

        $credentials = json_decode(
            $credentialsContent,
            true
        );

        if (!is_array($credentials)) {
            throw new RuntimeException(
                'Firebase credentials JSON is invalid.'
            );
        }

        $projectId = trim(
            (string) ($credentials['project_id'] ?? '')
        );

        if ($projectId === '') {
            throw new RuntimeException(
                'Firebase project ID not found in credentials.'
            );
        }

        return $projectId;
    }

    /**
     * Relative और absolute दोनों credentials paths handle करें।
     */
    private function credentialsPath(): string
    {
        $credentialsPath = trim(
            (string) config(
                'services.firebase.credentials'
            )
        );

        if ($credentialsPath === '') {
            throw new RuntimeException(
                'Firebase credentials path is missing.'
            );
        }

        /*
         * Linux absolute path "/" से शुरू होता है।
         * Windows absolute path जैसे C:\ से शुरू होता है।
         */
        $isAbsolutePath =
            str_starts_with($credentialsPath, '/')
            || preg_match(
                '/^[A-Za-z]:[\\\\\/]/',
                $credentialsPath
            ) === 1;

        if (!$isAbsolutePath) {
            $credentialsPath =
                base_path($credentialsPath);
        }

        $credentialsPath =
            str_replace(
                ['/', '\\'],
                DIRECTORY_SEPARATOR,
                $credentialsPath
            );

        if (!is_file($credentialsPath)) {
            throw new RuntimeException(
                "Firebase credentials file not found: "
                . $credentialsPath
            );
        }

        if (!is_readable($credentialsPath)) {
            throw new RuntimeException(
                "Firebase credentials file is not readable: "
                . $credentialsPath
            );
        }

        return $credentialsPath;
    }

    /**
     * Firebase configuration health check.
     */
    public function healthCheck(): array
    {
        try {
            $credentialsPath =
                $this->credentialsPath();

            $projectId =
                $this->projectId();

            $accessToken =
                $this->accessToken();

            return [
                'success' => true,
                'project_id' => $projectId,
                'credentials_path' => $credentialsPath,
                'credentials_file_exists' =>
                    is_file($credentialsPath),
                'credentials_file_readable' =>
                    is_readable($credentialsPath),
                'access_token_generated' =>
                    $accessToken !== '',
            ];
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'project_id' => config(
                    'services.firebase.project_id'
                ),
                'configured_credentials_path' =>
                    config(
                        'services.firebase.credentials'
                    ),
                'access_token_generated' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }
}
