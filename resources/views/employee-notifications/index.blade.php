@extends('layouts.crm')

@section('title', 'Employee Notifications')

@section('content')
    @php
        $activeIds = collect((array) old('recipient_ids', $selectedIds))
            ->filter(fn ($id) => is_scalar($id))
            ->map(fn ($id) => (string) $id)
            ->all();
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

            <div>
                <label for="audience" class="block text-sm font-semibold">Send to</label>

                <select id="audience" name="audience"
                    class="mt-1 w-full rounded-lg border p-2">
                    <option value="selected" @selected(old('audience', 'selected') === 'selected')>
                        Selected Employees
                    </option>
                    <option value="all" @selected(old('audience') === 'all')>
                        All Employees
                    </option>
                </select>
            </div>

            <details class="rounded-lg border p-3"
                @if ($errors->has('recipient_ids') || $editing) open @endif>
                <summary class="cursor-pointer text-sm font-semibold">
                    Select employees
                </summary>

                <div class="mt-2 max-h-56 space-y-1 overflow-y-auto">
                    @forelse ($employees as $employee)
                        <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1 hover:bg-slate-50">
                            <input type="checkbox" name="recipient_ids[]"
                                value="{{ $employee->id }}"
                                @checked(in_array((string) $employee->id, $activeIds, true))>

                            <span class="text-sm">
                                {{ $employee->name }}
                                · {{ $employee->employee_code ?: '#' . $employee->id }}
                            </span>
                        </label>
                    @empty
                        <p class="text-sm text-slate-500">No eligible employees found.</p>
                    @endforelse
                </div>

                <p class="mt-2 text-xs text-slate-500">
                    These selections apply when Send to is set to Selected Employees.
                </p>
            </details>

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

            <button type="submit"
                class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white">
                {{ $editing ? 'Save & Resend' : 'Send Notification' }}
            </button>
        </form>

        @if ($history !== null)
            <section class="space-y-4 rounded-xl border bg-white p-5">
                <h2 class="text-lg font-bold text-slate-900">Notification History</h2>

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

                                    <td class="p-2 whitespace-nowrap">
                                        <a href="{{ route('employee-notifications.edit', $item->id) }}"
                                            class="font-semibold text-indigo-700">
                                            Edit &amp; Resend
                                        </a>
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

                {{ $history->links() }}
            </section>
        @endif
    </div>
@endsection