@extends('layouts.crm', ['title' => $demoCity->name . ' Demo'])

@section('content')

@include('demo-cities._styles')

@php
    $media = collect($demoCity->media ?? []);

    $images = $media
        ->where('type', 'image')
        ->count();

    $videos = $media
        ->where('type', 'video')
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Bulk Download Files
    |--------------------------------------------------------------------------
    */

    $bulkDownloadFiles = $media
        ->filter(function ($item) {
            return !empty($item['id'])
                && !empty($item['path']);
        })
        ->map(function ($item) use ($demoCity) {

            return [
                'id' => $item['id'],

                'name' => $item['original_name']
                    ?? basename($item['path']),

                'url' => route(
                    'demo-cities.media.download',
                    [
                        $demoCity,
                        $item['id']
                    ]
                ),
            ];

        })
        ->values()
        ->all();
@endphp


<div class="demo-board space-y-4">

    {{-- HEADER --}}
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

        <div>

            <div class="flex items-center gap-2">

                <i
                    data-lucide="map-pin"
                    class="h-6 w-6 text-amber-500"
                ></i>

                <h1 class="text-2xl font-extrabold text-slate-900">
                    {{ $demoCity->name }} Demo
                </h1>

            </div>

            <p class="mt-1 text-xs text-slate-500">
                All demo images and videos for this city
            </p>

        </div>


        <div class="flex flex-wrap gap-2">

            {{-- ALL CITIES --}}
            <a
                href="{{ route('demo-cities.index') }}"
                class="demo-btn"
            >
                <i data-lucide="arrow-left"></i>

                All Cities
            </a>


            {{-- MANAGE --}}
            <a
                href="{{ route('demo-cities.edit', $demoCity) }}"
                class="demo-btn demo-btn-yellow"
            >
                <i data-lucide="upload"></i>

                Upload / Manage
            </a>


            {{-- BULK INDIVIDUAL DOWNLOAD --}}
            @if($media->isNotEmpty())

                <button
                    type="button"
                    class="demo-btn demo-btn-dark"
                    onclick='downloadCityFiles(
                        @json($bulkDownloadFiles),
                        this
                    )'
                >

                    <i data-lucide="download"></i>

                    <span class="bulk-download-text">
                        Bulk Download
                    </span>

                </button>

            @endif


            {{-- OPTIONAL ZIP --}}
            @if($media->isNotEmpty())

                <a
                    href="{{ route('demo-cities.download-all', $demoCity) }}"
                    class="demo-btn"
                    title="Download all files inside one ZIP"
                >
                    <i data-lucide="archive"></i>

                    ZIP
                </a>

            @endif

        </div>

    </div>



    {{-- STATS --}}
    <div class="demo-stat-grid">

        {{-- TOTAL --}}
        <div class="demo-stat">

            <span class="demo-stat-icon bg-blue-50 text-blue-600">
                <i data-lucide="files"></i>
            </span>

            <div>

                <div class="demo-stat-label">
                    Total Files
                </div>

                <div class="demo-stat-value">
                    {{ $media->count() }}
                </div>

            </div>

        </div>


        {{-- IMAGES --}}
        <div class="demo-stat">

            <span class="demo-stat-icon bg-emerald-50 text-emerald-600">
                <i data-lucide="image"></i>
            </span>

            <div>

                <div class="demo-stat-label">
                    Images
                </div>

                <div class="demo-stat-value">
                    {{ $images }}
                </div>

            </div>

        </div>


        {{-- VIDEOS --}}
        <div class="demo-stat">

            <span class="demo-stat-icon bg-violet-50 text-violet-600">
                <i data-lucide="video"></i>
            </span>

            <div>

                <div class="demo-stat-label">
                    Videos
                </div>

                <div class="demo-stat-value">
                    {{ $videos }}
                </div>

            </div>

        </div>


        {{-- UPDATED --}}
        <div class="demo-stat">

            <span class="demo-stat-icon bg-amber-50 text-amber-600">
                <i data-lucide="calendar"></i>
            </span>

            <div>

                <div class="demo-stat-label">
                    Last Updated
                </div>

                <div class="mt-1 text-xs font-extrabold text-slate-800">

                    {{ $demoCity->updated_at?->format('d M Y, h:i A') }}

                </div>

            </div>

        </div>

    </div>



    {{-- MEDIA GALLERY --}}
    <div class="demo-shell p-5">

        @if($media->isNotEmpty())

            <div class="media-grid">

                @foreach($media as $item)

                    <div class="media-card">

                        {{-- MEDIA --}}
                        <div class="media-box">

                            @if(($item['type'] ?? '') === 'image')

                                <a
                                    href="{{ Storage::url($item['path']) }}"
                                    target="_blank"
                                >

                                    <img
                                        src="{{ Storage::url($item['path']) }}"
                                        alt="{{ $item['original_name'] ?? 'Demo Image' }}"
                                    >

                                </a>

                            @elseif(($item['type'] ?? '') === 'video')

                                <video
                                    src="{{ Storage::url($item['path']) }}"
                                    controls
                                    preload="metadata"
                                ></video>

                            @endif

                        </div>


                        {{-- DETAILS --}}
                        <div class="media-info">

                            <div
                                class="media-name"
                                title="{{ $item['original_name'] ?? '' }}"
                            >
                                {{ $item['original_name'] ?? 'Demo file' }}
                            </div>


                            <div class="media-meta">

                                {{ strtoupper($item['type'] ?? 'file') }}

                                •

                                {{ number_format(
                                    ($item['size'] ?? 0) / 1024 / 1024,
                                    2
                                ) }} MB

                            </div>


                            <div class="mt-3 flex gap-2">

                                {{-- SINGLE DOWNLOAD --}}
                                <a
                                    href="{{ route(
                                        'demo-cities.media.download',
                                        [
                                            $demoCity,
                                            $item['id']
                                        ]
                                    ) }}"
                                    class="demo-btn flex-1"
                                >
                                    <i data-lucide="download"></i>

                                    Download
                                </a>


                                {{-- DELETE --}}
                                <form
                                    method="POST"
                                    action="{{ route(
                                        'demo-cities.media.destroy',
                                        [
                                            $demoCity,
                                            $item['id']
                                        ]
                                    ) }}"
                                    onsubmit="return confirm('Delete this demo file?')"
                                >

                                    @csrf

                                    @method('DELETE')


                                    <button
                                        type="submit"
                                        class="demo-btn demo-btn-red"
                                    >
                                        <i data-lucide="trash-2"></i>
                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            {{-- EMPTY --}}
            <div class="py-12 text-center">

                <i
                    data-lucide="folder-open"
                    class="mx-auto h-12 w-12 text-slate-300"
                ></i>

                <div class="mt-3 text-sm font-extrabold text-slate-700">
                    No demo files uploaded
                </div>

                <a
                    href="{{ route(
                        'demo-cities.edit',
                        $demoCity
                    ) }}"
                    class="demo-btn demo-btn-yellow mt-4"
                >
                    <i data-lucide="upload"></i>

                    Upload Demo
                </a>

            </div>

        @endif

    </div>



    {{-- DELETE CITY --}}
    <div class="demo-shell border-rose-100 p-5">

        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

            <div>

                <div class="text-xs font-extrabold text-slate-800">
                    Delete complete city demo
                </div>

                <div class="mt-1 text-[10px] text-slate-500">
                    This will remove city and all stored demo files.
                </div>

            </div>


            <form
                method="POST"
                action="{{ route(
                    'demo-cities.destroy',
                    $demoCity
                ) }}"
                onsubmit="return confirm(
                    'Delete city and ALL demo files permanently?'
                )"
            >

                @csrf

                @method('DELETE')


                <button
                    type="submit"
                    class="demo-btn demo-btn-red"
                >
                    <i data-lucide="trash-2"></i>

                    Delete City
                </button>

            </form>

        </div>

    </div>

