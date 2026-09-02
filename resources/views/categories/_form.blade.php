@php
    $isEdit = isset($category);
@endphp


<div class="space-y-6">

    {{-- Category Name --}}
    <div>

        <label
            for="name"
            class="mb-2 block text-sm font-semibold text-slate-700"
        >
            Category Name

            <span class="text-rose-500">*</span>
        </label>


        <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name', $category->name ?? '') }}"
            placeholder="Enter category name"
            autofocus
            required
            class="w-full rounded-lg border-slate-300
                   text-sm shadow-sm
                   focus:border-blue-500 focus:ring-blue-500
                   @error('name') border-rose-400 @enderror"
        >


        @error('name')
            <p class="mt-1.5 text-sm text-rose-600">
                {{ $message }}
            </p>
        @enderror

    </div>


    {{-- Description --}}
    <div>

        <div class="mb-2 flex items-center justify-between">

            <label
                for="description"
                class="block text-sm font-semibold text-slate-700"
            >
                Description
            </label>

            <span class="text-xs text-slate-400">
                Optional
            </span>

        </div>


        <textarea
            id="description"
            name="description"
            rows="5"
            maxlength="255"
            placeholder="Enter a short description about this category..."
            class="w-full resize-none rounded-lg
                   border-slate-300 text-sm shadow-sm
                   focus:border-blue-500 focus:ring-blue-500
                   @error('description') border-rose-400 @enderror"
        >{{ old('description', $category->description ?? '') }}</textarea>


        @error('description')
            <p class="mt-1.5 text-sm text-rose-600">
                {{ $message }}
            </p>
        @enderror


        <div class="mt-1 text-right text-xs text-slate-400">
            Maximum 255 characters
        </div>

    </div>


    {{-- Buttons --}}
    <div
        class="flex flex-col-reverse gap-3
               border-t border-slate-200 pt-5
               sm:flex-row sm:justify-end"
    >

        <a
            href="{{ route('categories.index') }}"
            class="inline-flex items-center justify-center
                   rounded-lg border border-slate-300
                   bg-white px-5 py-2.5
                   text-sm font-semibold text-slate-700
                   hover:bg-slate-50"
        >
            Cancel
        </a>


        <button
            type="submit"
            class="inline-flex items-center justify-center
                   gap-2 rounded-lg bg-blue-600
                   px-5 py-2.5 text-sm
                   font-semibold text-white
                   shadow-sm transition
                   hover:bg-blue-700"
        >

            <svg
                class="h-4 w-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path d="M5 12l4 4L19 6"/>
            </svg>

            {{ $isEdit ? 'Update Category' : 'Save Category' }}

        </button>

    </div>

</div>