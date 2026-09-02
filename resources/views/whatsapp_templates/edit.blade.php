@extends('layouts.crm', [
    'title' => 'Edit WhatsApp Template',
])

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    <div class="flex items-start justify-between gap-4">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Edit WhatsApp Template
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                {{ $template->name }}
            </p>
        </div>

        <a
            href="{{ route('whatsapp-templates.index') }}"
            class="rounded-xl border border-slate-300 bg-white
                   px-4 py-2 text-sm font-semibold text-slate-700
                   hover:bg-slate-50"
        >
            Back
        </a>

    </div>


    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

        <form
            method="POST"
            action="{{ route('whatsapp-templates.update', $template) }}"
        >
            @csrf
            @method('PUT')

            @include('whatsapp_templates._form', [
                'template' => $template,
            ])

        </form>

    </div>

</div>

@endsection
