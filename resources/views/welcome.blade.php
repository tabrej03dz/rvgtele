<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>RVG Tele CRM - Smart Sales & Lead Management</title>
    <meta name="description" content="RVG Tele CRM helps sales teams manage leads, calls, follow-ups, WhatsApp communication, pipelines and reports from one simple platform.">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }

        .hero-grid {
            background-image:
                linear-gradient(rgba(255,255,255,.055) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.055) 1px, transparent 1px);
            background-size: 34px 34px;
        }

        .soft-grid {
            background-image:
                linear-gradient(rgba(15,23,42,.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15,23,42,.045) 1px, transparent 1px);
            background-size: 30px 30px;
        }

        .crm-shadow {
            box-shadow: 0 30px 80px -30px rgba(15, 23, 42, .42);
        }

        .float-card {
            animation: floatCard 5s ease-in-out infinite;
        }

        .float-card-delay {
            animation: floatCard 5s ease-in-out 1.2s infinite;
        }

        @keyframes floatCard {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-9px); }
        }
    </style>
</head>
<body class="bg-white font-sans text-slate-900 antialiased selection:bg-indigo-200 selection:text-indigo-950">

    {{-- Navigation --}}
    <header id="siteHeader" class="fixed inset-x-0 top-0 z-50 border-b border-transparent transition-all duration-300">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-18 items-center justify-between py-3">
                <a href="#home" class="flex items-center gap-3">
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-lg font-black text-white shadow-lg shadow-indigo-500/25">
                        R
                    </span>
                    <span>
                        <span class="block text-lg font-black leading-none tracking-tight text-white header-brand">RVG TELE CRM</span>
                        <span class="mt-1 block text-[10px] font-bold uppercase tracking-[.22em] text-indigo-200 header-subtitle">Sell smarter. Grow faster.</span>
                    </span>
                </a>

                <nav class="hidden items-center gap-7 lg:flex">
                    <a href="#features" class="nav-link text-sm font-semibold text-slate-200 transition hover:text-white">Features</a>
                    <a href="#workflow" class="nav-link text-sm font-semibold text-slate-200 transition hover:text-white">How it works</a>
                    <a href="#modules" class="nav-link text-sm font-semibold text-slate-200 transition hover:text-white">Modules</a>
                    <a href="#reports" class="nav-link text-sm font-semibold text-slate-200 transition hover:text-white">Reports</a>
                    <a href="#contact" class="nav-link text-sm font-semibold text-slate-200 transition hover:text-white">Contact</a>
                </nav>

                <div class="hidden items-center gap-3 lg:flex">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-indigo-700 shadow-lg shadow-indigo-950/10 transition hover:-translate-y-0.5">
                            Open Dashboard
                        </a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="nav-link rounded-xl px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/10">
                                Login
                            </a>
                        @endif

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-indigo-700 shadow-lg shadow-indigo-950/10 transition hover:-translate-y-0.5">
                                Start Free
                            </a>
                        @endif
                    @endauth
                </div>

                <button id="mobileMenuButton" type="button" class="grid h-11 w-11 place-items-center rounded-xl border border-white/15 bg-white/10 text-white backdrop-blur lg:hidden" aria-label="Open menu">
                    <svg id="menuOpenIcon" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg id="menuCloseIcon" class="hidden h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div id="mobileMenu" class="hidden pb-4 lg:hidden">
                <div class="rounded-2xl border border-white/10 bg-slate-950/95 p-3 shadow-2xl backdrop-blur-xl">
                    <a href="#features" class="mobile-link block rounded-xl px-4 py-3 text-sm font-semibold text-slate-200 hover:bg-white/10">Features</a>
                    <a href="#workflow" class="mobile-link block rounded-xl px-4 py-3 text-sm font-semibold text-slate-200 hover:bg-white/10">How it works</a>
                    <a href="#modules" class="mobile-link block rounded-xl px-4 py-3 text-sm font-semibold text-slate-200 hover:bg-white/10">Modules</a>
                    <a href="#reports" class="mobile-link block rounded-xl px-4 py-3 text-sm font-semibold text-slate-200 hover:bg-white/10">Reports</a>
                    <a href="#contact" class="mobile-link block rounded-xl px-4 py-3 text-sm font-semibold text-slate-200 hover:bg-white/10">Contact</a>

                    <div class="mt-3 grid grid-cols-2 gap-2 border-t border-white/10 pt-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="col-span-2 rounded-xl bg-white px-4 py-3 text-center text-sm font-bold text-indigo-700">Open Dashboard</a>
                        @else
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="rounded-xl border border-white/15 px-4 py-3 text-center text-sm font-bold text-white">Login</a>
                            @endif
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-xl bg-white px-4 py-3 text-center text-sm font-bold text-indigo-700">Start Free</a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        {{-- Hero --}}
        <section id="home" class="hero-grid relative isolate overflow-hidden bg-slate-950 pt-28 sm:pt-32">
            <div class="absolute -left-32 top-28 -z-10 h-96 w-96 rounded-full bg-indigo-600/30 blur-3xl"></div>
            <div class="absolute -right-20 top-10 -z-10 h-[32rem] w-[32rem] rounded-full bg-violet-600/25 blur-3xl"></div>
            <div class="absolute inset-x-0 bottom-0 -z-10 h-40 bg-gradient-to-t from-white to-transparent"></div>

            <div class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8 lg:pb-28">
                <div class="grid items-center gap-14 lg:grid-cols-[.92fr_1.08fr]">
                    <div class="pt-6 text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 rounded-full border border-indigo-300/20 bg-indigo-400/10 px-4 py-2 text-xs font-bold uppercase tracking-[.16em] text-indigo-200 backdrop-blur">
                            <span class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_0_5px_rgba(52,211,153,.12)]"></span>
                            Complete Sales Operating System
                        </div>

                        <h1 class="mt-7 text-4xl font-black leading-[1.08] tracking-tight text-white sm:text-5xl lg:text-6xl">
                            Build a powerful
                            <span class="bg-gradient-to-r from-indigo-300 via-violet-300 to-fuchsia-300 bg-clip-text text-transparent">sales system</span>
                            for your team
                        </h1>

                        <p class="mx-auto mt-6 max-w-2xl text-base leading-8 text-slate-300 sm:text-lg lg:mx-0">
                            Manage leads, calls, follow-ups, WhatsApp conversations, pipelines and team performance from one simple CRM built for fast-moving Indian sales teams.
                        </p>

                        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row lg:justify-start">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-7 py-4 text-sm font-extrabold text-white shadow-xl shadow-indigo-600/30 transition hover:-translate-y-1 hover:bg-indigo-500">
                                    Open CRM Dashboard
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                </a>
                            @else
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-7 py-4 text-sm font-extrabold text-white shadow-xl shadow-indigo-600/30 transition hover:-translate-y-1 hover:bg-indigo-500">
                                        Start Free Trial
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                    </a>
                                @endif
                            @endauth

                            <a href="#demo" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-7 py-4 text-sm font-extrabold text-white backdrop-blur transition hover:bg-white/15">
                                <span class="grid h-7 w-7 place-items-center rounded-full bg-white text-indigo-700">
                                    <svg class="ml-0.5 h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                </span>
                                View Product Tour
                            </a>
                        </div>

                        <div class="mt-9 flex flex-wrap justify-center gap-x-6 gap-y-3 text-xs font-semibold text-slate-300 lg:justify-start">
                            <span class="flex items-center gap-2"><span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-400/15 text-emerald-300">✓</span>No credit card</span>
                            <span class="flex items-center gap-2"><span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-400/15 text-emerald-300">✓</span>Easy team setup</span>
                            <span class="flex items-center gap-2"><span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-400/15 text-emerald-300">✓</span>Mobile friendly</span>
                        </div>
                    </div>

                    {{-- Dashboard mockup --}}
                    <div class="relative mx-auto w-full max-w-3xl lg:mx-0">
                        <div class="crm-shadow relative overflow-hidden rounded-[28px] border border-white/15 bg-white p-2 shadow-2xl">
                            <div class="overflow-hidden rounded-[22px] border border-slate-200 bg-slate-50">
                                <div class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                                        <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                                    </div>
                                    <div class="h-7 w-44 rounded-lg bg-slate-100"></div>
                                    <div class="h-8 w-8 rounded-full bg-indigo-100"></div>
                                </div>

                                <div class="grid min-h-[430px] grid-cols-[72px_1fr] sm:grid-cols-[170px_1fr]">
                                    <aside class="bg-slate-950 p-3 text-white sm:p-4">
                                        <div class="mb-6 flex items-center gap-2">
                                            <span class="grid h-8 w-8 place-items-center rounded-xl bg-indigo-600 text-xs font-black">R</span>
                                            <span class="hidden text-xs font-bold sm:block">RVG CRM</span>
                                        </div>
                                        @foreach (['Dashboard','Leads','Pipeline','Calls','Follow-ups','Reports'] as $index => $item)
                                            <div class="mb-2 flex items-center gap-2 rounded-lg px-2 py-2.5 {{ $index === 0 ? 'bg-indigo-600' : 'text-slate-400' }}">
                                                <span class="h-3.5 w-3.5 rounded {{ $index === 0 ? 'bg-white/80' : 'bg-slate-600' }}"></span>
                                                <span class="hidden text-[10px] font-semibold sm:block">{{ $item }}</span>
                                            </div>
                                        @endforeach
                                    </aside>

                                    <div class="min-w-0 p-3 sm:p-5">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-[10px] font-semibold text-slate-400">Thursday, 30 July</p>
                                                <h3 class="mt-1 text-sm font-black text-slate-900 sm:text-lg">Sales Dashboard</h3>
                                            </div>
                                            <button class="rounded-lg bg-indigo-600 px-3 py-2 text-[9px] font-bold text-white sm:text-[10px]">+ Add Lead</button>
                                        </div>

                                        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-3">
                                            @foreach ([['Total Leads','1,248','+12%'],['Calls Today','186','+8%'],['Follow-ups','42','Due'],['Conversions','28','+18%']] as $card)
                                                <div class="rounded-xl border border-slate-200 bg-white p-3">
                                                    <p class="text-[8px] font-semibold text-slate-400 sm:text-[9px]">{{ $card[0] }}</p>
                                                    <div class="mt-2 flex items-end justify-between gap-1">
                                                        <span class="text-base font-black text-slate-900 sm:text-lg">{{ $card[1] }}</span>
                                                        <span class="text-[7px] font-bold text-emerald-600 sm:text-[8px]">{{ $card[2] }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="mt-3 grid gap-3 sm:grid-cols-[1.15fr_.85fr]">
                                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[9px] font-bold text-slate-700">Lead Performance</span>
                                                    <span class="text-[8px] text-slate-400">Last 7 days</span>
                                                </div>
                                                <div class="mt-5 flex h-28 items-end gap-2">
                                                    @foreach ([42,62,48,78,58,88,72] as $bar)
                                                        <div class="flex flex-1 flex-col justify-end gap-1">
                                                            <div class="rounded-t-md bg-gradient-to-t from-indigo-600 to-violet-400" style="height: {{ $bar }}%"></div>
                                                            <span class="text-center text-[6px] text-slate-400">{{ ['M','T','W','T','F','S','S'][$loop->index] }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                                <span class="text-[9px] font-bold text-slate-700">Pipeline</span>
                                                <div class="mt-4 space-y-3">
                                                    @foreach ([['New Leads',76,'bg-indigo-500'],['Qualified',58,'bg-violet-500'],['Proposal',38,'bg-amber-500'],['Won',22,'bg-emerald-500']] as $stage)
                                                        <div>
                                                            <div class="mb-1 flex justify-between text-[7px] font-semibold text-slate-500"><span>{{ $stage[0] }}</span><span>{{ $stage[1] }}</span></div>
                                                            <div class="h-1.5 rounded-full bg-slate-100"><div class="h-full rounded-full {{ $stage[2] }}" style="width: {{ $stage[1] }}%"></div></div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                                            <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2.5">
                                                <span class="text-[9px] font-bold text-slate-700">Recent Leads</span>
                                                <span class="text-[8px] font-semibold text-indigo-600">View all</span>
                                            </div>
                                            @foreach ([['Amit Sharma','Facebook','Follow-up'],['Neha Verma','Website','Qualified'],['Rahul Singh','WhatsApp','New']] as $lead)
                                                <div class="grid grid-cols-[1fr_.7fr_.7fr] items-center gap-2 border-b border-slate-50 px-3 py-2 text-[7px] last:border-0 sm:text-[8px]">
                                                    <span class="font-semibold text-slate-700">{{ $lead[0] }}</span>
                                                    <span class="text-slate-400">{{ $lead[1] }}</span>
                                                    <span class="rounded-full bg-indigo-50 px-2 py-1 text-center font-semibold text-indigo-600">{{ $lead[2] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="float-card absolute -left-4 top-20 hidden rounded-2xl border border-white/20 bg-white/95 p-4 shadow-xl backdrop-blur sm:block lg:-left-10">
                            <div class="flex items-center gap-3">
                                <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-100 text-emerald-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/></svg>
                                </span>
                                <div><p class="text-[10px] font-semibold text-slate-400">Follow-up reminder</p><p class="mt-1 text-xs font-black text-slate-800">Call Amit at 3:30 PM</p></div>
                            </div>
                        </div>

                        <div class="float-card-delay absolute -bottom-6 right-2 hidden rounded-2xl border border-white/20 bg-white/95 p-4 shadow-xl backdrop-blur sm:block lg:-right-8">
                            <div class="flex items-center gap-3">
                                <span class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-100 text-indigo-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 15l4-4 3 3 5-6"/></svg>
                                </span>
                                <div><p class="text-[10px] font-semibold text-slate-400">Conversion growth</p><p class="mt-1 text-lg font-black text-slate-900">+24.8%</p></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Trust strip --}}
        <section class="relative z-10 -mt-5 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-6xl rounded-3xl border border-slate-200 bg-white px-6 py-6 shadow-xl shadow-slate-200/60">
                <p class="text-center text-xs font-bold uppercase tracking-[.18em] text-slate-400">Built for sales teams across industries</p>
                <div class="mt-5 grid grid-cols-2 gap-4 text-center sm:grid-cols-3 lg:grid-cols-6">
                    @foreach (['Real Estate','Education','Finance','Healthcare','Automobile','Services'] as $industry)
                        <div class="rounded-xl bg-slate-50 px-3 py-3 text-xs font-extrabold text-slate-600">{{ $industry }}</div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Features --}}
        <section id="features" class="soft-grid py-24 sm:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <span class="text-sm font-extrabold uppercase tracking-[.2em] text-indigo-600">Everything in one CRM</span>
                    <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950 sm:text-5xl">Tools that help your sales team close more deals</h2>
                    <p class="mt-5 text-base leading-8 text-slate-600">Remove manual work, improve follow-up discipline and get complete visibility into every lead and employee.</p>
                </div>

                <div class="mt-14 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @php
                        $features = [
                            ['Lead Management','Capture, assign, filter and track every lead from first contact to final conversion.','M4 6h16M4 12h16M4 18h10','from-indigo-500 to-indigo-700'],
                            ['Call Tracking','Log incoming, outgoing and missed calls with notes, outcomes and complete history.','M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106a1.125 1.125 0 0 0-1.173.417l-.97 1.293a1.125 1.125 0 0 1-1.21.38 12.035 12.035 0 0 1-7.143-7.143 1.125 1.125 0 0 1 .38-1.21l1.293-.97c.36-.27.527-.734.417-1.173L6.963 3.102A1.125 1.125 0 0 0 5.872 2.25H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z','from-violet-500 to-purple-700'],
                            ['Smart Follow-ups','Create callbacks and tasks so no opportunity is ever missed or forgotten.','M12 6v6l4 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z','from-amber-500 to-orange-600'],
                            ['Sales Pipeline','Move deals through custom stages and identify bottlenecks in your process.','M4 5h16v4H4V5Zm0 6h10v4H4v-4Zm0 6h7v2H4v-2Z','from-cyan-500 to-blue-700'],
                            ['WhatsApp CRM','Sync conversations, send quick messages and keep communication linked to leads.','M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm3.75 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm3.75 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM21 12c0 4.97-4.03 9-9 9a9.96 9.96 0 0 1-4.21-.93L3 21l.93-4.79A9.96 9.96 0 0 1 3 12a9 9 0 1 1 18 0Z','from-emerald-500 to-teal-700'],
                            ['Live Reports','Measure calls, follow-ups, lead sources, conversions and employee performance.','M3 3v18h18M7 16l4-5 3 3 5-7','from-rose-500 to-pink-700'],
                        ];
                    @endphp

                    @foreach ($features as $feature)
                        <article class="group rounded-3xl border border-slate-200 bg-white p-7 shadow-sm transition duration-300 hover:-translate-y-2 hover:border-indigo-200 hover:shadow-2xl hover:shadow-indigo-100/70">
                            <div class="grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br {{ $feature[3] }} text-white shadow-lg">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature[2] }}"/></svg>
                            </div>
                            <h3 class="mt-6 text-xl font-black text-slate-900">{{ $feature[0] }}</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-600">{{ $feature[1] }}</p>
                            <a href="#demo" class="mt-5 inline-flex items-center gap-2 text-sm font-extrabold text-indigo-600">Explore feature <span class="transition group-hover:translate-x-1">→</span></a>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Workflow --}}
        <section id="workflow" class="overflow-hidden bg-slate-950 py-24 text-white sm:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid items-center gap-14 lg:grid-cols-2">
                    <div>
                        <span class="text-sm font-extrabold uppercase tracking-[.2em] text-indigo-300">Simple sales workflow</span>
                        <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-5xl">From new lead to closed deal—without confusion</h2>
                        <p class="mt-5 max-w-xl text-base leading-8 text-slate-300">RVG Tele CRM gives every employee a clear next action while managers get complete control and visibility.</p>

                        <div class="mt-10 space-y-5">
                            @foreach ([
                                ['01','Capture leads automatically','Bring leads from website, Facebook, WhatsApp, imports and manual entry into one central inbox.'],
                                ['02','Assign and follow up','Distribute leads to team members, schedule callbacks and record every interaction.'],
                                ['03','Track and improve','Monitor pipeline movement, call activity, conversions and team performance in real time.']
                            ] as $step)
                                <div class="flex gap-4 rounded-2xl border border-white/10 bg-white/[.06] p-5 transition hover:bg-white/[.09]">
                                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-indigo-500 text-sm font-black">{{ $step[0] }}</span>
                                    <div><h3 class="font-black text-white">{{ $step[1] }}</h3><p class="mt-2 text-sm leading-6 text-slate-400">{{ $step[2] }}</p></div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="relative">
                        <div class="absolute inset-0 rounded-full bg-indigo-600/20 blur-3xl"></div>
                        <div class="relative rounded-[30px] border border-white/10 bg-white/[.06] p-5 backdrop-blur">
                            <div class="rounded-2xl bg-white p-5 text-slate-900 shadow-2xl">
                                <div class="flex items-center justify-between">
                                    <div><p class="text-xs font-semibold text-slate-400">Pipeline overview</p><h3 class="mt-1 text-lg font-black">July Sales Funnel</h3></div>
                                    <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-600">Live</span>
                                </div>
                                <div class="mt-6 grid grid-cols-4 gap-2">
                                    @foreach ([['New','124','bg-indigo-500'],['Contacted','86','bg-violet-500'],['Proposal','42','bg-amber-500'],['Won','24','bg-emerald-500']] as $column)
                                        <div class="rounded-xl bg-slate-50 p-2.5 text-center">
                                            <div class="mx-auto h-2 w-2 rounded-full {{ $column[2] }}"></div>
                                            <p class="mt-2 text-[9px] font-semibold text-slate-400">{{ $column[0] }}</p>
                                            <p class="mt-1 text-lg font-black">{{ $column[1] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-5 space-y-3">
                                    @foreach ([['Priya Enterprises','₹85,000','Proposal'],['Sharma Motors','₹62,500','Qualified'],['Bright Academy','₹48,000','Follow-up']] as $deal)
                                        <div class="flex items-center justify-between rounded-xl border border-slate-100 p-3">
                                            <div class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-indigo-50 text-xs font-black text-indigo-600">{{ substr($deal[0],0,1) }}</span><div><p class="text-xs font-bold">{{ $deal[0] }}</p><p class="mt-1 text-[9px] text-slate-400">{{ $deal[2] }}</p></div></div>
                                            <span class="text-xs font-black">{{ $deal[1] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Modules --}}
        <section id="modules" class="py-24 sm:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <span class="text-sm font-extrabold uppercase tracking-[.2em] text-indigo-600">Complete CRM suite</span>
                    <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950 sm:text-5xl">One platform for your entire sales operation</h2>
                </div>

                <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach (['Companies & Branches','Teams & Employees','Lead Sources','Lead Statuses','Calls & Dispositions','Tasks & Follow-ups','Notes & Assignments','Products & Campaigns','Custom Pipelines','Customers & Orders','Payments Tracking','Roles & Permissions'] as $module)
                        <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-indigo-200 hover:shadow-lg">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg>
                            </span>
                            <span class="text-sm font-extrabold text-slate-700">{{ $module }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Reports --}}
        <section id="reports" class="bg-indigo-50 py-24 sm:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid items-center gap-14 lg:grid-cols-[1.05fr_.95fr]">
                    <div class="rounded-[30px] border border-indigo-100 bg-white p-5 shadow-2xl shadow-indigo-200/40 sm:p-7">
                        <div class="flex items-center justify-between">
                            <div><p class="text-xs font-semibold text-slate-400">Team performance</p><h3 class="mt-1 text-xl font-black">Monthly Report</h3></div>
                            <select class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600"><option>July 2026</option></select>
                        </div>
                        <div class="mt-6 grid grid-cols-3 gap-3">
                            @foreach ([['Total Calls','4,820'],['Follow-ups','1,945'],['Deals Won','286']] as $metric)
                                <div class="rounded-2xl bg-slate-50 p-4"><p class="text-[10px] font-semibold text-slate-400">{{ $metric[0] }}</p><p class="mt-2 text-xl font-black text-slate-900">{{ $metric[1] }}</p></div>
                            @endforeach
                        </div>
                        <div class="mt-5 rounded-2xl border border-slate-100 p-4">
                            <div class="flex h-52 items-end gap-3">
                                @foreach ([55,72,48,85,64,92,78,88,68,95,74,89] as $bar)
                                    <div class="group relative flex h-full flex-1 items-end">
                                        <div class="w-full rounded-t-lg bg-gradient-to-t from-indigo-600 to-violet-400 transition group-hover:from-indigo-500" style="height: {{ $bar }}%"></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div>
                        <span class="text-sm font-extrabold uppercase tracking-[.2em] text-indigo-600">Actionable analytics</span>
                        <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950 sm:text-5xl">Know what is working and where your team needs help</h2>
                        <p class="mt-5 text-base leading-8 text-slate-600">Use real-time reports instead of assumptions. Compare team members, lead sources, call outcomes, pipeline stages and conversion performance.</p>

                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            @foreach (['Daily call activity','Employee leaderboard','Lead source ROI','Conversion analysis'] as $report)
                                <div class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm"><span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-50 text-emerald-600">✓</span><span class="text-sm font-extrabold text-slate-700">{{ $report }}</span></div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section id="demo" class="px-4 py-24 sm:px-6 sm:py-28 lg:px-8">
            <div class="relative mx-auto max-w-6xl overflow-hidden rounded-[36px] bg-slate-950 px-6 py-14 text-center shadow-2xl sm:px-12 sm:py-20">
                <div class="absolute -left-20 -top-20 h-72 w-72 rounded-full bg-indigo-600/40 blur-3xl"></div>
                <div class="absolute -bottom-24 -right-20 h-72 w-72 rounded-full bg-violet-600/40 blur-3xl"></div>
                <div class="relative">
                    <span class="text-sm font-extrabold uppercase tracking-[.2em] text-indigo-300">Ready to grow?</span>
                    <h2 class="mx-auto mt-4 max-w-3xl text-3xl font-black tracking-tight text-white sm:text-5xl">Give your sales team the CRM they will actually use</h2>
                    <p class="mx-auto mt-5 max-w-2xl text-base leading-8 text-slate-300">Set up your team, import your leads and start managing calls and follow-ups from day one.</p>
                    <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-2xl bg-white px-7 py-4 text-sm font-extrabold text-indigo-700 transition hover:-translate-y-1">Open Dashboard</a>
                        @else
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-2xl bg-white px-7 py-4 text-sm font-extrabold text-indigo-700 transition hover:-translate-y-1">Create Free Account</a>
                            @endif
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="rounded-2xl border border-white/15 bg-white/10 px-7 py-4 text-sm font-extrabold text-white transition hover:bg-white/15">Login to CRM</a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer id="contact" class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-10 md:grid-cols-[1.2fr_.8fr_.8fr]">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-lg font-black text-white">R</span>
                        <div><p class="text-lg font-black">RVG TELE CRM</p><p class="text-[10px] font-bold uppercase tracking-[.2em] text-slate-400">Real Victory Groups</p></div>
                    </div>
                    <p class="mt-5 max-w-md text-sm leading-7 text-slate-600">A simple, powerful and scalable sales CRM for lead management, telecalling, follow-ups and performance tracking.</p>
                </div>

                <div>
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">Product</h3>
                    <div class="mt-4 space-y-3 text-sm font-semibold text-slate-500"><a href="#features" class="block hover:text-indigo-600">Features</a><a href="#workflow" class="block hover:text-indigo-600">Workflow</a><a href="#modules" class="block hover:text-indigo-600">CRM Modules</a><a href="#reports" class="block hover:text-indigo-600">Reports</a></div>
                </div>

                <div>
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">Get Started</h3>
                    <div class="mt-4 space-y-3 text-sm font-semibold text-slate-500">
                        @if (Route::has('login'))<a href="{{ route('login') }}" class="block hover:text-indigo-600">Login</a>@endif
                        @if (Route::has('register'))<a href="{{ route('register') }}" class="block hover:text-indigo-600">Create Account</a>@endif
                        <a href="#demo" class="block hover:text-indigo-600">Request Demo</a>
                    </div>
                </div>
            </div>

            <div class="mt-10 flex flex-col gap-3 border-t border-slate-200 pt-6 text-xs font-semibold text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                <p>© {{ date('Y') }} RVG Tele CRM. All rights reserved.</p>
                <p>Designed for Real Victory Groups</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const header = document.getElementById('siteHeader');
            const mobileMenu = document.getElementById('mobileMenu');
            const mobileButton = document.getElementById('mobileMenuButton');
            const openIcon = document.getElementById('menuOpenIcon');
            const closeIcon = document.getElementById('menuCloseIcon');
            const brand = document.querySelector('.header-brand');
            const subtitle = document.querySelector('.header-subtitle');
            const navLinks = document.querySelectorAll('.nav-link');

            const updateHeader = () => {
                const scrolled = window.scrollY > 20;

                header.classList.toggle('bg-white/95', scrolled);
                header.classList.toggle('backdrop-blur-xl', scrolled);
                header.classList.toggle('border-slate-200', scrolled);
                header.classList.toggle('shadow-sm', scrolled);

                brand?.classList.toggle('text-white', !scrolled);
                brand?.classList.toggle('text-slate-950', scrolled);
                subtitle?.classList.toggle('text-indigo-200', !scrolled);
                subtitle?.classList.toggle('text-indigo-600', scrolled);

                navLinks.forEach(link => {
                    link.classList.toggle('text-slate-200', !scrolled);
                    link.classList.toggle('text-slate-700', scrolled);
                    link.classList.toggle('hover:text-white', !scrolled);
                    link.classList.toggle('hover:text-indigo-600', scrolled);
                });

                mobileButton?.classList.toggle('border-white/15', !scrolled);
                mobileButton?.classList.toggle('bg-white/10', !scrolled);
                mobileButton?.classList.toggle('text-white', !scrolled);
                mobileButton?.classList.toggle('border-slate-200', scrolled);
                mobileButton?.classList.toggle('bg-slate-100', scrolled);
                mobileButton?.classList.toggle('text-slate-900', scrolled);
            };

            updateHeader();
            window.addEventListener('scroll', updateHeader, { passive: true });

            mobileButton?.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
                openIcon.classList.toggle('hidden');
                closeIcon.classList.toggle('hidden');
            });

            document.querySelectorAll('.mobile-link').forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.add('hidden');
                    openIcon.classList.remove('hidden');
                    closeIcon.classList.add('hidden');
                });
            });
        });
    </script>
</body>
</html>