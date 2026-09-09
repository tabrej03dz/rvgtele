@extends('layouts.crm')

@section('title', 'Upload APK')

@section('content')

<div class="mx-auto max-w-4xl space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Upload New APK
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Upload a new mobile application version with screenshots.
            </p>
        </div>

        <a
            href="{{ route('mobile-apks.index') }}"
            class="inline-flex items-center gap-2 self-start rounded-lg
                   border border-slate-300 bg-white px-4 py-2.5
                   text-sm font-semibold text-slate-700
                   hover:bg-slate-50 sm:self-auto"
        >
            <svg
                class="h-4 w-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path d="m15 18-6-6 6-6"/>
            </svg>

            Back
        </a>

    </div>


    {{-- Validation Errors --}}
    @if($errors->any())

        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">

            <div class="font-semibold text-rose-800">
                Please fix the following errors:
            </div>

            <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-rose-700">

                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif


    {{-- AJAX Error --}}
    <div
        id="ajaxErrorBox"
        class="hidden rounded-xl border border-rose-200 bg-rose-50 p-4"
    >
        <div
            id="ajaxErrorText"
            class="text-sm font-semibold text-rose-700"
        ></div>
    </div>


    <form
        id="apkUploadForm"
        method="POST"
        action="{{ route('mobile-apks.store') }}"
        enctype="multipart/form-data"
        class="space-y-5"
    >

        @csrf


        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">

                <h2 class="font-bold text-slate-900">
                    APK Information
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Version number will be generated automatically.
                </p>

            </div>


            <div class="space-y-6 p-5 sm:p-6">


                {{-- Application Name --}}
                <div>

                    <label
                        for="name"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Application Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', 'CRM') }}"
                        placeholder="CRM"
                        class="w-full rounded-lg border-slate-300
                               px-3 py-2.5 text-sm
                               focus:border-blue-500
                               focus:ring-blue-500"
                    >

                </div>


                {{-- APK Upload --}}
                <div>

                    <label
                        for="apk"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        APK File

                        <span class="text-rose-500">
                            *
                        </span>
                    </label>


                    <label
                        for="apk"
                        class="group flex cursor-pointer flex-col
                               items-center justify-center rounded-xl
                               border-2 border-dashed border-slate-300
                               bg-slate-50 px-6 py-10 text-center
                               transition hover:border-blue-400
                               hover:bg-blue-50/50"
                    >

                        <div
                            class="flex h-14 w-14 items-center
                                   justify-center rounded-full
                                   bg-blue-100 text-blue-600"
                        >

                            <svg
                                class="h-7 w-7"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path d="M12 16V4"/>
                                <path d="m7 9 5-5 5 5"/>
                                <path d="M5 20h14"/>
                            </svg>

                        </div>


                        <div class="mt-4 font-semibold text-slate-700">
                            Click to select APK
                        </div>


                        <div class="mt-1 text-sm text-slate-500">
                            Only .apk file allowed
                        </div>


                        <div
                            id="file-name"
                            class="mt-3 hidden rounded-lg bg-white
                                   px-3 py-2 text-sm font-medium
                                   text-blue-700 shadow-sm"
                        ></div>

                    </label>


                    <input
                        type="file"
                        id="apk"
                        name="apk"
                        accept=".apk"
                        required
                        class="hidden"
                    >

                </div>


                {{-- Auto Version --}}
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">

                    <div class="flex gap-3">

                        <svg
                            class="mt-0.5 h-5 w-5 shrink-0 text-blue-600"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 11v5"/>
                            <path d="M12 8h.01"/>
                        </svg>

                        <div>

                            <div class="text-sm font-semibold text-blue-900">
                                Automatic Version
                            </div>

                            <p class="mt-1 text-sm text-blue-700">
                                Every new APK automatically gets the next version number.
                            </p>

                        </div>

                    </div>

                </div>


                {{-- Screenshots --}}
                <div>

                    <label
                        for="images"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        App Screenshots
                    </label>


                    <label
                        for="images"
                        class="flex cursor-pointer flex-col items-center
                               justify-center rounded-xl border-2
                               border-dashed border-slate-300 bg-slate-50
                               px-6 py-8 text-center
                               transition hover:border-indigo-400
                               hover:bg-indigo-50/40"
                    >

                        <svg
                            class="h-8 w-8 text-indigo-500"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <path d="m21 15-5-5L5 21"/>
                        </svg>


                        <div class="mt-3 font-semibold text-slate-700">
                            Select multiple screenshots
                        </div>


                        <div class="mt-1 text-xs text-slate-500">
                            JPG, JPEG, PNG, WEBP
                        </div>

                    </label>


                    <input
                        type="file"
                        id="images"
                        name="images[]"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        class="hidden"
                    >


                    <div
                        id="image-preview"
                        class="mt-4 grid grid-cols-2 gap-3
                               sm:grid-cols-3 md:grid-cols-4"
                    ></div>

                </div>


                {{-- ================================================= --}}
                {{-- Upload Progress --}}
                {{-- ================================================= --}}

                <div
                    id="uploadProgressWrapper"
                    class="hidden rounded-xl border
                           border-blue-200 bg-blue-50 p-5"
                >

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <div
                                id="uploadStatusText"
                                class="text-sm font-bold text-blue-900"
                            >
                                Uploading APK...
                            </div>

                            <div
                                id="uploadSizeText"
                                class="mt-1 text-xs text-blue-700"
                            >
                                Preparing upload...
                            </div>

                        </div>


                        <div
                            id="uploadPercentText"
                            class="shrink-0 text-xl font-black text-blue-700"
                        >
                            0%
                        </div>

                    </div>


                    {{-- Bar --}}
                    <div
                        class="mt-4 h-3 overflow-hidden
                               rounded-full bg-blue-100"
                    >

                        <div
                            id="uploadProgressBar"
                            class="h-full rounded-full bg-blue-600
                                   transition-all duration-150"
                            style="width: 0%"
                        ></div>

                    </div>


                    <div
                        class="mt-3 flex flex-col gap-1
                               text-xs text-slate-500
                               sm:flex-row sm:items-center
                               sm:justify-between"
                    >

                        <span id="uploadSpeedText">
                            Upload starting...
                        </span>

                        <span id="uploadRemainingText"></span>

                    </div>

                </div>


            </div>

        </section>


        {{-- Buttons --}}
        <div
            class="flex flex-col-reverse gap-3
                   sm:flex-row sm:justify-end"
        >

            <a
                id="cancelButton"
                href="{{ route('mobile-apks.index') }}"
                class="inline-flex items-center justify-center
                       rounded-lg border border-slate-300 bg-white
                       px-5 py-2.5 text-sm font-semibold
                       text-slate-700 hover:bg-slate-50"
            >
                Cancel
            </a>


            <button
                type="submit"
                id="uploadButton"
                class="inline-flex items-center justify-center gap-2
                       rounded-lg bg-blue-600 px-5 py-2.5
                       text-sm font-semibold text-white
                       hover:bg-blue-700
                       disabled:cursor-not-allowed
                       disabled:opacity-60"
            >

                {{-- Normal Upload Icon --}}
                <svg
                    id="uploadIcon"
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="M12 16V4"/>
                    <path d="m7 9 5-5 5 5"/>
                    <path d="M5 20h14"/>
                </svg>


                {{-- Spinner --}}
                <svg
                    id="uploadSpinner"
                    class="hidden h-4 w-4 animate-spin"
                    viewBox="0 0 24 24"
                    fill="none"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>

                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                    ></path>
                </svg>


                <span id="uploadButtonText">
                    Upload APK
                </span>

            </button>

        </div>

    </form>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Elements
    |--------------------------------------------------------------------------
    */

    const form = document.getElementById('apkUploadForm');

    const apkInput = document.getElementById('apk');
    const fileNameBox = document.getElementById('file-name');

    const imagesInput = document.getElementById('images');
    const imagePreview = document.getElementById('image-preview');

    const uploadButton = document.getElementById('uploadButton');
    const uploadButtonText = document.getElementById('uploadButtonText');

    const uploadIcon = document.getElementById('uploadIcon');
    const uploadSpinner = document.getElementById('uploadSpinner');

    const cancelButton = document.getElementById('cancelButton');

    const progressWrapper = document.getElementById('uploadProgressWrapper');

    const progressBar = document.getElementById('uploadProgressBar');

    const percentText = document.getElementById('uploadPercentText');

    const statusText = document.getElementById('uploadStatusText');

    const sizeText = document.getElementById('uploadSizeText');

    const speedText = document.getElementById('uploadSpeedText');

    const remainingText = document.getElementById('uploadRemainingText');

    const ajaxErrorBox = document.getElementById('ajaxErrorBox');
    const ajaxErrorText = document.getElementById('ajaxErrorText');


    /*
    |--------------------------------------------------------------------------
    | APK File Selected
    |--------------------------------------------------------------------------
    */

    apkInput?.addEventListener('change', function () {

        if (this.files && this.files.length > 0) {

            const file = this.files[0];

            fileNameBox.textContent =
                file.name + ' (' + formatBytes(file.size) + ')';

            fileNameBox.classList.remove('hidden');

        } else {

            fileNameBox.textContent = '';

            fileNameBox.classList.add('hidden');

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Screenshots Preview
    |--------------------------------------------------------------------------
    */

    imagesInput?.addEventListener('change', function () {

        imagePreview.innerHTML = '';

        Array.from(this.files).forEach(function (file) {

            const reader = new FileReader();

            reader.onload = function (event) {

                const wrapper = document.createElement('div');

                wrapper.className =
                    'overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm';

                const image = document.createElement('img');

                image.src = event.target.result;

                image.className =
                    'aspect-[9/16] w-full object-cover';


                const name = document.createElement('div');

                name.className =
                    'truncate px-2 py-2 text-xs text-slate-600';

                name.textContent = file.name;


                wrapper.appendChild(image);

                wrapper.appendChild(name);

                imagePreview.appendChild(wrapper);

            };

            reader.readAsDataURL(file);

        });

    });


    /*
    |--------------------------------------------------------------------------
    | Form AJAX Upload
    |--------------------------------------------------------------------------
    */

    form?.addEventListener('submit', function (event) {

        event.preventDefault();


        /*
        |--------------------------------------------------------------------------
        | Basic validation
        |--------------------------------------------------------------------------
        */

        if (!apkInput.files || apkInput.files.length === 0) {

            showError('Please select an APK file.');

            return;

        }


        const apkFile = apkInput.files[0];

        const extension = apkFile.name
            .split('.')
            .pop()
            .toLowerCase();


        if (extension !== 'apk') {

            showError('Please select a valid APK file.');

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | Clear errors
        |--------------------------------------------------------------------------
        */

        ajaxErrorBox.classList.add('hidden');

        ajaxErrorText.textContent = '';


        /*
        |--------------------------------------------------------------------------
        | Form Data
        |--------------------------------------------------------------------------
        */

        const formData = new FormData(form);


        /*
        |--------------------------------------------------------------------------
        | XHR
        |--------------------------------------------------------------------------
        */

        const xhr = new XMLHttpRequest();


        let lastLoaded = 0;

        let lastTime = Date.now();


        /*
        |--------------------------------------------------------------------------
        | Reset Progress UI
        |--------------------------------------------------------------------------
        */

        progressWrapper.classList.remove('hidden');

        progressBar.style.width = '0%';

        percentText.textContent = '0%';

        statusText.textContent = 'Uploading APK...';

        sizeText.textContent = 'Preparing upload...';

        speedText.textContent = 'Upload starting...';

        remainingText.textContent = '';


        /*
        |--------------------------------------------------------------------------
        | Disable Controls
        |--------------------------------------------------------------------------
        */

        uploadButton.disabled = true;

        uploadButtonText.textContent = 'Uploading...';

        uploadIcon.classList.add('hidden');

        uploadSpinner.classList.remove('hidden');

        apkInput.disabled = true;

        imagesInput.disabled = true;

        cancelButton.classList.add(
            'pointer-events-none',
            'opacity-50'
        );


        /*
        |--------------------------------------------------------------------------
        | Open Request
        |--------------------------------------------------------------------------
        */

        xhr.open(
            'POST',
            form.action,
            true
        );


        xhr.setRequestHeader(
            'X-Requested-With',
            'XMLHttpRequest'
        );


        xhr.setRequestHeader(
            'Accept',
            'application/json'
        );


        /*
        |--------------------------------------------------------------------------
        | Progress
        |--------------------------------------------------------------------------
        */

        xhr.upload.addEventListener('progress', function (event) {

            if (!event.lengthComputable) {
                return;
            }


            const percent =
                Math.round(
                    (event.loaded / event.total) * 100
                );


            progressBar.style.width =
                percent + '%';


            percentText.textContent =
                percent + '%';


            sizeText.textContent =
                formatBytes(event.loaded)
                + ' / '
                + formatBytes(event.total);


            /*
            |--------------------------------------------------------------------------
            | Speed
            |--------------------------------------------------------------------------
            */

            const currentTime = Date.now();

            const elapsedSeconds =
                (currentTime - lastTime) / 1000;


            if (elapsedSeconds >= 0.5) {

                const uploadedSinceLastCheck =
                    event.loaded - lastLoaded;


                const bytesPerSecond =
                    uploadedSinceLastCheck / elapsedSeconds;


                if (bytesPerSecond > 0) {

                    speedText.textContent =
                        formatBytes(bytesPerSecond)
                        + '/s';


                    const remainingBytes =
                        event.total - event.loaded;


                    const remainingSeconds =
                        remainingBytes / bytesPerSecond;


                    remainingText.textContent =
                        'Approx. '
                        + formatTime(remainingSeconds)
                        + ' remaining';

                }


                lastLoaded = event.loaded;

                lastTime = currentTime;

            }


            /*
            |--------------------------------------------------------------------------
            | Browser finished sending
            |--------------------------------------------------------------------------
            */

            if (percent >= 100) {

                statusText.textContent =
                    'Upload complete. Processing APK...';


                uploadButtonText.textContent =
                    'Processing...';


                speedText.textContent =
                    'File uploaded to server';


                remainingText.textContent = '';

            }

        });


        /*
        |--------------------------------------------------------------------------
        | Server Response
        |--------------------------------------------------------------------------
        */

        xhr.addEventListener('load', function () {

            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            if (xhr.status >= 200 && xhr.status < 300) {

                progressBar.style.width = '100%';

                percentText.textContent = '100%';

                statusText.textContent =
                    'APK uploaded successfully.';

                sizeText.textContent =
                    'Upload completed successfully.';

                speedText.textContent =
                    'Saving completed';

                remainingText.textContent = '';

                uploadButtonText.textContent =
                    'Uploaded';


                setTimeout(function () {

                    window.location.href =
                        "{{ route('mobile-apks.index') }}";

                }, 500);


                return;

            }


            /*
            |--------------------------------------------------------------------------
            | Error response
            |--------------------------------------------------------------------------
            */

            let errorMessage =
                'APK upload failed. Please try again.';


            try {

                const response =
                    JSON.parse(xhr.responseText);


                /*
                |--------------------------------------------------------------------------
                | Laravel validation errors
                |--------------------------------------------------------------------------
                */

                if (response.errors) {

                    const errors = [];

                    Object.keys(response.errors)
                        .forEach(function (key) {

                            const messages =
                                response.errors[key];

                            if (Array.isArray(messages)) {

                                messages.forEach(function (message) {
                                    errors.push(message);
                                });

                            }

                        });


                    if (errors.length > 0) {

                        errorMessage =
                            errors.join(' ');

                    }

                } else if (response.message) {

                    errorMessage =
                        response.message;

                }

            } catch (error) {

                /*
                |--------------------------------------------------------------------------
                | If server returns HTML / 413 / 500 etc.
                |--------------------------------------------------------------------------
                */

                if (xhr.status === 413) {

                    errorMessage =
                        'APK file is too large for current server upload limit.';

                } else if (xhr.status === 419) {

                    errorMessage =
                        'Session expired. Please refresh the page and try again.';

                } else if (xhr.status === 500) {

                    errorMessage =
                        'Server error occurred while processing APK.';

                }

            }


            uploadFailed(errorMessage);

        });


        /*
        |--------------------------------------------------------------------------
        | Network Error
        |--------------------------------------------------------------------------
        */

        xhr.addEventListener('error', function () {

            uploadFailed(
                'Network error. Please check your internet connection and try again.'
            );

        });


        /*
        |--------------------------------------------------------------------------
        | Request Timeout
        |--------------------------------------------------------------------------
        */

        xhr.addEventListener('timeout', function () {

            uploadFailed(
                'Upload request timed out. Please try again.'
            );

        });


        /*
        |--------------------------------------------------------------------------
        | Upload Aborted
        |--------------------------------------------------------------------------
        */

        xhr.addEventListener('abort', function () {

            uploadFailed(
                'Upload was cancelled.'
            );

        });


        /*
        |--------------------------------------------------------------------------
        | Failed UI
        |--------------------------------------------------------------------------
        */

        function uploadFailed(message) {

            statusText.textContent =
                'Upload failed';


            percentText.textContent =
                'Failed';


            progressBar.style.width =
                '0%';


            sizeText.textContent =
                message;


            speedText.textContent = '';

            remainingText.textContent = '';


            showError(message);


            uploadButton.disabled = false;

            uploadButtonText.textContent =
                'Try Again';


            uploadSpinner.classList.add('hidden');

            uploadIcon.classList.remove('hidden');


            apkInput.disabled = false;

            imagesInput.disabled = false;


            cancelButton.classList.remove(
                'pointer-events-none',
                'opacity-50'
            );

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Show Error
    |--------------------------------------------------------------------------
    */

    function showError(message) {

        ajaxErrorText.textContent = message;

        ajaxErrorBox.classList.remove('hidden');

        ajaxErrorBox.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

    }


    /*
    |--------------------------------------------------------------------------
    | Byte Formatter
    |--------------------------------------------------------------------------
    */

    function formatBytes(bytes) {

        bytes = Number(bytes) || 0;


        if (bytes === 0) {

            return '0 B';

        }


        const units = [
            'B',
            'KB',
            'MB',
            'GB'
        ];


        const index =
            Math.floor(
                Math.log(bytes)
                /
                Math.log(1024)
            );


        const value =
            bytes
            /
            Math.pow(1024, index);


        return value.toFixed(
            index >= 2 ? 2 : 1
        )
        + ' '
        + units[index];

    }


    /*
    |--------------------------------------------------------------------------
    | Remaining Time Formatter
    |--------------------------------------------------------------------------
    */

    function formatTime(seconds) {

        seconds =
            Math.max(
                0,
                Math.round(seconds)
            );


        if (seconds < 60) {

            return seconds + ' sec';

        }


        const minutes =
            Math.floor(seconds / 60);


        const remainingSeconds =
            seconds % 60;


        if (minutes < 60) {

            return minutes
                + ' min '
                + remainingSeconds
                + ' sec';

        }


        const hours =
            Math.floor(minutes / 60);


        const remainingMinutes =
            minutes % 60;


        return hours
            + ' hr '
            + remainingMinutes
            + ' min';

    }

});
</script>

@endsection