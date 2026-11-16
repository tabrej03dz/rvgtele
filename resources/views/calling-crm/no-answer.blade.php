@extends('layouts.crm')

@section('title', $title)

@section('content')
<div class="mx-auto max-w-[1500px] space-y-5">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                No Answer — 1 Call
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                No Answer leads with exactly one call history record.
            </p>
        </div>

        <a
            href="{{ route('calling-crm.index') }}"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm"
        >
            Back to Calling CRM
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 p-4 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg bg-red-50 p-4 text-red-800">
            <ul class="list-inside list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="GET"
        action="{{ route('calling-crm.no-answer') }}"
        class="flex flex-wrap items-end gap-3 rounded-xl border bg-white p-4"
    >
        <div>
            <label for="search" class="mb-1 block text-sm">
                Search
            </label>

            <input
                id="search"
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Name, phone or lead ID"
                class="rounded-lg border border-slate-300 px-3 py-2"
            >
        </div>

        <div>
            <label for="user_id" class="mb-1 block text-sm">
                Current Employee
            </label>

            <select
                id="user_id"
                name="user_id"
                class="rounded-lg border border-slate-300 px-3 py-2"
            >
                <option value="">All Employees</option>

                @foreach($filterUsers as $employee)
                    <option
                        value="{{ $employee->id }}"
                        @selected(request('user_id') == $employee->id)
                    >
                        {{ $employee->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button
            type="submit"
            class="rounded-lg bg-blue-600 px-4 py-2 text-white"
        >
            Filter
        </button>

        <a
            href="{{ route('calling-crm.no-answer') }}"
            class="rounded-lg border px-4 py-2"
        >
            Reset
        </a>
    </form>

    <div class="rounded-xl border bg-white p-4">
        Total Matching Leads:
        <strong>{{ number_format($leads->total()) }}</strong>
    </div>

    @php
        $selectedLeadIds = array_map('strval', (array) old('lead_ids', []));
    @endphp

    <form
        method="POST"
        action="{{ route('calling-crm.no-answer.reassign') }}"
        class="overflow-hidden rounded-xl border bg-white"
    >
        @csrf

        <div class="flex flex-wrap items-end gap-3 border-b p-4">
            <div>
                <label for="assigned_to" class="mb-1 block text-sm">
                    Assign To
                </label>

                <select
                    id="assigned_to"
                    name="assigned_to"
                    required
                    class="rounded-lg border border-slate-300 px-3 py-2"
                >
                    <option value="">Select Employee</option>

                    @foreach($employees as $employee)
                        <option
                            value="{{ $employee->id }}"
                            @selected(old('assigned_to') == $employee->id)
                        >
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button
                type="submit"
                @disabled($leads->isEmpty())
                class="rounded-lg bg-blue-600 px-4 py-2 text-white disabled:opacity-50"
            >
                Reassign Selected & Set Pending
            </button>

            <p class="text-sm text-slate-500">
                Select leads using the checkboxes below.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="p-3">
                            <input
                                type="checkbox"
                                id="select-all"
                                aria-label="Select all leads on this page"
                                @disabled($leads->isEmpty())
                            >
                        </th>
                        <th class="p-3">Lead ID</th>
                        <th class="p-3">Name / Phone</th>
                        <th class="p-3">Category</th>
                        <th class="p-3">Assigned To</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">History Count</th>
                        <th class="p-3">Last Call / Called By</th>
                        <th class="p-3">Last Call Duration</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse($leads as $lead)
                        @php
                            $history = $lead->callHistories
                                ->sortByDesc('call_at')
                                ->first();

                            $duration = $history?->call_duration;
                            $durationText = '—';

                            if ($duration !== null && is_numeric($duration)) {
                                $durationSeconds = max(0, (int) $duration);

                                $durationText = sprintf(
                                    '%02d:%02d',
                                    intdiv($durationSeconds, 60),
                                    $durationSeconds % 60
                                );
                            }
                        @endphp

                        <tr>
                            <td class="p-3">
                                <input
                                    type="checkbox"
                                    name="lead_ids[]"
                                    value="{{ $lead->id }}"
                                    class="lead-checkbox"
                                    aria-label="Select lead {{ $lead->id }}"
                                    @checked(
                                        in_array(
                                            (string) $lead->id,
                                            $selectedLeadIds,
                                            true
                                        )
                                    )
                                >
                            </td>

                            <td class="p-3">
                                {{ $lead->id }}
                            </td>

                            <td class="p-3">
                                <div class="font-semibold">
                                    {{ $lead->name ?: '—' }}
                                </div>

                                <div class="text-slate-500">
                                    {{ $lead->mobile ?: '—' }}
                                </div>
                            </td>

                            <td class="p-3">
                                {{ $lead->leadCategory?->name ?? '—' }}
                            </td>

                            <td class="p-3">
                                {{ $lead->assignedUser?->name ?? 'Unassigned' }}
                            </td>

                            <td class="p-3">
                                <span class="rounded bg-amber-50 px-2 py-1 text-amber-700">
                                    {{ $lead->call_status }}
                                </span>
                            </td>

                            <td class="p-3">
                                {{ $lead->call_histories_count }}
                            </td>

                            <td class="whitespace-nowrap p-3">
                                <div>
                                    {{ $history?->call_at?->format('d M Y, h:i A') ?? '—' }}
                                </div>

                                <div class="text-slate-500">
                                    {{ $history?->user?->name ?? '—' }}
                                </div>
                            </td>

                            <td class="whitespace-nowrap p-3">
                                <div class="font-semibold text-slate-900">
                                    {{ $durationText }}
                                </div>

                                @if($durationText !== '—')
                                    <div class="text-xs text-slate-500">
                                        min:sec
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="9"
                                class="p-8 text-center text-slate-500"
                            >
                                No matching leads found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <div>
        {{ $leads->withQueryString()->links() }}
    </div>

</div>

<script>
(function () {
    const selectAll = document.getElementById('select-all');
    const checkboxes = Array.from(
        document.querySelectorAll('.lead-checkbox')
    );

    if (!selectAll) {
        return;
    }

    function updateSelectAll() {
        const selectedCount = checkboxes.filter(
            (checkbox) => checkbox.checked
        ).length;

        selectAll.checked =
            checkboxes.length > 0 &&
            selectedCount === checkboxes.length;

        selectAll.indeterminate =
            selectedCount > 0 &&
            selectedCount < checkboxes.length;
    }

    selectAll.addEventListener('change', function () {
        checkboxes.forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
        });

        updateSelectAll();
    });

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', updateSelectAll);
    });

    updateSelectAll();
})();
</script>
@endsection