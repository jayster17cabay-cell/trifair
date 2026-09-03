{{--
    Auth page shell opener: full-screen navy gradient background with a
    centered white card. Params:
    - $authWidth (optional)  max-w class for the column, default 'max-w-md'
--}}
@php $authWidth = $authWidth ?? 'max-w-md'; @endphp
<div class="tw-auth-shell">
    <div class="pointer-events-none absolute inset-0 lp-bg-grid"></div>
    <div class="pointer-events-none anim-drift absolute -right-24 -top-28 h-[420px] w-[420px] rounded-full bg-[radial-gradient(circle,rgba(245,184,0,0.16)_0%,transparent_65%)]"></div>
    <div class="pointer-events-none anim-drift-slow absolute -bottom-32 -left-20 h-[400px] w-[400px] rounded-full bg-[radial-gradient(circle,rgba(46,125,209,0.24)_0%,transparent_65%)]"></div>
    <div class="pointer-events-none anim-drift absolute -top-10 left-1/4 h-[260px] w-[260px] rounded-full bg-[radial-gradient(circle,rgba(245,184,0,0.10)_0%,transparent_65%)]" style="animation-delay:-8s;"></div>

    <div class="relative z-10 w-full {{ $authWidth }}">
        <div class="overflow-hidden rounded-3xl border border-white/60 bg-white p-7 shadow-[0_30px_70px_rgba(0,0,0,0.45)] sm:p-9">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-navy-700 via-gold to-navy-700"></div>
            <div class="mb-6 mt-1.5 flex flex-col items-center text-center">
                <span class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gold text-2xl text-navy-800 shadow-goldlift">
                    <i class="bi bi-bicycle"></i>
                </span>
                <span class="text-2xl font-black tracking-tight text-navy-700">Tri<span class="text-gold">Fair</span></span>
                <p class="mt-1 text-xs font-medium uppercase tracking-widest text-slate-400">Bayan ng Solano</p>
            </div>
