<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TriFair — the tricycle operator rating system for Solano, Nueva Vizcaya. Scan, rate, and help build accountable, community-trusted rides.">
    <title>TriFair — Tricycle Operator Rating System</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/tailwind.css') . '?v=' . filemtime(public_path('css/tailwind.css')) }}">
</head>
<body class="overflow-x-hidden bg-white font-sans text-slate-800 antialiased">

{{-- MOBILE MENU --}}
<div id="mobileMenu" class="lp-mobile-menu">
    <div class="mb-5 flex items-center justify-between">
        <a href="/" class="flex items-center gap-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gold text-lg text-navy-800"><i class="bi bi-bicycle"></i></span>
            <span class="text-xl font-black text-white">Tri<span class="text-gold">Fair</span></span>
        </a>
        <button id="mobileMenuClose" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-white/70 transition hover:bg-white/10 hover:text-white" aria-label="Close menu">
            <i class="bi bi-x-lg text-xl"></i>
        </button>
    </div>
    <nav class="flex flex-col gap-1">
        <a href="#features" class="mobile-link rounded-xl px-4 py-3 text-base font-semibold text-white/80 transition hover:bg-white/10 hover:text-white">Features</a>
        <a href="#how" class="mobile-link rounded-xl px-4 py-3 text-base font-semibold text-white/80 transition hover:bg-white/10 hover:text-white">How It Works</a>
        <a href="#roles" class="mobile-link rounded-xl px-4 py-3 text-base font-semibold text-white/80 transition hover:bg-white/10 hover:text-white">Who It's For</a>
        <a href="#area" class="mobile-link rounded-xl px-4 py-3 text-base font-semibold text-white/80 transition hover:bg-white/10 hover:text-white">Service Area</a>
        <div class="mt-4 grid gap-2.5">
            <a href="{{ route('login') }}" class="tw-btn tw-btn-gold tw-btn-lg w-full"><i class="bi bi-box-arrow-in-right"></i> Log In</a>
            <a href="{{ route('register') }}" class="tw-btn tw-btn-lg w-full border border-white/20 bg-white/10 text-white backdrop-blur hover:border-white/30 hover:bg-white/15"><i class="bi bi-person-plus"></i> Sign Up</a>
        </div>
    </nav>
</div>

{{-- NAVBAR --}}
<nav id="nav" class="lp-nav">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 lg:px-8">
        <a href="/" class="flex items-center gap-2.5 text-white">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gold text-xl text-navy-800 shadow-goldlift"><i class="bi bi-bicycle"></i></span>
            <span class="text-xl font-black tracking-tight">Tri<span class="text-gold">Fair</span></span>
        </a>
        <div class="hidden items-center gap-1 md:flex">
            <a href="#features" class="lp-link">Features</a>
            <a href="#how" class="lp-link">How It Works</a>
            <a href="#roles" class="lp-link">Who It's For</a>
            <a href="#area" class="lp-link">Service Area</a>
        </div>
        <div class="hidden items-center gap-2 md:flex">
            <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 text-sm font-semibold text-white/80 transition hover:text-white">Log In</a>
            <a href="{{ route('register') }}" class="tw-btn tw-btn-gold"><i class="bi bi-person-plus"></i> Sign Up</a>
        </div>
        <button id="mobileMenuOpen" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-white transition hover:bg-white/10 md:hidden" aria-label="Open menu">
            <i class="bi bi-list text-2xl"></i>
        </button>
    </div>
</nav>

