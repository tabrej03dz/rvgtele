@extends('layouts.crm')

@section('title', 'Edit APK')

@section('content')

<div class="mx-auto max-w-5xl space-y-5">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Edit APK
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Update application information, status and screenshots.
            </p>
        </div>

        <a
            href="{{ route('mobile-apks.index') }}"
            class="inline-flex items-center gap-2 rounded-lg
                   border border-slate-300 bg-white px-4 py-2.5
                   text-sm font-semibold text-slate-700
                   hover:bg-slate-50"
        >
            Back
        </a>

    </div>


    @if($errors->any())

        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">

            <ul class="list-inside list-disc space-y-1 text-sm text-rose-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>

    @endif


    <form
        method="POST"
        action="{{ route('mobile-apks.update', $mobileApk) }}"
        enctype="multipart/form-data"
        class="space-y-5"
    >

        @csrf
        @method('PUT')


        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">

                <h2 class="font-bold text-slate-900">
                    APK Information
                </h2>

            </div>


            <div class="space-y-6 p-5 sm:p-6">


                {{-- Name --}}
                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Application Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $mobileApk->name) }}"
                        required
                        class="w-full rounded-lg border-slate-300
                               px-3 py-2.5 text-sm
                               focus:border-blue-500 focus:ring-blue-500"
                    >

                </div>


                {{-- Version --}}
                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Version
                    </label>

                    <div class="flex items-center justify-between
                                rounded-lg border border-slate-200
                                bg-slate-50 px-4 py-3">

                        <span class="font-bold text-slate-800">
                            v{{ $mobileApk->version }}
                        </span>

                        <span class="text-xs text-slate-500">
                            Auto generated
                        </span>

                    </div>

                </div>


                {{-- Status --}}
                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Status
                    </label>

                    <label class="flex cursor-pointer items-start gap-3
                                  rounded-xl border border-slate-200
                                  p-4 hover:bg-slate-50">

                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            @checked(old('is_active', $mobileApk->is_active))
                            class="mt-0.5 rounded border-slate-300
                                   text-blue-600 focus:ring-blue-500"
                        >

                        <div>
                            <div class="text-sm font-semibold text-slate-800">
                                Active APK
                            </div>

                            <div class="mt-1 text-xs text-slate-500">
                                Only active APK can be downloaded.
                            </div>
                        </div>

                    </label>

                </div>


                {{-- APK File --}}
                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        APK File
                    </label>

                    <div class="rounded-xl border border-slate-200
                                bg-slate-50 px-4 py-4">

                        <div class="text-sm font-semibold text-slate-800">
                            {{ basename($mobileApk->file_path) }}
                        </div>

                        <div class="mt-1 text-xs text-slate-500">
                            APK file cannot be replaced from edit page.
                            Upload a new APK to create next version.
                        </div>

                    </div>

                </div>


                {{-- Existing Screenshots --}}
                <div>

                    <div class="mb-3 flex items-center justify-between">

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">
                                Existing Screenshots
                            </label>

                            <p class="mt-1 text-xs text-slate-500">
                                Tick an image if you want to remove it.
                            </p>
                        </div>

                    </div>


                    @if(!empty($mobileApk->images) && count($mobileApk->images))

                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">

                            @foreach($mobileApk->images as $index => $image)

                                <label
                                    class="group relative cursor-pointer overflow-hidden
                                           rounded-xl border border-slate-200 bg-white"
                                >

                                    <img
                                        src="{{ Storage::url($image) }}"
                                        alt="Screenshot"
                                        class="aspect-[9/16] w-full object-cover"
                                    >

                                    <div class="flex items-center gap-2 border-t
                                                border-slate-200 px-3 py-2">

                                        <input
                                            type="checkbox"
                                            name="remove_images[]"
                                            value="{{ $image }}"
                                            class="rounded border-slate-300
                                                   text-rose-600 focus:ring-rose-500"
                                        >

                                        <span class="text-xs font-semibold text-rose-600">
                                            Remove
                                        </span>

                                    </div>

                                </label>

                            @endforeach

                        </div>

                    @else

                        <div class="rounded-xl border border-dashed
                                    border-slate-300 bg-slate-50
                                    px-4 py-8 text-center text-sm text-slate-500">
                            No screenshots uploaded.
                        </div>

                    @endif

                </div>


                {{-- Add More --}}
                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Add More Screenshots
                    </label>

                    <input
                        type="file"
                        id="images"
                        name="images[]"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        class="block w-full rounded-lg border
                               border-slate-300 bg-white px-3 py-2.5
                               text-sm text-slate-700
                               file:mr-4 file:rounded-lg file:border-0
                               file:bg-blue-50 file:px-4 file:py-2
                               file:text-sm file:font-semibold
                               file:text-blue-700 hover:file:bg-blue-100"
                    >

                    <div
                        id="image-preview"
                        class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4"
                    ></div>

                </div>

            </div>

        </section>


        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

            <a
                href="{{ route('mobile-apks.index') }}"
                class="inline-flex items-center justify-center
                       rounded-lg border border-slate-300 bg-white
                       px-5 py-2.5 text-sm font-semibold text-slate-700
                       hover:bg-slate-50"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="inline-flex items-center justify-center
                       rounded-lg bg-blue-600 px-5 py-2.5
                       text-sm font-semibold text-white
                       hover:bg-blue-700"
            >
                Save Changes
            </button>

        </div>

    </form>

</div>


<script>
document.getElementById('images')?.addEventListener('change', function () {

    const preview = document.getElementById('image-preview');

    preview.innerHTML = '';

    Array.from(this.files).forEach(file => {

        const reader = new FileReader();

        reader.onload = function(e) {

            const div = document.createElement('div');

            div.className =
                'overflow-hidden rounded-xl border border-slate-200 bg-white';

            div.innerHTML = `
                <img
                    src="${e.target.result}"
                    class="aspect-[9/16] w-full object-cover"
                >
            `;

            preview.appendChild(div);
        };

        reader.readAsDataURL(file);

    });

});
</script>

@endsection