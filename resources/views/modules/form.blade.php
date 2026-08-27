@extends('layouts.crm')

@section('title', $title)

@section('content')

@php
    $permissionPrefix = str_starts_with($routeName, 'crm-settings.')
        ? str($routeName)->after('crm-settings.')->toString()
        : $routeName;

    $isEdit = $item->exists;

    $fieldClass = function (string $name) use ($errors) {
        return $errors->has($name)
            ? 'border-rose-300 bg-rose-50 focus:border-rose-500 focus:ring-rose-500'
            : 'border-slate-300 bg-white focus:border-blue-500 focus:ring-blue-500';
    };
@endphp


<div class="mx-auto max-w-5xl space-y-5">

    {{-- ========================================================= --}}
    {{-- Header --}}
    {{-- ========================================================= --}}

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <div class="flex items-center gap-2 text-sm text-slate-500">

                <a
                    href="{{ route($routeName . '.index') }}"
                    class="hover:text-blue-600"
                >
                    {{ $title }}
                </a>

                <span>/</span>

                <span>
                    {{ $isEdit ? 'Edit' : 'Create' }}
                </span>

            </div>


            <h1 class="mt-1 text-2xl font-bold text-slate-900">
                {{ $title }}
            </h1>


            <p class="mt-1 text-sm text-slate-500">
                Required details fill karke record save karein.
            </p>

        </div>


        <a
            href="{{ route($routeName . '.index') }}"
            class="inline-flex self-start rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:self-auto"
        >
            Back
        </a>

    </div>


    {{-- ========================================================= --}}
    {{-- Validation Errors --}}
    {{-- ========================================================= --}}

    @if ($errors->any())

        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3">

            <div class="font-semibold text-rose-800">
                Form submit nahi hua
            </div>


            <p class="mt-1 text-sm text-rose-700">
                Highlighted fields ko correct karke dobara save karein.
            </p>


            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700">

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif



    {{-- ========================================================= --}}
    {{-- Form --}}
    {{-- ========================================================= --}}

    <form
        method="POST"
        action="{{
            $isEdit
                ? route($routeName . '.update', ['item' => $item])
                : route($routeName . '.store')
        }}"
        class="space-y-5"
    >

        @csrf


        @if ($isEdit)
            @method('PUT')
        @endif



        {{-- ===================================================== --}}
        {{-- Form Fields --}}
        {{-- ===================================================== --}}

        <section
            class="rounded-xl border border-slate-200 bg-white shadow-sm"
        >

            {{-- Section Header --}}

            <div class="border-b border-slate-200 px-5 py-4">

                <h2 class="font-bold text-slate-900">

                    {{
                        $isEdit
                            ? 'Update Information'
                            : 'Basic Information'
                    }}

                </h2>


                <p class="mt-0.5 text-sm text-slate-500">

                    Fields marked with

                    <span class="text-rose-500">
                        *
                    </span>

                    are required.

                </p>

            </div>


            {{-- Fields Grid --}}

            <div class="grid gap-4 p-5 md:grid-cols-2">


                @foreach ($fields as $name => $config)

                    @php

                        /*
                        |--------------------------------------------------------------------------
                        | Normalize Field Config
                        |--------------------------------------------------------------------------
                        */

                        if (is_string($config)) {

                            $config = [
                                'type' => $config,
                            ];

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Field Type
                        |--------------------------------------------------------------------------
                        */

                        $type = $config['type'] ?? 'text';


                        /*
                        |--------------------------------------------------------------------------
                        | Label
                        |--------------------------------------------------------------------------
                        */

                        $label = $config['label']
                            ?? ucwords(
                                str_replace('_', ' ', $name)
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Required
                        |--------------------------------------------------------------------------
                        */

                        $required =
                            $config['required'] ?? false;


                        /*
                        |--------------------------------------------------------------------------
                        | Placeholder
                        |--------------------------------------------------------------------------
                        */

                        $placeholder =
                            $config['placeholder']
                            ?? (
                                'Enter '
                                . strtolower($label)
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Select Options
                        |--------------------------------------------------------------------------
                        */

                        $options =
                            $config['options'] ?? [];


                        $optionValue =
                            $config['option_value'] ?? 'id';


                        $optionLabel =
                            $config['option_label'] ?? 'name';


                        $emptyLabel =
                            $config['empty_label']
                            ?? (
                                'Select '
                                . $label
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Column Width
                        |--------------------------------------------------------------------------
                        */

                        $columnClass =
                            in_array(
                                $type,
                                [
                                    'textarea',
                                ],
                                true
                            )
                                ? 'md:col-span-2'
                                : '';


                        /*
                        |--------------------------------------------------------------------------
                        | Current Value
                        |--------------------------------------------------------------------------
                        */

                        $currentValue =
                            old(
                                $name,
                                data_get(
                                    $item,
                                    $name
                                )
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Carbon Formatting
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $currentValue
                            instanceof
                            \Carbon\CarbonInterface
                        ) {

                            $currentValue = match ($type) {

                                'date' =>
                                    $currentValue->format(
                                        'Y-m-d'
                                    ),

                                'datetime-local' =>
                                    $currentValue->format(
                                        'Y-m-d\TH:i'
                                    ),

                                default =>
                                    $currentValue,

                            };

                        }

                    @endphp



                    <div class="{{ $columnClass }}">


                        {{-- ===================================== --}}
                        {{-- Checkbox --}}
                        {{-- ===================================== --}}

                        @if ($type === 'checkbox')


                            <input
                                type="hidden"
                                name="{{ $name }}"
                                value="0"
                            >


                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4 transition hover:border-blue-200 hover:bg-slate-50"
                            >

                                <input
                                    type="checkbox"
                                    name="{{ $name }}"
                                    value="1"

                                    @checked(
                                        (bool)
                                        old(
                                            $name,
                                            data_get(
                                                $item,
                                                $name
                                            )
                                        )
                                    )

                                    class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                >


                                <div>

                                    <div class="text-sm font-medium text-slate-800">

                                        {{ $label }}

                                    </div>


                                    @if (!empty($config['help']))

                                        <p class="mt-1 text-xs leading-5 text-slate-500">

                                            {{ $config['help'] }}

                                        </p>

                                    @endif

                                </div>

                            </label>



                        {{-- ===================================== --}}
                        {{-- Select --}}
                        {{-- ===================================== --}}

                        @elseif ($type === 'select')


                            <label class="block">


                                <span class="mb-1.5 block text-sm font-medium text-slate-700">

                                    {{ $label }}


                                    @if ($required)

                                        <span class="text-rose-500">
                                            *
                                        </span>

                                    @endif

                                </span>


                                <select
                                    name="{{ $name }}"

                                    @required($required)

                                    class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none transition focus:ring-2 {{ $fieldClass($name) }}"
                                >

                                    <option value="">

                                        {{ $emptyLabel }}

                                    </option>


                                    @foreach ($options as $option)

                                        @php

                                            $value =
                                                data_get(
                                                    $option,
                                                    $optionValue
                                                );


                                            $displayText =
                                                is_callable(
                                                    $optionLabel
                                                )

                                                    ? $optionLabel(
                                                        $option
                                                    )

                                                    : data_get(
                                                        $option,
                                                        $optionLabel
                                                    );

                                        @endphp


                                        <option
                                            value="{{ $value }}"

                                            @selected(
                                                (string) $currentValue
                                                ===
                                                (string) $value
                                            )
                                        >

                                            {{ $displayText }}

                                        </option>

                                    @endforeach

                                </select>


                                @if (!empty($config['help']))

                                    <p class="mt-1 text-xs leading-5 text-slate-500">

                                        {{ $config['help'] }}

                                    </p>

                                @endif

                            </label>



                        {{-- ===================================== --}}
                        {{-- Textarea --}}
                        {{-- ===================================== --}}

                        @elseif ($type === 'textarea')


                            <label class="block">


                                <span class="mb-1.5 block text-sm font-medium text-slate-700">

                                    {{ $label }}


                                    @if ($required)

                                        <span class="text-rose-500">
                                            *
                                        </span>

                                    @endif

                                </span>


                                <textarea
                                    name="{{ $name }}"

                                    rows="{{
                                        $config['rows'] ?? 4
                                    }}"

                                    placeholder="{{ $placeholder }}"

                                    @required($required)

                                    class="w-full resize-y rounded-lg border px-3 py-2.5 text-sm outline-none transition focus:ring-2 {{ $fieldClass($name) }}"
                                >{{ $currentValue }}</textarea>


                                @if (!empty($config['help']))

                                    <p class="mt-1 text-xs leading-5 text-slate-500">

                                        {{ $config['help'] }}

                                    </p>

                                @endif

                            </label>



                        {{-- ===================================== --}}
                        {{-- Normal Inputs --}}
                        {{-- ===================================== --}}

                        @else


                            <label class="block">


                                <span class="mb-1.5 block text-sm font-medium text-slate-700">

                                    {{ $label }}


                                    @if ($required)

                                        <span class="text-rose-500">
                                            *
                                        </span>

                                    @endif

                                </span>


                                <input
                                    type="{{ $type }}"

                                    name="{{ $name }}"

                                    value="{{
                                        $type === 'password'
                                            ? ''
                                            : $currentValue
                                    }}"

                                    placeholder="{{ $placeholder }}"

                                    @required($required)


                                    @if (isset($config['min']))

                                        min="{{ $config['min'] }}"

                                    @endif


                                    @if (isset($config['max']))

                                        max="{{ $config['max'] }}"

                                    @endif


                                    @if (isset($config['step']))

                                        step="{{ $config['step'] }}"

                                    @endif


                                    class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none transition focus:ring-2 {{ $fieldClass($name) }}"
                                >


                                @if (!empty($config['help']))

                                    <p class="mt-1 text-xs leading-5 text-slate-500">

                                        {{ $config['help'] }}

                                    </p>

                                @endif

                            </label>

                        @endif



                        {{-- ===================================== --}}
                        {{-- Field Error --}}
                        {{-- ===================================== --}}

                        @error($name)

                            <p class="mt-1 text-xs font-medium text-rose-600">

                                {{ $message }}

                            </p>

                        @enderror


                    </div>


                @endforeach


            </div>

        </section>



        {{-- ===================================================== --}}
        {{-- Actions --}}
        {{-- ===================================================== --}}

        <div
            class="flex flex-col-reverse gap-2 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm sm:flex-row sm:justify-end"
        >


            <a
                href="{{ route($routeName . '.index') }}"

                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >

                Cancel

            </a>



            @can(
                $permissionPrefix
                .
                (
                    $isEdit
                        ? '.update'
                        : '.create'
                )
            )

                <button
                    type="submit"

                    class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >

                    {{
                        $isEdit
                            ? 'Update'
                            : 'Save'
                    }}

                </button>

            @endcan


        </div>


    </form>

</div>

@endsection