use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

public function download(Request $request)
{
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

    $url = trim($validated['url']);

    /*
    |--------------------------------------------------------------------------
    | Validate Remote URL
    |--------------------------------------------------------------------------
    */

    $parsedUrl = parse_url($url);

    $scheme = strtolower(
        $parsedUrl['scheme'] ?? ''
    );

    $host = strtolower(
        $parsedUrl['host'] ?? ''
    );

    $path = $parsedUrl['path'] ?? '';

    abort_unless(
        $scheme === 'https'
        &&
        $host === 'post.realvictorygroups.com'
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
    | Extension
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
    | Download File Name
    |--------------------------------------------------------------------------
    */

    $requestedName = trim(
        (string) (
            $validated['name'] ?? ''
        )
    );

    if ($requestedName !== '') {

        $baseName = pathinfo(
            $requestedName,
            PATHINFO_FILENAME
        );

        $baseName = preg_replace(
            '/[<>:"\/\\\\|?*\x00-\x1F]/',
            '-',
            $baseName
        );

        $baseName = preg_replace(
            '/\s+/',
            ' ',
            $baseName
        );

        $baseName = trim(
            $baseName,
            ". \t\n\r\0\x0B"
        );

        if ($baseName === '') {
            $baseName = 'demo-image';
        }

        $fileName =
            $baseName
            . '.'
            . $extension;

    } else {

        $fileName =
            basename($path);
    }


    /*
    |--------------------------------------------------------------------------
    | Temp Folder
    |--------------------------------------------------------------------------
    */

    $tempDirectory = storage_path(
        'app/temp/demo-image-downloads'
    );

    File::ensureDirectoryExists(
        $tempDirectory
    );

    $tempPath =
        $tempDirectory
        . DIRECTORY_SEPARATOR
        . Str::uuid()
        . '.'
        . $extension;


    /*
    |--------------------------------------------------------------------------
    | Multiple Attempts
    |--------------------------------------------------------------------------
    */

    $maxAttempts = 5;

    $lastError = null;


    for (
        $attempt = 1;
        $attempt <= $maxAttempts;
        $attempt++
    ) {

        /*
        | Previous failed/partial file clean
        */
        File::delete(
            $tempPath
        );

        try {

            /*
            |--------------------------------------------------------------------------
            | Fetch Like Browser
            |--------------------------------------------------------------------------
            */

            $response = Http::withHeaders([

                    'User-Agent' =>
                        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                        . 'AppleWebKit/537.36 (KHTML, like Gecko) '
                        . 'Chrome/153.0 Safari/537.36',

                    'Accept' =>
                        'image/avif,image/webp,image/apng,image/svg+xml,'
                        . 'image/*,*/*;q=0.8',

                    'Accept-Language' =>
                        'en-US,en;q=0.9',

                    'Referer' =>
                        'https://post.realvictorygroups.com/',

                    'Cache-Control' =>
                        'no-cache',
                ])
                ->connectTimeout(20)
                ->timeout(120)
                ->withOptions([

                    /*
                    | Follow redirects
                    */
                    'allow_redirects' => [
                        'max' => 10,
                        'strict' => false,
                        'referer' => true,
                        'track_redirects' => true,
                    ],

                    /*
                    | Directly write response to file.
                    | Large images bhi memory me load nahi hongi.
                    */
                    'sink' => $tempPath,
                ])
                ->get($url);


            /*
            |--------------------------------------------------------------------------
            | Success Check
            |--------------------------------------------------------------------------
            */

            if (
                $response->successful()
                &&
                File::exists($tempPath)
                &&
                File::size($tempPath) > 0
            ) {

                /*
                |--------------------------------------------------------------------------
                | Verify It Is Really An Image
                |--------------------------------------------------------------------------
                */

                $mime = File::mimeType(
                    $tempPath
                );

                if (
                    $mime
                    &&
                    str_starts_with(
                        strtolower($mime),
                        'image/'
                    )
                ) {

                    return response()
                        ->download(
                            $tempPath,
                            $fileName,
                            [
                                'Content-Type' =>
                                    $mime,

                                'Cache-Control' =>
                                    'no-store, no-cache, must-revalidate',
                            ]
                        )
                        ->deleteFileAfterSend(
                            true
                        );
                }


                /*
                | Remote server ne HTML/error page bhej diya.
                */
                $lastError =
                    'Downloaded content is not an image. MIME: '
                    . ($mime ?: 'unknown');

            } else {

                $lastError =
                    'Remote status: '
                    . $response->status();
            }

        } catch (\Throwable $e) {

            $lastError =
                $e->getMessage();

            Log::warning(
                'Demo image download attempt failed.',
                [
                    'url' => $url,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Wait Before Retry
        |--------------------------------------------------------------------------
        |
        | Attempt 1 => 800ms
        | Attempt 2 => 1600ms
        | Attempt 3 => 2400ms
        | ...
        |
        */

        if (
            $attempt < $maxAttempts
        ) {

            usleep(
                800000 * $attempt
            );
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
        'Demo image download completely failed.',
        [
            'url' => $url,
            'error' => $lastError,
        ]
    );


    return response()->json([
        'status' => false,
        'message' => 'Image download failed after multiple attempts.',
        'error' => $lastError,
    ], 502);
}