{{-- HERO --}}
<section class="relative flex min-h-screen items-center overflow-hidden bg-[linear-gradient(160deg,#060e1a_0%,#0d2137_25%,#0f2b4a_50%,#1e3a5f_80%,#2a4a7a_100%)] px-6 pb-16 pt-28 md:px-8 lg:pt-24">
    <div class="pointer-events-none absolute inset-0 lp-bg-grid"></div>
    <div class="pointer-events-none absolute -right-24 -top-[20%] h-[640px] w-[640px] rounded-full bg-[radial-gradient(circle,rgba(245,166,35,0.10)_0%,transparent_65%)]"></div>
    <div class="pointer-events-none absolute -left-[10%] bottom-[8%] h-[420px] w-[420px] rounded-full bg-[radial-gradient(circle,rgba(46,125,209,0.14)_0%,transparent_65%)]"></div>

    <div class="relative z-10 mx-auto grid w-full max-w-6xl grid-cols-1 items-center gap-14 lg:grid-cols-2">
        {{-- Left --}}
        <div class="text-center lg:text-left">
            <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-gold/25 bg-gold/10 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-gold">
                <i class="bi bi-shield-check"></i> Bayan ng Solano, Nueva Vizcaya
            </div>
            <h1 class="mb-6 text-[2.5rem] font-black leading-[1.08] tracking-tight text-white md:text-6xl">
                Every ride counts.<br>
                <span class="bg-gradient-to-r from-gold via-gold to-gold-light bg-clip-text text-transparent">Every operator accountable.</span>
            </h1>
            <p class="mx-auto mb-9 max-w-[500px] text-lg leading-relaxed text-white/60 lg:mx-0">
                TriFair lets passengers rate their tricycle ride in seconds — turning everyday trips into honest feedback that builds trust and improves public transport.
            </p>
            <div class="flex flex-wrap justify-center gap-3 lg:justify-start">
                <a href="{{ route('login') }}" class="tw-btn tw-btn-gold tw-btn-lg"><i class="bi bi-box-arrow-in-right"></i> Log In</a>
                <a href="#how" class="tw-btn tw-btn-lg border border-white/20 bg-white/10 text-white backdrop-blur transition hover:border-white/30 hover:bg-white/15"><i class="bi bi-play-circle"></i> How It Works</a>
            </div>
            <div class="mt-10 flex flex-wrap items-center justify-center gap-x-7 gap-y-3 text-white/50 lg:justify-start">
                <div class="flex items-center gap-2 text-sm font-medium"><i class="bi bi-qr-code-scan text-gold"></i> No app download needed</div>
                <div class="flex items-center gap-2 text-sm font-medium"><i class="bi bi-shield-check text-gold"></i> Verified by TODA officers</div>
            </div>
        </div>

        {{-- Right: app mockup --}}
        <div class="relative mx-auto w-full max-w-md">
            <div class="lp-hero-ring h-[420px] w-[420px] -top-8 -right-8 hidden lg:block"></div>
            <div class="lp-hero-ring h-[320px] w-[320px] -bottom-10 -left-12 hidden lg:block"></div>

            <div class="relative rounded-3xl bg-white p-5 shadow-[0_40px_80px_rgba(0,0,0,0.35)] sm:p-6">
                <div class="mb-4 flex items-center justify-between rounded-2xl bg-gradient-to-br from-navy-600 to-navy-500 px-5 py-4 text-white">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider text-white/60">Operator</div>
                        <div class="text-base font-extrabold">R. Cruz</div>
                        <div class="text-xs text-white/60">Plate # AB-1234 · TODA Solano East</div>
                    </div>
                    <div class="text-right">
                        <div class="flex items-center gap-0.5 text-gold">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                        </div>
                        <div class="mt-0.5 text-sm font-extrabold">4.8</div>
                        <div class="text-[10px] text-white/60">127 ratings</div>
                    </div>
                </div>

                <div class="mb-4 flex items-center gap-3 rounded-2xl bg-slate-50 p-4">
                    <svg width="84" height="84" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg" class="shrink-0">
                        <rect width="120" height="120" rx="8" fill="white"/>
                        <rect x="16" y="16" width="28" height="28" rx="4" fill="#1e3a5f"/>
                        <rect x="76" y="16" width="28" height="28" rx="4" fill="#1e3a5f"/>
                        <rect x="16" y="76" width="28" height="28" rx="4" fill="#1e3a5f"/>
                        <rect x="22" y="22" width="16" height="16" rx="2" fill="white"/>
                        <rect x="82" y="22" width="16" height="16" rx="2" fill="white"/>
                        <rect x="22" y="82" width="16" height="16" rx="2" fill="white"/>
                        <rect x="26" y="26" width="8" height="8" rx="1" fill="#1e3a5f"/>
                        <rect x="86" y="26" width="8" height="8" rx="1" fill="#1e3a5f"/>
                        <rect x="26" y="86" width="8" height="8" rx="1" fill="#1e3a5f"/>
                        <rect x="50" y="16" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="50" y="28" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="50" y="46" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="16" y="50" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="28" y="50" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="42" y="50" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="56" y="50" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="68" y="50" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="50" y="56" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="56" y="56" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="68" y="56" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="50" y="68" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="62" y="68" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="50" y="82" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="56" y="82" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="68" y="82" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="76" y="50" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="88" y="50" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="76" y="62" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="88" y="62" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="94" y="68" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="76" y="82" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="82" y="88" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="94" y="82" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="100" y="88" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="76" y="94" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="88" y="94" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="100" y="94" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="94" y="100" width="6" height="6" fill="#1e3a5f"/>
                        <rect x="100" y="100" width="6" height="6" fill="#1e3a5f"/>
                    </svg>
                    <div class="min-w-0">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400">How did your ride go?</div>
                        <div class="mt-1 flex items-center gap-1 text-gold">
                            <i class="bi bi-star"></i><i class="bi bi-star"></i><i class="bi bi-star"></i><i class="bi bi-star"></i><i class="bi bi-star"></i>
                        </div>
                        <div class="mt-1 text-[11px] font-medium text-slate-400">Tap a star to rate instantly</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-3.5">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Rating received</div>
                        <div class="mt-1 flex items-center gap-1.5 text-emerald-600">
                            <i class="bi bi-check-circle-fill"></i>
                            <span class="text-sm font-extrabold text-emerald-700">5 stars</span>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-3.5">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Avg rating</div>
                        <div class="mt-1 flex items-center gap-1.5">
                            <i class="bi bi-graph-up-arrow text-navy-600"></i>
                            <span class="text-sm font-extrabold text-navy-600">+0.2 this month</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Floating badge --}}
            <div class="anim anim-d1 absolute -left-5 top-10 hidden rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-white shadow-xl backdrop-blur-md sm:block">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400"><i class="bi bi-patch-check-fill"></i></span>
                    <div>
                        <div class="text-xs font-extrabold">Feedback verified</div>
                        <div class="text-[11px] text-white/60">Proof attached · reviewed</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="absolute inset-x-0 bottom-0 h-[3px] bg-gradient-to-r from-transparent via-gold to-transparent opacity-60"></div>
