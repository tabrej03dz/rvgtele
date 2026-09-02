@php
    $editing = isset($demoCity);
@endphp

@if($errors->any())
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-bold text-rose-700">
        {{ $errors->first() }}
    </div>
@endif

<div class="demo-shell p-5">

    {{-- CITY + CATEGORY + INFO --}}
    <div class="grid gap-5 lg:grid-cols-3">

        {{-- CITY --}}
        <div class="demo-field">
            <label>
                City Name <span class="text-rose-500">*</span>
            </label>

            <input
                type="text"
                name="name"
                required
                maxlength="120"
                value="{{ old('name', $demoCity->name ?? '') }}"
                placeholder="Example: Kanpur"
            >
        </div>

        {{-- CATEGORY --}}
        <div class="demo-field">
            <label>
                Category <span class="text-rose-500">*</span>
            </label>

            <select
                name="category_id"
                required
                class="h-[45px] w-full rounded-[10px] border border-[#d7dde7] bg-white px-3 text-[11px] text-slate-800 outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
            >
                <option value="">Select Category</option>

                @foreach($categories as $category)
                    <option
                        value="{{ $category->id }}"
                        @selected(
                            old(
                                'category_id',
                                $demoCity->category_id ?? ''
                            ) == $category->id
                        )
                    >
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- INFO --}}
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4">
            <div class="flex items-start gap-3">
                <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0 text-amber-600"></i>

                <div class="text-[10px] leading-5 text-slate-600">
                    Multiple images/videos upload kar sakte ho.
                    <br>
                    Complete ZIP pack bhi upload kar sakte ho.
                </div>
            </div>
        </div>

    </div>


    {{-- UPLOAD SECTION --}}
    <div class="mt-5 grid gap-4 lg:grid-cols-2">

        {{-- IMAGE / VIDEO UPLOAD --}}
        <div>
            <input
                id="mediaFiles"
                type="file"
                name="media_files[]"
                multiple
                accept="image/*,video/*"
                class="hidden"
                onchange="showSelectedMedia(this)"
            >

            <label
                for="mediaFiles"
                class="group flex min-h-[128px] cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-amber-400 bg-amber-50/40 p-5 text-center transition hover:border-amber-500 hover:bg-amber-50"
            >
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-amber-500 shadow-sm">
                    <i data-lucide="images" class="h-6 w-6"></i>
                </div>

                <div class="mt-3 text-xs font-extrabold text-slate-900">
                    Upload Images / Videos
                </div>

                <div class="mt-1 text-[9px] text-slate-500">
                    JPG, PNG, WEBP, GIF, MP4, MOV, AVI, MKV, WEBM
                </div>

                <div class="mt-3 rounded-lg bg-amber-400 px-4 py-2 text-[10px] font-extrabold text-slate-900">
                    Choose Files
                </div>

                <div
                    id="mediaFilesText"
                    class="mt-2 max-w-full truncate text-[9px] font-semibold text-slate-500"
                >
                    No files selected
                </div>
            </label>
        </div>


        {{-- ZIP UPLOAD --}}
        <div>
            <input
                id="zipFile"
                type="file"
                name="zip_file"
                accept=".zip,application/zip"
                class="hidden"
                onchange="showSelectedZip(this)"
            >

            <label
                for="zipFile"
                class="group flex min-h-[128px] cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-amber-400 bg-amber-50/40 p-5 text-center transition hover:border-amber-500 hover:bg-amber-50"
            >
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-amber-500 shadow-sm">
                    <i data-lucide="archive" class="h-6 w-6"></i>
                </div>

                <div class="mt-3 text-xs font-extrabold text-slate-900">
                    Upload Complete ZIP Pack
                </div>

                <div class="mt-1 text-[9px] text-slate-500">
                    ZIP ke andar valid images/videos automatic upload ho jayengi.
                </div>

                <div class="mt-3 rounded-lg bg-amber-400 px-4 py-2 text-[10px] font-extrabold text-slate-900">
                    Choose ZIP
                </div>

                <div
                    id="zipFileText"
                    class="mt-2 max-w-full truncate text-[9px] font-semibold text-slate-500"
                >
                    No ZIP selected
                </div>
            </label>
        </div>

    </div>


    {{-- ACTION BUTTONS --}}
    <div class="mt-5 flex flex-wrap justify-end gap-2">

        <a
            href="{{ $editing ? route('demo-cities.show', $demoCity) : route('demo-cities.index') }}"
            class="demo-btn"
        >
            Cancel
        </a>

        <button
            type="submit"
            class="demo-btn demo-btn-yellow"
        >
            <i data-lucide="{{ $editing ? 'save' : 'plus' }}"></i>

            {{ $editing ? 'Update City Demo' : 'Create City Demo' }}
        </button>

    </div>

</div>


<script>
    function showSelectedMedia(input) {
        const text = document.getElementById('mediaFilesText');

        if (!input.files || input.files.length === 0) {
            text.textContent = 'No files selected';
            return;
        }

        if (input.files.length === 1) {
            text.textContent = input.files[0].name;
            return;
        }

        text.textContent = input.files.length + ' files selected';
    }

    function showSelectedZip(input) {
        const text = document.getElementById('zipFileText');

        if (!input.files || input.files.length === 0) {
            text.textContent = 'No ZIP selected';
            return;
        }

        text.textContent = input.files[0].name;
    }
</script>