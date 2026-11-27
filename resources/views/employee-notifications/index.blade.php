@extends('layouts.crm')

@section('title', 'Employee Notifications')

@section('content')
    @php
        $activeIds = collect((array) (old('audience') === 'all'
            ? []
            : old('recipient_ids', $selectedIds)))
            ->filter(fn ($id) => is_scalar($id))
            ->map(fn ($id) => (string) $id)
            ->all();

        $recipientError = $errors->first('recipient_ids')
            ?: $errors->first('recipient_ids.*');
    @endphp

    <div class="mx-auto max-w-5xl space-y-6">
        <form method="POST"
            action="{{ $editing
                ? route('employee-notifications.resend', $editing->id)
                : route('employee-notifications.store') }}"
            enctype="multipart/form-data"
            class="space-y-4 rounded-xl border bg-white p-5">
            @csrf

            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-bold text-slate-900">
                    {{ $editing ? 'Edit & Resend #' . $editing->id : 'Employee Notifications' }}
                </h1>

                @if ($editing)
                    <a href="{{ route('employee-notifications.index') }}"
                        class="text-sm font-semibold text-indigo-700">
                        Back to history
                    </a>
                @endif
            </div>

            <div id="employee-picker" class="space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <span id="employee-picker-label" class="text-sm font-semibold text-slate-800">
                        Send to
                    </span>

                    <span data-recipient-count aria-live="polite" hidden
                        class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700"></span>
                </div>

                <div class="relative flex min-h-12 flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white p-2 shadow-sm focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-100">
                    <div data-recipient-chips class="flex min-w-0 flex-1 flex-wrap items-center gap-1.5 pl-1">
                        <span class="px-1 text-sm font-semibold text-slate-800">
                            {{ $activeIds === [] ? 'All Employees' : 'Selected Employees' }}
                        </span>
                    </div>

                    <button type="button" data-recipient-clear hidden
                        class="rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">
                        Clear all
                    </button>

                    <details data-recipient-menu class="shrink-0" @if ($recipientError) open @endif>
                        <summary aria-describedby="employee-picker-hint"
                            class="flex cursor-pointer list-none items-center gap-2 rounded-lg bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 [&::-webkit-details-marker]:hidden">
                            Choose employees
                            <span aria-hidden="true" class="ml-0.5 h-2 w-2 -translate-y-0.5 rotate-45 border-b border-r border-current"></span>
                        </summary>

                        <div class="absolute inset-x-0 top-full z-30 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                            <div class="border-b border-slate-100 bg-slate-50 p-3">
                                <input type="search" data-recipient-search
                                    aria-label="Search by employee name or code" autocomplete="off"
                                    placeholder="Search by name or employee code"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                            </div>

                            <div class="max-h-60 overflow-y-auto overscroll-contain p-2">
                                @forelse ($employees as $employee)
                                    @php
                                        $employeeCode = $employee->employee_code ?: '#' . $employee->id;
                                    @endphp

                                    <label data-recipient-option data-employee-name="{{ $employee->name }}">
                                        <span class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 hover:bg-slate-50 has-[:checked]:bg-indigo-50">
                                            <input type="checkbox" name="recipient_ids[]"
                                                value="{{ $employee->id }}"
                                                class="h-4 w-4 shrink-0 accent-indigo-600"
                                                @checked(in_array((string) $employee->id, $activeIds, true))>

                                            <span class="min-w-0">
                                                <span class="block break-words text-sm font-medium text-slate-800">
                                                    {{ $employee->name }}
                                                </span>
                                                <span class="block text-xs text-slate-500">
                                                    {{ $employeeCode }}
                                                </span>
                                            </span>
                                        </span>
                                    </label>
                                @empty
                                    <p class="px-3 py-4 text-sm text-slate-500">No eligible employees found.</p>
                                @endforelse

                                <p data-recipient-no-results hidden class="px-3 py-4 text-sm text-slate-500">
                                    No matching employees.
                                </p>
                            </div>
                        </div>
                    </details>
                </div>

                <p id="employee-picker-hint" class="text-xs text-slate-500">
                    Leave the selection empty to send to all eligible employees.
                </p>

                @if ($recipientError)
                    <p role="alert" class="text-sm font-medium text-red-600">{{ $recipientError }}</p>
                @endif
            </div>

            <div>
                <label for="message" class="block text-sm font-semibold">Message</label>

                <textarea id="message" name="message" rows="5" maxlength="600"
                    class="mt-1 w-full rounded-lg border p-2">{{ old('message', $editing?->message) }}</textarea>
            </div>

            <div>
                <label for="image" class="block text-sm font-semibold">Image · up to 10 MB</label>

                <input type="file" id="image" name="image"
                    accept="image/jpeg,image/png,image/webp">

                @if ($editing?->image_path)
                    <label class="mt-1 flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remove_image" value="1"
                            @checked(old('remove_image'))>
                        Remove existing image from this resend
                    </label>
                @endif
            </div>

            <div>
                <label for="video" class="block text-sm font-semibold">MP4 video · up to 20 MB</label>

                <input type="file" id="video" name="video" accept="video/mp4">

                @if ($editing?->video_path)
                    <label class="mt-1 flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remove_video" value="1"
                            @checked(old('remove_video'))>
                        Remove existing video from this resend
                    </label>
                @endif
            </div>

            <button type="submit" data-notification-submit
                data-all-label="{{ $editing ? 'Resend to All Employees' : 'Send to All Employees' }}"
                data-selected-label="{{ $editing ? 'Save & Resend' : 'Send Notification' }}"
                class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">
                @if ($activeIds === [])
                    {{ $editing ? 'Resend to All Employees' : 'Send to All Employees' }}
                @else
                    {{ $editing ? 'Save & Resend' : 'Send Notification' }}
                @endif
            </button>
        </form>

        @if ($history !== null)
            <section id="notification-history" class="scroll-mt-24 space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-lg font-bold text-slate-900">Notification History</h2>
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                        {{ number_format($history->total()) }} notifications
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b text-slate-600">
                            <tr>
                                <th class="p-2">ID</th>
                                <th class="p-2">Message</th>
                                <th class="p-2">Created</th>
                                <th class="p-2">Participants</th>
                                <th class="p-2">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y">
                            @forelse ($history as $item)
                                <tr>
                                    <td class="p-2">
                                        #{{ $item->id }}
                                        @if ($item->resent_from_id)
                                            <div class="text-xs text-slate-500">
                                                Resent from #{{ $item->resent_from_id }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="p-2">
                                        <a href="{{ route('employee-notifications.details', $item->id) }}"
                                            class="font-semibold text-indigo-700">
                                            {{ $item->message !== '' ? \Illuminate\Support\Str::limit($item->message, 90) : 'Media notification' }}
                                        </a>

                                        @if ($item->image_path || $item->video_path)
                                            <div class="text-xs text-slate-500">
                                                {{ $item->image_path ? 'Image' : '' }}
                                                {{ $item->image_path && $item->video_path ? '·' : '' }}
                                                {{ $item->video_path ? 'Video' : '' }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="p-2 whitespace-nowrap">{{ $item->created_at }}</td>

                                    <td class="p-2">
                                        <a href="{{ route('employee-notifications.details', $item->id) }}"
                                            class="text-indigo-700">
                                            {{ $item->participant_count }} users
                                        </a>
                                    </td>

                                    <td class="p-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('employee-notifications.edit', $item->id) }}"
                                                class="whitespace-nowrap rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                                                Edit &amp; Resend
                                            </a>

                                            <form method="POST"
                                                action="{{ route('employee-notifications.destroy', $item->id) }}"
                                                onsubmit="return confirm('Delete notification #{{ (int) $item->id }} from history and employee inboxes?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="history_page" value="{{ $history->currentPage() }}">

                                                <button type="submit"
                                                    class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-3 text-slate-500">
                                        No notifications created yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($history->hasPages())
                    <div class="border-t border-slate-100 pt-4">
                        {{ $history->fragment('notification-history')->onEachSide(1)->links() }}
                    </div>
                @endif
            </section>
        @endif
    </div>

    <script>
        (() => {
            const picker = document.getElementById('employee-picker');
            const form = picker.closest('form');
            const menu = picker.querySelector('[data-recipient-menu]');
            const summary = menu.querySelector('summary');
            const search = picker.querySelector('[data-recipient-search]');
            const chips = picker.querySelector('[data-recipient-chips]');
            const clear = picker.querySelector('[data-recipient-clear]');
            const count = picker.querySelector('[data-recipient-count]');
            const noResults = picker.querySelector('[data-recipient-no-results]');
            const submit = form.querySelector('[data-notification-submit]');
            const options = Array.from(picker.querySelectorAll('[data-recipient-option]'), row => ({
                row,
                checkbox: row.querySelector('input'),
                name: row.dataset.employeeName,
                searchText: row.textContent.replace(/\s+/g, ' ').trim().toLowerCase(),
            }));

            function renderSelection() {
                const selected = options.filter(option => option.checkbox.checked);
                const sendsToAll = selected.length === 0;

                clear.hidden = sendsToAll;
                count.hidden = sendsToAll;
                count.textContent = `${selected.length} selected`;
                submit.textContent = sendsToAll ? submit.dataset.allLabel : submit.dataset.selectedLabel;
                chips.replaceChildren();

                if (sendsToAll) {
                    const label = document.createElement('span');
                    label.className = 'rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700';
                    label.textContent = 'All Employees';
                    chips.append(label);
                    return;
                }

                for (const { checkbox, name } of selected) {
                    const chip = document.createElement('button');
                    chip.type = 'button';
                    chip.className = 'max-w-full break-words rounded-full bg-indigo-50 px-3 py-1 text-left text-xs font-semibold text-indigo-700 hover:bg-indigo-100 focus-visible:ring-2 focus-visible:ring-indigo-400';
                    chip.textContent = `${name} ×`;
                    chip.setAttribute('aria-label', `Remove ${name}`);

                    chip.addEventListener('click', () => {
                        checkbox.checked = false;
                        renderSelection();
                        summary.focus();
                    });

                    chips.append(chip);
                }
            }

            menu.addEventListener('change', event => {
            if (event.target.matches('input[type="checkbox"]')) renderSelection();
        });
            summary.addEventListener('click', () => {
                if (!menu.open) requestAnimationFrame(() => { if (menu.open) search.focus(); });
            });

            clear.addEventListener('click', () => {
                options.forEach(({ checkbox }) => { checkbox.checked = false; });
                renderSelection();
                summary.focus();
            });

            search.addEventListener('input', () => {
                const term = search.value.trim().toLowerCase();
                let found = false;

                for (const option of options) {
                    const matches = option.searchText.includes(term);
                    option.row.hidden = !matches;
                    found ||= matches;
                }

                noResults.hidden = options.length === 0 || found;
            });

            search.addEventListener('keydown', event => {
                if (event.key === 'Enter') event.preventDefault();
            });

            picker.addEventListener('keydown', event => {
                if (event.key === 'Escape' && menu.open) {
                    event.preventDefault();
                    menu.open = false;
                    summary.focus();
                }
            });

            document.addEventListener('click', event => {
                if (menu.open && !picker.contains(event.target)) menu.open = false;
            });

            window.addEventListener('pageshow', renderSelection);
            renderSelection();
        })();
    </script>
@endsection