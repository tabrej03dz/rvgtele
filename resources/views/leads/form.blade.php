@extends('layouts.crm', [
    'title' => isset($lead) ? 'Edit Lead' : 'Create Lead',
])

@section('content')
@php
    $lead = $lead ?? null;
    $isEdit = $lead !== null;

    $fieldValue = function (string $name, string $type = 'text') use ($lead, $isEdit) {
        $oldValue = old($name);

        if ($oldValue !== null) {
            return $oldValue;
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
            ? 'border-rose-300 bg-rose-50 focus:border-rose-500 focus:ring-rose-500'
            : 'border-slate-300 bg-white focus:border-blue-500 focus:ring-blue-500';
    };
@endphp

<div class="mx-auto max-w-6xl space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <a href="{{ route('leads.index') }}" class="hover:text-blue-600">
                    Leads
                </a>
                <span>/</span>
                <span>{{ $isEdit ? 'Edit' : 'Create' }}</span>
            </div>

            <h1 class="mt-1 text-2xl font-bold text-slate-900">
                {{ $isEdit ? 'Edit Lead' : 'Create Lead' }}
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Lead ki contact, CRM aur follow-up details fill karein.
            </p>
        </div>

        <a
            href="{{ $isEdit ? route('leads.show', $lead) : route('leads.index') }}"
            class="inline-flex self-start rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:self-auto"
        >
            Back
        </a>
    </div>

    {{-- Validation Summary --}}
    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3">
            <div class="font-semibold text-rose-800">
                Form submit nahi hua
            </div>

            <p class="mt-1 text-sm text-rose-700">
                Highlighted fields correct karein. Aapka entered data safe hai.
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ $isEdit ? route('leads.update', $lead) : route('leads.store') }}"
        class="space-y-5"
    >
        @csrf

        @if ($isEdit)
            @method('PUT')
        @endif

        {{-- Basic Details --}}
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-bold text-slate-900">
                    Basic Details
                </h2>

                <p class="mt-0.5 text-sm text-slate-500">
                    Lead ki primary contact information.
                </p>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Lead Name <span class="text-rose-500">*</span>
                    </span>

                    <input
                        type="text"
                        name="name"
                        value="{{ $fieldValue('name') }}"
                        placeholder="Example: Rahul Sharma"
                        required
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('name') }}"
                    >

                    @error('name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Mobile Number <span class="text-rose-500">*</span>
                    </span>

                    <input
                        type="tel"
                        name="mobile"
                        value="{{ $fieldValue('mobile') }}"
                        placeholder="Example: 9876543210"
                        inputmode="numeric"
                        required
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('mobile') }}"
                    >

                    @error('mobile')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Alternate Mobile
                    </span>

                    <input
                        type="tel"
                        name="alternate_mobile"
                        value="{{ $fieldValue('alternate_mobile') }}"
                        placeholder="Secondary mobile number"
                        inputmode="numeric"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('alternate_mobile') }}"
                    >

                    @error('alternate_mobile')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        WhatsApp Number
                    </span>

                    <input
                        type="tel"
                        name="whatsapp_number"
                        value="{{ $fieldValue('whatsapp_number') }}"
                        placeholder="WhatsApp enabled number"
                        inputmode="numeric"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('whatsapp_number') }}"
                    >

                    @error('whatsapp_number')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Email
                    </span>

                    <input
                        type="email"
                        name="email"
                        value="{{ $fieldValue('email') }}"
                        placeholder="rahul@example.com"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('email') }}"
                    >

                    @error('email')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Company Name
                    </span>

                    <input
                        type="text"
                        name="company_name"
                        value="{{ $fieldValue('company_name') }}"
                        placeholder="Example: RVG Enterprises"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('company_name') }}"
                    >

                    @error('company_name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>
        </section>

        {{-- CRM Details --}}
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-bold text-slate-900">
                    CRM Details
                </h2>

                <p class="mt-0.5 text-sm text-slate-500">
                    Source, status, assignment aur pipeline information.
                </p>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Lead Source <span class="text-rose-500">*</span>
                    </span>

                    <select
                        name="lead_source_id"
                        required
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('lead_source_id') }}"
                    >
                        <option value="">Select source</option>

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
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Lead Status <span class="text-rose-500">*</span>
                    </span>

                    <select
                        name="lead_status_id"
                        required
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('lead_status_id') }}"
                    >
                        <option value="">Select status</option>

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
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Assign To
                    </span>

                    <select
                        name="assigned_to"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('assigned_to') }}"
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
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Team
                    </span>

                    <select
                        name="team_id"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('team_id') }}"
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
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Pipeline Stage
                    </span>

                    <select
                        name="pipeline_stage_id"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('pipeline_stage_id') }}"
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
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Priority <span class="text-rose-500">*</span>
                    </span>

                    <select
                        name="priority"
                        required
                        class="w-full rounded-lg border px-3 py-2.5 text-sm capitalize {{ $inputClass('priority') }}"
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
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Temperature <span class="text-rose-500">*</span>
                    </span>

                    <select
                        name="temperature"
                        required
                        class="w-full rounded-lg border px-3 py-2.5 text-sm capitalize {{ $inputClass('temperature') }}"
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
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Preferred Language
                    </span>

                    <input
                        type="text"
                        name="preferred_language"
                        value="{{ $fieldValue('preferred_language') }}"
                        placeholder="Example: Hindi"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('preferred_language') }}"
                    >

                    @error('preferred_language')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>
        </section>

        {{-- Sales Details --}}
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-bold text-slate-900">
                    Sales Details
                </h2>

                <p class="mt-0.5 text-sm text-slate-500">
                    Requirement, budget aur follow-up planning.
                </p>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">

                <label class="block sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Required Product / Service
                    </span>

                    <input
                        type="text"
                        name="required_product"
                        value="{{ $fieldValue('required_product') }}"
                        placeholder="Example: Telecalling CRM"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('required_product') }}"
                    >

                    @error('required_product')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Estimated Budget
                    </span>

                    <input
                        type="number"
                        name="estimated_budget"
                        value="{{ $fieldValue('estimated_budget') }}"
                        placeholder="50000"
                        min="0"
                        step="0.01"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('estimated_budget') }}"
                    >

                    @error('estimated_budget')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Expected Deal Value
                    </span>

                    <input
                        type="number"
                        name="expected_deal_value"
                        value="{{ $fieldValue('expected_deal_value') }}"
                        placeholder="75000"
                        min="0"
                        step="0.01"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('expected_deal_value') }}"
                    >

                    @error('expected_deal_value')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Expected Closing Date
                    </span>

                    <input
                        type="date"
                        name="expected_closing_date"
                        value="{{ $fieldValue('expected_closing_date', 'date') }}"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('expected_closing_date') }}"
                    >

                    @error('expected_closing_date')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Next Follow-up
                    </span>

                    <input
                        type="datetime-local"
                        name="next_follow_up_at"
                        value="{{ $fieldValue('next_follow_up_at', 'datetime-local') }}"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('next_follow_up_at') }}"
                    >

                    @error('next_follow_up_at')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>
        </section>

        {{-- Address --}}
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-bold text-slate-900">
                    Address
                </h2>

                <p class="mt-0.5 text-sm text-slate-500">
                    Lead location details.
                </p>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        City
                    </span>

                    <input
                        type="text"
                        name="city"
                        value="{{ $fieldValue('city') }}"
                        placeholder="Example: Kanpur"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('city') }}"
                    >

                    @error('city')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        District
                    </span>

                    <input
                        type="text"
                        name="district"
                        value="{{ $fieldValue('district') }}"
                        placeholder="Example: Kanpur Nagar"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('district') }}"
                    >

                    @error('district')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        State
                    </span>

                    <input
                        type="text"
                        name="state"
                        value="{{ $fieldValue('state') }}"
                        placeholder="Example: Uttar Pradesh"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('state') }}"
                    >

                    @error('state')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        PIN Code
                    </span>

                    <input
                        type="text"
                        name="pincode"
                        value="{{ $fieldValue('pincode') }}"
                        placeholder="Example: 208001"
                        inputmode="numeric"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('pincode') }}"
                    >

                    @error('pincode')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block sm:col-span-2 lg:col-span-4">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Full Address
                    </span>

                    <textarea
                        name="address"
                        rows="4"
                        placeholder="House/office number, street and landmark"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $inputClass('address') }}"
                    >{{ old('address', $lead->address ?? '') }}</textarea>

                    @error('address')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>
        </section>

        {{-- Actions --}}
        <div class="flex flex-col-reverse gap-2 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm sm:flex-row sm:justify-end">
            <a
                href="{{ $isEdit ? route('leads.show', $lead) : route('leads.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
            >
                {{ $isEdit ? 'Update Lead' : 'Save Lead' }}
            </button>
        </div>
    </form>
</div>
@endsection