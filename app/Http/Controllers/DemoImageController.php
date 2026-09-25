<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DemoImageController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Demo Images Page
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return view('demo-images.index');
    }


    /*
    |--------------------------------------------------------------------------
    | Get Demo Images From Main Project
    |--------------------------------------------------------------------------
    */

    public function images(Request $request)
    {
        $validated = $request->validate([
            'city' => [
                'required',
                'string',
                'max:255',
            ],

            'number_of_images' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $city = trim($validated['city']);

        /*
        |--------------------------------------------------------------------------
        | Default 10 Images
        |--------------------------------------------------------------------------
        */

        $numberOfImages = (int) (
            $validated['number_of_images'] ?? 10
        );


        /*
        |--------------------------------------------------------------------------
        | Main Project API
        |--------------------------------------------------------------------------
        */

        $apiUrl =
            'https://post.realvictorygroups.com/api/demo-images';


        try {

            /*
            |--------------------------------------------------------------------------
            | Call Main API
            |--------------------------------------------------------------------------
            */

            $response = Http::acceptJson()
                ->connectTimeout(15)
                ->timeout(60)
                ->retry(
                    3,
                    700
                )
                ->post(
                    $apiUrl,
                    [
                        'city' => $city,

                        'number_of_images' =>
                            $numberOfImages,
                    ]
                );


            /*
            |--------------------------------------------------------------------------
            | API Error
            |--------------------------------------------------------------------------
            */

            if (!$response->successful()) {

                Log::warning(
                    'Demo images external API failed.',
                    [
                        'city' => $city,

                        'status' =>
                            $response->status(),

                        'response' =>
                            $response->body(),
                    ]
                );

                return response()->json([
                    'status' => false,

                    'message' =>
                        'Unable to fetch demo images.',

                    'city' =>
                        $city,

                    'requested_images' =>
                        $numberOfImages,

                    'total_images' =>
                        0,

                    'data' =>
                        [],
                ], $response->status());
            }


            /*
            |--------------------------------------------------------------------------
            | Response Data
            |--------------------------------------------------------------------------
            */

            $externalData =
                $response->json();


            $images = collect(
                $externalData['data'] ?? []
            )
                ->filter(function ($image) {

                    return is_array($image)
                        &&
                        !empty(
                            $image['media']
                        );
                })
                ->map(function ($image) {

                    return [

                        'customer_id' =>
                            $image['customer_id']
                            ?? null,

                        'customer_name' =>
                            $image['customer_name']
                            ?? null,

                        'phone' =>
                            $image['phone']
                            ?? null,

                        'city' =>
                            $image['city']
                            ?? null,

                        'image_id' =>
                            $image['image_id']
                            ?? null,

                        'title' =>
                            $image['title']
                            ?? null,

                        'tag' =>
                            $image['tag']
                            ?? null,

                        'date' =>
                            $image['date']
                            ?? null,

                        /*
                        |--------------------------------------------------------------------------
                        | Full Image URL
                        |--------------------------------------------------------------------------
                        */

                        'media' =>
                            $image['media'],

                        'user_id' =>
                            $image['user_id']
                            ?? null,

                        'user_package_id' =>
                            $image['user_package_id']
                            ?? null,

                        'sent' =>
                            $image['sent']
                            ?? null,
                    ];
                })
                ->values();


            /*
            |--------------------------------------------------------------------------
            | Success Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'status' => true,

                'message' =>
                    'Demo images fetched successfully.',

                'city' =>
                    $city,

                'requested_images' =>
                    $numberOfImages,

                'total_images' =>
                    $images->count(),

                'data' =>
                    $images,
            ]);

        } catch (\Throwable $e) {

            Log::error(
                'Demo images API exception.',
                [
                    'city' =>
                        $city,

                    'message' =>
                        $e->getMessage(),
                ]
            );

            return response()->json([
                'status' => false,

                'message' =>
                    'Unable to fetch demo images.',

                'city' =>
                    $city,

                'requested_images' =>
                    $numberOfImages,

                'total_images' =>
                    0,

                'data' =>
                    [],
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Download Remote Demo Image
    |--------------------------------------------------------------------------
    |
    | Image ko browser direct remote domain se download nahi karega.
    |
    | Flow:
    |
    | Browser
    |    ↓
    | This Laravel Project
    |    ↓
    | post.realvictorygroups.com
    |
    | Isse CORS problem nahi hogi.
    |
    */

    public function download(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'url' => [
                'required',
                'url',
            ],

            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);


        $url = trim(
            $validated['url']
        );


        /*
        |--------------------------------------------------------------------------
        | Parse URL
        |--------------------------------------------------------------------------
        */

        $parsedUrl =
            parse_url(
                $url
            );


        $scheme = strtolower(
            $parsedUrl['scheme']
            ?? ''
        );


        $host = strtolower(
            $parsedUrl['host']
            ?? ''
        );


        $path =
            $parsedUrl['path']
            ?? '';


        /*
        |--------------------------------------------------------------------------
        | Security Check
        |--------------------------------------------------------------------------
        |
        | Sirf apne image server ki storage/images files allow hongi.
        |
        */

        abort_unless(
            $scheme === 'https'
            &&
            $host ===
                'post.realvictorygroups.com'
            &&
            str_starts_with(
                $path,
                '/storage/images/'
            ),
            403,
            'Invalid demo image URL.'
        );


        /*
        |--------------------------------------------------------------------------
        | Image Extension
        |--------------------------------------------------------------------------
        */

        $extension = strtolower(
            pathinfo(
                $path,
                PATHINFO_EXTENSION
            )
        );


        $allowedExtensions = [
            'jpg',
            'jpeg',
            'png',
            'webp',
            'gif',
        ];


        abort_unless(
            in_array(
                $extension,
                $allowedExtensions,
                true
            ),
            422,
            'Invalid image format.'
        );


        /*
        |--------------------------------------------------------------------------
        | Download Filename
        |--------------------------------------------------------------------------
        */

        $requestedName = trim(
            (string) (
                $validated['name']
                ?? ''
            )
        );


        if (
            $requestedName !== ''
        ) {

            $fileName =
                $this->safeDownloadName(
                    $requestedName,
                    $extension
                );

        } else {

            $fileName =
                basename(
                    $path
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Temporary Folder
        |--------------------------------------------------------------------------
        */

        $tempDirectory =
            storage_path(
                'app/temp/demo-image-downloads'
            );


        File::ensureDirectoryExists(
            $tempDirectory
        );


        $tempPath =
            $tempDirectory
            .
            DIRECTORY_SEPARATOR
            .
            Str::uuid()
            .
            '.'
            .
            $extension;


        /*
        |--------------------------------------------------------------------------
        | Retry Settings
        |--------------------------------------------------------------------------
        |
        | Agar remote server temporary fail kare:
        |
        | Attempt 1
        | Attempt 2
        | Attempt 3
        | Attempt 4
        | Attempt 5
        |
        */

        $maxAttempts = 5;

        $lastError = null;


        /*
        |--------------------------------------------------------------------------
        | Download Attempts
        |--------------------------------------------------------------------------
        */

        for (
            $attempt = 1;
            $attempt <= $maxAttempts;
            $attempt++
        ) {

            /*
            | Previous incomplete file remove
            */
            File::delete(
                $tempPath
            );


            try {

                /*
                |--------------------------------------------------------------------------
                | Remote Request
                |--------------------------------------------------------------------------
                */

                $response = Http::withHeaders([

                        'User-Agent' =>
                            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                            .
                            'AppleWebKit/537.36 (KHTML, like Gecko) '
                            .
                            'Chrome/153.0 Safari/537.36',

                        'Accept' =>
                            'image/avif,image/webp,image/apng,'
                            .
                            'image/*,*/*;q=0.8',

                        'Accept-Language' =>
                            'en-US,en;q=0.9',

                        'Referer' =>
                            'https://post.realvictorygroups.com/',

                        'Cache-Control' =>
                            'no-cache',

                        'Pragma' =>
                            'no-cache',
                    ])
                    ->connectTimeout(
                        20
                    )
                    ->timeout(
                        120
                    )
                    ->withOptions([

                        /*
                        | Follow remote redirects
                        */
                        'allow_redirects' => [
                            'max' => 10,
                            'strict' => false,
                            'referer' => true,
                            'track_redirects' => true,
                        ],

                        /*
                        | Response directly file me save
                        */
                        'sink' =>
                            $tempPath,
                    ])
                    ->get(
                        $url
                    );


                /*
                |--------------------------------------------------------------------------
                | HTTP Success Check
                |--------------------------------------------------------------------------
                */

                if (
                    !$response->successful()
                ) {

                    $lastError =
                        'Remote server returned HTTP '
                        .
                        $response->status();


                    Log::warning(
                        'Demo image remote HTTP error.',
                        [
                            'url' =>
                                $url,

                            'attempt' =>
                                $attempt,

                            'status' =>
                                $response->status(),
                        ]
                    );


                    /*
                    | Retry
                    */
                    if (
                        $attempt < $maxAttempts
                    ) {

                        usleep(
                            800000
                            *
                            $attempt
                        );

                        continue;
                    }


                    break;
                }


                /*
                |--------------------------------------------------------------------------
                | File Exists Check
                |--------------------------------------------------------------------------
                */

                if (
                    !File::exists(
                        $tempPath
                    )
                ) {

                    $lastError =
                        'Temporary image file was not created.';


                    if (
                        $attempt < $maxAttempts
                    ) {

                        usleep(
                            800000
                            *
                            $attempt
                        );

                        continue;
                    }


                    break;
                }


                /*
                |--------------------------------------------------------------------------
                | Empty File Check
                |--------------------------------------------------------------------------
                */

                $fileSize =
                    File::size(
                        $tempPath
                    );


                if (
                    $fileSize <= 0
                ) {

                    $lastError =
                        'Downloaded image is empty.';


                    File::delete(
                        $tempPath
                    );


                    if (
                        $attempt < $maxAttempts
                    ) {

                        usleep(
                            800000
                            *
                            $attempt
                        );

                        continue;
                    }


                    break;
                }


                /*
                |--------------------------------------------------------------------------
                | MIME Validation
                |--------------------------------------------------------------------------
                |
                | Kabhi server 200 return karta hai lekin actual image ke badle
                | HTML error page return ho sakta hai.
                |
                */

                $mime = null;


                try {

                    $mime =
                        File::mimeType(
                            $tempPath
                        );

                } catch (\Throwable $mimeException) {

                    $mime =
                        null;
                }


                /*
                |--------------------------------------------------------------------------
                | Fallback MIME From Extension
                |--------------------------------------------------------------------------
                */

                if (
                    !$mime
                    ||
                    !str_starts_with(
                        strtolower(
                            $mime
                        ),
                        'image/'
                    )
                ) {

                    $mimeByExtension = [
                        'jpg' =>
                            'image/jpeg',

                        'jpeg' =>
                            'image/jpeg',

                        'png' =>
                            'image/png',

                        'webp' =>
                            'image/webp',

                        'gif' =>
                            'image/gif',
                    ];


                    /*
                    | Agar File MIME clearly HTML/text hai to reject
                    */
                    if (
                        $mime
                        &&
                        (
                            str_contains(
                                strtolower($mime),
                                'text/html'
                            )
                            ||
                            str_contains(
                                strtolower($mime),
                                'text/plain'
                            )
                            ||
                            str_contains(
                                strtolower($mime),
                                'application/json'
                            )
                        )
                    ) {

                        $lastError =
                            'Remote server returned invalid content: '
                            .
                            $mime;


                        File::delete(
                            $tempPath
                        );


                        if (
                            $attempt < $maxAttempts
                        ) {

                            usleep(
                                800000
                                *
                                $attempt
                            );

                            continue;
                        }


                        break;
                    }


                    $mime =
                        $mimeByExtension[
                            $extension
                        ]
                        ??
                        'application/octet-stream';
                }


                /*
                |--------------------------------------------------------------------------
                | Success
                |--------------------------------------------------------------------------
                */

                Log::info(
                    'Demo image downloaded successfully.',
                    [
                        'url' =>
                            $url,

                        'attempt' =>
                            $attempt,

                        'size' =>
                            $fileSize,

                        'mime' =>
                            $mime,
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | Return File To Browser
                |--------------------------------------------------------------------------
                */

                return response()
                    ->download(
                        $tempPath,
                        $fileName,
                        [
                            'Content-Type' =>
                                $mime,

                            'Cache-Control' =>
                                'no-store, no-cache, must-revalidate',

                            'Pragma' =>
                                'no-cache',
                        ]
                    )
                    ->deleteFileAfterSend(
                        true
                    );


            } catch (\Throwable $e) {

                /*
                |--------------------------------------------------------------------------
                | Attempt Failed
                |--------------------------------------------------------------------------
                */

                $lastError =
                    $e->getMessage();


                File::delete(
                    $tempPath
                );


                Log::warning(
                    'Demo image download attempt failed.',
                    [
                        'url' =>
                            $url,

                        'attempt' =>
                            $attempt,

                        'max_attempts' =>
                            $maxAttempts,

                        'error' =>
                            $e->getMessage(),
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | Retry Delay
                |--------------------------------------------------------------------------
                */

                if (
                    $attempt < $maxAttempts
                ) {

                    usleep(
                        800000
                        *
                        $attempt
                    );
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | All Attempts Failed
        |--------------------------------------------------------------------------
        */

        File::delete(
            $tempPath
        );


        Log::error(
            'Demo image completely failed.',
            [
                'url' =>
                    $url,

                'error' =>
                    $lastError,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | JSON Error
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => false,

            'message' =>
                'Image download failed after multiple attempts.',

            'error' =>
                $lastError,
        ], 502);
    }


    /*
    |--------------------------------------------------------------------------
    | Safe Download Filename
    |--------------------------------------------------------------------------
    */

    private function safeDownloadName(
        string $name,
        string $extension
    ): string {

        /*
        |--------------------------------------------------------------------------
        | Remove Existing Extension
        |--------------------------------------------------------------------------
        */

        $baseName =
            pathinfo(
                $name,
                PATHINFO_FILENAME
            );


        /*
        |--------------------------------------------------------------------------
        | Invalid Windows Filename Characters
        |--------------------------------------------------------------------------
        */

        $baseName =
            preg_replace(
                '/[<>:"\/\\\\|?*\x00-\x1F]/',
                '-',
                $baseName
            );


        /*
        |--------------------------------------------------------------------------
        | Multiple Spaces
        |--------------------------------------------------------------------------
        */

        $baseName =
            preg_replace(
                '/\s+/',
                ' ',
                $baseName
            );


        /*
        |--------------------------------------------------------------------------
        | Trim
        |--------------------------------------------------------------------------
        */

        $baseName =
            trim(
                $baseName,
                ". \t\n\r\0\x0B"
            );


        /*
        |--------------------------------------------------------------------------
        | Fallback
        |--------------------------------------------------------------------------
        */

        if (
            $baseName === ''
        ) {

            $baseName =
                'demo-image';
        }


        /*
        |--------------------------------------------------------------------------
        | Final Name
        |--------------------------------------------------------------------------
        */

        return $baseName
            .
            '.'
            .
            $extension;
    }
}