</div>


{{-- BULK DOWNLOAD SCRIPT --}}
<script>
    window.bulkDownloadRunning = false;

    async function bulkDownloadFiles(urls, button = null) {

        if (window.bulkDownloadRunning) {
            return;
        }

        if (!Array.isArray(urls) || urls.length === 0) {

            alert('No demo files available for download.');

            return;
        }


        const total = urls.length;

        const textElement = button
            ? button.querySelector('.bulk-download-text')
            : null;

        const originalText = textElement
            ? textElement.innerText
            : 'Bulk Download';


        window.bulkDownloadRunning = true;


        if (button) {
            button.disabled = true;
            button.style.opacity = '0.65';
            button.style.cursor = 'not-allowed';
        }


        try {

            for (
                let index = 0;
                index < urls.length;
                index++
            ) {

                const url = urls[index];


                /*
                |--------------------------------------------------------------------------
                | Update Button
                |--------------------------------------------------------------------------
                */

                if (textElement) {

                    textElement.innerText =
                        `Downloading ${index + 1}/${total}`;

                }


                /*
                |--------------------------------------------------------------------------
                | Create Temporary Download Link
                |--------------------------------------------------------------------------
                */

                const link = document.createElement('a');

                link.href = url;

                link.style.display = 'none';


                /*
                |--------------------------------------------------------------------------
                | Add To DOM
                |--------------------------------------------------------------------------
                */

                document.body.appendChild(link);


                /*
                |--------------------------------------------------------------------------
                | Trigger Download
                |--------------------------------------------------------------------------
                */

                link.click();


                /*
                |--------------------------------------------------------------------------
                | Remove Temporary Link
                |--------------------------------------------------------------------------
                */

                setTimeout(() => {

                    if (link.parentNode) {
                        link.parentNode.removeChild(link);
                    }

                }, 1000);


                /*
                |--------------------------------------------------------------------------
                | Delay Between Downloads
                |--------------------------------------------------------------------------
                |
                | Browser ko ek file ka request start karne ka time milega.
                |
                */

                await new Promise(resolve => {

                    setTimeout(resolve, 900);

                });

            }


            if (textElement) {

                textElement.innerText =
                    `${total} Files Started`;

            }


            setTimeout(() => {

                if (textElement) {
                    textElement.innerText = originalText;
                }

            }, 2500);

        } catch (error) {

            console.error(
                'Bulk download error:',
                error
            );


            alert(
                'Some files could not be downloaded.'
            );


            if (textElement) {
                textElement.innerText = originalText;
            }

        } finally {

            window.bulkDownloadRunning = false;


            if (button) {

                setTimeout(() => {

                    button.disabled = false;
                    button.style.opacity = '';
                    button.style.cursor = '';

                }, 1000);

            }

        }

    }
