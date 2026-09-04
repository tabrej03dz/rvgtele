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


            {{-- SEARCH --}}

            <form
                method="GET"

                action="{{
                    route(
                        'demo-cities.index'
                    )
                }}"

                class="relative"
            >


                <i
                    data-lucide="search"

                    class="
                        absolute
                        left-3
                        top-1/2
                        h-4
                        w-4
                        -translate-y-1/2
                        text-slate-400
                    "
                ></i>


                <input
                    type="text"

                    name="search"

                    value="{{
                        request(
                            'search'
                        )
                    }}"

                    placeholder="Search city..."

                    class="
                        h-10
                        w-[280px]
                        rounded-lg
                        border
                        border-slate-200
                        bg-white
                        pl-10
                        pr-4
                        text-xs
                        outline-none

                        focus:border-amber-400
                    "
                >

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

                        <div>

                            <div class="city-title">

                                <i data-lucide="map-pin"></i>

                                <span>
                                    {{ $city->name }}
                                </span>

                            </div>

                            <div class="mt-2 flex gap-2 text-[9px] font-bold text-slate-500">

                                <span>
                                    {{ $media->count() }} Files
                                </span>

                                <span>•</span>

                                <span>
                                    {{ $images }} Images
                                </span>

                                <span>•</span>

                                <span>
                                    {{ $videos }} Videos
                                </span>

                            </div>

                        </div>

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

                if (textElement) {

                    textElement.innerText =
                        `Downloading ${index + 1}/${total}`;

                }


                const link =
                    document.createElement('a');

                link.href = urls[index];

                link.style.display = 'none';


                document.body.appendChild(link);

                link.click();


                setTimeout(() => {

                    if (link.parentNode) {

                        link.parentNode
                            .removeChild(link);

                    }

                }, 1000);


                /*
                |--------------------------------------------------------------------------
                | 900ms Gap
                |--------------------------------------------------------------------------
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

        } finally {

            window.bulkDownloadRunning = false;


            setTimeout(() => {

                if (button) {

                    button.disabled = false;
                    button.style.opacity = '';
                    button.style.cursor = '';

                }


                if (textElement) {

                    textElement.innerText =
                        originalText;

                }

            }, 1000);

        }
    }
</script>

@endsection