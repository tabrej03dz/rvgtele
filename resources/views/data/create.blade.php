@extends('layouts.crm', ['title' => 'Add Data'])

@section('content')

@include('backend.data.partials.styles')

<div class="software-ui mx-auto w-full max-w-6xl space-y-3">

    <section class="software-toolbar">

        <div class="flex items-center justify-between gap-4 px-4 py-4">

            <div class="flex items-center gap-3">

                <span class="data-toolbar-icon">
                    <i data-lucide="database-zap"></i>
                </span>

                <div>

                    <h1 class="text-lg font-bold text-slate-900">
                        Add New Data
                    </h1>

                    <p class="mt-1 text-[11px] text-slate-500">
                        Raw customer information add karein.
                    </p>

                </div>

            </div>


            <a
                href="{{ route('data.index') }}"
                class="software-btn"
            >
                <i data-lucide="arrow-left"></i>
                BACK
            </a>

        </div>

    </section>


    <form
        method="POST"
        action="{{ route('data.store') }}"
        class="space-y-3"
    >

        @csrf

        @include('backend.data.partials.form', [
            'data' => null
        ])


        <section class="software-panel">

            <div
                class="
                    flex
                    justify-end
                    gap-2
                    p-4
                "
            >

                <a
                    href="{{ route('data.index') }}"
                    class="software-btn"
                >
                    CANCEL
                </a>

                <button
                    type="submit"
                    class="software-btn software-btn-primary"
                >
                    <i data-lucide="save"></i>
                    SAVE DATA
                </button>

            </div>

        </section>

    </form>

</div>

@endsection