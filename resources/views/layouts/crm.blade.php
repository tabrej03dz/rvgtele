<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'TeleCRM')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800">
<div class="min-h-screen lg:flex">
    <aside class="w-full bg-slate-950 text-white lg:fixed lg:inset-y-0 lg:left-0 lg:w-72 lg:overflow-y-auto">
        <div class="border-b border-slate-800 px-6 py-5">
            <div class="text-xl font-bold">Telecalling Sales CRM</div>
            <div class="mt-1 text-xs text-slate-400">Lead • Call • Follow-up • Conversion</div>
        </div>
        @php
            $sections = [
                'Workspace' => [
                    ['Dashboard', 'dashboard', 'dashboard'],
                    ['Leads', 'leads.index', 'leads.*'],
                    ['Pipeline', 'pipeline.index', 'pipeline.*'],
                    ['Follow-ups', 'followups.index', 'followups.*'],
                    ['Call Logs', 'calls.index', 'calls.*'],
                ],
                'Organization' => [
                    ['Employees', 'employees.index', 'employees.*'],
                    ['Branches', 'branches.index', 'branches.*'],
                    ['Teams', 'teams.index', 'teams.*'],
                ],
                'Sales' => [
                    ['Campaigns', 'campaigns.index', 'campaigns.*'],
                    ['Products', 'products.index', 'products.*'],
                    ['Customers', 'customers.index', 'customers.*'],
                    ['Tasks', 'tasks.index', 'tasks.*'],
                    ['Orders', 'orders.index', 'orders.*'],
                    ['Payments', 'payments.index', 'payments.*'],
                    ['Reports', 'reports.index', 'reports.*'],
                ],
                'CRM Settings' => [
                    ['Lead Sources', 'crm-settings.lead-sources.index', 'crm-settings.lead-sources.*'],
                    ['Lead Statuses', 'crm-settings.lead-statuses.index', 'crm-settings.lead-statuses.*'],
                    ['Call Dispositions', 'crm-settings.call-dispositions.index', 'crm-settings.call-dispositions.*'],
                ],
            ];
        @endphp
        <nav class="space-y-5 p-4">
            @foreach($sections as $label => $links)
                <div>
                    <div class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-widest text-slate-500">{{ $label }}</div>
                    <div class="space-y-1">
                        @foreach($links as [$text, $route, $pattern])
                            <a href="{{ route($route) }}" class="block rounded-lg px-4 py-2.5 text-sm transition {{ request()->routeIs($pattern) ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">{{ $text }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
    </aside>
    <main class="min-w-0 flex-1 lg:ml-72">
        <header class="sticky top-0 z-20 flex items-center justify-between border-b bg-white/95 px-5 py-4 backdrop-blur lg:px-8">
            <div>
                <div class="font-semibold text-slate-900">{{ auth()->user()->company?->name ?? 'TeleCRM Workspace' }}</div>
                <div class="text-xs text-slate-500">{{ auth()->user()->branch?->name ?? 'All Branches' }}</div>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right"><div class="text-sm font-medium">{{ auth()->user()->name }}</div><div class="text-xs text-slate-500">{{ auth()->user()->roles->pluck('name')->join(', ') }}</div></div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border px-3 py-2 text-sm text-rose-600">Logout</button></form>
            </div>
        </header>
        <section class="p-5 lg:p-8">
            @if(session('success'))<div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-700">{{ session('success') }}</div>@endif
            @yield('content')
        </section>
    </main>
</div>
</body>
</html>