<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileApk;
use Illuminate\Http\Request;

class MobileApkController extends Controller
{
    public function latest(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Current App Version
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'current_version' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Get Latest Active APK
        |--------------------------------------------------------------------------
        */

        $apk = MobileApk::query()
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();


        /*
        |--------------------------------------------------------------------------
        | No APK Found
        |--------------------------------------------------------------------------
        */

        if (!$apk) {

            return response()->json([
                'status' => false,
                'message' => 'No APK available.',
                'data' => null,
            ], 404);

        }


        /*
        |--------------------------------------------------------------------------
        | Version Compare
        |--------------------------------------------------------------------------
        */

        $currentVersion = (int) ($request->current_version ?? 0);

        $latestVersion = (int) $apk->version;

        $updateAvailable =
            $currentVersion < $latestVersion;


        /*
        |--------------------------------------------------------------------------
        | Images Paths
        |--------------------------------------------------------------------------
        */

        $images = [];

        if (!empty($apk->images)) {

            foreach ($apk->images as $image) {

                $images[] = $image;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => true,

            'message' => $updateAvailable
                ? 'New update available.'
                : 'Application is up to date.',

            'data' => [

                'id' => $apk->id,

                'name' => $apk->name,

                'current_version' => $currentVersion,

                'latest_version' => $latestVersion,

                'update_available' => $updateAvailable,

                /*
                |--------------------------------------------------------------------------
                | APK Storage Path
                |--------------------------------------------------------------------------
                |
                | Example:
                | apks/crm-v7.apk
                |
                */

                'file_path' => $apk->file_path,

                /*
                |--------------------------------------------------------------------------
                | Screenshot Paths
                |--------------------------------------------------------------------------
                |
                | Example:
                | [
                |   "apk-images/crm-v7-abc.png",
                |   "apk-images/crm-v7-def.png"
                | ]
                |
                */

                'images' => $images,

                'uploaded_at' => $apk->created_at
                    ?->toIso8601String(),

            ],
        ]);
    }
}
