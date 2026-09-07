<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DemoCity;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DemoCityController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Get Current User Company ID
    |--------------------------------------------------------------------------
    */

    private function companyId(Request $request): int
    {
        $user = $request->user();

        abort_unless(
            $user,
            401,
            'Unauthenticated.'
        );

        $companyId = (int) ($user->company_id ?? 0);

        abort_if(
            $companyId <= 0,
            422,
            'Company is not selected.'
        );

        return $companyId;
    }


    /*
    |--------------------------------------------------------------------------
    | Check Demo City Belongs To Current Company
    |--------------------------------------------------------------------------
    */

    private function cityForCompany(
        Request $request,
        DemoCity $demoCity
    ): DemoCity {

        abort_unless(
            (int) $demoCity->company_id === $this->companyId($request),
            404,
            'Demo city not found.'
        );

        return $demoCity;
    }


    /*
    |--------------------------------------------------------------------------
    | Allowed Media Extensions
    |--------------------------------------------------------------------------
    */

    private function allowedExtensions(): array
    {
        return [
            'jpg',
            'jpeg',
            'png',
            'webp',
            'gif',

            'mp4',
            'mov',
            'avi',
            'mkv',
            'webm',
            'm4v',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Maximum File Size
    |--------------------------------------------------------------------------
    |
    | 102400 KB = 100 MB
    |
    */

    private function maxFileKb(): int
    {
        return 102400;
    }


    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    |
    | Mobile app me city create/update karte waqt category dropdown
    | ke liye ye API use kar sakte hain.
    |
    */

    public function categories(Request $request)
    {
        $companyId = $this->companyId($request);

        $categories = Category::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Categories fetched successfully.',
            'data' => $categories,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Demo Cities List
    |--------------------------------------------------------------------------
    |
    | GET /api/demo-cities
    |
    | Query Params:
    | search
    | category_id
    | per_page
    | page
    |
    */

    public function index(Request $request)
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:120',
            ],

            'category_id' => [
                'nullable',
                'integer',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        $categories = Category::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);


        /*
        |--------------------------------------------------------------------------
        | Query
        |--------------------------------------------------------------------------
        */

        $query = DemoCity::query()
            ->where('company_id', $companyId);


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['search'])) {

            $search = trim($validated['search']);

            $query->where(
                'name',
                'like',
                '%' . $search . '%'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Category Filter
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['category_id'])) {

            $categoryId = (int) $validated['category_id'];

            $categoryExists = $categories->contains(
                fn ($category) =>
                    (int) $category->id === $categoryId
            );

            abort_unless(
                $categoryExists,
                404,
                'Category not found.'
            );

            $query->where(
                'category_id',
                $categoryId
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = (int) ($validated['per_page'] ?? 12);

        $cities = $query
            ->latest('id')
            ->paginate($perPage);


        /*
        |--------------------------------------------------------------------------
        | Dashboard Counts
        |--------------------------------------------------------------------------
        */

        $allCities = DemoCity::query()
            ->where('company_id', $companyId)
            ->get([
                'id',
                'media',
            ]);

        $totalCities = $allCities->count();

        $totalFiles = 0;
        $totalImages = 0;
        $totalVideos = 0;

        foreach ($allCities as $city) {

            foreach (($city->media ?? []) as $item) {

                $totalFiles++;

                if (($item['type'] ?? null) === 'image') {

                    $totalImages++;

                } elseif (($item['type'] ?? null) === 'video') {

                    $totalVideos++;
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Format Cities
        |--------------------------------------------------------------------------
        */

        $items = collect($cities->items())
            ->map(
                fn ($city) =>
                    $this->formatCity($city)
            )
            ->values();


        return response()->json([
            'success' => true,
            'message' => 'Demo cities fetched successfully.',

            'stats' => [
                'total_cities' => $totalCities,
                'total_files' => $totalFiles,
                'total_images' => $totalImages,
                'total_videos' => $totalVideos,
            ],

            'categories' => $categories,

            'data' => $items,

            'pagination' => [
                'current_page' => $cities->currentPage(),
                'last_page' => $cities->lastPage(),
                'per_page' => $cities->perPage(),
                'total' => $cities->total(),
                'from' => $cities->firstItem(),
                'to' => $cities->lastItem(),
                'has_more_pages' => $cities->hasMorePages(),
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Demo City
    |--------------------------------------------------------------------------
    |
    | POST /api/demo-cities
    |
    */

    public function store(Request $request)
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([

            'name' => [
                'required',
                'string',
                'max:120',

                Rule::unique(
                    'demo_cities',
                    'name'
                )->where(
                    fn ($query) =>
                        $query
                            ->where(
                                'company_id',
                                $companyId
                            )
                            ->where(
                                'category_id',
                                (int) $request->input('category_id')
                            )
                ),
            ],


            'category_id' => [
                'required',
                'integer',

                Rule::exists(
                    'categories',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'company_id',
                            $companyId
                        )
                ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Multiple Media Files
            |--------------------------------------------------------------------------
            */

            'media_files' => [
                'nullable',
                'array',
            ],

            'media_files.*' => [
                'file',
                'max:' . $this->maxFileKb(),
                'mimes:' . implode(
                    ',',
                    $this->allowedExtensions()
                ),
            ],


            /*
            |--------------------------------------------------------------------------
            | ZIP File
            |--------------------------------------------------------------------------
            */

            'zip_file' => [
                'nullable',
                'file',
                'mimes:zip',
                'max:512000',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Create City
        |--------------------------------------------------------------------------
        */

        $city = DemoCity::create([

            'name' => trim(
                $validated['name']
            ),

            'category_id' => $validated['category_id'],

            'company_id' => $companyId,

            'created_by' => $request->user()->id,

            'updated_by' => $request->user()->id,

            'media' => [],
        ]);


        try {

            $media = [];


            /*
            |--------------------------------------------------------------------------
            | Normal Files
            |--------------------------------------------------------------------------
            */

            if ($request->hasFile('media_files')) {

                $media = array_merge(
                    $media,
                    $this->storeUploadedFiles(
                        $city,
                        $request->file('media_files')
                    )
                );
            }


            /*
            |--------------------------------------------------------------------------
            | ZIP
            |--------------------------------------------------------------------------
            */

            if ($request->hasFile('zip_file')) {

                $media = array_merge(
                    $media,
                    $this->extractZipAndStore(
                        $city,
                        $request->file('zip_file')
                    )
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Update Media JSON
            |--------------------------------------------------------------------------
            */

            $city->update([
                'media' => array_values($media),
                'updated_by' => $request->user()->id,
            ]);

        } catch (\Throwable $e) {

            Storage::disk('public')
                ->deleteDirectory(
                    'demo-cities/'
                    . $companyId
                    . '/'
                    . $city->id
                );

            $city->delete();

            throw $e;
        }


        $city->refresh();


        return response()->json([
            'success' => true,
            'message' => 'Demo city created successfully.',
            'data' => $this->formatCity($city),
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Show Single Demo City
    |--------------------------------------------------------------------------
    |
    | GET /api/demo-cities/{demoCity}
    |
    */

    public function show(
        Request $request,
        DemoCity $demoCity
    ) {

        $demoCity = $this->cityForCompany(
            $request,
            $demoCity
        );

        return response()->json([
            'success' => true,
            'message' => 'Demo city fetched successfully.',
            'data' => $this->formatCity($demoCity),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Update Demo City
    |--------------------------------------------------------------------------
    |
    | PUT/PATCH/POST /api/demo-cities/{demoCity}
    |
    */

    public function update(
        Request $request,
        DemoCity $demoCity
    ) {

        $demoCity = $this->cityForCompany(
            $request,
            $demoCity
        );

        $companyId = $this->companyId($request);


        $validated = $request->validate([

            'name' => [
                'required',
                'string',
                'max:120',

                Rule::unique(
                    'demo_cities',
                    'name'
                )
                    ->where(
                        fn ($query) =>
                            $query
                                ->where(
                                    'company_id',
                                    $companyId
                                )
                                ->where(
                                    'category_id',
                                    (int) $request->input('category_id')
                                )
                    )
                    ->ignore($demoCity->id),
            ],


            'category_id' => [
                'required',
                'integer',

                Rule::exists(
                    'categories',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'company_id',
                            $companyId
                        )
                ),
            ],


            'media_files' => [
                'nullable',
                'array',
            ],

            'media_files.*' => [
                'file',
                'max:' . $this->maxFileKb(),
                'mimes:' . implode(
                    ',',
                    $this->allowedExtensions()
                ),
            ],


            'zip_file' => [
                'nullable',
                'file',
                'mimes:zip',
                'max:512000',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Existing Media
        |--------------------------------------------------------------------------
        */

        $media = $demoCity->media ?? [];


        /*
        |--------------------------------------------------------------------------
        | New Normal Files
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('media_files')) {

            $media = array_merge(
                $media,
                $this->storeUploadedFiles(
                    $demoCity,
                    $request->file('media_files')
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | New ZIP
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('zip_file')) {

            $media = array_merge(
                $media,
                $this->extractZipAndStore(
                    $demoCity,
                    $request->file('zip_file')
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        $demoCity->update([

            'name' => trim(
                $validated['name']
            ),

            'category_id' => $validated['category_id'],

            'media' => array_values($media),

            'updated_by' => $request->user()->id,
        ]);


        $demoCity->refresh();


        return response()->json([
            'success' => true,
            'message' => 'Demo city updated successfully.',
            'data' => $this->formatCity($demoCity),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Upload Multiple Media Separately
    |--------------------------------------------------------------------------
    |
    | POST /api/demo-cities/{demoCity}/media
    |
    */

    public function uploadMedia(
        Request $request,
        DemoCity $demoCity
    ) {

        $demoCity = $this->cityForCompany(
            $request,
            $demoCity
        );


        $request->validate([

            'media_files' => [
                'required',
                'array',
                'min:1',
            ],

            'media_files.*' => [
                'required',
                'file',
                'max:' . $this->maxFileKb(),
                'mimes:' . implode(
                    ',',
                    $this->allowedExtensions()
                ),
            ],
        ]);


        $newFiles = $this->storeUploadedFiles(
            $demoCity,
            $request->file('media_files')
        );


        $media = array_merge(
            $demoCity->media ?? [],
            $newFiles
        );


        $demoCity->update([

            'media' => array_values($media),

            'updated_by' => $request->user()->id,
        ]);


        $demoCity->refresh();


        return response()->json([
            'success' => true,
            'message' => 'Demo files uploaded successfully.',
            'uploaded_count' => count($newFiles),
            'data' => $this->formatCity($demoCity),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Upload ZIP Separately
    |--------------------------------------------------------------------------
    |
    | POST /api/demo-cities/{demoCity}/zip
    |
    */

    public function uploadZip(
        Request $request,
        DemoCity $demoCity
    ) {

        $demoCity = $this->cityForCompany(
            $request,
            $demoCity
        );


        $request->validate([

            'zip_file' => [
                'required',
                'file',
                'mimes:zip',
                'max:512000',
            ],
        ]);


        $newMedia = $this->extractZipAndStore(
            $demoCity,
            $request->file('zip_file')
        );


        $media = array_merge(
            $demoCity->media ?? [],
            $newMedia
        );


        $demoCity->update([

            'media' => array_values($media),

            'updated_by' => $request->user()->id,
        ]);


        $demoCity->refresh();


        return response()->json([
            'success' => true,
            'message' => 'ZIP demo pack uploaded successfully.',
            'uploaded_count' => count($newMedia),
            'data' => $this->formatCity($demoCity),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Single Media
    |--------------------------------------------------------------------------
    |
    | DELETE /api/demo-cities/{demoCity}/media/{mediaId}
    |
    */

    public function destroyMedia(
        Request $request,
        DemoCity $demoCity,
        string $mediaId
    ) {

        $demoCity = $this->cityForCompany(
            $request,
            $demoCity
        );


        $media = collect(
            $demoCity->media ?? []
        );


        $item = $media->first(

            fn ($row) =>
                (string) ($row['id'] ?? '')
                ===
                (string) $mediaId
        );


        abort_unless(
            $item,
            404,
            'Demo file not found.'
        );


        /*
        |--------------------------------------------------------------------------
        | Delete Physical File
        |--------------------------------------------------------------------------
        */

        if (!empty($item['path'])) {

            Storage::disk('public')
                ->delete(
                    $item['path']
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Remove From Media JSON
        |--------------------------------------------------------------------------
        */

        $newMedia = $media
            ->reject(

                fn ($row) =>
                    (string) ($row['id'] ?? '')
                    ===
                    (string) $mediaId
            )
            ->values()
            ->all();


        $demoCity->update([

            'media' => $newMedia,

            'updated_by' => $request->user()->id,
        ]);


        return response()->json([
            'success' => true,
            'message' => 'Demo file deleted successfully.',
            'data' => $this->formatCity(
                $demoCity->fresh()
            ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Download Single Media
    |--------------------------------------------------------------------------
    |
    | GET /api/demo-cities/{demoCity}/media/{mediaId}/download
    |
    */

    public function downloadMedia(
        Request $request,
        DemoCity $demoCity,
        string $mediaId
    ) {

        $demoCity = $this->cityForCompany(
            $request,
            $demoCity
        );


        $item = collect(
            $demoCity->media ?? []
        )->first(

            fn ($row) =>
                (string) ($row['id'] ?? '')
                ===
                (string) $mediaId
        );


        abort_unless(
            $item,
            404,
            'Demo file not found.'
        );


        $path = $item['path'] ?? null;


        abort_unless(
            $path
            &&
            Storage::disk('public')->exists($path),
            404,
            'Stored file not found.'
        );


        return Storage::disk('public')
            ->download(
                $path,
                $item['original_name']
                    ?? basename($path)
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Download Complete City As ZIP
    |--------------------------------------------------------------------------
    |
    | GET /api/demo-cities/{demoCity}/download-all
    |
    */

    public function downloadAll(
        Request $request,
        DemoCity $demoCity
    ): BinaryFileResponse {

        $demoCity = $this->cityForCompany(
            $request,
            $demoCity
        );


        $media = collect(
            $demoCity->media ?? []
        )
            ->filter(

                fn ($item) =>
                    !empty($item['path'])
                    &&
                    Storage::disk('public')
                        ->exists($item['path'])
            )
            ->values();


        abort_if(
            $media->isEmpty(),
            404,
            'No demo files available.'
        );


        /*
        |--------------------------------------------------------------------------
        | Temporary ZIP Folder
        |--------------------------------------------------------------------------
        */

        $tempDirectory = storage_path(
            'app/temp/demo-city-zips'
        );


        File::ensureDirectoryExists(
            $tempDirectory
        );


        $zipName =
            Str::slug($demoCity->name)
            . '-demo-pack-'
            . now()->format('Ymd-His')
            . '.zip';


        $zipPath =
            $tempDirectory
            . DIRECTORY_SEPARATOR
            . $zipName;


        /*
        |--------------------------------------------------------------------------
        | Create ZIP
        |--------------------------------------------------------------------------
        */

        $zip = new ZipArchive();


        abort_unless(

            $zip->open(
                $zipPath,
                ZipArchive::CREATE
                |
                ZipArchive::OVERWRITE
            ) === true,

            500,
            'Unable to create ZIP.'
        );


        $usedNames = [];


        foreach (
            $media
            as
            $index => $item
        ) {

            $absolutePath = Storage::disk('public')
                ->path(
                    $item['path']
                );


            $name =
                $item['original_name']
                ??
                basename($absolutePath);


            $name = $this->uniqueZipName(
                $name,
                $usedNames,
                $index
            );


            $zip->addFile(
                $absolutePath,
                $name
            );


            $usedNames[] = $name;
        }


        $zip->close();


        return response()
            ->download(
                $zipPath,
                $zipName
            )
            ->deleteFileAfterSend(true);
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Complete Demo City
    |--------------------------------------------------------------------------
    |
    | DELETE /api/demo-cities/{demoCity}
    |
    */

    public function destroy(
        Request $request,
        DemoCity $demoCity
    ) {

        $demoCity = $this->cityForCompany(
            $request,
            $demoCity
        );


        /*
        |--------------------------------------------------------------------------
        | Delete All Physical Files
        |--------------------------------------------------------------------------
        */

        foreach (
            ($demoCity->media ?? [])
            as
            $item
        ) {

            if (!empty($item['path'])) {

                Storage::disk('public')
                    ->delete(
                        $item['path']
                    );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Delete Directory
        |--------------------------------------------------------------------------
        */

        Storage::disk('public')
            ->deleteDirectory(

                'demo-cities/'
                . $demoCity->company_id
                . '/'
                . $demoCity->id
            );


        /*
        |--------------------------------------------------------------------------
        | Delete Database Record
        |--------------------------------------------------------------------------
        */

        $demoCity->delete();


        return response()->json([
            'success' => true,
            'message' => 'Demo city deleted successfully.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Format Demo City API Response
    |--------------------------------------------------------------------------
    */

    private function formatCity(
        DemoCity $city
    ): array {

        $media = collect(
            $city->media ?? []
        )
            ->map(function ($item) use ($city) {

                $path = $item['path'] ?? null;

                $publicUrl = null;

                if ($path) {

                    $relativeUrl = Storage::disk('public')
                        ->url($path);

                    $publicUrl = url($relativeUrl);
                }


                return [

                    'id' => $item['id'] ?? null,

                    'original_name' =>
                        $item['original_name']
                        ?? null,

                    'stored_name' =>
                        $item['stored_name']
                        ?? null,

                    'path' => $path,

                    'url' => $publicUrl,

                    'type' =>
                        $item['type']
                        ?? null,

                    'mime' =>
                        $item['mime']
                        ?? null,

                    'size' =>
                        (int) (
                            $item['size']
                            ?? 0
                        ),

                    'uploaded_at' =>
                        $item['uploaded_at']
                        ?? null,

                    'uploaded_by' =>
                        $item['uploaded_by']
                        ?? null,

                    'download_url' =>
                        isset($item['id'])
                        ?
                        route(
                            'api.demo-cities.media.download',
                            [
                                'demoCity' => $city->id,
                                'mediaId' => $item['id'],
                            ]
                        )
                        :
                        null,
                ];
            })
            ->values()
            ->all();


        return [

            'id' => $city->id,

            'name' => $city->name,

            'category_id' =>
                $city->category_id,

            'company_id' =>
                $city->company_id,

            'created_by' =>
                $city->created_by,

            'updated_by' =>
                $city->updated_by,

            'media_count' =>
                count($media),

            'image_count' =>
                collect($media)
                    ->where(
                        'type',
                        'image'
                    )
                    ->count(),

            'video_count' =>
                collect($media)
                    ->where(
                        'type',
                        'video'
                    )
                    ->count(),

            'media' => $media,

            'download_all_url' =>
                route(
                    'api.demo-cities.download-all',
                    [
                        'demoCity' =>
                            $city->id,
                    ]
                ),

            'created_at' =>
                optional(
                    $city->created_at
                )->toIso8601String(),

            'updated_at' =>
                optional(
                    $city->updated_at
                )->toIso8601String(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Store Multiple Uploaded Files
    |--------------------------------------------------------------------------
    */

    private function storeUploadedFiles(
        DemoCity $city,
        array|UploadedFile $uploadedFiles
    ): array {

        $uploadedFiles = is_array(
            $uploadedFiles
        )
            ? $uploadedFiles
            : [$uploadedFiles];


        $stored = [];


        foreach ($uploadedFiles as $file) {

            if (
                !$file instanceof UploadedFile
                ||
                !$file->isValid()
            ) {
                continue;
            }


            $extension = strtolower(
                $file->getClientOriginalExtension()
            );


            if (
                !in_array(
                    $extension,
                    $this->allowedExtensions(),
                    true
                )
            ) {
                continue;
            }


            $stored[] = $this->storeOneFile(

                $city,

                $file,

                $file->getClientOriginalName()
            );
        }


        return $stored;
    }


    /*
    |--------------------------------------------------------------------------
    | Store Single File
    |--------------------------------------------------------------------------
    */

    private function storeOneFile(
        DemoCity $city,
        UploadedFile $file,
        string $originalName
    ): array {

        $extension = strtolower(

            $file->getClientOriginalExtension()

            ?:

            $file->extension()

            ?:

            'bin'
        );


        /*
        |--------------------------------------------------------------------------
        | Safe File Name
        |--------------------------------------------------------------------------
        */

        $safeBase = Str::slug(

            pathinfo(
                $originalName,
                PATHINFO_FILENAME
            )
        );


        if ($safeBase === '') {

            $safeBase = 'demo';
        }


        $storedName =

            now()->format('YmdHis')

            . '-'

            . Str::lower(
                Str::random(8)
            )

            . '-'

            . $safeBase

            . '.'

            . $extension;


        /*
        |--------------------------------------------------------------------------
        | Directory
        |--------------------------------------------------------------------------
        */

        $directory =

            'demo-cities/'

            . $city->company_id

            . '/'

            . $city->id;


        /*
        |--------------------------------------------------------------------------
        | Store
        |--------------------------------------------------------------------------
        */

        $path = $file->storeAs(

            $directory,

            $storedName,

            'public'
        );


        /*
        |--------------------------------------------------------------------------
        | MIME
        |--------------------------------------------------------------------------
        */

        $mime =

            $file->getMimeType()

            ?:

            $file->getClientMimeType()

            ?:

            'application/octet-stream';


        /*
        |--------------------------------------------------------------------------
        | Media Type
        |--------------------------------------------------------------------------
        */

        $type = Str::startsWith(
            $mime,
            'image/'
        )
            ? 'image'
            : 'video';


        return [

            'id' =>
                (string) Str::uuid(),

            'original_name' =>
                $originalName,

            'stored_name' =>
                $storedName,

            'path' =>
                $path,

            'type' =>
                $type,

            'mime' =>
                $mime,

            'size' =>
                (int) $file->getSize(),

            'uploaded_at' =>
                now()->toIso8601String(),

            'uploaded_by' =>
                auth()->id(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Extract ZIP And Store Files
    |--------------------------------------------------------------------------
    */

    private function extractZipAndStore(
        DemoCity $city,
        UploadedFile $zipFile
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Temporary Directory
        |--------------------------------------------------------------------------
        */

        $tempRoot = storage_path(

            'app/temp/demo-city-imports/'

            . Str::uuid()
        );


        File::ensureDirectoryExists(
            $tempRoot
        );


        /*
        |--------------------------------------------------------------------------
        | Open ZIP
        |--------------------------------------------------------------------------
        */

        $zip = new ZipArchive();


        $opened = $zip->open(
            $zipFile->getRealPath()
        );


        abort_unless(
            $opened === true,
            422,
            'Invalid ZIP file.'
        );


        /*
        |--------------------------------------------------------------------------
        | Maximum 500 Files
        |--------------------------------------------------------------------------
        */

        abort_if(
            $zip->numFiles > 500,
            422,
            'ZIP can contain maximum 500 files.'
        );


        $stored = [];


        try {

            for (
                $i = 0;
                $i < $zip->numFiles;
                $i++
            ) {

                $stat = $zip->statIndex($i);


                if (
                    !$stat
                    ||
                    empty($stat['name'])
                ) {
                    continue;
                }


                $entryName = str_replace(
                    '\\',
                    '/',
                    $stat['name']
                );


                /*
                |--------------------------------------------------------------------------
                | Skip Folder And Mac System Files
                |--------------------------------------------------------------------------
                */

                if (

                    str_ends_with(
                        $entryName,
                        '/'
                    )

                    ||

                    str_contains(
                        $entryName,
                        '__MACOSX/'
                    )

                    ||

                    basename(
                        $entryName
                    ) === '.DS_Store'

                ) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | ZIP Slip Protection
                |--------------------------------------------------------------------------
                */

                if (

                    str_contains(
                        $entryName,
                        '../'
                    )

                    ||

                    str_starts_with(
                        $entryName,
                        '/'
                    )

                ) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Extension
                |--------------------------------------------------------------------------
                */

                $extension = strtolower(

                    pathinfo(
                        $entryName,
                        PATHINFO_EXTENSION
                    )
                );


                if (
                    !in_array(
                        $extension,
                        $this->allowedExtensions(),
                        true
                    )
                ) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | File Size
                |--------------------------------------------------------------------------
                */

                $uncompressedSize = (int) (
                    $stat['size']
                    ??
                    0
                );


                if (
                    $uncompressedSize
                    >
                    (
                        $this->maxFileKb()
                        *
                        1024
                    )
                ) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Read ZIP Entry
                |--------------------------------------------------------------------------
                */

                $stream = $zip->getStream(
                    $stat['name']
                );


                if (!$stream) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Temporary File
                |--------------------------------------------------------------------------
                */

                $tempName =

                    Str::uuid()

                    . '.'

                    . $extension;


                $tempPath =

                    $tempRoot

                    . DIRECTORY_SEPARATOR

                    . $tempName;


                $out = fopen(
                    $tempPath,
                    'wb'
                );


                if (!$out) {

                    fclose($stream);

                    continue;
                }


                while (!feof($stream)) {

                    $chunk = fread(
                        $stream,
                        8192
                    );

                    if ($chunk === false) {
                        break;
                    }

                    fwrite(
                        $out,
                        $chunk
                    );
                }


                fclose($out);

                fclose($stream);


                /*
                |--------------------------------------------------------------------------
                | Convert To UploadedFile
                |--------------------------------------------------------------------------
                */

                $uploaded = new UploadedFile(

                    $tempPath,

                    basename(
                        $entryName
                    ),

                    null,

                    null,

                    true
                );


                /*
                |--------------------------------------------------------------------------
                | Store
                |--------------------------------------------------------------------------
                */

                $stored[] =
                    $this->storeOneFile(

                        $city,

                        $uploaded,

                        basename(
                            $entryName
                        )
                    );
            }

        } finally {

            $zip->close();

            File::deleteDirectory(
                $tempRoot
            );
        }


        return $stored;
    }


    /*
    |--------------------------------------------------------------------------
    | Prevent Duplicate Names Inside ZIP
    |--------------------------------------------------------------------------
    */

    private function uniqueZipName(
        string $name,
        array $usedNames,
        int $index
    ): string {

        $candidate = $name;


        if (
            !in_array(
                $candidate,
                $usedNames,
                true
            )
        ) {
            return $candidate;
        }


        $base = pathinfo(
            $name,
            PATHINFO_FILENAME
        );


        $extension = pathinfo(
            $name,
            PATHINFO_EXTENSION
        );


        return

            $base

            . '-'

            . ($index + 1)

            .

            (
                $extension
                ? '.' . $extension
                : ''
            );
    }
}