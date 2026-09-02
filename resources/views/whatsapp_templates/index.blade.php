@extends('layouts.crm', [
    'title' => 'WhatsApp Templates',
])

@section('content')

<div class="mx-auto max-w-7xl space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                WhatsApp Templates
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                WhatsApp ke reusable messages manage karein.
            </p>
        </div>


        @can('whatsapp-template.create')

            <a
                href="{{ route('whatsapp-templates.create') }}"
                class="inline-flex items-center justify-center gap-2
                       rounded-xl bg-green-600 px-4 py-2.5
                       text-sm font-semibold text-white
                       shadow-sm hover:bg-green-700"
            >
                + New Template
            </a>

        @endcan

    </div>


    {{-- Success --}}
    @if(session('success'))

        <div class="rounded-xl border border-green-200 bg-green-50
                    px-4 py-3 text-sm font-medium text-green-800">
            {{ session('success') }}
        </div>

    @endif


    {{-- Filters --}}
    <form
        method="GET"
        action="{{ route('whatsapp-templates.index') }}"
        class="rounded-2xl border border-slate-200 bg-white
               p-4 shadow-sm"
    >

        <div class="grid gap-3 md:grid-cols-5">

            {{-- Search --}}
            <div class="md:col-span-2">

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search template..."
                    class="w-full rounded-xl border border-slate-300
                           px-4 py-2.5 text-sm outline-none
                           focus:border-green-500 focus:ring-4
                           focus:ring-green-100"
                >

            </div>


            {{-- User --}}
            @can('whatsapp-template.view-all')

                <select
                    name="user_id"
                    class="rounded-xl border border-slate-300
                           px-3 py-2.5 text-sm"
                >
                    <option value="">
                        All Users
                    </option>

                    @foreach($users as $user)

                        <option
                            value="{{ $user->id }}"
                            @selected(
                                request('user_id') == $user->id
                            )
                        >
                            {{ $user->name }}
                        </option>

                    @endforeach

                </select>

            @endcan


            {{-- Type --}}
            <select
                name="type"
                class="rounded-xl border border-slate-300
                       px-3 py-2.5 text-sm"
            >

                <option value="">
                    All Types
                </option>

                <option
                    value="personal"
                    @selected(request('type') === 'personal')
                >
                    Personal
                </option>

                <option
                    value="global"
                    @selected(request('type') === 'global')
                >
                    Global
                </option>

            </select>


            {{-- Status --}}
            <select
                name="status"
                class="rounded-xl border border-slate-300
                       px-3 py-2.5 text-sm"
            >

                <option value="">
                    All Status
                </option>

                <option
                    value="active"
                    @selected(request('status') === 'active')
                >
                    Active
                </option>

                <option
                    value="inactive"
                    @selected(request('status') === 'inactive')
                >
                    Inactive
                </option>

            </select>

        </div>


        <div class="mt-3 flex justify-end gap-2">

            <a
                href="{{ route('whatsapp-templates.index') }}"
                class="rounded-lg border border-slate-300
                       px-4 py-2 text-sm font-semibold text-slate-600"
            >
                Reset
            </a>

            <button
                class="rounded-lg bg-slate-900 px-4 py-2
                       text-sm font-semibold text-white"
            >
                Filter
            </button>

        </div>

    </form>


    {{-- Templates --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200
                bg-white shadow-sm">

        @forelse($templates as $template)

            <div class="border-b border-slate-100 p-5 last:border-b-0">

                <div class="flex flex-col gap-4 lg:flex-row
                            lg:items-start lg:justify-between">

                    {{-- Main --}}
                    <div class="min-w-0 flex-1">

                        <div class="flex flex-wrap items-center gap-2">

                            <h3 class="font-bold text-slate-900">
                                {{ $template->name }}
                            </h3>


                            @if($template->is_global)

                                <span
                                    class="rounded-full bg-purple-100
                                           px-2.5 py-1 text-xs
                                           font-semibold text-purple-700"
                                >
                                    Global
                                </span>

                            @else

                                <span
                                    class="rounded-full bg-blue-100
                                           px-2.5 py-1 text-xs
                                           font-semibold text-blue-700"
                                >
                                    Personal
                                </span>

                            @endif


                            @if($template->is_active)

                                <span
                                    class="rounded-full bg-green-100
                                           px-2.5 py-1 text-xs
                                           font-semibold text-green-700"
                                >
                                    Active
                                </span>

                            @else

                                <span
                                    class="rounded-full bg-slate-100
                                           px-2.5 py-1 text-xs
                                           font-semibold text-slate-600"
                                >
                                    Inactive
                                </span>

                            @endif

                        </div>


                        @can('whatsapp-template.view-all')

                            <div class="mt-2 text-xs text-slate-500">

                                Owner:

                                <span class="font-semibold text-slate-700">
                                    {{ $template->user?->name ?? 'System' }}
                                </span>

                                @if($template->company)

                                    <span class="mx-1">•</span>

                                    {{ $template->company->name }}

                                @endif

                            </div>

                        @endcan


                        <div
                            class="mt-3 whitespace-pre-line rounded-xl
                                   bg-slate-50 p-4 text-sm leading-6
                                   text-slate-700"
                        >{{ $template->message }}</div>

                    </div>


                    {{-- Actions --}}
                    <div class="flex shrink-0 items-center gap-2">

                        @php

                            $canEdit =
                                auth()->user()->can('whatsapp-template.edit-all')
                                ||
                                (
                                    auth()->id() === $template->user_id
                                    &&
                                    auth()->user()->can('whatsapp-template.edit-own')
                                );

                            $canDelete =
                                auth()->user()->can('whatsapp-template.delete-all')
                                ||
                                (
                                    auth()->id() === $template->user_id
                                    &&
                                    auth()->user()->can('whatsapp-template.delete-own')
                                );

                        @endphp


                        @if($canEdit)

                            <a
                                href="{{ route('whatsapp-templates.edit', $template) }}"
                                class="rounded-lg border border-blue-200
                                       bg-blue-50 px-3 py-2
                                       text-xs font-semibold text-blue-700
                                       hover:bg-blue-100"
                            >
                                Edit
                            </a>

                        @endif


                        @if($canDelete)

                            <form
                                method="POST"
                                action="{{ route('whatsapp-templates.destroy', $template) }}"
                                onsubmit="return confirm('Delete this WhatsApp template?')"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="rounded-lg border border-red-200
                                           bg-red-50 px-3 py-2
                                           text-xs font-semibold text-red-700
                                           hover:bg-red-100"
                                >
                                    Delete
                                </button>

                            </form>

                        @endif

                    </div>

                </div>

            </div>

        @empty

            <div class="p-10 text-center">

                <div class="text-lg font-semibold text-slate-700">
                    No WhatsApp Templates
                </div>

                <p class="mt-1 text-sm text-slate-500">
                    Abhi koi template available nahi hai.
                </p>

            </div>

        @endforelse

    </div>


    {{-- Pagination --}}
    @if($templates->hasPages())

        <div>
            {{ $templates->links() }}
        </div>

    @endif

</div>

@endsection