</script>

<script>
    let bulkDownloadRunning = false;


    /*
    |--------------------------------------------------------------------------
    | Safe File Name
    |--------------------------------------------------------------------------
    */

    function safeDownloadFileName(name) {

        name = String(name || 'demo-file');

        /*
        | Windows invalid characters remove
        */

        name = name.replace(
            /[<>:"/\\|?*\x00-\x1F]/g,
            '-'
        );

        /*
        | Multiple spaces
        */

        name = name.replace(/\s+/g, ' ');

        /*
        | Ending dots/spaces
        */

        name = name.replace(/[. ]+$/g, '');

        if (!name) {
            name = 'demo-file';
        }

        return name;
    }


    /*
    |--------------------------------------------------------------------------
    | Duplicate Filename Protection
    |--------------------------------------------------------------------------
    */

    function uniqueDownloadFileName(
        originalName,
        usedNames
    ) {

        let name = safeDownloadFileName(
            originalName
        );

        let lower = name.toLowerCase();


        if (!usedNames.has(lower)) {

            usedNames.add(lower);

            return name;
        }


        /*
        |--------------------------------------------------------------------------
        | Separate Extension
        |--------------------------------------------------------------------------
        */

        const lastDot = name.lastIndexOf('.');

        let base = name;

        let extension = '';


        if (lastDot > 0) {

            base = name.substring(
                0,
                lastDot
            );

            extension = name.substring(
                lastDot
            );
        }


        let counter = 2;

        let candidate;


        do {

            candidate =
                base
                + ' ('
                + counter
                + ')'
                + extension;

            counter++;

        } while (
            usedNames.has(
                candidate.toLowerCase()
            )
        );


        usedNames.add(
            candidate.toLowerCase()
        );


        return candidate;
    }


    /*
    |--------------------------------------------------------------------------
    | Bulk Download City Files
    |--------------------------------------------------------------------------
    */

    async function downloadCityFiles(
        files,
        button = null
    ) {

        if (bulkDownloadRunning) {
            return;
        }


        if (
            !Array.isArray(files)
            ||
            files.length === 0
        ) {

            alert(
                'No demo files available.'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Chrome / Edge Folder API Check
        |--------------------------------------------------------------------------
        */

        if (
            typeof window.showDirectoryPicker
            !==
            'function'
        ) {

            alert(
                'Bulk individual download requires Chrome or Edge desktop. Please open this page in latest Chrome/Edge.'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | Folder picker ko direct click ke andar hi open karna hai.
        | Isse browser multiple-download blocking problem nahi hogi.
        |
        */

        let directoryHandle;


        try {

            directoryHandle =
                await window.showDirectoryPicker({
                    mode: 'readwrite'
                });

        } catch (error) {

            /*
            | User cancelled folder selection
            */

            if (
                error
                &&
                error.name === 'AbortError'
            ) {
                return;
            }


            console.error(error);

            alert(
                'Unable to select download folder.'
            );

            return;
        }


        bulkDownloadRunning = true;


        const total = files.length;


        const textElement = button
            ? button.querySelector(
                '.bulk-download-text'
            )
            : null;


        const originalText = textElement
            ? textElement.textContent
            : 'Bulk Download';


        if (button) {

            button.disabled = true;

            button.style.opacity = '0.65';

            button.style.cursor = 'not-allowed';
        }


        const usedNames = new Set();

        let downloaded = 0;

        let failed = 0;


        try {

            for (
                let index = 0;
                index < files.length;
                index++
            ) {

                const file = files[index];


                /*
                |--------------------------------------------------------------------------
                | Progress
                |--------------------------------------------------------------------------
                */

                if (textElement) {

                    textElement.textContent =
                        `Downloading ${index + 1}/${total}`;
                }


                try {

                    /*
                    |--------------------------------------------------------------------------
                    | Download From Laravel
                    |--------------------------------------------------------------------------
                    */

                    const response = await fetch(
                        file.url,
                        {
                            method: 'GET',

                            credentials: 'same-origin',

                            headers: {
                                'X-Requested-With':
                                    'XMLHttpRequest'
                            }
                        }
                    );


                    if (!response.ok) {

                        throw new Error(
                            'Download failed: '
                            + response.status
                        );
                    }


                    const blob =
                        await response.blob();


                    /*
                    |--------------------------------------------------------------------------
                    | Unique Original Filename
                    |--------------------------------------------------------------------------
                    */

                    const fileName =
                        uniqueDownloadFileName(
                            file.name,
                            usedNames
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Create File In Selected Folder
                    |--------------------------------------------------------------------------
                    */

                    const fileHandle =
                        await directoryHandle
                            .getFileHandle(
                                fileName,
                                {
                                    create: true
                                }
                            );


                    /*
                    |--------------------------------------------------------------------------
                    | Write File
                    |--------------------------------------------------------------------------
                    */

                    const writable =
                        await fileHandle
                            .createWritable();


                    await writable.write(
                        blob
                    );


                    await writable.close();


                    downloaded++;

                } catch (fileError) {

                    failed++;


                    console.error(
                        'Failed file:',
                        file,
                        fileError
                    );
                }

            }


            /*
            |--------------------------------------------------------------------------
            | Complete
            |--------------------------------------------------------------------------
            */

            if (textElement) {

                if (failed === 0) {

                    textElement.textContent =
                        `${downloaded} Files Downloaded`;

                } else {

                    textElement.textContent =
                        `${downloaded} Downloaded, ${failed} Failed`;
                }
            }


            if (failed === 0) {

                alert(
                    downloaded
                    + ' files successfully downloaded.'
                );

            } else {

                alert(
                    downloaded
                    + ' files downloaded.\n'
                    + failed
                    + ' files failed.'
                );
            }

        } catch (error) {

            console.error(
                'Bulk download error:',
                error
            );


            alert(
                'Bulk download could not be completed.'
            );

        } finally {

            bulkDownloadRunning = false;


            setTimeout(function () {

                if (button) {

                    button.disabled = false;

                    button.style.opacity = '';

                    button.style.cursor = '';
                }


                if (textElement) {

                    textElement.textContent =
                        originalText;
                }

            }, 2000);
        }
    }
</script>

@endsection