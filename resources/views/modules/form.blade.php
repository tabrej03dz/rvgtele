@extends('layouts.crm')

@section('title', $title)

@section('content')
<div class="mx-auto max-w-5xl space-y-6">

    {{-- Page Heading --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-900">
            {{ $title }}
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Fill all required details carefully.
        </p>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-700">
            <div class="mb-2 font-semibold">
                Please correct the following errors:
            </div>

            <ul class="list-disc space-y-1 pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ $item->exists
            ? route($routeName . '.update', ['item' => $item])
            : route($routeName . '.store') }}"
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >
        @csrf

        @if ($item->exists)
            @method('PUT')
        @endif

        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
            <h2 class="font-semibold text-slate-900">
                {{ $item->exists ? 'Update Information' : 'Basic Information' }}
            </h2>
        </div>

        <div class="grid gap-5 p-6 md:grid-cols-2">

            @foreach ($fields as $name => $config)
                @php
                    /*
                    |--------------------------------------------------------------------------
                    | Backward compatibility
                    |--------------------------------------------------------------------------
                    |
                    | Old controller format:
                    | 'name' => 'text'
                    |
                    | New controller format:
                    | 'branch_id' => [
                    |     'type' => 'select',
                    |     'label' => 'Branch',
                    |     'options' => $branches,
                    | ]
                    |
                    */

                    if (is_string($config)) {
                        $config = [
                            'type' => $config,
                        ];
                    }

                    $type = $config['type'] ?? 'text';

                    $label = $config['label']
                        ?? ucwords(str_replace('_', ' ', $name));

                    $required = $config['required'] ?? false;

                    $placeholder = $config['placeholder']
                        ?? ('Enter ' . strtolower($label));

                    $options = $config['options'] ?? [];

                    $optionValue = $config['option_value'] ?? 'id';

                    $optionLabel = $config['option_label'] ?? 'name';

                    $emptyLabel = $config['empty_label']
                        ?? ('Select ' . $label);

                    $columnClass = in_array($type, ['textarea'], true)
                        ? 'md:col-span-2'
                        : '';

                    $currentValue = old($name, data_get($item, $name));

                    /*
                    |--------------------------------------------------------------------------
                    | Date formatting
                    |--------------------------------------------------------------------------
                    */

                    if ($currentValue instanceof \Carbon\CarbonInterface) {
                        $currentValue = match ($type) {
                            'date' => $currentValue->format('Y-m-d'),
                            'datetime-local' => $currentValue->format('Y-m-d\TH:i'),
                            default => $currentValue,
                        };
                    }
                @endphp

                <div class="{{ $columnClass }}">

                    {{-- Checkbox --}}
                    @if ($type === 'checkbox')
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
                            <input
                                type="checkbox"
                                name="{{ $name }}"
                                value="1"
                                @checked((bool) old($name, data_get($item, $name)))
                                class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            >

                            <div>
                                <div class="font-medium text-slate-800">
                                    {{ $label }}
                                </div>

                                @if (!empty($config['help']))
                                    <div class="text-xs text-slate-500">
                                        {{ $config['help'] }}
                                    </div>
                                @endif
                            </div>
                        </label>

                    {{-- Select Dropdown --}}
                    @elseif ($type === 'select')
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">
                                {{ $label }}

                                @if ($required)
                                    <span class="text-rose-500">*</span>
                                @endif
                            </span>

                            <select
                                name="{{ $name }}"
                                @required($required)
                                class="w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">
                                    {{ $emptyLabel }}
                                </option>

                                @foreach ($options as $option)
                                    @php
                                        $value = is_array($option)
                                            ? data_get($option, $optionValue)
                                            : data_get($option, $optionValue);

                                        $displayText = is_callable($optionLabel)
                                            ? $optionLabel($option)
                                            : data_get($option, $optionLabel);
                                    @endphp

                                    <option
                                        value="{{ $value }}"
                                        @selected((string) $currentValue === (string) $value)
                                    >
                                        {{ $displayText }}
                                    </option>
                                @endforeach
                            </select>

                            @if (!empty($config['help']))
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $config['help'] }}
                                </div>
                            @endif
                        </label>

                    {{-- Textarea --}}
                    @elseif ($type === 'textarea')
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">
                                {{ $label }}

                                @if ($required)
                                    <span class="text-rose-500">*</span>
                                @endif
                            </span>

                            <textarea
                                name="{{ $name }}"
                                rows="{{ $config['rows'] ?? 4 }}"
                                placeholder="{{ $placeholder }}"
                                @required($required)
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >{{ $currentValue }}</textarea>

                            @if (!empty($config['help']))
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $config['help'] }}
                                </div>
                            @endif
                        </label>

                    {{-- Normal Input --}}
                    @else
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">
                                {{ $label }}

                                @if ($required)
                                    <span class="text-rose-500">*</span>
                                @endif
                            </span>

                            <input
                                type="{{ $type }}"
                                name="{{ $name }}"
                                value="{{ $type === 'password' ? '' : $currentValue }}"
                                placeholder="{{ $placeholder }}"
                                @required($required)
                                @if (isset($config['min'])) min="{{ $config['min'] }}" @endif
                                @if (isset($config['max'])) max="{{ $config['max'] }}" @endif
                                @if (isset($config['step'])) step="{{ $config['step'] }}" @endif
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >

                            @if (!empty($config['help']))
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $config['help'] }}
                                </div>
                            @endif
                        </label>
                    @endif

                    @error($name)
                        <div class="mt-1 text-sm text-rose-600">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <a
                href="{{ route($routeName . '.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 font-medium text-slate-700 hover:bg-slate-100"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="rounded-lg bg-indigo-600 px-6 py-2.5 font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                {{ $item->exists ? 'Update' : 'Save' }}
            </button>
        </div>
    </form>
</div>
@endsection