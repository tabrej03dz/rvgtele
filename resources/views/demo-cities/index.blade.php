@extends('layouts.crm', [
    'title' => 'Demo Cities'
])

@section('content')

@include('demo-cities._styles')


<div class="demo-board space-y-4">


    {{-- SUCCESS --}}

    @if(
        session('success')
    )

        <div
            class="
                rounded-lg
                border
                border-emerald-200
                bg-emerald-50
                px-4
                py-3
                text-xs
                font-bold
                text-emerald-700
            "
        >

            {{
                session(
                    'success'
                )
            }}

        </div>

    @endif


    {{-- HEADER --}}

    <div
        class="
            flex
            flex-col
            gap-4

            lg:flex-row
            lg:items-center
            lg:justify-between
        "
    >

        <div>

            <h1
                class="
                    text-2xl
                    font-extrabold
                    text-slate-900
                "
            >

                City Wise Demo

            </h1>


            <p
                class="
                    mt-1
                    text-xs
                    text-slate-500
                "
            >

                Manage demo images
                and videos city wise

            </p>

        </div>


        <div
            class="
                flex
                flex-wrap
                items-center
                gap-2
            "
        >


            {{-- SEARCH + CATEGORY FILTER --}}

            <form
                method="GET"
                action="{{ route('demo-cities.index') }}"
                class="flex flex-wrap items-center gap-2"
            >
                <div class="relative">
                    <i
                        data-lucide="search"
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    ></i>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search city..."
                        class="h-10 w-[220px] rounded-lg border border-slate-200 bg-white pl-10 pr-4 text-xs outline-none focus:border-amber-400"
                    >
                </div>

                <select
                    name="category_id"
                    class="h-10 min-w-[180px] rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 outline-none focus:border-amber-400"
                >
                    <option value="">All Categories</option>

                    @foreach($categories as $category)
                        <option
                            value="{{ $category->id }}"
                            @selected((string) request('category_id') === (string) $category->id)
                        >
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                <button
                    type="submit"
                    class="demo-btn demo-btn-dark"
                >
                    <i data-lucide="filter"></i>
                    Filter
                </button>

                @if(request()->filled('search') || request()->filled('category_id'))
                    <a
                        href="{{ route('demo-cities.index') }}"
                        class="demo-btn"
                    >
                        <i data-lucide="x"></i>
                        Clear
                    </a>
                @endif
            </form>


            {{-- ADD CITY --}}

            <a
                href="{{
                    route(
                        'demo-cities.create'
                    )
                }}"

                class="
                    demo-btn
                    demo-btn-yellow
                "
            >

                <i
                    data-lucide="plus"
                ></i>

                Add City Demo

            </a>

        </div>

    </div>


    {{-- STATISTICS --}}

    <div
        class="
            demo-stat-grid
        "
    >


        {{-- CITIES --}}

        <div
            class="
                demo-stat
            "
        >

            <span
                class="
                    demo-stat-icon
                    bg-amber-50
                    text-amber-600
                "
            >

                <i
                    data-lucide="map-pinned"
                ></i>

            </span>


            <div>

                <div
                    class="
                        demo-stat-label
                    "
                >
                    Total Cities
                </div>


                <div
                    class="
                        demo-stat-value
                    "
                >

                    {{
                        number_format(
                            $totalCities
                        )
                    }}

                </div>

            </div>

        </div>


        {{-- TOTAL FILES --}}

        <div
            class="
                demo-stat
            "
        >

            <span
                class="
                    demo-stat-icon
                    bg-blue-50
                    text-blue-600
                "
            >

                <i
                    data-lucide="files"
                ></i>

            </span>


            <div>

                <div
                    class="
                        demo-stat-label
                    "
                >

                    Total Demo Files

                </div>


                <div
                    class="
                        demo-stat-value
                    "
                >

                    {{
                        number_format(
                            $totalFiles
                        )
                    }}

                </div>

            </div>

        </div>


        {{-- IMAGES --}}

        <div
            class="
                demo-stat
            "
        >

            <span
                class="
                    demo-stat-icon
                    bg-emerald-50
                    text-emerald-600
                "
            >

                <i
                    data-lucide="image"
                ></i>

            </span>


            <div>

                <div
                    class="
                        demo-stat-label
                    "
                >

                    Images

                </div>


                <div
                    class="
                        demo-stat-value
                    "
                >

                    {{
                        number_format(
                            $totalImages
                        )
                    }}

                </div>

            </div>

        </div>


        {{-- VIDEOS --}}

        <div
            class="
                demo-stat
            "
        >

            <span
                class="
                    demo-stat-icon
                    bg-violet-50
                    text-violet-600
                "
            >

                <i
                    data-lucide="video"
                ></i>

            </span>


            <div>

                <div
                    class="
                        demo-stat-label
                    "
                >

                    Videos

                </div>


                <div
                    class="
                        demo-stat-value
                    "
                >

                    {{
                        number_format(
                            $totalVideos
                        )
                    }}

                </div>

            </div>

        </div>

    </div>


    {{-- CITY GRID --}}

    <div
        class="
            demo-grid
        "
    >


        @forelse($cities as $city)

            @php
                $media = collect($city->media ?? []);

                $images = $media
                    ->where('type', 'image')
                    ->count();

                $videos = $media
                    ->where('type', 'video')
                    ->count();


                $bulkDownloadFiles = $media
                    ->filter(function ($item) {
                        return !empty($item['id'])
                            && !empty($item['path']);
                    })
                    ->map(function ($item) use ($city) {

                        return [
                            'id' => $item['id'],

                            'name' => $item['original_name']
                                ?? basename($item['path']),

                            'url' => route(
                                'demo-cities.media.download',
                                [
                                    $city,
                                    $item['id']
                                ]
                            ),
                        ];

                    })
                    ->values()
                    ->all();
            @endphp

            <section class="city-card">

                <div class="city-head">

                    <div class="flex items-start justify-between gap-3">

                        <div class="min-w-0">

                            <div class="city-title">

                                <i data-lucide="map-pin"></i>

                                <span>
                                    {{ $city->name }}
                                </span>

                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-[9px] font-extrabold text-amber-700"
                                >
                                    {{ $categoryNames->get($city->category_id, 'No Category') }}
                                </span>

                                <div class="flex gap-2 text-[9px] font-bold text-slate-500">
                                    <span>{{ $media->count() }} Files</span>
                                    <span>•</span>
                                    <span>{{ $images }} Images</span>
                                    <span>•</span>
                                    <span>{{ $videos }} Videos</span>
                                </div>
                            </div>

                        </div>

                        <form
                            method="POST"
                            action="{{ route('demo-cities.destroy', $city) }}"
                            onsubmit="return confirm('Delete this city demo and all its files? This action cannot be undone.');"
                            class="shrink-0"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                title="Delete city demo"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-600 transition hover:border-red-300 hover:bg-red-100"
                            >
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                            </button>
                        </form>

                    </div>

                </div>

                <div class="city-body">

                    <div class="media-preview-grid">

                        @forelse($media->take(3) as $item)

                            <div class="media-preview">

                                @if(($item['type'] ?? '') === 'image')

                                    <img
                                        src="{{ Storage::url($item['path']) }}"
                                        alt=""
                                    >

                                @elseif(($item['type'] ?? '') === 'video')

                                    <div class="media-placeholder">

                                        <i
                                            data-lucide="video"
                                            class="h-7 w-7"
                                        ></i>

                                    </div>

                                @endif

                            </div>

                        @empty

                            <div class="media-preview col-span-3">

                                <div class="media-placeholder text-[10px] font-bold">
                                    No demo uploaded
                                </div>

                            </div>

                        @endforelse

                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">

                        <a
                            href="{{ route('demo-cities.show', $city) }}"
                            class="demo-btn"
                        >

                            <i data-lucide="eye"></i>

                            View

                        </a>

                        <a
                            href="{{ route('demo-cities.edit', $city) }}"
                            class="demo-btn"
                        >

                            <i data-lucide="pencil"></i>

                            Manage

                        </a>

                        

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

                    </div>

                </div>

            </section>

        @empty

            <div class="demo-shell col-span-full p-10 text-center">

                <i
                    data-lucide="folder-open"
                    class="mx-auto h-10 w-10 text-slate-300"
                ></i>

                <div class="mt-3 text-sm font-extrabold text-slate-700">
                    No city demo found
                </div>

                <div class="mt-1 text-xs text-slate-400">
                    Create your first city and upload images/videos.
                </div>

            </div>

        @endforelse

    </div>


    {{-- PAGINATION --}}

    <div>

        {{
            $cities->links()
        }}

    </div>