</section>

{{-- TRUST BAR --}}
<div class="border-b border-slate-100 bg-white px-6 py-8">
    <div class="mx-auto grid max-w-6xl grid-cols-2 gap-8 text-center md:grid-cols-4">
        <div class="anim">
            <div class="text-2xl font-black text-navy-600">3</div>
            <div class="mt-1 text-xs font-semibold uppercase tracking-widest text-slate-400">User Roles</div>
        </div>
        <div class="anim anim-d1">
            <div class="text-2xl font-black text-navy-600">QR</div>
            <div class="mt-1 text-xs font-semibold uppercase tracking-widest text-slate-400">Scan to Rate</div>
        </div>
        <div class="anim anim-d2">
            <div class="text-2xl font-black text-navy-600">24/7</div>
            <div class="mt-1 text-xs font-semibold uppercase tracking-widest text-slate-400">Always Open</div>
        </div>
        <div class="anim anim-d3">
            <div class="text-2xl font-black text-navy-600">Free</div>
            <div class="mt-1 text-xs font-semibold uppercase tracking-widest text-slate-400">For Everyone</div>
        </div>
    </div>
</div>

{{-- SERVICE AREA / MAP --}}
<section id="area" class="bg-white px-6 py-16 md:px-8 md:py-20">
    <div class="mx-auto max-w-6xl">
        <div class="mb-8 text-center">
            <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3.5 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark"><i class="bi bi-geo-alt"></i> Service Area</div>
            <h2 class="mb-2.5 text-3xl font-extrabold tracking-tight text-slate-900 md:text-4xl">Serving Solano, Nueva Vizcaya</h2>
            <p class="mx-auto max-w-[540px] text-[0.95rem] leading-relaxed text-slate-500">TriFair is run in partnership with the local TODA associations and the Municipal Transport Regulation Board.</p>
        </div>
        <div class="anim relative overflow-hidden rounded-3xl border border-slate-100 shadow-soft">
            <img src="{{ asset('images/solano-municipal-hall.jpg') }}" alt="Solano Municipal Hall, Nueva Vizcaya" class="h-[320px] w-full object-cover md:h-[420px]" loading="lazy">
            <div class="pointer-events-none absolute left-4 top-4 rounded-2xl border border-white/60 bg-white/90 px-4 py-3 shadow-lg backdrop-blur">
                <div class="flex items-center gap-2 text-sm font-bold text-navy-700"><i class="bi bi-pin-map-fill text-gold"></i> Municipal Hall, Solano</div>
                <div class="mt-0.5 text-xs text-slate-500">16.5152° N, 121.1823° E</div>
            </div>
            <div class="absolute bottom-3 right-4 rounded-full bg-navy-900/55 px-3 py-1 text-[0.65rem] font-medium text-white/90 backdrop-blur">Photo: Elmer B. Domingo — CC BY-SA 4.0</div>
        </div>
    </div>
