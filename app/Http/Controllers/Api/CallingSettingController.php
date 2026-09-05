<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallingSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallingSettingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $setting = CallingSetting::query()
            ->where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->first();

        return response()->json([
            'success' => true,
            'message' => $setting
                ? 'Calling setting fetched successfully.'
                : 'Calling setting is not configured.',
            'data' => $setting,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
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
        ]);

        $user = $request->user();

        if (!$user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Logged-in user is not associated with a company.',
            ], 422);
        }

        $setting = CallingSetting::query()->updateOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'company_id' => $user->company_id,
                'sim_slot' => $validated['sim_slot'],
                'subscription_id' =>
                    $validated['subscription_id'] ?? null,
                'carrier_name' =>
                    $validated['carrier_name'] ?? null,
                'phone_account_id' =>
                    $validated['phone_account_id'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Work SIM setting saved successfully.',
            'data' => $setting,
        ]);
    }
}
