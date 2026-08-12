@extends('layouts.crm', [
    'title' => 'Activity Details',
])

@section('content')

@php
    $properties = $activity->properties ?? collect();

    $requestData = $properties->get('request_data', []);

    $routeParameters = $properties->get(
        'route_parameters',
        []
    );
@endphp

<div class="mx-auto max-w-5xl space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Activity Details
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Detailed information about this user activity.
            </p>
        </div>

        <a
            href="{{ route('activity-logs.index') }}"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
        >
            ← Back
        </a>

    </div>


    {{-- Main Info --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="font-semibold text-slate-900">
                Basic Information
            </h2>
        </div>

        <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2">

            <div>
                <p class="text-xs font-semibold uppercase text-slate-400">
                    User
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    {{ $activity->causer?->name ?? 'Unknown User' }}
                </p>

                <p class="text-sm text-slate-500">
                    {{ $activity->causer?->email }}
                </p>
            </div>


            <div>
                <p class="text-xs font-semibold uppercase text-slate-400">
                    Activity
                </p>

                <p class="mt-1 text-sm font-medium text-slate-900">
                    {{ $activity->description }}
                </p>
            </div>


            <div>
                <p class="text-xs font-semibold uppercase text-slate-400">
                    Method
                </p>

                <p class="mt-1 text-sm font-medium text-slate-900">
                    {{ $properties->get('method') ?? '-' }}
                </p>
            </div>


            <div>
                <p class="text-xs font-semibold uppercase text-slate-400">
                    Status Code
                </p>

                <p class="mt-1 text-sm font-medium text-slate-900">
                    {{ $properties->get('status_code') ?? '-' }}
                </p>
            </div>


            <div>
                <p class="text-xs font-semibold uppercase text-slate-400">
                    Route
                </p>

                <p class="mt-1 break-all text-sm text-slate-900">
                    {{ $properties->get('route') ?? '-' }}
                </p>
            </div>


            <div>
                <p class="text-xs font-semibold uppercase text-slate-400">
                    Controller
                </p>

                <p class="mt-1 break-all text-sm text-slate-900">
                    {{ $properties->get('controller_action') ?? '-' }}
                </p>
            </div>


            <div>
                <p class="text-xs font-semibold uppercase text-slate-400">
                    IP Address
                </p>

                <p class="mt-1 text-sm text-slate-900">
                    {{ $properties->get('ip_address') ?? '-' }}
                </p>
            </div>


            <div>
                <p class="text-xs font-semibold uppercase text-slate-400">
                    Request Time
                </p>

                <p class="mt-1 text-sm text-slate-900">
                    {{ $activity->created_at->format('d M Y h:i:s A') }}
                </p>
            </div>


            <div>
                <p class="text-xs font-semibold uppercase text-slate-400">
                    Request Duration
                </p>

                <p class="mt-1 text-sm text-slate-900">
                    {{ $properties->get('duration_ms') ?? '-' }} ms
                </p>
            </div>


            <div>
                <p class="text-xs font-semibold uppercase text-slate-400">
                    Previous Page
                </p>

                <p class="mt-1 break-all text-sm text-slate-900">
                    {{ $properties->get('referer') ?? '-' }}
                </p>
            </div>

        </div>

    </div>


    {{-- URL --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

        <p class="text-xs font-semibold uppercase text-slate-400">
            URL
        </p>

        <div class="mt-2 break-all rounded-lg bg-slate-50 p-4 text-sm text-slate-700">
            {{ $properties->get('url') ?? '-' }}
        </div>

    </div>


    {{-- Request Data --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="font-semibold text-slate-900">
                Submitted Data
            </h2>
        </div>

        <div class="p-6">

            @if(!empty($requestData))

                <pre class="max-h-[500px] overflow-auto rounded-lg bg-slate-950 p-5 text-xs leading-6 text-slate-100">{{ json_encode($requestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

            @else

                <p class="text-sm text-slate-500">
                    No submitted form data.
                </p>

            @endif

        </div>

    </div>


    {{-- Route Parameters --}}
    @if(!empty($routeParameters))

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="font-semibold text-slate-900">
                    Route Parameters
                </h2>
            </div>

            <div class="p-6">

                <pre class="overflow-auto rounded-lg bg-slate-950 p-5 text-xs leading-6 text-slate-100">{{ json_encode($routeParameters, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

            </div>

        </div>

    @endif


    {{-- Browser --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

        <p class="text-xs font-semibold uppercase text-slate-400">
            Browser / Device
        </p>

        <p class="mt-2 break-all text-sm leading-6 text-slate-600">
            {{ $properties->get('user_agent') ?? '-' }}
        </p>

    </div>

</div>

@endsection
