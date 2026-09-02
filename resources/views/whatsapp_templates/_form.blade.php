@php
    $isEdit = isset($template);
@endphp

<div class="space-y-6">

    {{-- Template Name --}}
    <div>
        <label
            for="name"
            class="mb-2 block text-sm font-semibold text-slate-700"
        >
            Template Name
            <span class="text-red-500">*</span>
        </label>

        <input
            type="text"
            name="name"
            id="name"
            value="{{ old('name', $template->name ?? '') }}"
            required
            maxlength="150"
            placeholder="Example: Demo Send"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3
                   text-sm text-slate-900 outline-none transition
                   focus:border-green-500 focus:ring-4 focus:ring-green-100"
        >

        @error('name')
            <p class="mt-1 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>


    {{-- Message --}}
    <div>
        <div class="mb-2 flex items-center justify-between gap-3">
            <label
                for="message"
                class="block text-sm font-semibold text-slate-700"
            >
                WhatsApp Message
                <span class="text-red-500">*</span>
            </label>

            <span
                id="messageCharacterCount"
                class="text-xs text-slate-400"
            >
                0 characters
            </span>
        </div>

        <textarea
    name="message"
    id="message"
    rows="10"
    required
    maxlength="10000"
    placeholder="Namaste @{{name}}, aapse baat karke achha laga..."
    class="w-full resize-y rounded-xl border border-slate-300 bg-white
           px-4 py-3 text-sm leading-6 text-slate-900 outline-none
           transition focus:border-green-500 focus:ring-4
           focus:ring-green-100"
>{{ old('message', $template->message ?? '') }}</textarea>

        @error('message')
            <p class="mt-1 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>


    {{-- Variables --}}
    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">

        <p class="text-sm font-semibold text-blue-900">
            Available Variables
        </p>

        <p class="mt-1 text-xs text-blue-700">
            Variable par click karke message me add kar sakte hain.
        </p>

        <div class="mt-3 flex flex-wrap gap-2">

            @foreach([
                '{{name}}',
                '{{business_name}}',
                '{{mobile}}',
                '{{city}}',
                '{{category}}',
                '{{user_name}}',
                '{{company_name}}',
            ] as $variable)

                <button
                    type="button"
                    onclick='insertWhatsappVariable(@json($variable))'
                    class="rounded-lg border border-blue-200 bg-white
                           px-3 py-1.5 font-mono text-xs font-medium
                           text-blue-700 transition hover:bg-blue-100"
                >
                    {{ $variable }}
                </button>

            @endforeach

        </div>
    </div>


    {{-- Settings --}}
    <div class="grid gap-4 md:grid-cols-2">

        {{-- Active --}}
        <label
            class="flex cursor-pointer items-center gap-3 rounded-xl
                   border border-slate-200 bg-slate-50 p-4"
        >
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked(
                    old(
                        'is_active',
                        $template->is_active ?? true
                    )
                )
                class="h-5 w-5 rounded border-slate-300
                       text-green-600 focus:ring-green-500"
            >

            <div>
                <p class="text-sm font-semibold text-slate-800">
                    Active Template
                </p>

                <p class="mt-0.5 text-xs text-slate-500">
                    WhatsApp popup me ye template show hoga.
                </p>
            </div>
        </label>


        {{-- Global --}}
        @can('whatsapp-template.create-global')

            <label
                class="flex cursor-pointer items-center gap-3 rounded-xl
                       border border-purple-200 bg-purple-50 p-4"
            >
                <input
                    type="checkbox"
                    name="is_global"
                    value="1"
                    @checked(
                        old(
                            'is_global',
                            $template->is_global ?? false
                        )
                    )
                    class="h-5 w-5 rounded border-purple-300
                           text-purple-600 focus:ring-purple-500"
                >

                <div>
                    <p class="text-sm font-semibold text-purple-900">
                        Global Template
                    </p>

                    <p class="mt-0.5 text-xs text-purple-700">
                        Company ke sabhi users isko use kar sakenge.
                    </p>
                </div>
            </label>

        @endcan

    </div>


    {{-- Buttons --}}
    <div class="flex flex-wrap items-center justify-end gap-3 border-t
                border-slate-200 pt-5">

        <a
            href="{{ route('whatsapp-templates.index') }}"
            class="rounded-xl border border-slate-300 bg-white
                   px-5 py-2.5 text-sm font-semibold text-slate-700
                   hover:bg-slate-50"
        >
            Cancel
        </a>

        <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-xl
                   bg-green-600 px-5 py-2.5 text-sm font-semibold
                   text-white shadow-sm transition hover:bg-green-700"
        >
            @if($isEdit)
                Update Template
            @else
                Create Template
            @endif
        </button>

    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const textarea = document.getElementById('message');
    const counter = document.getElementById('messageCharacterCount');

    function updateCounter() {
        if (!textarea || !counter) {
            return;
        }

        counter.textContent =
            textarea.value.length + ' characters';
    }

    textarea?.addEventListener('input', updateCounter);

    updateCounter();
});


function insertWhatsappVariable(variable) {

    const textarea = document.getElementById('message');

    if (!textarea) {
        return;
    }

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;

    const currentValue = textarea.value;

    textarea.value =
        currentValue.substring(0, start) +
        variable +
        currentValue.substring(end);

    const newPosition = start + variable.length;

    textarea.focus();
    textarea.setSelectionRange(
        newPosition,
        newPosition
    );

    textarea.dispatchEvent(
        new Event('input')
    );
}
</script>
