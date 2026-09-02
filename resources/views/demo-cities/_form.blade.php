@php

    $editing =
        isset(
            $demoCity
        );

@endphp


{{-- VALIDATION ERROR --}}

@if(
    $errors->any()
)

    <div
        class="
            mb-4
            rounded-lg
            border
            border-rose-200
            bg-rose-50
            px-4
            py-3
            text-xs
            font-bold
            text-rose-700
        "
    >

        {{
            $errors->first()
        }}

    </div>

@endif


<div
    class="
        demo-shell
        p-5
    "
>


    {{-- CITY + INFORMATION --}}

    <div
        class="
            grid
            gap-5

            lg:grid-cols-2
        "
    >


        {{-- CITY --}}

        <div
            class="
                demo-field
            "
        >

            <label>

                City Name

                <span
                    class="
                        text-rose-500
                    "
                >
                    *
                </span>

            </label>


            <input

                type="text"

                name="name"

                required

                maxlength="120"

                value="{{
                    old(
                        'name',
                        $demoCity->name
                        ??
                        ''
                    )
                }}"

                placeholder="Example: Kanpur"
            >

        </div>


        {{-- HELP --}}

        <div
            class="
                rounded-xl
                border
                border-amber-200
                bg-amber-50
                p-4
            "
        >

            <div
                class="
                    flex
                    items-start
                    gap-3
                "
            >


                <i
                    data-lucide="info"

                    class="
                        mt-0.5
                        h-4
                        w-4
                        text-amber-600
                    "
                ></i>


                <div
                    class="
                        text-[10px]
                        leading-5
                        text-slate-600
                    "
                >

                    You can upload
                    multiple images and
                    videos together.

                    <br>

                    You can also upload
                    one complete ZIP pack.

                </div>

            </div>

        </div>

    </div>


    {{-- UPLOAD SECTION --}}

    <div
        class="
            mt-5
            grid
            gap-4

            lg:grid-cols-2
        "
    >


        {{-- NORMAL FILE UPLOAD --}}

        <div
            class="
                drop-box
            "
        >


            <i
                data-lucide="images"

                class="
                    mx-auto
                    h-8
                    w-8
                    text-amber-500
                "
            ></i>


            <div
                class="
                    mt-2
                    text-xs
                    font-extrabold
                    text-slate-800
                "
            >

                Upload Images / Videos

            </div>


            <div
                class="
                    mt-1
                    text-[9px]
                    text-slate-500
                "
            >

                JPG, PNG, WEBP,
                GIF, MP4, MOV,
                AVI, MKV, WEBM

            </div>


            <input

                class="
                    mt-4
                    block
                    w-full
                    text-[10px]
                "

                type="file"

                name="media_files[]"

                multiple

                accept="
                    image/*,
                    video/*
                "
            >

        </div>


        {{-- ZIP FILE --}}

        <div
            class="
                drop-box
            "
        >


            <i
                data-lucide="archive"

                class="
                    mx-auto
                    h-8
                    w-8
                    text-amber-500
                "
            ></i>


            <div
                class="
                    mt-2
                    text-xs
                    font-extrabold
                    text-slate-800
                "
            >

                Upload Complete ZIP Pack

            </div>


            <div
                class="
                    mt-1
                    text-[9px]
                    text-slate-500
                "
            >

                ZIP ke andar
                jitni valid images/videos
                hongi sab upload ho jayengi.

            </div>


            <input

                class="
                    mt-4
                    block
                    w-full
                    text-[10px]
                "

                type="file"

                name="zip_file"

                accept="
                    .zip,
                    application/zip
                "
            >

        </div>

    </div>


    {{-- BUTTONS --}}

    <div
        class="
            mt-5
            flex
            flex-wrap
            justify-end
            gap-2
        "
    >


        {{-- CANCEL --}}

        <a

            href="{{
                $editing

                ?

                route(
                    'demo-cities.show',
                    $demoCity
                )

                :

                route(
                    'demo-cities.index'
                )
            }}"

            class="
                demo-btn
            "
        >

            Cancel

        </a>


        {{-- SUBMIT --}}

        <button

            type="submit"

            class="
                demo-btn
                demo-btn-yellow
            "
        >


            <i
                data-lucide="{{
                    $editing
                    ?
                    'save'
                    :
                    'plus'
                }}"
            ></i>


            {{
                $editing

                ?

                'Update City Demo'

                :

                'Create City Demo'
            }}

        </button>

    </div>

</div>