<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileApk;
use Illuminate\Http\Request;

class MobileApkController extends Controller
{
    public function latest(Request $request)
    {
        $request->validate([
            'current_version' => ['nullable', 'integer', 'min:1'],
        ]);

        $apk = MobileApk::query()
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if (!$apk) {
            return response()->json([
                'status' => false,
                'message' => 'No APK available.',
            ], 404);
        }

        $currentVersion = (int) ($request->current_version ?? 0);
        $latestVersion = (int) $apk->version;

        $updateAvailable = $currentVersion < $latestVersion;

        return response()->json([
            'status' => true,
            'message' => $updateAvailable
                ? 'New update available.'
                : 'Application is up to date.',

            'data' => [
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion,

                'update_available' => $updateAvailable,

                'name' => $apk->name,

                'download_url' => route(
                    'mobile-apks.download',
                    $apk
                ),

                'images' => collect($apk->images ?? [])
                    ->map(function ($image) {
                        return Storage::disk('public')->url($image);
                    })
                    ->values(),

                'uploaded_at' => $apk->created_at?->toIso8601String(),
            ],
        ]);
    }
}