</section>

{{-- FEATURES --}}
<section id="features" class="bg-slate-50 px-6 py-16 md:px-8 md:py-20">
    <div class="mx-auto max-w-6xl">
        <div class="mb-10 text-center">
            <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3.5 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark"><i class="bi bi-stars"></i> Features</div>
            <h2 class="mb-2.5 text-3xl font-extrabold tracking-tight text-slate-900 md:text-4xl">Built for Community Trust</h2>
            <p class="mx-auto max-w-[540px] text-[0.95rem] leading-relaxed text-slate-500">Everything you need to ensure fair, transparent, and accountable tricycle rides.</p>
        </div>
        <div class="grid gap-6 md:grid-cols-3">
            <div class="anim rounded-2xl border border-slate-100 bg-white p-7 shadow-sm transition-all hover:-translate-y-1 hover:border-gold/20 hover:shadow-soft">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-navy-600/10 text-lg text-navy-600"><i class="bi bi-qr-code-scan"></i></div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">QR Code Ratings</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">Passengers scan a QR code to instantly rate their trip. No app download required.</p>
            </div>
            <div class="anim anim-d1 rounded-2xl border border-slate-100 bg-white p-7 shadow-sm transition-all hover:-translate-y-1 hover:border-gold/20 hover:shadow-soft">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-gold/10 text-lg text-gold-dark"><i class="bi bi-star-half"></i></div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">Star Ratings + Comments</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">Rate operators 1 to 5 stars and leave feedback. Low ratings can include photo/video proof.</p>
            </div>
            <div class="anim anim-d2 rounded-2xl border border-slate-100 bg-white p-7 shadow-sm transition-all hover:-translate-y-1 hover:border-gold/20 hover:shadow-soft">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-600/10 text-lg text-emerald-600"><i class="bi bi-diagram-3"></i></div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">TODA Management</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">Organize operators by tricycle operators' associations. Track per-TODA performance.</p>
            </div>
            <div class="anim rounded-2xl border border-slate-100 bg-white p-7 shadow-sm transition-all hover:-translate-y-1 hover:border-gold/20 hover:shadow-soft">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-red-600/10 text-lg text-red-600"><i class="bi bi-flag"></i></div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">Complaint System</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">Passengers can file complaints with evidence. Admins review and act on flagged rides.</p>
            </div>
            <div class="anim anim-d1 rounded-2xl border border-slate-100 bg-white p-7 shadow-sm transition-all hover:-translate-y-1 hover:border-gold/20 hover:shadow-soft">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-sky-600/10 text-lg text-sky-600"><i class="bi bi-shield-check"></i></div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">Role-Based Access</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">Admins manage on desktop, operators access from their phones. Secure and purpose-built.</p>
            </div>
            <div class="anim anim-d2 rounded-2xl border border-slate-100 bg-white p-7 shadow-sm transition-all hover:-translate-y-1 hover:border-gold/20 hover:shadow-soft">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-navy-600/10 text-lg text-navy-600"><i class="bi bi-bar-chart-line"></i></div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">Reports &amp; Logs</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">Operator performance reports, activity logs, and rating trends at your fingertips.</p>
            </div>
        </div>
    </div>
</section>

