@extends('layouts.crm', [
    'title' => isset($lead) ? 'Edit Lead' : 'Create Lead',
])

@section('content')
@php
    /*
    |--------------------------------------------------------------------------
    | Create page par $lead variable available nahi hota.
    | Isliye pehle safe null value set karna zaroori hai.
    |--------------------------------------------------------------------------
    */
    $lead = $lead ?? null;
    $isEdit = $lead !== null;

    $fieldValue = function (string $name, string $type = 'text') use ($lead, $isEdit) {
        $value = old($name);

        if ($value !== null) {
            return $value;
        }

        if (!$isEdit) {
            return '';
        }

        $value = data_get($lead, $name);

        if ($value instanceof \Carbon\CarbonInterface) {
            return match ($type) {
                'datetime-local' => $value->format('Y-m-d\TH:i'),
                'date' => $value->format('Y-m-d'),
                default => $value->toDateTimeString(),
            };
        }

        return $value ?? '';
    };

    $inputClass = function (string $name) use ($errors) {
        return $errors->has($name)
            ? 'border-rose-300 bg-rose-50/70 text-rose-900 placeholder:text-rose-300 focus:border-rose-500 focus:ring-rose-100'
            : 'border-slate-200 bg-slate-50 text-slate-900 placeholder:text-slate-400 focus:border-violet-500 focus:bg-white focus:ring-violet-100';
    };
@endphp

<div
    class="min-h-screen bg-slate-50/70"
    x-data="{
        tooltip: null,
        showAdvanced: true
    }"
