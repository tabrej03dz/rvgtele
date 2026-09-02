@extends(
    'layouts.crm',
    [
        'title'
        =>
        'Manage Demo City'
    ]
)


@section('content')


@include(
    'demo-cities._styles'
)


<div
    class="
        demo-board
        space-y-4
    "
>


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


            <h1
                class="
                    text-2xl
                    font-extrabold
                    text-slate-900
                "
            >

                Manage

                {{
                    $demoCity->name
                }}

            </h1>


            <p
                class="
                    mt-1
                    text-xs
                    text-slate-500
                "
            >

                Rename city or
                add more images/videos/ZIP

            </p>


        </div>


        <div
            class="
                flex
                gap-2
            "
        >


            {{-- VIEW --}}

            <a
                href="{{
                    route(
                        'demo-cities.show',
                        $demoCity
                    )
                }}"

                class="
                    demo-btn
                "
            >

                <i
                    data-lucide="eye"
                ></i>

                View Demo

            </a>


            {{-- DOWNLOAD ALL --}}

            @if(
                !empty(
                    $demoCity->media
                )
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

                    Download All

                </a>

            @endif


        </div>

    </div>


    {{-- UPDATE FORM --}}

    <form

        method="POST"

        action="{{
            route(
                'demo-cities.update',
                $demoCity
            )
        }}"

        enctype="
            multipart/form-data
        "
    >


        @csrf

        @method('PUT')


        @include(
            'demo-cities._form',
            [
                'demoCity'
                =>
                $demoCity
            ]
        )


    </form>


    {{-- EXISTING MEDIA --}}

    @if(
        !empty(
            $demoCity->media
        )
    )


        <div
            class="
                demo-shell
                p-5
            "
        >


            {{-- TITLE --}}

            <div
                class="
                    mb-4
                    flex
                    items-center
                    justify-between
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

                        Single download
                        and delete available

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

                    {{
                        count(
                            $demoCity->media
                            ??
                            []
                        )
                    }}

                    Files

                </span>


            </div>


            {{-- MEDIA GRID --}}

            <div
                class="
                    media-grid
                "
            >


                @foreach(
                    (
                        $demoCity->media
                        ??
                        []
                    )
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


                                <img

                                    src="{{
                                        Storage::url(
                                            $item['path']
                                        )
                                    }}"

                                    alt=""
                                >


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


        </div>


    @endif


</div>


@endsection