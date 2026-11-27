@extends('layouts.crm')

@section('title', 'Notification Details')

@section('content')
    <div class="mx-auto max-w-5xl space-y-5">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">
                        Notification #{{ $item->id }}
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ $item->created_at }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('employee-notifications.index') }}"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Back to History
                    </a>

                    <a href="{{ route('employee-notifications.edit', $item->id) }}"
                        class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Edit &amp; Resend
                    </a>
                </div>
            </div>

            <div class="mt-5 rounded-lg bg-slate-50 p-4">
                <h2 class="mb-2 text-sm font-semibold text-slate-700">Message</h2>

                <p class="whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-900">{{ filled($item->message) ? $item->message : 'Media notification' }}</p>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-medium">
                @if ($item->image_path)
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-indigo-700">
                        Image attached
                    </span>
                @endif

                @if ($item->video_path)
                    <span class="rounded-full bg-violet-50 px-3 py-1 text-violet-700">
                        Video attached
                    </span>
                @endif

                @if ($item->resent_from_id)
                    <a href="{{ route('employee-notifications.details', $item->resent_from_id) }}"
                        class="rounded-full bg-slate-100 px-3 py-1 text-slate-700 hover:bg-slate-200">
                        Resent from #{{ $item->resent_from_id }}
                    </a>
                @endif
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">
                Participants
                <span class="ml-1 rounded-full bg-indigo-50 px-2.5 py-1 text-sm text-indigo-700">
                    {{ $participants->total() }}
                </span>
            </h2>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3">User ID</th>
                            <th scope="col" class="px-4 py-3">Employee</th>
                            <th scope="col" class="px-4 py-3">Employee Code</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($participants as $participant)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-600">#{{ $participant->id }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $participant->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $participant->employee_code ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-slate-500">
                                    No participants found for this notification.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($participants->hasPages())
                <div class="mt-4">
                    {{ $participants->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection