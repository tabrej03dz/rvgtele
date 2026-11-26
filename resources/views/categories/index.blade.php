@extends('layouts.crm')

@section('title', $title)

@section('content')
<div class="mx-auto max-w-[1500px] space-y-5">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Categories</h1>
            <p class="mt-1 text-sm text-slate-500">
                Manage business categories and view assigned leads.
            </p>
        </div>

        @can('categories.create')
            <a href="{{ route('categories.create') }}"
               class="inline-flex items-center gap-2 self-start rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 sm:self-auto">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Add Category
            </a>
        @endcan
    </div>

    @if(session('success'))
        <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error') || $errors->has('confirm_delete'))
        <div role="alert" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ session('error') ?? $errors->first('confirm_delete') }}
        </div>
    @endif

    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('categories.index') }}"
              class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m20 20-3.5-3.5"/>
                </svg>

                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search category..." aria-label="Search categories"
                       class="w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <button type="submit"
                    class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                Search
            </button>

            @if(request()->filled('search'))
                <a href="{{ route('categories.index') }}"
                   class="rounded-lg border border-slate-300 px-5 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Reset
                </a>
            @endif
        </form>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-bold text-slate-900">Category List</h2>
            <p class="mt-0.5 text-sm text-slate-500">
                {{ $categories->firstItem() ?? 0 }} – {{ $categories->lastItem() ?? 0 }}
                of {{ $categories->total() }}
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-sm">
                <thead class="bg-slate-50">
                    <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="w-16 px-4 py-3">#</th>
                        <th class="px-4 py-3">Category Name</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3 text-center">Total Leads</th>
                        <th class="px-4 py-3 text-center">Total Call Count</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($categories as $category)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-4 py-4 text-slate-500">
                                {{ $categories->firstItem() + $loop->index }}
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 font-bold text-blue-600">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($category->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900">{{ $category->name }}</div>
                                        <div class="text-xs text-slate-400">ID: {{ $category->id }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4 text-slate-600">
                                <div class="max-w-md truncate" title="{{ $category->description }}">
                                    {{ $category->description ?: '—' }}
                                </div>
                            </td>

                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex min-w-[65px] items-center justify-center rounded-lg px-3 py-1.5 text-sm font-bold
                                    {{ $category->leads_count > 0 ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ number_format($category->leads_count) }}
                                </span>
                            </td>

                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex min-w-[65px] items-center justify-center rounded-lg px-3 py-1.5 text-sm font-bold
                                    {{ $category->total_call_count > 0 ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ number_format($category->total_call_count) }}
                                </span>
                            </td>

                            <td class="px-4 py-4 text-slate-600">
                                <div class="whitespace-nowrap">{{ $category->created_at?->format('d M Y') }}</div>
                                <div class="mt-0.5 text-xs text-slate-400">{{ $category->created_at?->format('h:i A') }}</div>
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    @can('categories.view')
                                        <a href="{{ route('categories.show', $category) }}"
                                           class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                            View
                                        </a>
                                    @endcan

                                    @can('categories.update')
                                        <a href="{{ route('categories.edit', $category) }}"
                                           class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            Edit
                                        </a>
                                    @endcan

                                    @can('categories.delete')
                                        <button type="button"
                                                data-category-delete
                                                data-url="{{ route('categories.destroy', $category) }}"
                                                data-name="{{ $category->name }}"
                                                data-count="{{ number_format($category->leads_count) }}"
                                                aria-haspopup="dialog"
                                                aria-controls="category-delete-dialog"
                                                class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                            Delete
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <div class="font-semibold text-slate-700">No categories found</div>
                                @can('categories.create')
                                    <a href="{{ route('categories.create') }}"
                                       class="mt-4 inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                        Add Category
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($categories->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $categories->links() }}
            </div>
        @endif
    </section>
</div>

@can('categories.delete')
    <dialog id="category-delete-dialog"
            aria-labelledby="delete-category-title"
            aria-describedby="delete-category-warning"
            class="fixed inset-0 m-auto w-[calc(100%-2rem)] max-w-md rounded-xl border border-rose-200 bg-white p-0 shadow-xl backdrop:bg-slate-900/60">
        <div class="border-t-4 border-rose-600 p-6">
            <h2 id="delete-category-title" class="text-xl font-bold text-rose-800">
                Delete category and all its leads?
            </h2>

            <div id="delete-category-warning"
                 class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm leading-6 text-rose-950">
                <p>
                    Deleting <strong id="delete-category-name" class="break-words"></strong>
                    will permanently delete every lead assigned to it.
                </p>
                <p class="mt-2">
                    Leads shown in this list: <strong id="delete-category-count"></strong>
                </p>
                <p class="mt-2 font-semibold">This action cannot be undone.</p>
            </div>

            <form id="category-delete-form" method="POST" class="mt-6 flex flex-wrap justify-end gap-2">
                @csrf
                @method('DELETE')

                <button id="cancel-category-delete" type="button" autofocus
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>

                <button type="submit" name="confirm_delete" value="1"
                        class="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-800">
                    Permanently delete
                </button>
            </form>
        </div>
    </dialog>

    <script>
        (() => {
            const dialog = document.getElementById('category-delete-dialog');
            const form = document.getElementById('category-delete-form');
            const name = document.getElementById('delete-category-name');
            const count = document.getElementById('delete-category-count');
            const cancel = document.getElementById('cancel-category-delete');

            document.querySelectorAll('[data-category-delete]').forEach((button) => {
                button.addEventListener('click', () => {
                    form.action = button.dataset.url;
                    name.textContent = button.dataset.name;
                    count.textContent = button.dataset.count;
                    dialog.showModal();
                    cancel.focus();
                });
            });

            cancel.addEventListener('click', () => dialog.close());
        })();
    </script>
@endcan
@endsection