@extends('layouts.crm', [
    'title' => 'Manage Demo City'
])

@section('content')

@include('demo-cities._styles')

<div class="demo-board space-y-4">

    {{-- =========================================================
        HEADER
    ========================================================== --}}
    <div
        class="
            flex
            flex-col
            gap-3
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
                Manage {{ $demoCity->name }}
            </h1>

            <p
                class="
                    mt-1
                    text-xs
                    text-slate-500
                "
            >
                Rename city or add more images/videos/ZIP
            </p>
        </div>


        <div class="flex flex-wrap gap-2">

            {{-- VIEW DEMO --}}
            <a
                href="{{ route('demo-cities.show', $demoCity) }}"
                class="demo-btn"
            >
                <i data-lucide="eye"></i>
                View Demo
            </a>


            {{-- DOWNLOAD ALL --}}
            @if(!empty($demoCity->media))

                <a
                    href="{{ route('demo-cities.download-all', $demoCity) }}"
                    class="demo-btn demo-btn-dark"
                >
                    <i data-lucide="archive"></i>
                    Download All
                </a>

            @endif

        </div>

    </div>


    {{-- =========================================================
        VALIDATION ERRORS
    ========================================================== --}}
    @if($errors->any())

        <div
            class="
                rounded-xl
                border
                border-red-200
                bg-red-50
                px-5
                py-4
            "
        >

            <div
                class="
                    text-sm
                    font-extrabold
                    text-red-700
                "
            >
                Please fix the following errors:
            </div>

            <ul
                class="
                    mt-2
                    list-disc
                    space-y-1
                    pl-5
                    text-xs
                    font-medium
                    text-red-600
                "
            >
                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach
            </ul>

        </div>

    @endif


    {{-- =========================================================
        SUCCESS MESSAGE
    ========================================================== --}}
    @if(session('success'))

        <div
            class="
                rounded-xl
                border
                border-emerald-200
                bg-emerald-50
                px-5
                py-4
                text-xs
                font-bold
                text-emerald-700
            "
        >
            {{ session('success') }}
        </div>

    @endif


    {{-- =========================================================
        UPDATE CITY FORM
    ========================================================== --}}

    <form
        method="POST"
        action="{{ route('demo-cities.update', $demoCity) }}"
        enctype="multipart/form-data"
    >

        @csrf
        @method('PUT')

        @include('demo-cities._form', [
            'demoCity' => $demoCity
        ])

    </form>


    {{-- =========================================================
        EXISTING MEDIA
    ========================================================== --}}
    @if(!empty($demoCity->media) && count($demoCity->media) > 0)

        <div class="demo-shell p-5">

            {{-- HEADER --}}
            <div
                class="
                    mb-4
                    flex
                    flex-wrap
                    items-center
                    justify-between
                    gap-3
                "
            >

                <div>

                    <h2
                        class="
                            text-sm
                            font-extrabold
                            text-slate-900
                        "
                    >
                        Current Demo Files
                    </h2>

                    <p
                        class="
                            mt-1
                            text-[10px]
                            text-slate-500
                        "
                    >
                        Single download and delete available
                    </p>

                </div>


                <span
                    class="
                        rounded-full
                        bg-amber-50
                        px-3
                        py-1
                        text-[10px]
                        font-extrabold
                        text-amber-700
                    "
                >
                    {{ count($demoCity->media ?? []) }} Files
                </span>

            </div>


            {{-- =====================================================
                MEDIA GRID
            ====================================================== --}}
            <div class="media-grid">

                @foreach(($demoCity->media ?? []) as $item)

                    @php
                        $mediaId = $item['id'] ?? null;
                        $mediaPath = $item['path'] ?? null;
                        $mediaType = $item['type'] ?? 'file';

                        $mediaName = $item['original_name']
                            ?? (
                                $mediaPath
                                    ? basename($mediaPath)
                                    : 'Demo file'
                            );

                        $mediaSize = (int) ($item['size'] ?? 0);
                    @endphp


                    <div class="media-card">

                        {{-- =========================================
                            PREVIEW
                        ========================================== --}}
                        <div class="media-box">

                            @if(
                                $mediaPath &&
                                $mediaType === 'image'
                            )

                                <img
                                    src="{{ Storage::url($mediaPath) }}"
                                    alt="{{ $mediaName }}"
                                    loading="lazy"
                                >

                            @elseif(
                                $mediaPath &&
                                $mediaType === 'video'
                            )

                                <video
                                    src="{{ Storage::url($mediaPath) }}"
                                    controls
                                    preload="metadata"
                                ></video>

                            @else

                                <div
                                    class="
                                        flex
                                        h-full
                                        w-full
                                        items-center
                                        justify-center
                                        bg-slate-100
                                        text-slate-400
                                    "
                                >
                                    <i
                                        data-lucide="file"
                                        class="h-8 w-8"
                                    ></i>
                                </div>

                            @endif

                        </div>


                        {{-- =========================================
                            MEDIA INFO
                        ========================================== --}}
                        <div class="media-info">

                            <div
                                class="media-name"
                                title="{{ $mediaName }}"
                            >
                                {{ $mediaName }}
                            </div>


                            <div class="media-meta">

                                {{ strtoupper($mediaType) }}

                                <span class="mx-1">•</span>

                                @if($mediaSize > 0)

                                    {{ number_format(
                                        $mediaSize / 1024 / 1024,
                                        2
                                    ) }} MB

                                @else

                                    Size unavailable

                                @endif

                            </div>


                            {{-- =====================================
                                ACTION BUTTONS
                            ====================================== --}}
                            <div class="mt-3 flex gap-2">

                                {{-- DOWNLOAD --}}
                                @if($mediaId)

                                    <a
                                        href="{{ route(
                                            'demo-cities.media.download',
                                            [
                                                $demoCity,
                                                $mediaId
                                            ]
                                        ) }}"
                                        class="demo-btn flex-1"
                                        title="Download"
                                    >
                                        <i data-lucide="download"></i>

                                        <span>
                                            Download
                                        </span>
                                    </a>

                                @endif


                                {{-- DELETE --}}
                                @if($mediaId)

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'demo-cities.media.destroy',
                                            [
                                                $demoCity,
                                                $mediaId
                                            ]
                                        ) }}"
                                        onsubmit="
                                            return confirm(
                                                'Are you sure you want to delete this demo file?'
                                            );
                                        "
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="demo-btn demo-btn-red"
                                            title="Delete"
                                        >
                                            <i data-lucide="trash-2"></i>
                                        </button>

                                    </form>

                                @endif

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

        </div>

    @endif

</div>


{{-- =============================================================
    LUCIDE ICON REFRESH
============================================================== --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {

        if (
            typeof lucide !== 'undefined' &&
            typeof lucide.createIcons === 'function'
        ) {
            lucide.createIcons();
        }

    });
</script>

@endsection