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

        $numberOfImages = (int) (
            $validated['number_of_images'] ?? 10
        );

        $apiUrl = 'https://post.realvictorygroups.com/api/demo-images';

        if (!$apiUrl) {

            return response()->json([
                'status' => false,
                'message' => 'Demo images API URL is not configured.',
                'data' => [],
            ], 500);
        }

        try {

            $response = Http::acceptJson()
                ->timeout(30)
                ->retry(2, 500)
                ->post(
                    $apiUrl,
                    [
                        'city' => $city,
                        'number_of_images' => $numberOfImages,
                    ]
                );

            if (!$response->successful()) {

                Log::warning(
                    'Demo images external API failed.',
                    [
                        'city' => $city,
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]
                );

                return response()->json([
                    'status' => false,
                    'message' => 'Unable to fetch demo images.',
                    'city' => $city,
                    'requested_images' => $numberOfImages,
                    'total_images' => 0,
                    'data' => [],
                ], $response->status());
            }

            $externalData = $response->json();

            $images = collect(
                $externalData['data'] ?? []
            )
                ->filter(function ($image) {

                    return is_array($image)
                        && !empty($image['media']);
                })
                ->map(function ($image) {

                    return [
                        'customer_id' =>
                            $image['customer_id'] ?? null,

                        'customer_name' =>
                            $image['customer_name'] ?? null,

                        'phone' =>
                            $image['phone'] ?? null,

                        'city' =>
                            $image['city'] ?? null,

                        'image_id' =>
                            $image['image_id'] ?? null,

                        'title' =>
                            $image['title'] ?? null,

                        'tag' =>
                            $image['tag'] ?? null,

                        'date' =>
                            $image['date'] ?? null,

                        /*
                        |--------------------------------------------------------------------------
                        | Full Remote Image URL
                        |--------------------------------------------------------------------------
                        */

                        'media' =>
                            $image['media'],

                        'user_id' =>
                            $image['user_id'] ?? null,

                        'user_package_id' =>
                            $image['user_package_id'] ?? null,

                        'sent' =>
                            $image['sent'] ?? null,
                    ];
                })
                ->values();

            return response()->json([
                'status' => true,
                'message' => 'Demo images fetched successfully.',
                'city' => $city,
                'requested_images' => $numberOfImages,
                'total_images' => $images->count(),
                'data' => $images,
            ]);

        } catch (\Throwable $e) {

            Log::error(
                'Demo images API exception.',
                [
                    'city' => $city,
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch demo images.',
                'city' => $city,
                'requested_images' => $numberOfImages,
                'total_images' => 0,
                'data' => [],
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Download Remote Demo Image
    |--------------------------------------------------------------------------
    |
    | Browser ko direct post.realvictorygroups.com se fetch karwane ke bajay
    | Laravel ke through download karenge.
    |
    | Isse CORS problem nahi hogi aur bulk download bhi properly chalega.
    |
    */

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

        $url = $validated['url'];

        /*
        |--------------------------------------------------------------------------
        | Security Check
        |--------------------------------------------------------------------------
        |
        | Sirf apne main image server se hi download allow karenge.
        |
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
        | Original Extension
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
                $validated['name'] ?? ''
            )
        );

        if ($requestedName !== '') {

            $fileName = $this->safeDownloadName(
                $requestedName,
                $extension
            );

        } else {

            $fileName = basename($path);
        }


        /*
        |--------------------------------------------------------------------------
        | Temporary Directory
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


        try {

            /*
            |--------------------------------------------------------------------------
            | Download Remote Image To Temporary File
            |--------------------------------------------------------------------------
            */

            $response = Http::timeout(90)
                ->retry(2, 500)
                ->withOptions([
                    'sink' => $tempPath,
                ])
                ->get($url);


            if (
                !$response->successful()
                ||
                !File::exists($tempPath)
            ) {

                File::delete($tempPath);

                abort(
                    404,
                    'Image could not be downloaded.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Send Download
            |--------------------------------------------------------------------------
            */

            return response()
                ->download(
                    $tempPath,
                    $fileName
                )
                ->deleteFileAfterSend(
                    true
                );

        } catch (\Throwable $e) {

            File::delete($tempPath);

            report($e);

            abort(
                500,
                'Unable to download image.'
            );
        }
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
        | Remove extension supplied by browser
        */
        $baseName = pathinfo(
            $name,
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

        return $baseName
            . '.'
            . $extension;
    }
}