>
    <div class="mx-auto max-w-[1500px] px-3 py-4 sm:px-5 lg:px-6">

        {{-- Page Header --}}
        <div class="relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-indigo-950 to-violet-900 px-5 py-6 text-white shadow-xl sm:px-7 sm:py-8">
            <div class="absolute -right-20 -top-20 h-72 w-72 rounded-full bg-violet-500/20 blur-3xl"></div>
            <div class="absolute -bottom-24 left-1/3 h-64 w-64 rounded-full bg-blue-500/20 blur-3xl"></div>

            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold text-violet-100 backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        RVG Tele CRM
                    </div>

                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                        {{ $isEdit ? 'Edit Lead' : 'Create New Lead' }}
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                        {{ $isEdit
                            ? 'Lead information, assignment and follow-up details update karein.'
                            : 'Customer details add karke apni sales pipeline mein naya lead create karein.' }}
                    </p>
                </div>

                <a
                    href="{{ $isEdit ? route('leads.show', $lead) : route('leads.index') }}"
                    class="inline-flex items-center justify-center gap-2 self-start rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20 lg:self-auto"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Back
                </a>
            </div>
        </div>

        {{-- Global Validation Summary --}}
        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 8v5M12 16h.01"/>
                        </svg>
                    </div>

                    <div>
                        <h2 class="font-bold text-rose-900">
                            Form submit nahi hua
                        </h2>
                        <p class="mt-1 text-sm text-rose-700">
                            Neeche highlighted fields ko sahi karke dobara submit karein. Aapka bhara hua data safe hai.
                        </p>

                        <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-rose-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form
            method="POST"
            action="{{ $isEdit ? route('leads.update', $lead) : route('leads.store') }}"
            class="space-y-6"
            novalidate
        >
            @csrf

            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">

                {{-- Main Form --}}
                <div class="space-y-6">

                    {{-- Basic Information --}}
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="8" r="4"/>
                                        <path d="M4 21a8 8 0 0 1 16 0"/>
                                    </svg>
                                </div>

                                <div>
                                    <h2 class="font-bold text-slate-900">Basic Information</h2>
                                    <p class="text-sm text-slate-500">Lead ki primary contact details.</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-3">

                            {{-- Lead Name --}}
                            <label class="block">
                                <span class="mb-1.5 flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                                    Lead Name <span class="text-rose-500">*</span>

                                    <span
                                        class="relative inline-flex"
                                        @mouseenter="tooltip = 'name'"
                                        @mouseleave="tooltip = null"
                                    >
                                        <svg class="h-4 w-4 cursor-help text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="9"/>
                                            <path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 2-2.5 2-2.5 4"/>
                                            <path d="M12 17h.01"/>
                                        </svg>

                                        <span
                                            x-show="tooltip === 'name'"
                                            x-cloak
                                            class="absolute bottom-full left-1/2 z-20 mb-2 w-56 -translate-x-1/2 rounded-lg bg-slate-900 px-3 py-2 text-xs font-normal text-white shadow-xl"
                                        >
                                            Customer ya contact person ka complete naam enter karein.
                                        </span>
                                    </span>
                                </span>

                                <input
                                    type="text"
                                    name="name"
                                    value="{{ $fieldValue('name') }}"
                                    placeholder="Example: Rahul Sharma"
                                    autocomplete="name"
                                    required
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('name') }}"
                                >

                                @error('name')
                                    <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600">
                                        <span>●</span> {{ $message }}
                                    </p>
                                @enderror
                            </label>

                            {{-- Mobile --}}
                            <label class="block">
                                <span class="mb-1.5 flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                                    Mobile Number <span class="text-rose-500">*</span>

                                    <span
                                        class="relative inline-flex"
                                        @mouseenter="tooltip = 'mobile'"
                                        @mouseleave="tooltip = null"
                                    >
                                        <svg class="h-4 w-4 cursor-help text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="9"/>
                                            <path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 2-2.5 2-2.5 4"/>
                                            <path d="M12 17h.01"/>
                                        </svg>

                                        <span
                                            x-show="tooltip === 'mobile'"
                                            x-cloak
                                            class="absolute bottom-full left-1/2 z-20 mb-2 w-56 -translate-x-1/2 rounded-lg bg-slate-900 px-3 py-2 text-xs font-normal text-white shadow-xl"
                                        >
                                            Unique primary contact number. Same company mein duplicate mobile allowed nahi hai.
                                        </span>
                                    </span>
                                </span>

                                <input
                                    type="tel"
                                    name="mobile"
                                    value="{{ $fieldValue('mobile') }}"
                                    placeholder="Example: 9876543210"
                                    inputmode="numeric"
                                    autocomplete="tel"
                                    required
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('mobile') }}"
                                >

                                @error('mobile')
                                    <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600">
                                        <span>●</span> {{ $message }}
                                    </p>
                                @enderror
                            </label>

                            {{-- Alternate Mobile --}}
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Alternate Mobile
                                </span>

                                <input
                                    type="tel"
                                    name="alternate_mobile"
                                    value="{{ $fieldValue('alternate_mobile') }}"
                                    placeholder="Secondary contact number"
                                    inputmode="numeric"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('alternate_mobile') }}"
                                >

                                @error('alternate_mobile')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>

                            {{-- WhatsApp --}}
                            <label class="block">
                                <span class="mb-1.5 flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                                    WhatsApp Number

                                    <span
                                        class="relative inline-flex"
                                        @mouseenter="tooltip = 'whatsapp'"
                                        @mouseleave="tooltip = null"
                                    >
                                        <svg class="h-4 w-4 cursor-help text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="9"/>
                                            <path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 2-2.5 2-2.5 4"/>
                                            <path d="M12 17h.01"/>
                                        </svg>

                                        <span
                                            x-show="tooltip === 'whatsapp'"
                                            x-cloak
                                            class="absolute bottom-full left-1/2 z-20 mb-2 w-56 -translate-x-1/2 rounded-lg bg-slate-900 px-3 py-2 text-xs font-normal text-white shadow-xl"
                                        >
                                            WhatsApp follow-up ke liye active WhatsApp number enter karein.
                                        </span>
                                    </span>
                                </span>

                                <input
                                    type="tel"
                                    name="whatsapp_number"
                                    value="{{ $fieldValue('whatsapp_number') }}"
                                    placeholder="WhatsApp enabled number"
                                    inputmode="numeric"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('whatsapp_number') }}"
                                >

                                @error('whatsapp_number')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>

                            {{-- Email --}}
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Email Address
                                </span>

                                <input
                                    type="email"
                                    name="email"
                                    value="{{ $fieldValue('email') }}"
                                    placeholder="rahul@example.com"
                                    autocomplete="email"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('email') }}"
                                >

                                @error('email')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>

                            {{-- Company --}}
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Company Name
                                </span>

                                <input
                                    type="text"
                                    name="company_name"
                                    value="{{ $fieldValue('company_name') }}"
                                    placeholder="Example: RVG Enterprises"
                                    autocomplete="organization"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('company_name') }}"
                                >

                                @error('company_name')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>
                        </div>
                    </section>

                    {{-- CRM Classification --}}
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 6h16M7 12h10M10 18h4"/>
                                    </svg>
                                </div>

                                <div>
                                    <h2 class="font-bold text-slate-900">CRM Classification</h2>
                                    <p class="text-sm text-slate-500">Lead source, status, owner aur pipeline details.</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-3">

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Lead Source <span class="text-rose-500">*</span>
                                </span>

                                <select
                                    name="lead_source_id"
                                    required
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('lead_source_id') }}"
                                >
                                    <option value="">Select lead source</option>
                                    @foreach ($sources as $source)
                                        <option
                                            value="{{ $source->id }}"
                                            @selected((string) old('lead_source_id', $lead->lead_source_id ?? '') === (string) $source->id)
                                        >
                                            {{ $source->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('lead_source_id')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Lead Status <span class="text-rose-500">*</span>
                                </span>

                                <select
                                    name="lead_status_id"
                                    required
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('lead_status_id') }}"
                                >
                                    <option value="">Select lead status</option>
                                    @foreach ($statuses as $status)
                                        <option
                                            value="{{ $status->id }}"
                                            @selected((string) old('lead_status_id', $lead->lead_status_id ?? '') === (string) $status->id)
                                        >
                                            {{ $status->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('lead_status_id')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Assign To
                                </span>

                                <select
                                    name="assigned_to"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('assigned_to') }}"
                                >
                                    <option value="">Keep unassigned</option>
                                    @foreach ($users as $user)
                                        <option
                                            value="{{ $user->id }}"
                                            @selected((string) old('assigned_to', $lead->assigned_to ?? '') === (string) $user->id)
                                        >
                                            {{ $user->name }}
                                            @if ($user->employee_code)
                                                ({{ $user->employee_code }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>

                                @error('assigned_to')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Team
                                </span>

                                <select
                                    name="team_id"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('team_id') }}"
                                >
                                    <option value="">Select team</option>
                                    @foreach ($teams as $team)
                                        <option
                                            value="{{ $team->id }}"
                                            @selected((string) old('team_id', $lead->team_id ?? '') === (string) $team->id)
                                        >
                                            {{ $team->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('team_id')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Pipeline Stage
                                </span>

                                <select
                                    name="pipeline_stage_id"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('pipeline_stage_id') }}"
                                >
                                    <option value="">Use default stage</option>
                                    @foreach ($stages as $stage)
                                        <option
                                            value="{{ $stage->id }}"
                                            @selected((string) old('pipeline_stage_id', $lead->pipeline_stage_id ?? '') === (string) $stage->id)
                                        >
                                            {{ $stage->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('pipeline_stage_id')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Preferred Language
                                </span>

                                <input
                                    type="text"
                                    name="preferred_language"
                                    value="{{ $fieldValue('preferred_language') }}"
                                    placeholder="Example: Hindi, English"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('preferred_language') }}"
                                >

                                @error('preferred_language')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Priority <span class="text-rose-500">*</span>
                                </span>

                                <select
                                    name="priority"
                                    required
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm capitalize outline-none transition focus:ring-4 {{ $inputClass('priority') }}"
                                >
                                    @foreach (['low', 'normal', 'high', 'urgent', 'hot'] as $priority)
                                        <option
                                            value="{{ $priority }}"
                                            @selected(old('priority', $lead->priority ?? 'normal') === $priority)
                                        >
                                            {{ ucfirst($priority) }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('priority')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Temperature <span class="text-rose-500">*</span>
                                </span>

                                <select
                                    name="temperature"
                                    required
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm capitalize outline-none transition focus:ring-4 {{ $inputClass('temperature') }}"
                                >
                                    @foreach (['cold', 'warm', 'hot'] as $temperature)
                                        <option
                                            value="{{ $temperature }}"
                                            @selected(old('temperature', $lead->temperature ?? 'cold') === $temperature)
                                        >
                                            {{ ucfirst($temperature) }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('temperature')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>
                        </div>
                    </section>

                    {{-- Sales Details --}}
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 19V5M4 19h16"/>
                                        <path d="m7 15 4-4 3 3 5-7"/>
                                    </svg>
                                </div>

                                <div>
                                    <h2 class="font-bold text-slate-900">Sales Opportunity</h2>
                                    <p class="text-sm text-slate-500">Requirement, budget aur follow-up planning.</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-3">

                            <label class="block lg:col-span-2">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Required Product / Service
                                </span>

                                <input
                                    type="text"
                                    name="required_product"
                                    value="{{ $fieldValue('required_product') }}"
                                    placeholder="Example: CRM software, website development, digital marketing"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('required_product') }}"
                                >

                                @error('required_product')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Estimated Budget
                                </span>

                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">₹</span>
                                    <input
                                        type="number"
                                        name="estimated_budget"
                                        value="{{ $fieldValue('estimated_budget') }}"
                                        placeholder="50000"
                                        min="0"
                                        step="0.01"
                                        class="w-full rounded-xl border py-2.5 pl-8 pr-3.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('estimated_budget') }}"
                                    >
                                </div>

                                @error('estimated_budget')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Expected Deal Value
                                </span>

                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">₹</span>
                                    <input
                                        type="number"
                                        name="expected_deal_value"
                                        value="{{ $fieldValue('expected_deal_value') }}"
                                        placeholder="75000"
                                        min="0"
                                        step="0.01"
                                        class="w-full rounded-xl border py-2.5 pl-8 pr-3.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('expected_deal_value') }}"
                                    >
                                </div>

                                @error('expected_deal_value')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Expected Closing Date
                                </span>

                                <input
                                    type="date"
                                    name="expected_closing_date"
                                    value="{{ $fieldValue('expected_closing_date', 'date') }}"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('expected_closing_date') }}"
                                >

                                @error('expected_closing_date')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="mb-1.5 flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                                    Next Follow-up

                                    <span
                                        class="relative inline-flex"
                                        @mouseenter="tooltip = 'followup'"
                                        @mouseleave="tooltip = null"
                                    >
                                        <svg class="h-4 w-4 cursor-help text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="9"/>
                                            <path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 2-2.5 2-2.5 4"/>
                                            <path d="M12 17h.01"/>
                                        </svg>

                                        <span
                                            x-show="tooltip === 'followup'"
                                            x-cloak
                                            class="absolute bottom-full right-0 z-20 mb-2 w-60 rounded-lg bg-slate-900 px-3 py-2 text-xs font-normal text-white shadow-xl"
                                        >
                                            Agli call ya follow-up ki exact date aur time set karein.
                                        </span>
                                    </span>
                                </span>

                                <input
                                    type="datetime-local"
                                    name="next_follow_up_at"
                                    value="{{ $fieldValue('next_follow_up_at', 'datetime-local') }}"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('next_follow_up_at') }}"
                                >

                                @error('next_follow_up_at')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </label>
                        </div>
                    </section>

                    {{-- Address --}}
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <button
                            type="button"
                            @click="showAdvanced = !showAdvanced"
                            class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left sm:px-6"
                        >
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/>
                                        <circle cx="12" cy="10" r="2.5"/>
                                    </svg>
                                </div>

                                <div>
                                    <h2 class="font-bold text-slate-900">Address Details</h2>
                                    <p class="text-sm text-slate-500">Location aur delivery/service area information.</p>
                                </div>
                            </div>

                            <svg
                                class="h-5 w-5 text-slate-400 transition"
                                :class="showAdvanced ? 'rotate-180' : ''"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <div x-show="showAdvanced" x-collapse>
                            <div class="grid gap-5 border-t border-slate-100 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-4">
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-700">City</span>
                                    <input
                                        type="text"
                                        name="city"
                                        value="{{ $fieldValue('city') }}"
                                        placeholder="Example: Kanpur"
                                        autocomplete="address-level2"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('city') }}"
                                    >
                                    @error('city')
                                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                    @enderror
                                </label>

                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-700">District</span>
                                    <input
                                        type="text"
                                        name="district"
                                        value="{{ $fieldValue('district') }}"
                                        placeholder="Example: Kanpur Nagar"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('district') }}"
                                    >
                                    @error('district')
                                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                    @enderror
                                </label>

                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-700">State</span>
                                    <input
                                        type="text"
                                        name="state"
                                        value="{{ $fieldValue('state') }}"
                                        placeholder="Example: Uttar Pradesh"
                                        autocomplete="address-level1"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('state') }}"
                                    >
                                    @error('state')
                                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                    @enderror
                                </label>

                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-700">PIN Code</span>
                                    <input
                                        type="text"
                                        name="pincode"
                                        value="{{ $fieldValue('pincode') }}"
                                        placeholder="Example: 208001"
                                        inputmode="numeric"
                                        autocomplete="postal-code"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 {{ $inputClass('pincode') }}"
                                    >
                                    @error('pincode')
                                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                    @enderror
                                </label>

                                <label class="block sm:col-span-2 lg:col-span-4">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-700">Full Address</span>
                                    <textarea
                                        name="address"
                                        rows="4"
                                        placeholder="House/office number, street, landmark and complete address"
                                        class="w-full rounded-xl border px-3.5 py-3 text-sm outline-none transition focus:ring-4 {{ $inputClass('address') }}"
                                    >{{ old('address', $lead->address ?? '') }}</textarea>

                                    @error('address')
                                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                    @enderror
                                </label>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- Sidebar --}}
                <aside class="space-y-5 xl:sticky xl:top-5 xl:self-start">

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9"/>
                                    <path d="M12 11v5M12 8h.01"/>
                                </svg>
                            </div>

                            <div>
                                <h3 class="font-bold text-slate-900">Form Help</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-500">
                                    <span class="font-semibold text-rose-500">*</span> wale fields required hain.
                                    Error aane par Laravel `old()` ke through entered data preserve rahega.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-violet-200 bg-gradient-to-br from-violet-50 to-indigo-50 p-5 shadow-sm">
                        <h3 class="font-bold text-violet-950">Quick Tips</h3>

                        <ul class="mt-4 space-y-3 text-sm text-violet-800">
                            <li class="flex gap-2.5">
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-violet-500"></span>
                                Primary mobile number duplicate nahi hona chahiye.
                            </li>
                            <li class="flex gap-2.5">
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-violet-500"></span>
                                Follow-up date set karne se sales calling miss nahi hogi.
                            </li>
                            <li class="flex gap-2.5">
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-violet-500"></span>
                                Priority aur temperature alag cheezein hain—priority urgency batati hai, temperature buying interest.
                            </li>
                        </ul>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="font-bold text-slate-900">Ready to Save?</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Required fields check karke form save karein.
                        </p>

                        <div class="mt-5 space-y-3">
                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-violet-200 transition hover:bg-violet-700 focus:outline-none focus:ring-4 focus:ring-violet-200"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 21h14a2 2 0 0 0 2-2V7l-4-4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2Z"/>
                                    <path d="M17 21v-8H7v8M7 3v5h8"/>
                                </svg>
                                {{ $isEdit ? 'Update Lead' : 'Save Lead' }}
                            </button>

                            <a
                                href="{{ $isEdit ? route('leads.show', $lead) : route('leads.index') }}"
                                class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                            >
                                Cancel
                            </a>
                        </div>
                    </div>
                </aside>
            </div>

            {{-- Mobile Bottom Save Bar --}}
            <div class="sticky bottom-3 z-30 mt-6 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-2xl backdrop-blur xl:hidden">
                <div class="flex gap-2">
                    <a
                        href="{{ $isEdit ? route('leads.show', $lead) : route('leads.index') }}"
                        class="inline-flex flex-1 items-center justify-center rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="inline-flex flex-[1.4] items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-3 text-sm font-bold text-white"
                    >
                        {{ $isEdit ? 'Update Lead' : 'Save Lead' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection