<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'TeleCRM')</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="bg-slate-100 text-slate-800">

<div class="min-h-screen lg:flex">

    {{-- ========================================================= --}}
    {{-- SIDEBAR --}}
    {{-- ========================================================= --}}

    <aside class="w-full bg-slate-950 text-white lg:fixed lg:inset-y-0 lg:left-0 lg:w-72 lg:overflow-y-auto">

        {{-- Logo / Title --}}
        <div class="border-b border-slate-800 px-6 py-5">

            <div class="text-xl font-bold">
                Telecalling Sales CRM
            </div>

            <div class="mt-1 text-xs text-slate-400">
                Lead • Call • Follow-up • Conversion
            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- MENU CONFIGURATION --}}
        {{-- ===================================================== --}}

        @php

            $user = auth()->user();

            $isSuperAdmin = $user->hasRole('super_admin');


            /*
            |--------------------------------------------------------------------------
            | Sidebar Sections
            |--------------------------------------------------------------------------
            */

            $sections = [];


            /*
            |--------------------------------------------------------------------------
            | PLATFORM
            | Sirf Super Admin ko dikhega
            |--------------------------------------------------------------------------
            */

            if ($isSuperAdmin) {

                $sections['Platform'] = [

                    [
                        'Companies',
                        'companies.index',
                        'companies.*'
                    ],

                ];
            }


            /*
            |--------------------------------------------------------------------------
            | WORKSPACE
            |--------------------------------------------------------------------------
            */

            $sections['Workspace'] = [

                [
                    'Dashboard',
                    'dashboard',
                    'dashboard'
                ],

                [
                    'Leads',
                    'leads.index',
                    'leads.*'
                ],

                [
                    'Pipeline',
                    'pipeline.index',
                    'pipeline.*'
                ],

                [
                    'Follow-ups',
                    'followups.index',
                    'followups.*'
                ],

                [
                    'Call Logs',
                    'calls.index',
                    'calls.*'
                ],

            ];


            /*
            |--------------------------------------------------------------------------
            | ORGANIZATION
            |--------------------------------------------------------------------------
            */

            $sections['Organization'] = [

                [
                    'Employees',
                    'employees.index',
                    'employees.*'
                ],

                [
                    'Branches',
                    'branches.index',
                    'branches.*'
                ],

                [
                    'Teams',
                    'teams.index',
                    'teams.*'
                ],

            ];


            /*
            |--------------------------------------------------------------------------
            | SALES
            |--------------------------------------------------------------------------
            */

            $sections['Sales'] = [

                [
                    'Campaigns',
                    'campaigns.index',
                    'campaigns.*'
                ],

                [
                    'Products',
                    'products.index',
                    'products.*'
                ],

                [
                    'Customers',
                    'customers.index',
                    'customers.*'
                ],

                [
                    'Tasks',
                    'tasks.index',
                    'tasks.*'
                ],

                [
                    'Orders',
                    'orders.index',
                    'orders.*'
                ],

                [
                    'Payments',
                    'payments.index',
                    'payments.*'
                ],

                [
                    'Reports',
                    'reports.index',
                    'reports.*'
                ],

            ];


            /*
            |--------------------------------------------------------------------------
            | CRM SETTINGS
            |--------------------------------------------------------------------------
            */

            $sections['CRM Settings'] = [

                [
                    'Lead Sources',
                    'crm-settings.lead-sources.index',
                    'crm-settings.lead-sources.*'
                ],

                [
                    'Lead Statuses',
                    'crm-settings.lead-statuses.index',
                    'crm-settings.lead-statuses.*'
                ],

                [
                    'Call Dispositions',
                    'crm-settings.call-dispositions.index',
                    'crm-settings.call-dispositions.*'
                ],

            ];

        @endphp


        {{-- ===================================================== --}}
        {{-- SIDEBAR NAVIGATION --}}
        {{-- ===================================================== --}}

        <nav class="space-y-5 p-4">

            @if (session()->has('impersonator_id'))

                <div class="sticky top-0 z-[9999] border-b border-amber-300 bg-amber-50">
                    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3">

                        <div class="text-sm text-amber-900">
                            You are viewing
                            <strong>{{ auth()->user()->name }}</strong>
                            dashboard.
                        </div>

                        <form
                            method="POST"
                            action="{{ route('employees.stop-impersonating') }}"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                            >
                                ← Back to My Account
                            </button>
                        </form>

                    </div>
                </div>

            @endif

            @foreach($sections as $label => $links)

                <div>

                    {{-- Section Heading --}}
                    <div class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-widest text-slate-500">
                        {{ $label }}
                    </div>


                    <div class="space-y-1">

                        @foreach($links as [$text, $route, $pattern])

                            <a
                                href="{{ route($route) }}"
                                class="
                                    block
                                    rounded-lg
                                    px-4
                                    py-2.5
                                    text-sm
                                    transition

                                    {{
                                        request()->routeIs($pattern)
                                            ? 'bg-indigo-600 text-white'
                                            : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                                    }}
                                "
                            >
                                {{ $text }}
                            </a>

                        @endforeach

                    </div>

                </div>

            @endforeach

        </nav>

    </aside>


    {{-- ========================================================= --}}
    {{-- MAIN CONTENT --}}
    {{-- ========================================================= --}}

    <main class="min-w-0 flex-1 lg:ml-72">


        {{-- ===================================================== --}}
        {{-- HEADER --}}
        {{-- ===================================================== --}}

        <header class="sticky top-0 z-20 flex items-center justify-between border-b bg-white/95 px-5 py-4 backdrop-blur lg:px-8">

            {{-- Company / Branch --}}
            <div>

                <div class="font-semibold text-slate-900">

                    @if($isSuperAdmin)

                        {{
                            auth()->user()->company?->name
                            ?? 'TeleCRM Platform'
                        }}

                    @else

                        {{
                            auth()->user()->company?->name
                            ?? 'TeleCRM Workspace'
                        }}

                    @endif

                </div>


                <div class="text-xs text-slate-500">

                    @if($isSuperAdmin)

                        Super Admin

                    @else

                        {{
                            auth()->user()->branch?->name
                            ?? 'All Branches'
                        }}

                    @endif

                </div>

            </div>


            {{-- User --}}
            <div class="flex items-center gap-4">

                <div class="text-right">

                    <div class="text-sm font-medium">
                        {{ auth()->user()->name }}
                    </div>

                    <div class="text-xs text-slate-500">
                        {{
                            auth()
                                ->user()
                                ->roles
                                ->pluck('name')
                                ->join(', ')
                        }}
                    </div>

                </div>


                {{-- Logout --}}
                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >

                    @csrf

                    <button
                        type="submit"
                        class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50"
                    >
                        Logout
                    </button>

                </form>

            </div>

        </header>


        {{-- ===================================================== --}}
        {{-- PAGE CONTENT --}}
        {{-- ===================================================== --}}

        <section class="p-5 lg:p-8">

            {{-- Success Message --}}
            @if(session('success'))

                <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif


            {{-- Error Message --}}
            @if(session('error'))

                <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-700">
                    {{ session('error') }}
                </div>

            @endif


            {{-- Validation Errors --}}
            @if($errors->any())

                <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-700">

                    <div class="font-semibold">
                        Please fix the following errors:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">

                        @foreach($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            @yield('content')

        </section>

    </main>

</div>

</body>
</html>