{{-- HOW IT WORKS --}}
<section id="how" class="bg-white px-6 py-16 md:px-8 md:py-20">
    <div class="mx-auto max-w-6xl">
        <div class="mb-10 text-center">
            <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3.5 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark"><i class="bi bi-lightning"></i> Simple Process</div>
            <h2 class="mb-2.5 text-3xl font-extrabold tracking-tight text-slate-900 md:text-4xl">How It Works</h2>
            <p class="mx-auto max-w-[540px] text-[0.95rem] leading-relaxed text-slate-500">Three easy steps for passengers to rate their ride.</p>
        </div>
        <div class="relative grid gap-8 md:grid-cols-3">
            <div class="absolute left-[16%] right-[16%] top-9 hidden h-0.5 bg-gradient-to-r from-slate-200 via-gold to-slate-200 md:block"></div>
            <div class="anim relative z-10 text-center">
                <div class="mx-auto mb-4 flex h-[72px] w-[72px] items-center justify-center rounded-[18px] bg-gradient-to-br from-navy-600 to-navy-500 text-2xl font-black text-white shadow-lift">1</div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">Scan the QR Code</h4>
                <p class="mx-auto max-w-[280px] text-[0.84rem] leading-relaxed text-slate-500">After your ride, scan the operator's QR code displayed inside the tricycle.</p>
            </div>
            <div class="anim anim-d1 relative z-10 text-center">
                <div class="mx-auto mb-4 flex h-[72px] w-[72px] items-center justify-center rounded-[18px] bg-gradient-to-br from-navy-600 to-navy-500 text-2xl font-black text-white shadow-lift">2</div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">Rate Your Trip</h4>
                <p class="mx-auto max-w-[280px] text-[0.84rem] leading-relaxed text-slate-500">Select your route, give 1–5 stars, and optionally leave a comment or attach proof.</p>
            </div>
            <div class="anim anim-d2 relative z-10 text-center">
                <div class="mx-auto mb-4 flex h-[72px] w-[72px] items-center justify-center rounded-[18px] bg-gradient-to-br from-navy-600 to-navy-500 text-2xl font-black text-white shadow-lift">3</div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">Submit &amp; Done</h4>
                <p class="mx-auto max-w-[280px] text-[0.84rem] leading-relaxed text-slate-500">Your feedback is instantly recorded — and reviewed when needed. Done in seconds.</p>
            </div>
        </div>
    </div>
</section>

{{-- ROLES --}}
<section id="roles" class="bg-slate-50 px-6 py-16 md:px-8 md:py-20">
    <div class="mx-auto max-w-6xl">
        <div class="mb-10 text-center">
            <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3.5 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark"><i class="bi bi-people"></i> For Everyone</div>
            <h2 class="mb-2.5 text-3xl font-extrabold tracking-tight text-slate-900 md:text-4xl">Who Uses TriFair?</h2>
            <p class="mx-auto max-w-[540px] text-[0.95rem] leading-relaxed text-slate-500">Designed for every member of the tricycle community.</p>
        </div>
        <div class="grid gap-6 md:grid-cols-3">
            <div class="anim rounded-2xl border-2 border-slate-100 bg-white p-8 text-center transition-all hover:-translate-y-1 hover:border-gold hover:shadow-soft">
                <div class="mx-auto mb-3.5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gold/10 text-xl text-gold-dark"><i class="bi bi-person-walking"></i></div>
                <h4 class="mb-1 text-[0.95rem] font-bold text-slate-900">Passengers</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">Scan the QR, rate your ride, and help improve tricycle services — no account needed.</p>
            </div>
            <div class="anim anim-d1 rounded-2xl border-2 border-slate-100 bg-white p-8 text-center transition-all hover:-translate-y-1 hover:border-gold hover:shadow-soft">
                <div class="mx-auto mb-3.5 flex h-14 w-14 items-center justify-center rounded-2xl bg-navy-600/10 text-xl text-navy-600"><i class="bi bi-bicycle"></i></div>
                <h4 class="mb-1 text-[0.95rem] font-bold text-slate-900">Operators</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">View your ratings, track your performance, and build a strong reputation over time.</p>
            </div>
            <div class="anim anim-d2 rounded-2xl border-2 border-slate-100 bg-white p-8 text-center transition-all hover:-translate-y-1 hover:border-gold hover:shadow-soft">
                <div class="mx-auto mb-3.5 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-600/10 text-xl text-emerald-600"><i class="bi bi-shield-lock"></i></div>
                <h4 class="mb-1 text-[0.95rem] font-bold text-slate-900">TFRB Officers &amp; Superadmins</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">Manage operators and TODAs, review complaints, and generate reports with ease.</p>
            </div>
        </div>
    </div>
