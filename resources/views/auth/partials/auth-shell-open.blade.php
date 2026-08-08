{{--
    Auth page shell opener: full-screen navy gradient background with a
    centered white card. Params:
    - $authWidth (optional)  max-w class for the column, default 'max-w-md'
--}}
@php $authWidth = $authWidth ?? 'max-w-md'; @endphp
<div class="tw-auth-shell">
    <div class="pointer-events-none absolute inset-0 lp-bg-grid"></div>
    <div class="pointer-events-none absolute -right-20 -top-24 h-80 w-80 rounded-full bg-[radial-gradient(circle,rgba(245,184,0,0.12)_0%,transparent_65%)]"></div>
    <div class="pointer-events-none absolute -bottom-24 -left-16 h-72 w-72 rounded-full bg-[radial-gradient(circle,rgba(46,125,209,0.18)_0%,transparent_65%)]"></div>

    <div class="relative z-10 w-full {{ $authWidth }}">
        <div class="mb-6 flex flex-col items-center text-center">
            <a href="/" class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gold text-2xl text-navy-800 shadow-goldlift">
                <i class="bi bi-bicycle"></i>
            </a>
            <span class="text-2xl font-black tracking-tight text-white">Tri<span class="text-gold">Fair</span></span>
            <p class="mt-1 text-xs font-medium uppercase tracking-widest text-white/40">Bayan ng Solano</p>
        </div>

        <div class="rounded-3xl border border-slate-100 bg-white p-7 shadow-[0_24px_60px_rgba(0,0,0,0.35)] sm:p-9">
