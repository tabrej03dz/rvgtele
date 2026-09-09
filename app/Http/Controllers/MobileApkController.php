<?php

namespace App\Http\Controllers;

use App\Models\MobileApk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MobileApkController extends Controller
{
    /**
     * APK List
     */
    public function index(Request $request)
    {
        $query = MobileApk::query();

        // Search by name or version
        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('version', 'like', "%{$search}%");

            });
        }

        $apks = $query
            ->orderByDesc('version')
            ->paginate(15)
            ->withQueryString();

        return view('mobile_apks.index', compact('apks'));
    }


    /**
     * Create Page
     */
    public function create()
    {
        return view('mobile_apks.create');
    }


    /**
     * Store APK
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'apk' => ['required', 'file', 'max:512000'],

            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $file = $request->file('apk');

        if (strtolower($file->getClientOriginalExtension()) !== 'apk') {
            return back()
                ->withErrors([
                    'apk' => 'Please upload a valid APK file.',
                ])
                ->withInput();
        }

        $nextVersion = (MobileApk::max('version') ?? 0) + 1;

        $name = $request->filled('name')
            ? trim($request->name)
            : 'CRM';

        $safeName = Str::slug($name);

        if (empty($safeName)) {
            $safeName = 'crm';
        }

        $fileName = $safeName . '-v' . $nextVersion . '.apk';

        $filePath = $file->storeAs(
            'apks',
            $fileName,
            'public'
        );


        /*
        |--------------------------------------------------------------------------
        | Multiple Images Upload
        |--------------------------------------------------------------------------
        */

        $imagePaths = [];

        if ($request->hasFile('images')) {

            foreach ($request->file('images') as $image) {

                $imageName =
                    $safeName .
                    '-v' .
                    $nextVersion .
                    '-' .
                    uniqid() .
                    '.' .
                    $image->getClientOriginalExtension();

                $imagePath = $image->storeAs(
                    'apk-images',
                    $imageName,
                    'public'
                );

                $imagePaths[] = $imagePath;
            }
        }


        MobileApk::create([
            'name' => $name,
            'version' => $nextVersion,
            'file_path' => $filePath,
            'images' => $imagePaths,
            'is_active' => true,
        ]);

        return redirect()
            ->route('mobile-apks.index')
            ->with(
                'success',
                'APK uploaded successfully. Version v' . $nextVersion
            );
    }


    /**
     * Show APK Details
     */
    public function show(MobileApk $mobileApk)
    {
        return view(
            'mobile_apks.show',
            compact('mobileApk')
        );
    }


    /**
     * Edit Page
     */
    public function edit(MobileApk $mobileApk)
    {
        return view(
            'mobile_apks.edit',
            compact('mobileApk')
        );
    }


    /**
     * Update APK Information
     *
     * APK file aur version edit nahi hoga.
     */
    public function update(
        Request $request,
        MobileApk $mobileApk
    ) {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);


        $mobileApk->update([
            'name' => trim($request->name),
            'is_active' => $request->boolean('is_active'),
        ]);


        return redirect()
            ->route('mobile-apks.index')
            ->with(
                'success',
                'APK updated successfully.'
            );
    }


    /**
     * Download APK
     */
    public function download(MobileApk $mobileApk)
    {
        /**
         * Inactive APK download block
         */
        if (!$mobileApk->is_active) {

            return back()->with(
                'error',
                'This APK is currently inactive.'
            );
        }


        /**
         * Check APK File Exists
         */
        if (
            empty($mobileApk->file_path) ||
            !Storage::disk('public')->exists($mobileApk->file_path)
        ) {

            return back()->with(
                'error',
                'APK file not found.'
            );
        }


        /**
         * Download File Name
         *
         * Example:
         * crm-v4.apk
         */
        $safeName = Str::slug($mobileApk->name);

        if (empty($safeName)) {
            $safeName = 'crm';
        }

        $downloadName =
            $safeName .
            '-v' .
            $mobileApk->version .
            '.apk';


        return Storage::disk('public')->download(
            $mobileApk->file_path,
            $downloadName,
            [
                'Content-Type' => 'application/vnd.android.package-archive',
            ]
        );
    }


    /**
     * Active / Inactive APK
     */
    public function toggleStatus(MobileApk $mobileApk)
    {
        $mobileApk->update([
            'is_active' => !$mobileApk->is_active,
        ]);


        if ($mobileApk->is_active) {

            return back()->with(
                'success',
                'APK activated successfully.'
            );

        }


        return back()->with(
            'success',
            'APK deactivated successfully.'
        );
    }


    /**
     * Delete APK
     */
    public function destroy(MobileApk $mobileApk)
    {
        /**
         * Delete APK file first
         */
        if (
            !empty($mobileApk->file_path) &&
            Storage::disk('public')->exists($mobileApk->file_path)
        ) {

            Storage::disk('public')->delete(
                $mobileApk->file_path
            );
        }


        /**
         * Delete database record
         */
        $mobileApk->delete();


        return redirect()
            ->route('mobile-apks.index')
            ->with(
                'success',
                'APK deleted successfully.'
            );
    }


    /**
     * Latest Active APK API
     *
     * Flutter App isi API ko hit karke
     * latest version check kar sakta hai.
     */
    public function latest()
    {
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


        return response()->json([
            'status' => true,

            'data' => [
                'id' => $apk->id,

                'name' => $apk->name,

                'version' => (int) $apk->version,

                'download_url' => route(
                    'mobile-apks.download',
                    $apk
                ),

                'uploaded_at' => $apk->created_at
                    ?->format('d-m-Y h:i A'),
            ],
        ]);
    }
}