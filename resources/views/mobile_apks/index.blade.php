@extends('layouts.crm')

@section('title', 'Mobile APKs')

@section('content')

<div class="mx-auto max-w-[1500px] space-y-5">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <h1 class="text-2xl font-bold text-slate-900">
                Mobile APKs
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Manage mobile application versions and screenshots.
            </p>

        </div>

        <a
            href="{{ route('mobile-apks.create') }}"
            class="inline-flex items-center gap-2 rounded-lg
                   bg-blue-600 px-4 py-2.5
                   text-sm font-semibold text-white
                   hover:bg-blue-700"
        >
            Upload APK
        </a>

    </div>


    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">

        <form
            method="GET"
            action="{{ route('mobile-apks.index') }}"
            class="flex flex-col gap-3 sm:flex-row"
        >

            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search application or version..."
                class="w-full flex-1 rounded-lg border-slate-300
                       px-3 py-2.5 text-sm
                       focus:border-blue-500 focus:ring-blue-500"
            >

            <button
                class="rounded-lg bg-slate-900
                       px-5 py-2.5 text-sm font-semibold
                       text-white hover:bg-slate-800"
            >
                Search
            </button>

            @if(request()->filled('search'))

                <a
                    href="{{ route('mobile-apks.index') }}"
                    class="rounded-lg border border-slate-300
                           px-5 py-2.5 text-center
                           text-sm font-semibold text-slate-700"
                >
                    Reset
                </a>

            @endif

        </form>

    </section>


    <section class="overflow-hidden rounded-xl
                    border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-5 py-4">

            <h2 class="font-bold text-slate-900">
                APK List
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                {{ $apks->total() }} APK versions
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1050px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="border-b border-slate-200
                               text-left text-xs font-semibold
                               uppercase tracking-wide text-slate-500">

                        <th class="px-4 py-3">
                            #
                        </th>

                        <th class="px-4 py-3">
                            Application
                        </th>

                        <th class="px-4 py-3">
                            Screenshots
                        </th>

                        <th class="px-4 py-3">
                            Version
                        </th>

                        <th class="px-4 py-3">
                            Status
                        </th>

                        <th class="px-4 py-3">
                            Uploaded
                        </th>

                        <th class="px-4 py-3 text-right">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($apks as $apk)

                        <tr class="hover:bg-slate-50">

                            <td class="px-4 py-4 text-slate-500">
                                {{ $apks->firstItem() + $loop->index }}
                            </td>


                            <td class="px-4 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="flex h-11 w-11 items-center
                                                justify-center rounded-xl
                                                bg-blue-50 text-blue-600">

                                        <svg class="h-6 w-6"
                                             viewBox="0 0 24 24"
                                             fill="none"
                                             stroke="currentColor"
                                             stroke-width="1.8">
                                            <rect x="7" y="2"
                                                  width="10" height="20" rx="2"/>
                                        </svg>

                                    </div>

                                    <div>

                                        <div class="font-semibold text-slate-900">
                                            {{ $apk->name }}
                                        </div>

                                        <div class="text-xs text-slate-400">
                                            ID: {{ $apk->id }}
                                        </div>

                                    </div>

                                </div>

                            </td>


                            {{-- Screenshots --}}
                            <td class="px-4 py-4">

                                @if(!empty($apk->images) && count($apk->images))

                                    <div class="flex items-center gap-3">

                                        <img
                                            src="{{ Storage::url($apk->images[0]) }}"
                                            class="h-14 w-9 rounded-md
                                                   border border-slate-200
                                                   object-cover"
                                        >

                                        <div>

                                            <div class="font-semibold text-slate-700">
                                                {{ count($apk->images) }}
                                            </div>

                                            <div class="text-xs text-slate-400">
                                                Images
                                            </div>

                                        </div>

                                    </div>

                                @else

                                    <span class="text-slate-400">
                                        —
                                    </span>

                                @endif

                            </td>


                            <td class="px-4 py-4">

                                <span class="rounded-lg bg-indigo-50
                                             px-3 py-1 text-xs font-bold
                                             text-indigo-700">
                                    v{{ $apk->version }}
                                </span>

                            </td>


                            <td class="px-4 py-4">

                                @if($apk->is_active)

                                    <span class="rounded-full bg-emerald-50
                                                 px-2.5 py-1 text-xs
                                                 font-semibold text-emerald-700">
                                        Active
                                    </span>

                                @else

                                    <span class="rounded-full bg-slate-100
                                                 px-2.5 py-1 text-xs
                                                 font-semibold text-slate-600">
                                        Inactive
                                    </span>

                                @endif

                            </td>


                            <td class="px-4 py-4 text-slate-600">

                                <div>
                                    {{ $apk->created_at?->format('d M Y') }}
                                </div>

                                <div class="text-xs text-slate-400">
                                    {{ $apk->created_at?->format('h:i A') }}
                                </div>

                            </td>


                            <td class="px-4 py-4">

                                <div class="flex flex-wrap justify-end gap-2">

                                    <a
                                        href="{{ route('mobile-apks.show', $apk) }}"
                                        class="rounded-lg border border-blue-200
                                               bg-blue-50 px-3 py-1.5
                                               text-xs font-semibold text-blue-700"
                                    >
                                        View
                                    </a>

                                    @if($apk->is_active)

                                        <a
                                            href="{{ route('mobile-apks.download', $apk) }}"
                                            class="rounded-lg border border-emerald-200
                                                   bg-emerald-50 px-3 py-1.5
                                                   text-xs font-semibold
                                                   text-emerald-700"
                                        >
                                            Download
                                        </a>

                                    @endif

                                    <a
                                        href="{{ route('mobile-apks.edit', $apk) }}"
                                        class="rounded-lg border border-slate-300
                                               bg-white px-3 py-1.5
                                               text-xs font-semibold
                                               text-slate-700"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('mobile-apks.toggle-status', $apk) }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            class="rounded-lg border border-amber-200
                                                   bg-amber-50 px-3 py-1.5
                                                   text-xs font-semibold
                                                   text-amber-700"
                                        >
                                            {{ $apk->is_active ? 'Disable' : 'Enable' }}
                                        </button>

                                    </form>

                                    <form
                                        method="POST"
                                        action="{{ route('mobile-apks.destroy', $apk) }}"
                                        onsubmit="return confirm('Delete this APK and all screenshots?')"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            class="rounded-lg border border-rose-200
                                                   bg-rose-50 px-3 py-1.5
                                                   text-xs font-semibold
                                                   text-rose-700"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="7" class="px-5 py-16 text-center">

                                <div class="font-semibold text-slate-700">
                                    No APK uploaded
                                </div>

                                <p class="mt-1 text-sm text-slate-500">
                                    Upload your first mobile application.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($apks->hasPages())

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $apks->links() }}
            </div>

        @endif

    </section>

</div>

@endsection