@extends('layouts.crm')

@section('title', 'APK Details')

@section('content')

<div class="mx-auto max-w-6xl space-y-5">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <h1 class="text-2xl font-bold text-slate-900">
                APK Details
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                View APK information and application screenshots.
            </p>

        </div>

        <a
            href="{{ route('mobile-apks.index') }}"
            class="inline-flex items-center gap-2 rounded-lg
                   border border-slate-300 bg-white
                   px-4 py-2.5 text-sm font-semibold
                   text-slate-700 hover:bg-slate-50"
        >
            Back
        </a>

    </div>


    <section class="overflow-hidden rounded-xl
                    border border-slate-200 bg-white shadow-sm">


        {{-- Header --}}
        <div class="border-b border-slate-200
                    bg-gradient-to-r from-blue-50 via-white to-white
                    p-6 sm:p-8">

            <div class="flex flex-col gap-5 sm:flex-row
                        sm:items-center sm:justify-between">

                <div class="flex items-center gap-4">

                    <div class="flex h-16 w-16 items-center justify-center
                                rounded-2xl bg-blue-600 text-white">

                        <svg class="h-8 w-8" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="7" y="2" width="10" height="20" rx="2"/>
                            <path d="M11 18h2"/>
                        </svg>

                    </div>

                    <div>

                        <h2 class="text-xl font-bold text-slate-900">
                            {{ $mobileApk->name }}
                        </h2>

                        <div class="mt-2 flex flex-wrap gap-2">

                            <span class="rounded-lg bg-indigo-100
                                         px-2.5 py-1 text-xs font-bold
                                         text-indigo-700">
                                Version {{ $mobileApk->version }}
                            </span>


                            @if($mobileApk->is_active)

                                <span class="rounded-full bg-emerald-100
                                             px-2.5 py-1 text-xs font-semibold
                                             text-emerald-700">
                                    Active
                                </span>

                            @else

                                <span class="rounded-full bg-slate-100
                                             px-2.5 py-1 text-xs font-semibold
                                             text-slate-600">
                                    Inactive
                                </span>

                            @endif

                        </div>

                    </div>

                </div>


                @if($mobileApk->is_active)

                    <a
                        href="{{ route('mobile-apks.download', $mobileApk) }}"
                        class="inline-flex items-center justify-center gap-2
                               rounded-xl bg-emerald-600 px-5 py-3
                               text-sm font-semibold text-white
                               hover:bg-emerald-700"
                    >
                        Download APK
                    </a>

                @endif

            </div>

        </div>


        {{-- Info --}}
        <div class="grid md:grid-cols-2">

            <div class="border-b border-r-0 border-slate-100 p-5 md:border-r">
                <div class="text-xs font-semibold uppercase text-slate-400">
                    Application
                </div>

                <div class="mt-1 font-semibold text-slate-900">
                    {{ $mobileApk->name }}
                </div>
            </div>


            <div class="border-b border-slate-100 p-5">
                <div class="text-xs font-semibold uppercase text-slate-400">
                    Version
                </div>

                <div class="mt-1 font-semibold text-slate-900">
                    v{{ $mobileApk->version }}
                </div>
            </div>


            <div class="border-b border-r-0 border-slate-100 p-5 md:border-r">
                <div class="text-xs font-semibold uppercase text-slate-400">
                    Status
                </div>

                <div class="mt-1 font-semibold text-slate-900">
                    {{ $mobileApk->is_active ? 'Active' : 'Inactive' }}
                </div>
            </div>


            <div class="border-b border-slate-100 p-5">
                <div class="text-xs font-semibold uppercase text-slate-400">
                    Uploaded
                </div>

                <div class="mt-1 font-semibold text-slate-900">
                    {{ $mobileApk->created_at?->format('d M Y, h:i A') }}
                </div>
            </div>

        </div>


        {{-- Screenshots --}}
        <div class="border-t border-slate-200 p-5 sm:p-6">

            <div class="mb-4">

                <h3 class="font-bold text-slate-900">
                    App Screenshots
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Screenshots uploaded with this APK version.
                </p>

            </div>


            @if(!empty($mobileApk->images) && count($mobileApk->images))

                <div class="grid grid-cols-2 gap-4
                            sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">

                    @foreach($mobileApk->images as $image)

                        <a
                            href="{{ Storage::url($image) }}"
                            target="_blank"
                            class="group overflow-hidden rounded-xl
                                   border border-slate-200 bg-white
                                   shadow-sm transition hover:shadow-md"
                        >

                            <img
                                src="{{ Storage::url($image) }}"
                                alt="App Screenshot"
                                class="aspect-[9/16] w-full object-cover
                                       transition duration-300
                                       group-hover:scale-[1.02]"
                            >

                        </a>

                    @endforeach

                </div>

            @else

                <div class="rounded-xl border border-dashed
                            border-slate-300 bg-slate-50
                            px-4 py-10 text-center">

                    <div class="text-sm font-semibold text-slate-600">
                        No screenshots available
                    </div>

                </div>

            @endif

        </div>


        <div class="flex flex-col gap-3 border-t
                    border-slate-200 bg-slate-50
                    px-5 py-4 sm:flex-row sm:justify-end">

            <a
                href="{{ route('mobile-apks.edit', $mobileApk) }}"
                class="inline-flex items-center justify-center
                       rounded-lg border border-slate-300
                       bg-white px-4 py-2 text-sm
                       font-semibold text-slate-700 hover:bg-slate-50"
            >
                Edit
            </a>

            <form
                method="POST"
                action="{{ route('mobile-apks.toggle-status', $mobileApk) }}"
            >

                @csrf
                @method('PATCH')

                <button
                    type="submit"
                    class="w-full rounded-lg border border-amber-200
                           bg-amber-50 px-4 py-2 text-sm font-semibold
                           text-amber-700 hover:bg-amber-100"
                >
                    {{ $mobileApk->is_active ? 'Disable APK' : 'Enable APK' }}
                </button>

            </form>

        </div>

    </section>

</div>

@endsection