</section>

{{-- TESTIMONIALS --}}
<section class="bg-white px-6 py-16 md:px-8 md:py-20">
    <div class="mx-auto max-w-6xl">
        <div class="mb-10 text-center">
            <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3.5 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark"><i class="bi bi-chat-quote"></i> Voices</div>
            <h2 class="mb-2.5 text-3xl font-extrabold tracking-tight text-slate-900 md:text-4xl">What People Say</h2>
            <p class="mx-auto max-w-[540px] text-[0.95rem] leading-relaxed text-slate-500">From daily commuters to local operators — here's how TriFair makes a difference.</p>
        </div>
        <div class="grid gap-6 md:grid-cols-3">
            <div class="anim flex flex-col rounded-2xl border border-slate-100 bg-slate-50 p-7">
                <div class="mb-4 flex items-center gap-1 text-gold"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
                <p class="mb-5 flex-1 text-sm leading-relaxed text-slate-600">"I rate my ride right after I get off. It takes seconds, and I actually see operators improve — they greet you, and the tricycles feel cleaner."</p>
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-navy-500 to-navy-700 text-sm font-extrabold text-white">MG</span>
                    <div>
                        <div class="text-sm font-bold text-slate-900">Maria G.</div>
                        <div class="text-xs text-slate-400">Daily commuter · Solano</div>
                    </div>
                </div>
            </div>
            <div class="anim anim-d1 flex flex-col rounded-2xl border border-slate-100 bg-slate-50 p-7">
                <div class="mb-4 flex items-center gap-1 text-gold"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
                <p class="mb-5 flex-1 text-sm leading-relaxed text-slate-600">"My average rating went up after I started checking it. TriFair gives us honest feedback we can actually act on — not hearsay."</p>
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-gold to-gold-dark text-sm font-extrabold text-navy-800">RC</span>
                    <div>
                        <div class="text-sm font-bold text-slate-900">Romy C.</div>
                        <div class="text-xs text-slate-400">Tricycle operator · TODA Solano East</div>
                    </div>
                </div>
            </div>
            <div class="anim anim-d2 flex flex-col rounded-2xl border border-slate-100 bg-slate-50 p-7">
                <div class="mb-4 flex items-center gap-1 text-gold"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
                <p class="mb-5 flex-1 text-sm leading-relaxed text-slate-600">"Complaints with proof make our review fair and fast. We now resolve issues based on facts instead of arguments."</p>
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-emerald-700 text-sm font-extrabold text-white">JT</span>
                    <div>
                        <div class="text-sm font-bold text-slate-900">John T.</div>
                        <div class="text-xs text-slate-400">TODA officer · Solano</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="relative overflow-hidden bg-[linear-gradient(155deg,#060e1a_0%,#0f2b4a_40%,#1e3a5f_80%,#2a4a7a_100%)] px-6 py-16 text-center md:px-8 md:py-20">
    <div class="pointer-events-none absolute inset-0 lp-bg-grid"></div>
    <div class="pointer-events-none absolute -right-24 -top-[40%] h-[480px] w-[480px] rounded-full bg-[radial-gradient(circle,rgba(245,166,35,0.10)_0%,transparent_65%)]"></div>
    <div class="relative z-10 mx-auto max-w-2xl">
        <h2 class="mb-3 text-3xl font-extrabold tracking-tight text-white md:text-4xl">Ready to Get Started?</h2>
        <p class="mb-8 text-[0.95rem] leading-relaxed text-white/60">Log in to access your dashboard and start managing or rating tricycle rides today.</p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ route('login') }}" class="tw-btn tw-btn-gold tw-btn-lg"><i class="bi bi-box-arrow-in-right"></i> Log In Now</a>
            <a href="{{ route('register') }}" class="tw-btn tw-btn-lg border border-white/20 bg-white/10 text-white backdrop-blur transition hover:border-white/30 hover:bg-white/15"><i class="bi bi-person-plus"></i> Create Account</a>
        </div>
    </div>
</section>

