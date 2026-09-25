@extends('layouts.crm', [
    'title' => 'Demo Images'
])

@section('content')

<div class="mx-auto max-w-7xl space-y-5">

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
                Demo Images
            </h1>

            <p
                class="
                    mt-1
                    text-xs
                    text-slate-500
                "
            >
                Search and download city wise customer demo images
            </p>

        </div>


        {{-- BACK --}}
        <a
            href="{{ route('demo-cities.index') }}"
            class="
                inline-flex
                h-10
                items-center
                justify-center
                gap-2
                rounded-lg
                border
                border-slate-200
                bg-white
                px-4
                text-xs
                font-extrabold
                text-slate-700
                transition
                hover:bg-slate-50
            "
        >

            <i
                data-lucide="arrow-left"
                class="h-4 w-4"
            ></i>

            Demo Cities

        </a>

    </div>


    {{-- SEARCH --}}
    <div
        class="
            rounded-xl
            border
            border-slate-200
            bg-white
            p-4
            shadow-sm
        "
    >

        <form
            id="demoImageForm"
            class="
                flex
                flex-col
                gap-3
                md:flex-row
                md:items-end
            "
        >

            {{-- CITY --}}
            <div class="flex-1">

                <label
                    class="
                        mb-1.5
                        block
                        text-xs
                        font-bold
                        text-slate-600
                    "
                >
                    City
                </label>

                <input
                    type="text"
                    id="city"
                    name="city"
                    placeholder="Enter city name..."
                    required
                    class="
                        h-11
                        w-full
                        rounded-lg
                        border
                        border-slate-200
                        bg-white
                        px-4
                        text-sm
                        outline-none
                        transition
                        focus:border-amber-400
                    "
                >

            </div>


            {{-- NUMBER OF IMAGES --}}
            <div
                class="
                    w-full
                    md:w-48
                "
            >

                <label
                    class="
                        mb-1.5
                        block
                        text-xs
                        font-bold
                        text-slate-600
                    "
                >
                    Number of Images
                </label>

                <input
                    type="number"
                    id="number_of_images"
                    name="number_of_images"
                    value="10"
                    min="1"
                    max="100"
                    class="
                        h-11
                        w-full
                        rounded-lg
                        border
                        border-slate-200
                        bg-white
                        px-4
                        text-sm
                        outline-none
                        transition
                        focus:border-amber-400
                    "
                >

            </div>


            {{-- SEARCH BUTTON --}}
            <button
                type="submit"
                id="searchButton"
                class="
                    inline-flex
                    h-11
                    items-center
                    justify-center
                    gap-2
                    rounded-lg
                    bg-slate-900
                    px-5
                    text-xs
                    font-extrabold
                    text-white
                    transition
                    hover:bg-slate-800
                    disabled:cursor-not-allowed
                    disabled:opacity-60
                "
            >

                <i
                    data-lucide="search"
                    class="h-4 w-4"
                ></i>

                <span
                    id="searchButtonText"
                >
                    Get Images
                </span>

            </button>

        </form>

    </div>


    {{-- RESULT INFORMATION --}}
    <div
        id="resultInfo"
        class="
            hidden
            rounded-xl
            border
            border-slate-200
            bg-white
            p-4
            shadow-sm
        "
    >

        <div
            class="
                flex
                flex-col
                gap-4
                md:flex-row
                md:items-center
                md:justify-between
            "
        >

            <div
                class="
                    flex
                    flex-wrap
                    items-center
                    gap-8
                "
            >

                {{-- CITY --}}
                <div>

                    <div
                        class="
                            text-xs
                            text-slate-500
                        "
                    >
                        City
                    </div>

                    <div
                        id="resultCity"
                        class="
                            mt-1
                            text-base
                            font-extrabold
                            text-slate-900
                        "
                    ></div>

                </div>


                {{-- IMAGES --}}
                <div>

                    <div
                        class="
                            text-xs
                            text-slate-500
                        "
                    >
                        Images Found
                    </div>

                    <div
                        id="resultCount"
                        class="
                            mt-1
                            text-xl
                            font-extrabold
                            text-slate-900
                        "
                    >
                        0
                    </div>

                </div>

            </div>


            {{-- DOWNLOAD ALL --}}
            <button
                type="button"
                id="bulkDownloadButton"
                onclick="downloadAllDemoImages(this)"
                class="
                    hidden
                    inline-flex
                    h-10
                    items-center
                    justify-center
                    gap-2
                    rounded-lg
                    bg-slate-900
                    px-4
                    text-xs
                    font-extrabold
                    text-white
                    transition
                    hover:bg-slate-800
                    disabled:cursor-not-allowed
                    disabled:opacity-60
                "
            >

                <i
                    data-lucide="download"
                    class="h-4 w-4"
                ></i>

                <span
                    class="bulk-download-text"
                >
                    Download All
                </span>

            </button>

        </div>

    </div>


    {{-- DOWNLOAD PROGRESS --}}
    <div
        id="downloadProgressBox"
        class="
            hidden
            rounded-xl
            border
            border-blue-200
            bg-blue-50
            p-4
        "
    >

        <div
            class="
                flex
                items-center
                justify-between
                gap-4
            "
        >

            <div
                class="
                    min-w-0
                    flex-1
                "
            >

                <div
                    class="
                        text-xs
                        font-extrabold
                        text-blue-900
                    "
                >
                    Downloading Demo Images
                </div>

                <div
                    id="downloadCurrentFile"
                    class="
                        mt-1
                        truncate
                        text-[10px]
                        font-semibold
                        text-blue-700
                    "
                >
                    Preparing...
                </div>

            </div>


            <div
                id="downloadProgressText"
                class="
                    shrink-0
                    text-xs
                    font-extrabold
                    text-blue-900
                "
            >
                0 / 0
            </div>

        </div>


        {{-- PROGRESS BAR --}}
        <div
            class="
                mt-3
                h-2
                overflow-hidden
                rounded-full
                bg-blue-100
            "
        >

            <div
                id="downloadProgressBar"
                class="
                    h-full
                    rounded-full
                    bg-blue-600
                    transition-all
                    duration-300
                "
                style="width: 0%"
            ></div>

        </div>


        {{-- COUNTS --}}
        <div
            class="
                mt-2
                flex
                flex-wrap
                items-center
                justify-between
                gap-3
                text-[10px]
                font-bold
            "
        >

            <span
                class="
                    text-emerald-700
                "
            >
                Downloaded:

                <span
                    id="downloadedCount"
                >
                    0
                </span>
            </span>


            <span
                class="
                    text-amber-700
                "
            >
                Retrying:

                <span
                    id="retryCount"
                >
                    0
                </span>
            </span>


            <span
                class="
                    text-red-600
                "
            >
                Failed:

                <span
                    id="failedCount"
                >
                    0
                </span>
            </span>

        </div>

    </div>


    {{-- LOADING --}}
    <div
        id="loadingBox"
        class="
            hidden
            rounded-xl
            border
            border-slate-200
            bg-white
            p-10
            text-center
        "
    >

        <div
            class="
                mx-auto
                h-8
                w-8
                animate-spin
                rounded-full
                border-4
                border-slate-200
                border-t-slate-800
            "
        ></div>

        <div
            class="
                mt-3
                text-xs
                font-bold
                text-slate-500
            "
        >
            Loading demo images...
        </div>

    </div>


    {{-- ERROR --}}
    <div
        id="errorBox"
        class="
            hidden
            rounded-xl
            border
            border-red-200
            bg-red-50
            p-4
            text-xs
            font-bold
            text-red-700
        "
    ></div>


    {{-- EMPTY --}}
    <div
        id="emptyBox"
        class="
            hidden
            rounded-xl
            border
            border-slate-200
            bg-white
            p-12
            text-center
        "
    >

        <i
            data-lucide="image-off"
            class="
                mx-auto
                h-10
                w-10
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
            No demo images found
        </div>

        <div
            class="
                mt-1
                text-xs
                text-slate-400
            "
        >
            Try another city.
        </div>

    </div>


    {{-- IMAGE GRID --}}
    <div
        id="imageGrid"
        class="
            grid
            grid-cols-1
            gap-4
            sm:grid-cols-2
            lg:grid-cols-3
            xl:grid-cols-4
        "
    ></div>

