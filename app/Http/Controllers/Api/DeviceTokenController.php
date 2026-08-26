<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Services\FirebasePushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceTokenController extends Controller
{
    /**
     * Logged-in user के registered devices.
     */
    public function index(Request $request): JsonResponse
    {
        $tokens = DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->latest('last_used_at')
            ->latest('id')
            ->get([
                'id',
                'platform',
                'device_name',
                'last_used_at',
                'created_at',
            ]);

        return response()->json([
            'status' => true,
            'message' =>
                'Device tokens fetched successfully.',
            'data' => $tokens,
        ]);
    }

    /**
     * FCM token register/update करें।
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => [
                'required',
                'string',
                'max:500',
            ],

            'platform' => [
                'nullable',
                Rule::in([
                    'android',
                    'ios',
                    'web',
                ]),
            ],

            'device_name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        /*
         * Same FCM token अगर पहले किसी दूसरे user पर registered था,
         * तो login करने वाले current user पर transfer हो जाएगा।
         */
        $deviceToken = DeviceToken::query()
            ->updateOrCreate(
                [
                    'token' => $validated['token'],
                ],
                [
                    'user_id' => $request->user()->id,
                    'platform' =>
                        $validated['platform'] ?? null,
                    'device_name' =>
                        $validated['device_name'] ?? null,
                    'last_used_at' => now(),
                ]
            );

        return response()->json([
            'status' => true,
            'message' =>
                'Device token registered successfully.',

            'data' => [
                'id' => $deviceToken->id,
                'platform' => $deviceToken->platform,
                'device_name' =>
                    $deviceToken->device_name,
                'last_used_at' =>
                    $deviceToken->last_used_at,
            ],
        ]);
    }

    /**
     * Logout पर specific token delete करें।
     */
    public function destroy(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'token' => [
                'required',
                'string',
                'max:500',
            ],
        ]);

        $deleted = DeviceToken::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->where(
                'token',
                $validated['token']
            )
            ->delete();

        return response()->json([
            'status' => true,
            'message' => $deleted
                ? 'Device token removed successfully.'
                : 'Device token was not registered.',
        ]);
    }

    /**
     * Logged-in user के सभी device tokens delete करें।
     */
    public function destroyAll(
        Request $request
    ): JsonResponse {
        DeviceToken::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->delete();

        return response()->json([
            'status' => true,
            'message' =>
                'All device tokens removed successfully.',
        ]);
    }

    /**
     * Notification testing API.
     */
    public function test(
        Request $request,
        FirebasePushService $firebase
    ): JsonResponse {
        $result = $firebase->sendToUser(
            userId: (int) $request->user()->id,

            title: 'TeleCRM Test Notification',

            body:
                'Firebase push notification successfully working.',

            data: [
                'type' => 'test_notification',
                'click_action' =>
                    'FLUTTER_NOTIFICATION_CLICK',
            ]
        );

        return response()->json([
            'status' => $result['sent'] > 0,

            'message' => $result['sent'] > 0
                ? 'Test notification sent successfully.'
                : 'Notification could not be sent.',

            'data' => $result,
        ], $result['sent'] > 0 ? 200 : 422);
    }


    public function firebaseHealth(
    FirebasePushService $firebase
): JsonResponse {
    $result = $firebase->healthCheck();

    return response()->json([
        'status' => $result['success'],
        'message' => $result['success']
            ? 'Firebase backend configuration is working.'
            : 'Firebase backend configuration failed.',
        'data' => $result,
    ], $result['success'] ? 200 : 500);
}
}