</div>

<script>
    let bulkDownloadRunning = false;

    function safeDownloadFileName(name) {
        name = String(name || 'demo-file');

        // Windows invalid characters
        name = name.replace(/[<>:"/\\|?*\x00-\x1F]/g, '-');

        // Multiple spaces
        name = name.replace(/\s+/g, ' ');

        // Ending dots/spaces
        name = name.replace(/[. ]+$/g, '');

        if (!name) {
            name = 'demo-file';
        }

        return name;
    }

    function uniqueDownloadFileName(originalName, usedNames) {
        let name = safeDownloadFileName(originalName);
        let lower = name.toLowerCase();

        if (!usedNames.has(lower)) {
            usedNames.add(lower);
            return name;
        }

        const lastDot = name.lastIndexOf('.');

        let base = name;
        let extension = '';

        if (lastDot > 0) {
            base = name.substring(0, lastDot);
            extension = name.substring(lastDot);
        }

        let counter = 2;
        let candidate;

        do {
            candidate = base + ' (' + counter + ')' + extension;
            counter++;
        } while (usedNames.has(candidate.toLowerCase()));

        usedNames.add(candidate.toLowerCase());

        return candidate;
    }

    async function downloadCityFiles(files, button = null) {

        if (bulkDownloadRunning) {
            return;
        }

        if (!Array.isArray(files) || files.length === 0) {
            alert('No demo files available.');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Chrome / Edge Folder API
        |--------------------------------------------------------------------------
        */
        if (typeof window.showDirectoryPicker !== 'function') {
            alert(
                'Bulk Download requires latest Chrome or Edge desktop browser.'
            );
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Select Folder
        |--------------------------------------------------------------------------
        */
        let directoryHandle;

        try {

            directoryHandle = await window.showDirectoryPicker({
                mode: 'readwrite'
            });

        } catch (error) {

            if (error && error.name === 'AbortError') {
                return;
            }

            console.error('Folder picker error:', error);

            alert('Unable to select download folder.');

            return;
        }

        bulkDownloadRunning = true;

        const total = files.length;

        const textElement = button
            ? button.querySelector('.bulk-download-text')
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

            for (let index = 0; index < files.length; index++) {

                const file = files[index];

                if (textElement) {
                    textElement.textContent =
                        `Downloading ${index + 1}/${total}`;
                }

                try {

                    /*
                    |--------------------------------------------------------------------------
                    | Download file from Laravel
                    |--------------------------------------------------------------------------
                    */
                    const response = await fetch(file.url, {
                        method: 'GET',

                        credentials: 'same-origin',

                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(
                            'Download failed: ' + response.status
                        );
                    }

                    const blob = await response.blob();

                    /*
                    |--------------------------------------------------------------------------
                    | Safe unique filename
                    |--------------------------------------------------------------------------
                    */
                    const fileName = uniqueDownloadFileName(
                        file.name,
                        usedNames
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Create file
                    |--------------------------------------------------------------------------
                    */
                    const fileHandle =
                        await directoryHandle.getFileHandle(
                            fileName,
                            {
                                create: true
                            }
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Save blob
                    |--------------------------------------------------------------------------
                    */
                    const writable =
                        await fileHandle.createWritable();

                    await writable.write(blob);

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
            | Completed
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
                    downloaded +
                    ' files successfully downloaded.'
                );

            } else {

                alert(
                    downloaded +
                    ' files downloaded.\n' +
                    failed +
                    ' files failed.'
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
                    textElement.textContent = originalText;
                }

            }, 2000);
        }
    }
</script>

@endsection