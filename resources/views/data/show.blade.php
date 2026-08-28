@extends('layouts.crm', ['title' => 'Data Details'])

@section('content')

@include('backend.data.partials.styles')

<div class="software-ui mx-auto w-full max-w-6xl space-y-3">

    {{-- Header --}}

    <section class="software-toolbar">

        <div
            class="
                flex
                flex-col
                gap-4
                px-4
                py-4
                lg:flex-row
                lg:items-center
                lg:justify-between
            "
        >

            <div class="flex items-center gap-3">

                <span class="data-toolbar-icon">
                    <i data-lucide="database"></i>
                </span>

                <div>

                    <div class="flex flex-wrap items-center gap-2">

                        <h1 class="text-lg font-bold text-slate-900">
                            {{ $data->name ?: 'Unnamed Data' }}
                        </h1>

                        @if($data->converted)

                            <span
                                class="
                                    rounded-full
                                    bg-emerald-50
                                    px-2.5
                                    py-1
                                    text-[10px]
                                    font-bold
                                    text-emerald-700
                                "
                            >
                                Converted
                            </span>

                        @else

                            <span
                                class="
                                    rounded-full
                                    bg-amber-50
                                    px-2.5
                                    py-1
                                    text-[10px]
                                    font-bold
                                    text-amber-700
                                "
                            >
                                Not Converted
                            </span>

                        @endif

                    </div>

                    <p class="mt-1 text-[11px] text-slate-500">
                        Data ID #{{ $data->id }}
                    </p>

                </div>

            </div>


            <div class="flex flex-wrap gap-2">

                @if(!$data->converted)

                    <form
                        method="POST"
                        action="{{ route(
                            'data.convert-to-lead',
                            $data
                        ) }}"
                        onsubmit="
                            return confirm(
                                'Is record ko lead me convert karna hai?'
                            )
                        "
                    >

                        @csrf

                        <button
                            type="submit"
                            class="software-btn software-btn-success"
                        >
                            <i data-lucide="user-plus"></i>
                            CONVERT TO LEAD
                        </button>

                    </form>

                @elseif($data->lead)

                    <a
                        href="{{ route(
                            'leads.show',
                            $data->lead
                        ) }}"
                        class="software-btn software-btn-success"
                    >
                        <i data-lucide="external-link"></i>
                        OPEN LEAD
                    </a>

                @endif


                @can('data.edit')

                    <a
                        href="{{ route('data.edit', $data) }}"
                        class="software-btn software-btn-primary"
                    >
                        <i data-lucide="pencil"></i>
                        EDIT
                    </a>

                @endcan


                <a
                    href="{{ route('data.index') }}"
                    class="software-btn"
                >
                    <i data-lucide="arrow-left"></i>
                    BACK
                </a>

            </div>

        </div>

    </section>


    {{-- Main Customer Info --}}

    <div class="grid gap-3 lg:grid-cols-2">

        <section class="software-panel">

            <div class="software-panel-title">

                <span class="panel-heading-label">

                    <span class="panel-heading-icon">
                        <i data-lucide="user-round"></i>
                    </span>

                    Contact Information

                </span>

            </div>


            <div class="grid gap-4 p-4 md:grid-cols-2">

                @include(
                    'backend.data.partials.detail-row',
                    [
                        'label' => 'Name',
                        'value' => $data->name
                    ]
                )

                @include(
                    'backend.data.partials.detail-row',
                    [
                        'label' => 'Business Name',
                        'value' => $data->company_name
                    ]
                )

                @include(
                    'backend.data.partials.detail-row',
                    [
                        'label' => 'Mobile',
                        'value' => $data->mobile
                    ]
                )

                @include(
                    'backend.data.partials.detail-row',
                    [
                        'label' => 'Alternate Mobile',
                        'value' => $data->alternate_mobile
                    ]
                )

                @include(
                    'backend.data.partials.detail-row',
                    [
                        'label' => 'WhatsApp',
                        'value' => $data->whatsapp_number
                    ]
                )

                @include(
                    'backend.data.partials.detail-row',
                    [
                        'label' => 'Email',
                        'value' => $data->email
                    ]
                )

                @include(
                    'backend.data.partials.detail-row',
                    [
                        'label' => 'Language',
                        'value' => $data->preferred_language
                    ]
                )

            </div>

        </section>


        {{-- Data Classification --}}

        <section class="software-panel">

            <div class="software-panel-title">

                <span class="panel-heading-label">

                    <span class="panel-heading-icon">
                        <i data-lucide="tags"></i>
                    </span>

                    Data Information

                </span>

            </div>


            <div class="grid gap-4 p-4 md:grid-cols-2">

                @include(
                    'backend.data.partials.detail-row',
                    [
                        'label' => 'CRM Company',
                        'value' => $data->company?->name
                    ]
                )

                @include(
                    'backend.data.partials.detail-row',
                    [
                        'label' => 'Category',
                        'value' => $data->category
                    ]
                )

                @include(
                    'backend.data.partials.detail-row',
                    [
                        'label' => 'Lead Source',
                        'value' => $data->lead_source
                    ]
                )

                @include(
                    'backend.data.partials.detail-row',
                    [
                        'label' => 'Campaign',
                        'value' => $data->campaign
                    ]
                )

                @include(
                    'backend.data.partials.detail-row',
                    [
                        'label' => 'Industry',
                        'value' => $data->industry
                    ]
                )

                @include(
                    'backend.data.partials.detail-row',
                    [
                        'label' => 'Required Product',
                        'value' => $data->required_product
                    ]
                )

                @include(
                    'backend.data.partials.detail-row',
                    [
                        'label' => 'Estimated Budget',
                        'value' =>
                            $data->estimated_budget !== null
                                ? '₹' .
                                    number_format(
                                        (float)
                                        $data->estimated_budget,
                                        2
                                    )
                                : null
                    ]
                )

            </div>

        </section>

    </div>


    {{-- Location --}}

    <section class="software-panel">

        <div class="software-panel-title">

            <span class="panel-heading-label">

                <span class="panel-heading-icon">
                    <i data-lucide="map-pin"></i>
                </span>

                Location

            </span>

        </div>


        <div
            class="
                grid
                gap-4
                p-4
                md:grid-cols-2
                lg:grid-cols-4
            "
        >

            <div class="md:col-span-2 lg:col-span-4">

                <div class="software-label">
                    Address
                </div>

                <div class="font-medium text-slate-800">
                    {{ $data->address ?: '—' }}
                </div>

            </div>


            @include(
                'backend.data.partials.detail-row',
                [
                    'label' => 'City',
                    'value' => $data->city
                ]
            )

            @include(
                'backend.data.partials.detail-row',
                [
                    'label' => 'District',
                    'value' => $data->district
                ]
            )

            @include(
                'backend.data.partials.detail-row',
                [
                    'label' => 'State',
                    'value' => $data->state
                ]
            )

            @include(
                'backend.data.partials.detail-row',
                [
                    'label' => 'Pincode',
                    'value' => $data->pincode
                ]
            )

        </div>

    </section>


    {{-- Remarks --}}

    <section class="software-panel">

        <div class="software-panel-title">

            <span class="panel-heading-label">

                <span class="panel-heading-icon">
                    <i data-lucide="message-square-text"></i>
                </span>

                Remarks

            </span>

        </div>

        <div
            class="
                whitespace-pre-line
                p-4
                text-sm
                leading-6
                text-slate-700
            "
        >
            {{ $data->remarks ?: 'No remarks added.' }}
        </div>

    </section>


    {{-- Conversion --}}

    <section class="software-panel">

        <div class="software-panel-title">

            <span class="panel-heading-label">

                <span class="panel-heading-icon">
                    <i data-lucide="git-branch-plus"></i>
                </span>

                Lead Conversion

            </span>

        </div>


        <div class="grid gap-4 p-4 md:grid-cols-3">

            @include(
                'backend.data.partials.detail-row',
                [
                    'label' => 'Converted',
                    'value' =>
                        $data->converted
                            ? 'Yes'
                            : 'No'
                ]
            )

            @include(
                'backend.data.partials.detail-row',
                [
                    'label' => 'Lead ID',
                    'value' => $data->lead_id
                ]
            )

            @include(
                'backend.data.partials.detail-row',
                [
                    'label' => 'Converted At',
                    'value' =>
                        $data->converted_at
                            ? $data->converted_at
                                ->format(
                                    'd M Y, h:i A'
                                )
                            : null
                ]
            )

        </div>

    </section>


    {{-- Delete --}}

    @can('data.delete')

        <section class="software-panel">

            <div
                class="
                    flex
                    items-center
                    justify-between
                    gap-4
                    p-4
                "
            >

                <div>

                    <div class="font-bold text-slate-800">
                        Delete this data record
                    </div>

                    <div class="mt-1 text-[11px] text-slate-500">
                        Record soft-delete hoga aur baad me restore kiya ja sakta hai.
                    </div>

                </div>


                <form
                    method="POST"
                    action="{{ route(
                        'data.destroy',
                        $data
                    ) }}"
                    onsubmit="
                        return confirm(
                            'Kya aap ye data delete karna chahte hain?'
                        )
                    "
                >

                    @csrf
                    @method('DELETE')

                    <button
                        type="submit"
                        class="software-btn software-btn-danger"
                    >
                        <i data-lucide="trash-2"></i>
                        DELETE
                    </button>

                </form>

            </div>

        </section>

    @endcan

</div>

@endsection