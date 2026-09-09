@extends('layouts.crm')

@section('title', 'Upload APK')

@section('content')

<div class="mx-auto max-w-4xl space-y-5">

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
            <svg class="h-4 w-4" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2">
                <path d="m15 18-6-6 6-6"/>
            </svg>

            Back
        </a>

    </div>


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


    <form
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


                {{-- Name --}}
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
                               focus:border-blue-500 focus:ring-blue-500"
                    >

                </div>


                {{-- APK --}}
                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        APK File
                        <span class="text-rose-500">*</span>
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

                        <div class="flex h-14 w-14 items-center justify-center
                                    rounded-full bg-blue-100 text-blue-600">

                            <svg class="h-7 w-7" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="1.8">
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


                {{-- Auto version --}}
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">

                    <div class="flex gap-3">

                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-600"
                             viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2">
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

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        App Screenshots
                    </label>

                    <label
                        for="images"
                        class="flex cursor-pointer flex-col items-center
                               justify-center rounded-xl border-2
                               border-dashed border-slate-300 bg-slate-50
                               px-6 py-8 text-center
                               hover:border-indigo-400 hover:bg-indigo-50/40"
                    >

                        <svg class="h-8 w-8 text-indigo-500"
                             viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="1.8">
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
                class="inline-flex items-center justify-center gap-2
                       rounded-lg bg-blue-600 px-5 py-2.5
                       text-sm font-semibold text-white hover:bg-blue-700"
            >
                Upload APK
            </button>

        </div>

    </form>

</div>


<script>
document.getElementById('apk')?.addEventListener('change', function () {

    const box = document.getElementById('file-name');

    if (this.files.length > 0) {
        box.textContent = this.files[0].name;
        box.classList.remove('hidden');
    } else {
        box.classList.add('hidden');
        box.textContent = '';
    }

});


document.getElementById('images')?.addEventListener('change', function () {

    const preview = document.getElementById('image-preview');

    preview.innerHTML = '';

    Array.from(this.files).forEach(file => {

        const reader = new FileReader();

        reader.onload = function(e) {

            const wrapper = document.createElement('div');

            wrapper.className =
                'overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm';

            wrapper.innerHTML = `
                <img
                    src="${e.target.result}"
                    class="aspect-[9/16] w-full object-cover"
                >

                <div class="truncate px-2 py-2 text-xs text-slate-600">
                    ${file.name}
                </div>
            `;

            preview.appendChild(wrapper);
        };

        reader.readAsDataURL(file);

    });

});
</script>

@endsection