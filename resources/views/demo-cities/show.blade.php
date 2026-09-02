@extends(
    'layouts.crm',
    [
        'title'
        =>
        $demoCity->name
        .
        ' Demo'
    ]
)


@section('content')


@include(
    'demo-cities._styles'
)


@php

    $media = collect(
        $demoCity->media
        ??
        []
    );


    $images = $media
        ->where(
            'type',
            'image'
        )
        ->count();


    $videos = $media
        ->where(
            'type',
            'video'
        )
        ->count();

@endphp


<div
    class="
        demo-board
        space-y-4
    "
>


    {{-- SUCCESS --}}

    @if(
        session(
            'success'
        )
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
            gap-3

            lg:flex-row
            lg:items-center
            lg:justify-between
        "
    >


        <div>


            <div
                class="
                    flex
                    items-center
                    gap-2
                "
            >


                <i
                    data-lucide="map-pin"

                    class="
                        h-6
                        w-6
                        text-amber-500
                    "
                ></i>


                <h1
                    class="
                        text-2xl
                        font-extrabold
                        text-slate-900
                    "
                >

                    {{
                        $demoCity->name
                    }}

                    Demo

                </h1>


            </div>


            <p
                class="
                    mt-1
                    text-xs
                    text-slate-500
                "
            >

                All demo images and
                videos for this city

            </p>


        </div>


        {{-- BUTTONS --}}

        <div
            class="
                flex
                flex-wrap
                gap-2
            "
        >


            {{-- BACK --}}

            <a
                href="{{
                    route(
                        'demo-cities.index'
                    )
                }}"

                class="
                    demo-btn
                "
            >

                <i
                    data-lucide="arrow-left"
                ></i>

                All Cities

            </a>


            {{-- MANAGE --}}

            <a
                href="{{
                    route(
                        'demo-cities.edit',
                        $demoCity
                    )
                }}"

                class="
                    demo-btn
                    demo-btn-yellow
                "
            >

                <i
                    data-lucide="upload"
                ></i>

                Upload / Manage

            </a>


            {{-- ZIP DOWNLOAD --}}

            @if(
                $media->isNotEmpty()
            )

                <a
                    href="{{
                        route(
                            'demo-cities.download-all',
                            $demoCity
                        )
                    }}"

                    class="
                        demo-btn
                        demo-btn-dark
                    "
                >

                    <i
                        data-lucide="archive"
                    ></i>

                    Download All ZIP

                </a>

            @endif


        </div>


    </div>


    {{-- STATS --}}

    <div
        class="
            demo-stat-grid
        "
    >


        {{-- TOTAL --}}

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

                    Total Files

                </div>


                <div
                    class="
                        demo-stat-value
                    "
                >

                    {{
                        $media->count()
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
                        $images
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
                        $videos
                    }}

                </div>

            </div>

        </div>


        {{-- UPDATE --}}

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
                    data-lucide="calendar"
                ></i>

            </span>


            <div>

                <div
                    class="
                        demo-stat-label
                    "
                >

                    Last Updated

                </div>


                <div
                    class="
                        mt-1
                        text-xs
                        font-extrabold
                        text-slate-800
                    "
                >

                    {{
                        $demoCity
                            ->updated_at
                            ?->format(
                                'd M Y, h:i A'
                            )
                    }}

                </div>

            </div>

        </div>


    </div>


    {{-- MEDIA GALLERY --}}

    <div
        class="
            demo-shell
            p-5
        "
    >


        @if(
            $media->isNotEmpty()
        )


            <div
                class="
                    media-grid
                "
            >


                @foreach(
                    $media
                    as
                    $item
                )


                    <div
                        class="
                            media-card
                        "
                    >


                        {{-- PREVIEW --}}

                        <div
                            class="
                                media-box
                            "
                        >


                            @if(
                                ($item['type'] ?? '')
                                ===
                                'image'
                            )


                                <a

                                    href="{{
                                        Storage::url(
                                            $item['path']
                                        )
                                    }}"

                                    target="_blank"
                                >


                                    <img

                                        src="{{
                                            Storage::url(
                                                $item['path']
                                            )
                                        }}"

                                        alt=""
                                    >


                                </a>


                            @else


                                <video

                                    src="{{
                                        Storage::url(
                                            $item['path']
                                        )
                                    }}"

                                    controls

                                    preload="
                                        metadata
                                    "
                                ></video>


                            @endif


                        </div>


                        {{-- INFO --}}

                        <div
                            class="
                                media-info
                            "
                        >


                            <div
                                class="
                                    media-name
                                "

                                title="{{
                                    $item[
                                        'original_name'
                                    ]
                                    ??
                                    ''
                                }}"
                            >

                                {{
                                    $item[
                                        'original_name'
                                    ]
                                    ??
                                    'Demo file'
                                }}

                            </div>


                            <div
                                class="
                                    media-meta
                                "
                            >

                                {{
                                    strtoupper(
                                        $item[
                                            'type'
                                        ]
                                        ??
                                        'file'
                                    )
                                }}

                                •

                                {{
                                    number_format(

                                        (
                                            $item[
                                                'size'
                                            ]
                                            ??
                                            0
                                        )

                                        /

                                        1024

                                        /

                                        1024,

                                        2
                                    )
                                }}

                                MB

                            </div>


                            {{-- ACTIONS --}}

                            <div
                                class="
                                    mt-3
                                    flex
                                    gap-2
                                "
                            >


                                {{-- DOWNLOAD --}}

                                <a

                                    href="{{
                                        route(
                                            'demo-cities.media.download',
                                            [
                                                $demoCity,
                                                $item[
                                                    'id'
                                                ]
                                            ]
                                        )
                                    }}"

                                    class="
                                        demo-btn
                                        flex-1
                                    "
                                >

                                    <i
                                        data-lucide="download"
                                    ></i>

                                    Download

                                </a>


                                {{-- DELETE --}}

                                <form

                                    method="POST"

                                    action="{{
                                        route(
                                            'demo-cities.media.destroy',
                                            [
                                                $demoCity,
                                                $item[
                                                    'id'
                                                ]
                                            ]
                                        )
                                    }}"

                                    onsubmit="
                                        return confirm(
                                            'Delete this demo file?'
                                        )
                                    "
                                >


                                    @csrf

                                    @method('DELETE')


                                    <button

                                        type="submit"

                                        class="
                                            demo-btn
                                            demo-btn-red
                                        "
                                    >

                                        <i
                                            data-lucide="trash-2"
                                        ></i>

                                    </button>


                                </form>


                            </div>


                        </div>


                    </div>


                @endforeach


            </div>


        @else


            {{-- EMPTY --}}

            <div
                class="
                    py-12
                    text-center
                "
            >


                <i
                    data-lucide="folder-open"

                    class="
                        mx-auto
                        h-12
                        w-12
                        text-slate-300
                    "
                ></i>


                <div
                    class="
                        mt-3
                        text-sm
                        font-extrabold
                        text-slate-700
                    "
                >

                    No demo files uploaded

                </div>


                <a
                    href="{{
                        route(
                            'demo-cities.edit',
                            $demoCity
                        )
                    }}"

                    class="
                        demo-btn
                        demo-btn-yellow
                        mt-4
                    "
                >

                    <i
                        data-lucide="upload"
                    ></i>

                    Upload Demo

                </a>


            </div>


        @endif


    </div>


    {{-- DELETE COMPLETE CITY --}}

    <div
        class="
            demo-shell
            border-rose-100
            p-5
        "
    >


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


                <div
                    class="
                        text-xs
                        font-extrabold
                        text-slate-800
                    "
                >

                    Delete complete city demo

                </div>


                <div
                    class="
                        mt-1
                        text-[10px]
                        text-slate-500
                    "
                >

                    This will remove
                    city and all stored
                    demo files.

                </div>


            </div>


            <form

                method="POST"

                action="{{
                    route(
                        'demo-cities.destroy',
                        $demoCity
                    )
                }}"

                onsubmit="
                    return confirm(
                        'Delete city and ALL demo files permanently?'
                    )
                "
            >


                @csrf

                @method('DELETE')


                <button

                    class="
                        demo-btn
                        demo-btn-red
                    "

                    type="submit"
                >

                    <i
                        data-lucide="trash-2"
                    ></i>

                    Delete City

                </button>


            </form>


        </div>


    </div>


</div>


@endsection