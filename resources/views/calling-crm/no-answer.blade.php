@extends('layouts.crm')

@section('title', $title)

@section('content')
    @php
        $selectedLeadIds = collect((array) old('lead_ids', []))
            ->filter(fn ($id) => is_scalar($id))
            ->map(fn ($id) => (string) $id)
            ->flip();

        $selectedCount = $leads->getCollection()
            ->filter(fn ($lead) => $selectedLeadIds->has((string) $lead->id))
            ->count();
    @endphp

    <div class="mx-auto max-w-[1560px] space-y-3 text-[#151838]">
        <header class="flex flex-wrap items-center justify-between gap-3 rounded-r-xl border-l-2 border-violet-500 bg-[linear-gradient(112deg,#f5f4fe,#f7f9ff_63%,#f0ebff)] px-3 py-2">
            <div class="flex min-w-0 items-center gap-2.5">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-violet-100 text-violet-700">
                    <i data-lucide="phone-missed" aria-hidden="true" class="h-4 w-4"></i>
                </span>
                <div class="min-w-0">
                    <h1 class="text-xl font-extrabold leading-tight tracking-tight sm:text-2xl">{{ $title }}</h1>
                    <p class="mt-0.5 text-xs text-[#515d79]">
                        No Answer leads with exactly one call history record.
                    </p>
                </div>
            </div>

            <a href="{{ route('calling-crm.index') }}"
                class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                <span aria-hidden="true">←</span>
                Back to Calling CRM
            </a>
        </header>

        <section aria-labelledby="no-answer-filters-heading"
            class="min-w-0 overflow-hidden rounded-xl border border-[#e2e7f3] bg-white shadow-[0_2px_5px_#29335f15]">
            <header class="flex min-h-10 items-center gap-2 bg-[linear-gradient(90deg,#f3f5fd,#fafaff)] px-3">
                <i data-lucide="filter" aria-hidden="true" class="h-[17px] w-[17px] shrink-0 text-[#39238e]"></i>
                <h2 id="no-answer-filters-heading" class="text-[15px] font-extrabold">Search &amp; Filters</h2>
            </header>

            <form method="GET" action="{{ route('calling-crm.no-answer') }}" class="p-3">
                <div class="grid gap-2.5 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_minmax(12rem,0.6fr)_auto] xl:items-end">
                    <div class="min-w-0">
                        <label for="search" class="mb-1 block text-[11px] font-bold">Mobile / Lead Name</label>
                        <div class="relative">
                            <i data-lucide="search" aria-hidden="true"
                                class="pointer-events-none absolute top-2.5 left-2.5 h-3.5 w-3.5 text-[#515d79]"></i>
                            <input id="search" name="search" type="search" value="{{ request('search') }}"
                                placeholder="Search mobile, name or lead ID"
                                class="h-[34px] w-full rounded-lg border border-[#dce1ed] bg-white pr-2.5 pl-8 text-xs placeholder:text-slate-500 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-100">
                        </div>
                    </div>

                    <div class="min-w-0">
                        <label for="user_id" class="mb-1 block text-[11px] font-bold">Current Employee</label>
                        <select id="user_id" name="user_id"
                            class="h-[34px] w-full rounded-lg border border-[#dce1ed] bg-white px-2.5 text-xs focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-100">
                            <option value="">All Employees</option>
                            @foreach ($filterUsers as $employee)
                                <option value="{{ $employee->id }}" @selected((string) request('user_id') === (string) $employee->id)>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:col-span-2 xl:col-span-1">
                        <button type="submit"
                            class="inline-flex h-[34px] items-center gap-1.5 rounded-lg bg-[linear-gradient(120deg,#7837e6,#4539d9)] px-3 text-xs font-bold text-white hover:bg-violet-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600">
                            <i data-lucide="filter" aria-hidden="true" class="h-3.5 w-3.5 shrink-0"></i>
                            Apply Filters
                        </button>
                        <a href="{{ route('calling-crm.no-answer') }}"
                            class="inline-flex h-[34px] items-center gap-1.5 rounded-lg border border-[#dedff2] bg-white px-3 text-xs font-bold hover:bg-[#f2f3fc] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600">
                            <i data-lucide="rotate-ccw" aria-hidden="true" class="h-3.5 w-3.5 shrink-0"></i>
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </section>

        <section aria-labelledby="no-answer-results-heading"
            class="min-w-0 overflow-hidden rounded-xl border border-[#e2e7f3] bg-white shadow-[0_2px_5px_#29335f15]">
            <header class="flex min-h-10 flex-wrap items-center justify-between gap-2 bg-[linear-gradient(90deg,#f3f5fd,#fafaff)] px-3 py-1.5">
                <h2 id="no-answer-results-heading" class="flex items-center gap-2 text-[15px] font-extrabold">
                    <i data-lucide="phone-call" aria-hidden="true" class="h-[17px] w-[17px] shrink-0 text-[#39238e]"></i>
                    Call Records
                </h2>
                <span class="rounded-full bg-[#efebff] px-2.5 py-1 text-[11px] font-bold tabular-nums text-[#39238e]">
                    @if ($leads->isEmpty())
                        {{ $leads->total() === 0 ? '0 matching leads' : '0 records on this page' }}
                    @else
                        Showing {{ number_format($leads->firstItem()) }}
                        to {{ number_format($leads->lastItem()) }}
                        of {{ number_format($leads->total()) }}
                    @endif
                </span>
            </header>

            <form id="no-answer-reassign-form" method="POST"
                action="{{ route('calling-crm.no-answer.reassign') }}">
                @csrf

                <div class="flex flex-wrap items-end justify-between gap-2 border-b border-[#e2e7f3] bg-white px-3 py-2">
                    <p id="no-answer-selected-count" aria-live="polite"
                        class="self-center rounded-md bg-[#efebff] px-2 py-1 text-[11px] font-bold tabular-nums text-[#39238e]">
                        {{ $selectedCount }} selected
                    </p>

                    <div class="flex w-full flex-wrap items-end gap-2 sm:w-auto">
                        <div class="w-full min-w-0 sm:w-52">
                            <label for="assigned_to" class="mb-1.5 block text-xs font-semibold text-slate-700">
                                Assign To
                            </label>
                            <select id="assigned_to" name="assigned_to" required
                                class="h-[34px] w-full rounded-lg border border-[#dce1ed] bg-white px-2.5 text-xs text-[#151838] focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-100">
                                <option value="">Select Employee</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}" @selected((string) old('assigned_to') === (string) $employee->id)>
                                        {{ $employee->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button id="no-answer-reassign-button" type="submit"
                            @disabled($leads->isEmpty() || $employees->isEmpty())
                            class="inline-flex h-[34px] items-center justify-center gap-1.5 rounded-lg bg-violet-700 px-3 text-xs font-bold text-white hover:bg-violet-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-600">
                            <i data-lucide="user-round-check" aria-hidden="true" class="h-3.5 w-3.5 shrink-0"></i>
                            Reassign &amp; Set Pending
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto" role="region" aria-label="Matching leads table" tabindex="0">
                    <table class="w-full min-w-[56rem] text-left text-sm">
                        <thead class="border-b border-[#e2e7f3] bg-[#ececfc] text-[10px] uppercase tracking-wide text-[#252852]">
                            <tr>
                                <th scope="col" class="w-11 px-3 py-2">
                                    <input type="checkbox" id="select-all"
                                        aria-label="Select all leads on this page" @disabled($leads->isEmpty())
                                        class="h-4 w-4 cursor-pointer rounded border-slate-300 align-middle accent-violet-600 disabled:cursor-not-allowed">
                                </th>
                                <th scope="col" class="whitespace-nowrap px-2.5 py-2 font-bold">Lead ID</th>
                                <th scope="col" class="px-2.5 py-2 font-bold">Name / Phone</th>
                                <th scope="col" class="px-2.5 py-2 font-bold">Category</th>
                                <th scope="col" class="whitespace-nowrap px-2.5 py-2 font-bold">Assigned To</th>
                                <th scope="col" class="px-2.5 py-2 font-bold">Status</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2 text-center font-semibold">History Count</th>
                                <th scope="col" class="whitespace-nowrap px-2.5 py-2 font-bold">Last Call / Called By</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2 text-right font-semibold">Last Call Duration</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[#e9edf5]">
                            @forelse ($leads as $lead)
                                @php
                                    $history = $lead->callHistories->sortByDesc('call_at')->first();
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

                                <tr class="align-middle hover:bg-[#f7f6ff] has-[:checked]:bg-violet-50">
                                    <td class="px-3 py-2">
                                        <input type="checkbox" name="lead_ids[]" value="{{ $lead->id }}"
                                            aria-label="Select lead {{ $lead->id }}"
                                            @checked($selectedLeadIds->has((string) $lead->id))
                                            class="lead-checkbox h-4 w-4 cursor-pointer rounded border-slate-300 align-middle accent-violet-600">
                                    </td>

                                    <td class="whitespace-nowrap px-3 py-2 text-xs font-medium tabular-nums text-slate-600">
                                        #{{ $lead->id }}
                                    </td>

                                    <td class="min-w-36 px-3 py-2">
                                        <p class="break-words text-sm font-semibold text-slate-900">{{ $lead->name ?: '—' }}</p>
                                        <p class="mt-0.5 whitespace-nowrap text-xs tabular-nums text-slate-600">{{ $lead->mobile ?: '—' }}</p>
                                    </td>

                                    <td class="px-3 py-2 text-xs text-slate-700">{{ $lead->leadCategory?->name ?? '—' }}</td>

                                    <td class="px-3 py-2 text-xs font-medium text-slate-700">{{ $lead->assignedUser?->name ?? 'Unassigned' }}</td>

                                    <td class="px-3 py-2">
                                        <span class="inline-block whitespace-nowrap rounded-md bg-[#fff2d5] px-2 py-0.5 text-[11px] font-bold text-[#92440c]">
                                            {{ $lead->call_status }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-2 text-center text-xs font-semibold tabular-nums text-slate-700">
                                        {{ $lead->call_histories_count }}
                                    </td>

                                    <td class="whitespace-nowrap px-3 py-2">
                                        <p class="text-xs font-medium tabular-nums text-slate-900">{{ $history?->call_at?->format('d M Y, h:i A') ?? '—' }}</p>
                                        <p class="mt-0.5 text-xs text-slate-600">{{ $history?->user?->name ?? '—' }}</p>
                                    </td>

                                    <td class="whitespace-nowrap px-3 py-2 text-right">
                                        <p class="text-xs font-semibold tabular-nums text-slate-900">{{ $durationText }}</p>
                                        @if ($durationText !== '—')
                                            <p class="mt-0.5 text-xs text-slate-500">min:sec</p>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-10 text-center text-sm text-slate-600">
                                        No matching leads found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            @if ($leads->hasPages())
                <div class="border-t border-[#e2e7f3] bg-[#fafaff] px-3 py-2.5">
                    {{ $leads->withQueryString()->links() }}
                </div>
            @endif
        </section>
    </div>

    <script>
        (() => {
            const form = document.getElementById('no-answer-reassign-form');
            const selectAll = form.querySelector('#select-all');
            const checkboxes = Array.from(form.querySelectorAll('.lead-checkbox'));
            const assignee = form.querySelector('#assigned_to');
            const submit = form.querySelector('#no-answer-reassign-button');
            const selectedLabel = form.querySelector('#no-answer-selected-count');

            function updateSelection() {
                const selected = checkboxes.filter(checkbox => checkbox.checked).length;

                selectAll.checked = checkboxes.length > 0 && selected === checkboxes.length;
                selectAll.indeterminate = selected > 0 && selected < checkboxes.length;
                selectedLabel.textContent = `${selected} selected`;
                submit.disabled = selected === 0 || assignee.value === '';
            }

            selectAll.addEventListener('change', () => {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
                updateSelection();
            });

            checkboxes.forEach(checkbox => checkbox.addEventListener('change', updateSelection));
            assignee.addEventListener('change', updateSelection);
            window.addEventListener('pageshow', updateSelection);
            updateSelection();
        })();
    </script>
@endsection
