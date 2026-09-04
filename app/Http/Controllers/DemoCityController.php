<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DemoCity;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
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
    | Company ID
    |--------------------------------------------------------------------------
    |
    | Agar aapke project me companyId($request) ka already function hai,
    | to is method ko apne existing function se replace kar sakte ho.
    |
    */

    private function companyId(Request $request): int
    {
        $user = $request->user();

        abort_unless(
            $user,
            401
        );

        $companyId = (int) (
            $user->company_id ?? 0
        );

        abort_if(
            $companyId <= 0,
            422,
            'Company is not selected.'
        );

        return $companyId;
    }


    /*
    |--------------------------------------------------------------------------
    | Check City Company
    |--------------------------------------------------------------------------
    */

    private function cityForCompany(
        Request $request,
        DemoCity $demoCity
    ): DemoCity {

        abort_unless(
            (int) $demoCity->company_id
            ===
            $this->companyId($request),
            404
        );

        return $demoCity;
    }


    /*
    |--------------------------------------------------------------------------
    | Allowed Demo Extensions
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
    | Maximum Per File Size
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
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $companyId = $this->companyId($request);

        /*
        |--------------------------------------------------------------------------
        | Categories For Filter / Card Labels
        |--------------------------------------------------------------------------
        */

        $categories = Category::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        $categoryNames = $categories
            ->pluck('name', 'id');

        $query = DemoCity::query()
            ->where('company_id', $companyId);

        /*
        |--------------------------------------------------------------------------
        | Search City
        |--------------------------------------------------------------------------
        */

        if ($search = trim((string) $request->get('search'))) {
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

        if ($request->filled('category_id')) {
            $categoryId = (int) $request->get('category_id');

            abort_unless(
                $categories->contains(
                    fn ($category) => (int) $category->id === $categoryId
                ),
                404,
                'Category not found.'
            );

            $query->where('category_id', $categoryId);
        }

        $cities = $query
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Dashboard Counts
        |--------------------------------------------------------------------------
        */

        $totalCities = DemoCity::query()
            ->where('company_id', $companyId)
            ->count();

        $allCities = DemoCity::query()
            ->where('company_id', $companyId)
            ->get([
                'id',
                'media',
            ]);

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

        return view(
            'demo-cities.index',
            compact(
                'cities',
                'categories',
                'categoryNames',
                'totalCities',
                'totalFiles',
                'totalImages',
                'totalVideos'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create Page
    |--------------------------------------------------------------------------
    */

public function create(Request $request)
{
    $companyId = $this->companyId($request);

    $categories = Category::query()
        ->where('company_id', $companyId)
        ->orderBy('name')
        ->get([
            'id',
            'name',
        ]);

    return view(
        'demo-cities.create',
        compact('categories')
    );
}


    /*
    |--------------------------------------------------------------------------
    | Store City
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ) {

        $companyId = $this->companyId(
            $request
        );


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
                Rule::exists('categories', 'id')
                    ->where(
                        fn ($query) =>
                        $query->where(
                            'company_id',
                            $companyId
                        )
                    ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Multiple Files
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
            | ZIP Upload
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
        | Create City First
        |--------------------------------------------------------------------------
        */

        $city = DemoCity::create([

            'name' => trim(
                $validated['name']
            ),

            'category_id' => $validated['category_id'],

            'company_id' => $companyId,

            'created_by' => Auth::id(),

            'updated_by' => Auth::id(),

            'media' => [],
        ]);


        $media = [];


        /*
        |--------------------------------------------------------------------------
        | Normal Multiple Upload
        |--------------------------------------------------------------------------
        */

        if (
            $request->hasFile(
                'media_files'
            )
        ) {

            $media = array_merge(

                $media,

                $this->storeUploadedFiles(
                    $city,
                    $request->file(
                        'media_files'
                    )
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | ZIP Upload
        |--------------------------------------------------------------------------
        */

        if (
            $request->hasFile(
                'zip_file'
            )
        ) {

            $media = array_merge(

                $media,

                $this->extractZipAndStore(
                    $city,
                    $request->file(
                        'zip_file'
                    )
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Save Media JSON
        |--------------------------------------------------------------------------
        */

        $city->update([

            'media' => array_values(
                $media
            ),

            'updated_by' => Auth::id(),
        ]);


        return redirect()
            ->route(
                'demo-cities.show',
                $city
            )
            ->with(
                'success',
                'Demo city created successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        DemoCity $demoCity
    ) {

        $demoCity = $this->cityForCompany(
            $request,
            $demoCity
        );


        return view(
            'demo-cities.show',
            compact(
                'demoCity'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(
        Request $request,
        DemoCity $demoCity
    ) {
        $demoCity = $this->cityForCompany(
            $request,
            $demoCity
        );

        $companyId = $this->companyId($request);

        $categories = Category::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        return view(
            'demo-cities.edit',
            compact(
                'demoCity',
                'categories'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        DemoCity $demoCity
    ) {

        $demoCity = $this->cityForCompany(
            $request,
            $demoCity
        );


        $companyId = $this->companyId(
            $request
        );


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
                    ->ignore(
                        $demoCity->id
                    ),
            ],

            'category_id' => [
                'required',
                'integer',

                Rule::exists('categories', 'id')
                    ->where(
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

        $media = $demoCity->media
            ?? [];


        /*
        |--------------------------------------------------------------------------
        | Add New Files
        |--------------------------------------------------------------------------
        */

        if (
            $request->hasFile(
                'media_files'
            )
        ) {

            $media = array_merge(

                $media,

                $this->storeUploadedFiles(
                    $demoCity,
                    $request->file(
                        'media_files'
                    )
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Add ZIP Files
        |--------------------------------------------------------------------------
        */

        if (
            $request->hasFile(
                'zip_file'
            )
        ) {

            $media = array_merge(

                $media,

                $this->extractZipAndStore(
                    $demoCity,
                    $request->file(
                        'zip_file'
                    )
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Update City
        |--------------------------------------------------------------------------
        */

        $demoCity->update([

            'name' => trim(
                $validated['name']
            ),
            'category_id' => $validated['category_id'],
            'media' => array_values(
                $media
            ),

            'updated_by' => Auth::id(),
        ]);


        return redirect()
            ->route(
                'demo-cities.show',
                $demoCity
            )
            ->with(
                'success',
                'Demo city updated successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Separate Multiple Upload
    |--------------------------------------------------------------------------
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


        $media = $demoCity->media
            ?? [];


        $media = array_merge(

            $media,

            $this->storeUploadedFiles(
                $demoCity,
                $request->file(
                    'media_files'
                )
            )
        );


        $demoCity->update([

            'media' => array_values(
                $media
            ),

            'updated_by' => Auth::id(),
        ]);


        return back()->with(
            'success',
            'Demo files uploaded successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Separate ZIP Upload
    |--------------------------------------------------------------------------
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


        $media = array_merge(

            $demoCity->media ?? [],

            $this->extractZipAndStore(
                $demoCity,
                $request->file(
                    'zip_file'
                )
            )
        );


        $demoCity->update([

            'media' => array_values(
                $media
            ),

            'updated_by' => Auth::id(),
        ]);


        return back()->with(
            'success',
            'ZIP demo pack uploaded successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Single Media
    |--------------------------------------------------------------------------
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
            (string) (
                $row['id'] ?? ''
            )
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

        if (
            !empty(
                $item['path']
            )
        ) {

            Storage::disk(
                'public'
            )->delete(
                $item['path']
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Remove From JSON
        |--------------------------------------------------------------------------
        */

        $newMedia = $media
            ->reject(

                fn ($row) =>
                (string) (
                    $row['id'] ?? ''
                )
                ===
                (string) $mediaId
            )
            ->values()
            ->all();


        $demoCity->update([

            'media' => $newMedia,

            'updated_by' => Auth::id(),
        ]);


        return back()->with(
            'success',
            'Demo file deleted successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Download Single Media
    |--------------------------------------------------------------------------
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
            (string) (
                $row['id'] ?? ''
            )
            ===
            (string) $mediaId
        );


        abort_unless(
            $item,
            404,
            'Demo file not found.'
        );


        $path = $item['path']
            ?? null;


        abort_unless(

            $path
            &&
            Storage::disk(
                'public'
            )->exists(
                $path
            ),

            404,

            'Stored file not found.'
        );


        return Storage::disk(
            'public'
        )->download(

            $path,

            $item['original_name']
            ??
            basename(
                $path
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Download City All As ZIP
    |--------------------------------------------------------------------------
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

                !empty(
                    $item['path']
                )

                &&

                Storage::disk(
                    'public'
                )->exists(
                    $item['path']
                )
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


        $zipName = Str::slug(
            $demoCity->name
        )
            . '-demo-pack-'
            . now()->format(
                'Ymd-His'
            )
            . '.zip';


        $zipPath = $tempDirectory
            .
            DIRECTORY_SEPARATOR
            .
            $zipName;


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

            $absolutePath = Storage::disk(
                'public'
            )->path(
                $item['path']
            );


            $name =
                $item['original_name']
                ??
                basename(
                    $absolutePath
                );


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
            ->deleteFileAfterSend(
                true
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Complete City
    |--------------------------------------------------------------------------
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

            if (
                !empty(
                    $item['path']
                )
            ) {

                Storage::disk(
                    'public'
                )->delete(
                    $item['path']
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Delete City Directory
        |--------------------------------------------------------------------------
        */

        Storage::disk(
            'public'
        )->deleteDirectory(

            'demo-cities/'
            .
            $demoCity->company_id
            .
            '/'
            .
            $demoCity->id
        );


        /*
        |--------------------------------------------------------------------------
        | Delete DB
        |--------------------------------------------------------------------------
        */

        $demoCity->delete();


        return redirect()
            ->route(
                'demo-cities.index'
            )
            ->with(
                'success',
                'Demo city deleted successfully.'
            );
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
            ?
            $uploadedFiles
            :
            [$uploadedFiles];


        $stored = [];


        foreach (
            $uploadedFiles
            as
            $file
        ) {

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
    | Store One File
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
        | Safe Filename
        |--------------------------------------------------------------------------
        */

        $safeBase = Str::slug(

            pathinfo(
                $originalName,
                PATHINFO_FILENAME
            )
        );


        if (
            $safeBase === ''
        ) {
            $safeBase = 'demo';
        }


        $storedName =

            now()->format(
                'YmdHis'
            )

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
            ?
            'image'
            :
            'video';


        return [

            'id' => (string) Str::uuid(),

            'original_name' => $originalName,

            'stored_name' => $storedName,

            'path' => $path,

            'type' => $type,

            'mime' => $mime,

            'size' => (int) $file->getSize(),

            'uploaded_at' => now()
                ->toIso8601String(),

            'uploaded_by' => Auth::id(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Extract ZIP
    |--------------------------------------------------------------------------
    */

    private function extractZipAndStore(
        DemoCity $city,
        UploadedFile $zipFile
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Temporary Folder
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
        | Max 500 Files
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

                $stat = $zip->statIndex(
                    $i
                );


                if (
                    !$stat
                    ||
                    empty(
                        $stat['name']
                    )
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
                | Skip Folder / Mac Files
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


                if (
                    !$stream
                ) {
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


                while (
                    !feof(
                        $stream
                    )
                ) {

                    fwrite(

                        $out,

                        fread(
                            $stream,
                            8192
                        )
                    );
                }


                fclose(
                    $out
                );


                fclose(
                    $stream
                );


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

                $stored[] = $this->storeOneFile(

                    $city,

                    $uploaded,

                    basename(
                        $entryName
                    )
                );
            }

        } finally {

            /*
            |--------------------------------------------------------------------------
            | Cleanup
            |--------------------------------------------------------------------------
            */

            $zip->close();


            File::deleteDirectory(
                $tempRoot
            );
        }


        return $stored;
    }


    /*
    |--------------------------------------------------------------------------
    | Prevent Duplicate Names Inside Download ZIP
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

            . (
                $index + 1
            )

            .

            (
                $extension
                ?
                '.' . $extension
                :
                ''
            );
    }
}