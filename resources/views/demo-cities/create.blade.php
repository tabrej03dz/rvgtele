@extends('layouts.crm', ['title' => 'Add Demo City'])

@section('content')

@include('demo-cities._styles')

<div class="demo-board space-y-4">

    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">
                Add City Demo
            </h1>

            <p class="mt-1 text-xs text-slate-500">
                Create city and upload all demo images/videos together
            </p>
        </div>

        <a href="{{ route('demo-cities.index') }}" class="demo-btn">
            <i data-lucide="arrow-left"></i>
            Back
        </a>
    </div>

    <form
        method="POST"
        action="{{ route('demo-cities.store') }}"
        enctype="multipart/form-data"
    >
        @csrf

        @include('demo-cities._form')
    </form>

</div>

@endsection