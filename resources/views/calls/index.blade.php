@extends('layouts.crm', [
    'title' => 'Call Logs',
])

@section('content')
<div class="mx-auto max-w-7xl space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Call Logs
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Lead calls, employee activity aur call outcomes dekhein.
            </p>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- Call Logs Table --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-slate-900">
                    Call History
                </h2>

                <p class="mt-0.5 text-sm text-slate-500">
                    {{ $calls->firstItem() ?? 0 }}–{{ $calls->lastItem() ?? 0 }}
                    of {{ $calls->total() }}
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1050px] text-sm">
                <thead class="bg-slate-50">
                    <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Lead</th>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Disposition</th>
                        <th class="px-4 py-3">Duration</th>
                        <th class="px-4 py-3">Remarks</th>
                        <th class="px-4 py-3">Date</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($calls as $call)
                        @php
                            $duration = max(0, (int) ($call->duration_seconds ?? 0));

                            $hours = intdiv($duration, 3600);
                            $minutes = intdiv($duration % 3600, 60);
                            $seconds = $duration % 60;

                            $formattedDuration = $hours > 0
                                ? sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds)
                                : sprintf('%02d:%02d', $minutes, $seconds);
                        @endphp

                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                @if ($call->lead)
                                    <a
                                        href="{{ route('leads.show', $call->lead) }}"
                                        class="font-semibold text-blue-600 hover:text-blue-700"
                                    >
                                        {{ $call->lead->name }}
                                    </a>

                                    @if ($call->lead->company_name)
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $call->lead->company_name }}
                                        </div>
                                    @endif
                                @else
                                    <span class="font-medium text-slate-500">
                                        Lead unavailable
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">
                                    {{ $call->user?->name ?? 'Unknown User' }}
                                </div>

                                @if ($call->user?->employee_code)
                                    <div class="mt-0.5 text-xs text-slate-500">
                                        {{ $call->user->employee_code }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                    {{ $call->disposition?->name ?? 'Not Set' }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="font-mono text-sm font-medium text-slate-700">
                                    {{ $formattedDuration }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <div
                                    class="max-w-md text-sm leading-5 text-slate-600"
                                    title="{{ $call->remarks }}"
                                >
                                    {{ \Illuminate\Support\Str::limit($call->remarks ?: 'No remarks', 80) }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">
                                    {{ $call->created_at?->format('d M Y') ?? '—' }}
                                </div>

                                <div class="mt-0.5 text-xs text-slate-500">
                                    {{ $call->created_at?->format('h:i A') ?? '' }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center">
                                <div class="font-semibold text-slate-700">
                                    No call logs found
                                </div>

                                <div class="mt-1 text-sm text-slate-500">
                                    Call save hone ke baad records yahan dikhenge.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($calls->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $calls->links() }}
            </div>
        @endif
    </section>
</div>
@endsection