</div>


<script>

    /*
    |--------------------------------------------------------------------------
    | Global Variables
    |--------------------------------------------------------------------------
    */

    let currentDemoImages = [];

    let bulkDownloadRunning = false;


    /*
    |--------------------------------------------------------------------------
    | Elements
    |--------------------------------------------------------------------------
    */

    const form =
        document.getElementById(
            'demoImageForm'
        );


    const grid =
        document.getElementById(
            'imageGrid'
        );


    const loadingBox =
        document.getElementById(
            'loadingBox'
        );


    const errorBox =
        document.getElementById(
            'errorBox'
        );


    const emptyBox =
        document.getElementById(
            'emptyBox'
        );


    const resultInfo =
        document.getElementById(
            'resultInfo'
        );


    const resultCity =
        document.getElementById(
            'resultCity'
        );


    const resultCount =
        document.getElementById(
            'resultCount'
        );


    const searchButton =
        document.getElementById(
            'searchButton'
        );


    const searchButtonText =
        document.getElementById(
            'searchButtonText'
        );


    const bulkDownloadButton =
        document.getElementById(
            'bulkDownloadButton'
        );


    const downloadProgressBox =
        document.getElementById(
            'downloadProgressBox'
        );


    const downloadProgressText =
        document.getElementById(
            'downloadProgressText'
        );


    const downloadProgressBar =
        document.getElementById(
            'downloadProgressBar'
        );


    const downloadCurrentFile =
        document.getElementById(
            'downloadCurrentFile'
        );


    const downloadedCount =
        document.getElementById(
            'downloadedCount'
        );


    const retryCount =
        document.getElementById(
            'retryCount'
        );


    const failedCount =
        document.getElementById(
            'failedCount'
        );


    /*
    |--------------------------------------------------------------------------
    | Search Demo Images
    |--------------------------------------------------------------------------
    */

    form.addEventListener(
        'submit',
        async function (event) {

            event.preventDefault();


            if (
                bulkDownloadRunning
            ) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Reset
            |--------------------------------------------------------------------------
            */

            currentDemoImages =
                [];


            grid.innerHTML =
                '';


            errorBox
                .classList
                .add(
                    'hidden'
                );


            emptyBox
                .classList
                .add(
                    'hidden'
                );


            resultInfo
                .classList
                .add(
                    'hidden'
                );


            bulkDownloadButton
                .classList
                .add(
                    'hidden'
                );


            downloadProgressBox
                .classList
                .add(
                    'hidden'
                );


            loadingBox
                .classList
                .remove(
                    'hidden'
                );


            searchButton.disabled =
                true;


            searchButtonText.textContent =
                'Loading...';


            /*
            |--------------------------------------------------------------------------
            | Values
            |--------------------------------------------------------------------------
            */

            const city =
                document
                    .getElementById(
                        'city'
                    )
                    .value
                    .trim();


            const numberOfImages =
                document
                    .getElementById(
                        'number_of_images'
                    )
                    .value;


            try {

                /*
                |--------------------------------------------------------------------------
                | API Call
                |--------------------------------------------------------------------------
                */

                const response =
                    await fetch(
                        "{{ route('api.demo-images') }}",
                        {
                            method:
                                'POST',

                            headers: {

                                'Accept':
                                    'application/json',

                                'Content-Type':
                                    'application/json',
                            },

                            body:
                                JSON.stringify({

                                    city:
                                        city,

                                    number_of_images:
                                        numberOfImages
                                            ? Number(
                                                numberOfImages
                                            )
                                            : null,
                                }),
                        }
                    );


                /*
                |--------------------------------------------------------------------------
                | JSON
                |--------------------------------------------------------------------------
                */

                const data =
                    await response.json();


                /*
                |--------------------------------------------------------------------------
                | Check Error
                |--------------------------------------------------------------------------
                */

                if (
                    !response.ok
                    ||
                    !data.status
                ) {

                    throw new Error(
                        data.message
                        ||
                        'Unable to load images.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Images
                |--------------------------------------------------------------------------
                */

                currentDemoImages =
                    Array.isArray(
                        data.data
                    )
                        ?
                        data.data
                        :
                        [];


                /*
                |--------------------------------------------------------------------------
                | Header Information
                |--------------------------------------------------------------------------
                */

                resultCity.textContent =
                    data.city
                    ??
                    city;


                resultCount.textContent =
                    currentDemoImages.length;


                resultInfo
                    .classList
                    .remove(
                        'hidden'
                    );


                /*
                |--------------------------------------------------------------------------
                | Empty
                |--------------------------------------------------------------------------
                */

                if (
                    currentDemoImages.length
                    ===
                    0
                ) {

                    emptyBox
                        .classList
                        .remove(
                            'hidden'
                        );

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Download All Button
                |--------------------------------------------------------------------------
                */

                bulkDownloadButton
                    .classList
                    .remove(
                        'hidden'
                    );


                /*
                |--------------------------------------------------------------------------
                | Render Images
                |--------------------------------------------------------------------------
                */

                currentDemoImages
                    .forEach(
                        function (
                            item,
                            index
                        ) {

                            renderDemoImage(
                                item,
                                index,
                                city
                            );
                        }
                    );


            } catch (error) {

                console.error(
                    error
                );


                errorBox.textContent =
                    error.message
                    ||
                    'Something went wrong.';


                errorBox
                    .classList
                    .remove(
                        'hidden'
                    );


            } finally {

                loadingBox
                    .classList
                    .add(
                        'hidden'
                    );


                searchButton.disabled =
                    false;


                searchButtonText.textContent =
                    'Get Images';


                refreshIcons();
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Render Single Image
    |--------------------------------------------------------------------------
    */

    function renderDemoImage(
        item,
        index,
        city
    ) {

        const card =
            document.createElement(
                'div'
            );


        card.className =
            'overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm';


        /*
        |--------------------------------------------------------------------------
        | Image URL
        |--------------------------------------------------------------------------
        */

        const imageUrl =
            item.media
            ??
            '';


        /*
        |--------------------------------------------------------------------------
        | Download Filename
        |--------------------------------------------------------------------------
        */

        const fileName =
            makeDemoFileName(
                item,
                index
            );


        /*
        |--------------------------------------------------------------------------
        | Laravel Proxy Download URL
        |--------------------------------------------------------------------------
        */

        const downloadUrl =
            buildDownloadUrl(
                imageUrl,
                fileName
            );


        /*
        |--------------------------------------------------------------------------
        | Card
        |--------------------------------------------------------------------------
        */

        card.innerHTML = `

            <div
                class="
                    relative
                    aspect-[4/5]
                    overflow-hidden
                    bg-slate-100
                "
            >

                <a
                    href="${escapeHtml(imageUrl)}"
                    target="_blank"
                    rel="noopener"
                >

                    <img
                        src="${escapeHtml(imageUrl)}"
                        alt="${escapeHtml(
                            item.title
                            ||
                            'Demo Image'
                        )}"
                        loading="lazy"
                        class="
                            h-full
                            w-full
                            object-cover
                            transition
                            duration-300
                            hover:scale-[1.02]
                        "
                    >

                </a>


                <div
                    class="
                        absolute
                        left-2
                        top-2
                        rounded-md
                        bg-black/70
                        px-2
                        py-1
                        text-[9px]
                        font-extrabold
                        text-white
                    "
                >
                    ${index + 1}
                </div>

            </div>


            <div
                class="
                    p-3
                "
            >

                <div
                    class="
                        truncate
                        text-xs
                        font-extrabold
                        text-slate-800
                    "
                    title="${escapeHtml(
                        item.title
                        ||
                        item.customer_name
                        ||
                        'Demo Image'
                    )}"
                >
                    ${
                        escapeHtml(
                            item.title
                            ||
                            item.customer_name
                            ||
                            'Demo Image'
                        )
                    }
                </div>


                <div
                    class="
                        mt-2
                        flex
                        items-center
                        justify-between
                        gap-2
                        text-[10px]
                        font-semibold
                        text-slate-500
                    "
                >

                    <span>
                        ${
                            escapeHtml(
                                item.date
                                ||
                                ''
                            )
                        }
                    </span>

                    <span>
                        ${
                            escapeHtml(
                                item.city
                                ||
                                city
                            )
                        }
                    </span>

                </div>


                <div
                    class="
                        mt-3
                        grid
                        grid-cols-2
                        gap-2
                    "
                >

                    {{-- VIEW --}}
                    <a
                        href="${escapeHtml(imageUrl)}"
                        target="_blank"
                        rel="noopener"
                        class="
                            inline-flex
                            h-9
                            items-center
                            justify-center
                            gap-1.5
                            rounded-lg
                            border
                            border-slate-200
                            bg-white
                            text-[10px]
                            font-extrabold
                            text-slate-700
                            transition
                            hover:bg-slate-50
                        "
                    >
                        View
                    </a>


                    {{-- DOWNLOAD --}}
                    <a
                        href="${escapeHtml(downloadUrl)}"
                        class="
                            inline-flex
                            h-9
                            items-center
                            justify-center
                            gap-1.5
                            rounded-lg
                            bg-slate-900
                            text-[10px]
                            font-extrabold
                            text-white
                            transition
                            hover:bg-slate-800
                        "
                    >
                        Download
                    </a>

                </div>

            </div>
        `;


        grid.appendChild(
            card
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Fetch Download With Browser Retry
    |--------------------------------------------------------------------------
    |
    | Laravel backend khud 5 attempts karega.
    |
    | Uske baad bhi request fail hui to browser dobara request karega.
    |
    */

    async function fetchDownloadWithRetry(
        url,
        maxAttempts = 3
    ) {

        let lastError =
            null;


        for (
            let attempt = 1;
            attempt <= maxAttempts;
            attempt++
        ) {

            try {

                /*
                |--------------------------------------------------------------------------
                | Request
                |--------------------------------------------------------------------------
                */

                const response =
                    await fetch(
                        url,
                        {
                            method:
                                'GET',

                            credentials:
                                'same-origin',

                            headers: {

                                'Accept':
                                    'application/octet-stream,image/*,*/*',

                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },

                            cache:
                                'no-store',
                        }
                    );


                /*
                |--------------------------------------------------------------------------
                | Success
                |--------------------------------------------------------------------------
                */

                if (
                    response.ok
                ) {

                    const contentType =
                        (
                            response
                                .headers
                                .get(
                                    'content-type'
                                )
                            ||
                            ''
                        )
                        .toLowerCase();


                    /*
                    |--------------------------------------------------------------------------
                    | Prevent HTML Login/Error Page From Saving As Image
                    |--------------------------------------------------------------------------
                    */

                    if (
                        contentType.includes(
                            'text/html'
                        )
                        ||
                        contentType.includes(
                            'application/json'
                        )
                    ) {

                        const text =
                            await response.text();


                        throw new Error(
                            'Server returned invalid download response.'
                            +
                            (
                                text
                                    ?
                                    ' '
                                    +
                                    text.substring(
                                        0,
                                        150
                                    )
                                    :
                                    ''
                            )
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Blob
                    |--------------------------------------------------------------------------
                    */

                    const blob =
                        await response.blob();


                    /*
                    |--------------------------------------------------------------------------
                    | Empty Blob Protection
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !blob
                        ||
                        blob.size <= 0
                    ) {

                        throw new Error(
                            'Downloaded image is empty.'
                        );
                    }


                    return blob;
                }


                /*
                |--------------------------------------------------------------------------
                | Read Server Error
                |--------------------------------------------------------------------------
                */

                let serverMessage =
                    '';


                try {

                    const errorData =
                        await response
                            .clone()
                            .json();


                    serverMessage =
                        errorData.message
                        ||
                        errorData.error
                        ||
                        '';

                } catch (jsonError) {

                    try {

                        serverMessage =
                            await response
                                .clone()
                                .text();

                    } catch (textError) {

                        serverMessage =
                            '';
                    }
                }


                throw new Error(
                    serverMessage
                    ||
                    (
                        'HTTP '
                        +
                        response.status
                    )
                );


            } catch (error) {

                lastError =
                    error;


                console.warn(
                    `Download attempt ${attempt}/${maxAttempts} failed`,
                    url,
                    error
                );


                /*
                |--------------------------------------------------------------------------
                | Wait Before Retry
                |--------------------------------------------------------------------------
                */

                if (
                    attempt
                    <
                    maxAttempts
                ) {

                    await sleep(
                        1000
                        *
                        attempt
                    );
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Completely Failed
        |--------------------------------------------------------------------------
        */

        throw (
            lastError
            ||
            new Error(
                'Download failed.'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Download All Demo Images
    |--------------------------------------------------------------------------
    */

    async function downloadAllDemoImages(
        button
    ) {

        /*
        |--------------------------------------------------------------------------
        | Already Running
        |--------------------------------------------------------------------------
        */

        if (
            bulkDownloadRunning
        ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Images Check
        |--------------------------------------------------------------------------
        */

        if (
            !Array.isArray(
                currentDemoImages
            )
            ||
            currentDemoImages.length
            ===
            0
        ) {

            alert(
                'No demo images available.'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Chrome / Edge Folder API
        |--------------------------------------------------------------------------
        */

        if (
            typeof window.showDirectoryPicker
            !==
            'function'
        ) {

            alert(
                'Bulk download requires latest Chrome or Edge desktop browser.'
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

            directoryHandle =
                await window
                    .showDirectoryPicker({
                        mode:
                            'readwrite'
                    });


        } catch (error) {

            /*
            | User Cancelled
            */
            if (
                error
                &&
                error.name
                ===
                'AbortError'
            ) {

                return;
            }


            console.error(
                'Folder picker error:',
                error
            );


            alert(
                'Unable to select download folder.'
            );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Start
        |--------------------------------------------------------------------------
        */

        bulkDownloadRunning =
            true;


        const total =
            currentDemoImages.length;


        const textElement =
            button.querySelector(
                '.bulk-download-text'
            );


        const originalText =
            textElement
                ?
                textElement.textContent
                :
                'Download All';


        /*
        |--------------------------------------------------------------------------
        | Disable Buttons
        |--------------------------------------------------------------------------
        */

        button.disabled =
            true;


        button.style.opacity =
            '0.65';


        button.style.cursor =
            'not-allowed';


        searchButton.disabled =
            true;


        /*
        |--------------------------------------------------------------------------
        | Reset Progress
        |--------------------------------------------------------------------------
        */

        downloadProgressBox
            .classList
            .remove(
                'hidden'
            );


        downloadProgressBar
            .style
            .width =
            '0%';


        downloadProgressText
            .textContent =
            `0 / ${total}`;


        downloadedCount
            .textContent =
            '0';


        retryCount
            .textContent =
            '0';


        failedCount
            .textContent =
            '0';


        downloadCurrentFile
            .textContent =
            'Preparing download...';


        /*
        |--------------------------------------------------------------------------
        | Counters
        |--------------------------------------------------------------------------
        */

        const usedNames =
            new Set();


        let downloaded =
            0;


        let failed =
            0;


        /*
        |--------------------------------------------------------------------------
        | First Pass Failed Images
        |--------------------------------------------------------------------------
        */

        const failedImages =
            [];


        try {

            /*
            |--------------------------------------------------------------------------
            | FIRST PASS
            |--------------------------------------------------------------------------
            */

            for (
                let index = 0;
                index < currentDemoImages.length;
                index++
            ) {

                /*
                |--------------------------------------------------------------------------
                | Image
                |--------------------------------------------------------------------------
                */

                const item =
                    currentDemoImages[
                        index
                    ];


                const imageUrl =
                    item.media
                    ??
                    '';


                /*
                |--------------------------------------------------------------------------
                | Filename
                |--------------------------------------------------------------------------
                */

                let fileName =
                    makeDemoFileName(
                        item,
                        index
                    );


                fileName =
                    uniqueDownloadFileName(
                        fileName,
                        usedNames
                    );


                /*
                |--------------------------------------------------------------------------
                | Progress UI
                |--------------------------------------------------------------------------
                */

                if (
                    textElement
                ) {

                    textElement.textContent =
                        `Downloading ${index + 1}/${total}`;
                }


                downloadProgressText
                    .textContent =
                    `${index + 1} / ${total}`;


                downloadCurrentFile
                    .textContent =
                    fileName;


                /*
                |--------------------------------------------------------------------------
                | Download
                |--------------------------------------------------------------------------
                */

                try {

                    /*
                    |--------------------------------------------------------------------------
                    | Laravel Proxy URL
                    |--------------------------------------------------------------------------
                    */

                    const downloadUrl =
                        buildDownloadUrl(
                            imageUrl,
                            fileName
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Fetch With Retry
                    |--------------------------------------------------------------------------
                    */

                    const blob =
                        await fetchDownloadWithRetry(
                            downloadUrl,
                            3
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Create File
                    |--------------------------------------------------------------------------
                    */

                    const fileHandle =
                        await directoryHandle
                            .getFileHandle(
                                fileName,
                                {
                                    create:
                                        true
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


                    /*
                    |--------------------------------------------------------------------------
                    | Success
                    |--------------------------------------------------------------------------
                    */

                    downloaded++;


                    downloadedCount
                        .textContent =
                        downloaded;


                } catch (fileError) {

                    /*
                    |--------------------------------------------------------------------------
                    | Keep For Second Pass
                    |--------------------------------------------------------------------------
                    */

                    console.warn(
                        'First pass failed. Will retry later:',
                        item,
                        fileError
                    );


                    failedImages.push({
                        item:
                            item,

                        index:
                            index,

                        fileName:
                            fileName,
                    });


                    retryCount
                        .textContent =
                        failedImages.length;
                }


                /*
                |--------------------------------------------------------------------------
                | Progress Bar
                |--------------------------------------------------------------------------
                */

                const percentage =
                    Math.round(
                        (
                            (index + 1)
                            /
                            total
                        )
                        *
                        100
                    );


                downloadProgressBar
                    .style
                    .width =
                    percentage
                    +
                    '%';


                /*
                |--------------------------------------------------------------------------
                | Delay Between Images
                |--------------------------------------------------------------------------
                |
                | Remote server ko too fast requests nahi jayengi.
                |
                */

                await sleep(
                    800
                );
            }


            /*
            |--------------------------------------------------------------------------
            | SECOND PASS
            |--------------------------------------------------------------------------
            |
            | Jo images first pass me fail hui hain
            | unko dobara ek-ek karke try karega.
            |
            */

            if (
                failedImages.length
                >
                0
            ) {

                /*
                |--------------------------------------------------------------------------
                | Pause Before Retry
                |--------------------------------------------------------------------------
                */

                downloadCurrentFile
                    .textContent =
                    `${failedImages.length} images retry pending...`;


                if (
                    textElement
                ) {

                    textElement.textContent =
                        'Retrying Failed Images...';
                }


                await sleep(
                    2000
                );


                /*
                |--------------------------------------------------------------------------
                | Retry Failed Images
                |--------------------------------------------------------------------------
                */

                for (
                    let retryIndex = 0;
                    retryIndex < failedImages.length;
                    retryIndex++
                ) {

                    const failedRow =
                        failedImages[
                            retryIndex
                        ];


                    const item =
                        failedRow.item;


                    const fileName =
                        failedRow.fileName;


                    const imageUrl =
                        item.media
                        ??
                        '';


                    /*
                    |--------------------------------------------------------------------------
                    | UI
                    |--------------------------------------------------------------------------
                    */

                    retryCount
                        .textContent =
                        failedImages.length
                        -
                        retryIndex;


                    downloadCurrentFile
                        .textContent =
                        `Retry ${retryIndex + 1}/${failedImages.length}: ${fileName}`;


                    if (
                        textElement
                    ) {

                        textElement.textContent =
                            `Retrying ${retryIndex + 1}/${failedImages.length}`;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Retry
                    |--------------------------------------------------------------------------
                    */

                    try {

                        const downloadUrl =
                            buildDownloadUrl(
                                imageUrl,
                                fileName
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Again Browser Retry
                        |--------------------------------------------------------------------------
                        */

                        const blob =
                            await fetchDownloadWithRetry(
                                downloadUrl,
                                3
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | File Handle
                        |--------------------------------------------------------------------------
                        */

                        const fileHandle =
                            await directoryHandle
                                .getFileHandle(
                                    fileName,
                                    {
                                        create:
                                            true
                                    }
                                );


                        /*
                        |--------------------------------------------------------------------------
                        | Write
                        |--------------------------------------------------------------------------
                        */

                        const writable =
                            await fileHandle
                                .createWritable();


                        await writable.write(
                            blob
                        );


                        await writable.close();


                        /*
                        |--------------------------------------------------------------------------
                        | Success
                        |--------------------------------------------------------------------------
                        */

                        downloaded++;


                        downloadedCount
                            .textContent =
                            downloaded;


                    } catch (retryError) {

                        /*
                        |--------------------------------------------------------------------------
                        | Completely Failed
                        |--------------------------------------------------------------------------
                        */

                        failed++;


                        failedCount
                            .textContent =
                            failed;


                        console.error(
                            'Demo image completely failed:',
                            item,
                            retryError
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Remaining Retry Count
                    |--------------------------------------------------------------------------
                    */

                    retryCount
                        .textContent =
                        Math.max(
                            0,
                            failedImages.length
                            -
                            retryIndex
                            -
                            1
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Delay
                    |--------------------------------------------------------------------------
                    */

                    await sleep(
                        1200
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | COMPLETE
            |--------------------------------------------------------------------------
            */

            downloadProgressBar
                .style
                .width =
                '100%';


            downloadProgressText
                .textContent =
                `${total} / ${total}`;


            retryCount
                .textContent =
                '0';


            /*
            |--------------------------------------------------------------------------
            | Complete Message
            |--------------------------------------------------------------------------
            */

            if (
                failed === 0
            ) {

                downloadCurrentFile
                    .textContent =
                    'All images downloaded successfully.';


                if (
                    textElement
                ) {

                    textElement.textContent =
                        `${downloaded} Downloaded`;
                }


                alert(
                    downloaded
                    +
                    ' images successfully downloaded.'
                );


            } else {

                downloadCurrentFile
                    .textContent =
                    'Download completed with some failed images.';


                if (
                    textElement
                ) {

                    textElement.textContent =
                        `${downloaded} Downloaded, ${failed} Failed`;
                }


                alert(
                    downloaded
                    +
                    ' images downloaded.\n'
                    +
                    failed
                    +
                    ' images failed.'
                );
            }


        } catch (error) {

            /*
            |--------------------------------------------------------------------------
            | Complete Process Error
            |--------------------------------------------------------------------------
            */

            console.error(
                'Bulk download error:',
                error
            );


            downloadCurrentFile
                .textContent =
                'Bulk download error.';


            alert(
                'Bulk download could not be completed.'
            );


        } finally {

            /*
            |--------------------------------------------------------------------------
            | Reset Running State
            |--------------------------------------------------------------------------
            */

            bulkDownloadRunning =
                false;


            searchButton.disabled =
                false;


            /*
            |--------------------------------------------------------------------------
            | Enable Button
            |--------------------------------------------------------------------------
            */

            setTimeout(
                function () {

                    button.disabled =
                        false;


                    button.style.opacity =
                        '';


                    button.style.cursor =
                        '';


                    if (
                        textElement
                    ) {

                        textElement.textContent =
                            originalText;
                    }

                },
                2500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Build Laravel Download URL
    |--------------------------------------------------------------------------
    */

    function buildDownloadUrl(
        imageUrl,
        fileName
    ) {

        const baseUrl =
            @json(
                route(
                    'demo-images.download'
                )
            );


        const params =
            new URLSearchParams();


        params.set(
            'url',
            imageUrl
        );


        params.set(
            'name',
            fileName
        );


        return (
            baseUrl
            +
            '?'
            +
            params.toString()
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Filename
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | Kushinagar-2026-09-25-Shop-Name-1.jpg
    |
    */

    function makeDemoFileName(
        item,
        index
    ) {

        const imageUrl =
            item.media
            ||
            '';


        /*
        |--------------------------------------------------------------------------
        | Extension
        |--------------------------------------------------------------------------
        */

        let extension =
            getExtensionFromUrl(
                imageUrl
            );


        if (
            !extension
        ) {

            extension =
                'jpg';
        }


        /*
        |--------------------------------------------------------------------------
        | City
        |--------------------------------------------------------------------------
        */

        const city =
            safeDownloadFileNamePart(
                item.city
                ||
                document
                    .getElementById(
                        'city'
                    )
                    .value
                ||
                'demo'
            );


        /*
        |--------------------------------------------------------------------------
        | Date
        |--------------------------------------------------------------------------
        */

        const date =
            safeDownloadFileNamePart(
                item.date
                ||
                ''
            );


        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */

        const customer =
            safeDownloadFileNamePart(
                item.customer_name
                ||
                ''
            );


        /*
        |--------------------------------------------------------------------------
        | Parts
        |--------------------------------------------------------------------------
        */

        const parts = [
            city,
            date,
            customer,
            String(
                index + 1
            )
        ]
            .filter(
                Boolean
            );


        /*
        |--------------------------------------------------------------------------
        | Final
        |--------------------------------------------------------------------------
        */

        return (
            parts.join(
                '-'
            )
            +
            '.'
            +
            extension
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Get Extension From URL
    |--------------------------------------------------------------------------
    */

    function getExtensionFromUrl(
        url
    ) {

        try {

            const pathname =
                new URL(
                    url
                )
                    .pathname;


            const fileName =
                pathname
                    .split(
                        '/'
                    )
                    .pop()
                ||
                '';


            const dotIndex =
                fileName
                    .lastIndexOf(
                        '.'
                    );


            if (
                dotIndex <= 0
            ) {

                return '';
            }


            const extension =
                fileName
                    .substring(
                        dotIndex + 1
                    )
                    .toLowerCase();


            const allowed = [
                'jpg',
                'jpeg',
                'png',
                'webp',
                'gif',
            ];


            if (
                !allowed.includes(
                    extension
                )
            ) {

                return 'jpg';
            }


            return extension;


        } catch (error) {

            return 'jpg';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Safe Filename Part
    |--------------------------------------------------------------------------
    */

    function safeDownloadFileNamePart(
        value
    ) {

        return String(
            value
            ??
            ''
        )
            .replace(
                /[<>:"/\\|?*\x00-\x1F]/g,
                '-'
            )
            .replace(
                /\s+/g,
                '-'
            )
            .replace(
                /-+/g,
                '-'
            )
            .replace(
                /^-+|-+$/g,
                ''
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Safe Complete Filename
    |--------------------------------------------------------------------------
    */

    function safeDownloadFileName(
        name
    ) {

        name =
            String(
                name
                ||
                'demo-image.jpg'
            );


        name =
            name.replace(
                /[<>:"/\\|?*\x00-\x1F]/g,
                '-'
            );


        name =
            name.replace(
                /\s+/g,
                ' '
            );


        name =
            name.replace(
                /[. ]+$/g,
                ''
            );


        if (
            !name
        ) {

            name =
                'demo-image.jpg';
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

        let name =
            safeDownloadFileName(
                originalName
            );


        let lower =
            name
                .toLowerCase();


        /*
        |--------------------------------------------------------------------------
        | Name Available
        |--------------------------------------------------------------------------
        */

        if (
            !usedNames.has(
                lower
            )
        ) {

            usedNames.add(
                lower
            );


            return name;
        }


        /*
        |--------------------------------------------------------------------------
        | Extension
        |--------------------------------------------------------------------------
        */

        const lastDot =
            name.lastIndexOf(
                '.'
            );


        let base =
            name;


        let extension =
            '';


        if (
            lastDot > 0
        ) {

            base =
                name.substring(
                    0,
                    lastDot
                );


            extension =
                name.substring(
                    lastDot
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Counter
        |--------------------------------------------------------------------------
        */

        let counter =
            2;


        let candidate;


        do {

            candidate =
                base
                +
                ' ('
                +
                counter
                +
                ')'
                +
                extension;


            counter++;


        } while (
            usedNames.has(
                candidate
                    .toLowerCase()
            )
        );


        usedNames.add(
            candidate
                .toLowerCase()
        );


        return candidate;
    }


    /*
    |--------------------------------------------------------------------------
    | Sleep
    |--------------------------------------------------------------------------
    */

    function sleep(
        milliseconds
    ) {

        return new Promise(
            resolve =>
                setTimeout(
                    resolve,
                    milliseconds
                )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Escape HTML
    |--------------------------------------------------------------------------
    */

    function escapeHtml(
        value
    ) {

        return String(
            value
            ??
            ''
        )
            .replace(
                /&/g,
                '&amp;'
            )
            .replace(
                /</g,
                '&lt;'
            )
            .replace(
                />/g,
                '&gt;'
            )
            .replace(
                /"/g,
                '&quot;'
            )
            .replace(
                /'/g,
                '&#039;'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Refresh Lucide Icons
    |--------------------------------------------------------------------------
    */

    function refreshIcons()
    {
        if (
            typeof lucide
            !==
            'undefined'
        ) {

            lucide.createIcons();
        }
    }

</script>

@endsection