{{-- FOOTER --}}
<footer class="bg-slate-900 px-6 pb-8 pt-14 md:px-8">
    <div class="mx-auto max-w-6xl">
        <div class="grid gap-10 md:grid-cols-4">
            <div class="md:col-span-1">
                <a href="/" class="mb-4 flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gold text-lg text-navy-800"><i class="bi bi-bicycle"></i></span>
                    <span class="text-xl font-black tracking-tight text-white">Tri<span class="text-gold">Fair</span></span>
                </a>
                <p class="mb-5 max-w-xs text-sm leading-relaxed text-white/40">A transparent rating system for tricycle operators, built for the commuters of Solano, Nueva Vizcaya.</p>
                <div class="flex items-center gap-2">
                    <a href="#" aria-label="Facebook" class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/5 text-white/50 transition hover:bg-gold hover:text-navy-800"><i class="bi bi-facebook"></i></a>
                    <a href="#" aria-label="Twitter" class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/5 text-white/50 transition hover:bg-gold hover:text-navy-800"><i class="bi bi-twitter"></i></a>
                    <a href="#" aria-label="Instagram" class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/5 text-white/50 transition hover:bg-gold hover:text-navy-800"><i class="bi bi-instagram"></i></a>
                </div>
            </div>
            <div>
                <h5 class="mb-4 text-xs font-bold uppercase tracking-widest text-white/70">Explore</h5>
                <ul class="space-y-2.5 text-sm text-white/40">
                    <li><a href="#features" class="transition hover:text-gold">Features</a></li>
                    <li><a href="#how" class="transition hover:text-gold">How It Works</a></li>
                    <li><a href="#roles" class="transition hover:text-gold">Who It's For</a></li>
                    <li><a href="#area" class="transition hover:text-gold">Service Area</a></li>
                </ul>
            </div>
            <div>
                <h5 class="mb-4 text-xs font-bold uppercase tracking-widest text-white/70">Accounts</h5>
                <ul class="space-y-2.5 text-sm text-white/40">
                    <li><a href="{{ route('login') }}" class="transition hover:text-gold">Log In</a></li>
                    <li><a href="{{ route('register') }}" class="transition hover:text-gold">Sign Up</a></li>
                    <li><a href="{{ route('password.request') }}" class="transition hover:text-gold">Forgot Password</a></li>
                </ul>
            </div>
            <div>
                <h5 class="mb-4 text-xs font-bold uppercase tracking-widest text-white/70">Contact</h5>
                <ul class="space-y-2.5 text-sm text-white/40">
                    <li class="flex items-start gap-2.5"><i class="bi bi-geo-alt mt-0.5 text-gold"></i> Municipal Hall, Solano, Nueva Vizcaya</li>
                    <li class="flex items-start gap-2.5"><i class="bi bi-envelope mt-0.5 text-gold"></i> trifair@solano.gov.ph</li>
                    <li class="flex items-start gap-2.5"><i class="bi bi-clock mt-0.5 text-gold"></i> Mon – Fri · 8:00 AM – 5:00 PM</li>
                </ul>
            </div>
        </div>
        <div class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-white/10 pt-6 text-center text-xs text-white/40 md:flex-row">
            <p>&copy; {{ date('Y') }} <a href="/" class="font-semibold text-gold hover:underline">TriFair</a> &mdash; Tricycle Operator Rating System.</p>
            <p>Built for the commuters of <span class="text-white/60">Bayan ng Solano</span>.</p>
        </div>
    </div>
</footer>

<script>
    var nav = document.getElementById('nav');
    var mobileMenu = document.getElementById('mobileMenu');

    function setScrolled() {
        nav.classList.toggle('scrolled', window.scrollY > 40);
    }
    window.addEventListener('scroll', setScrolled);
    setScrolled();

    function openMobileMenu() {
        mobileMenu.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeMobileMenu() {
        mobileMenu.classList.remove('open');
        document.body.style.overflow = '';
    }
    document.getElementById('mobileMenuOpen').addEventListener('click', openMobileMenu);
    document.getElementById('mobileMenuClose').addEventListener('click', closeMobileMenu);
    document.querySelectorAll('.mobile-link').forEach(function (link) {
        link.addEventListener('click', closeMobileMenu);
    });

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.12 });
    document.querySelectorAll('.anim').forEach(function (el) { observer.observe(el); });
</script>
</body>
</html>
