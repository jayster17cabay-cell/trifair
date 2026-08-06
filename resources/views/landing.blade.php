<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TriFair - Tricycle Operator Rating System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/tailwind.css') . '?v=' . filemtime(public_path('css/tailwind.css')) }}">
</head>
<body class="overflow-x-hidden bg-white font-sans text-slate-800 antialiased">

<!-- NAVBAR -->
<nav id="nav" class="lp-nav">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-8">
        <a href="/" class="flex items-center gap-1.5 text-[1.35rem] font-black tracking-tight text-white">
            <i class="bi bi-bicycle"></i> Tri<span class="text-gold">Fair</span>
        </a>
        <div class="flex items-center gap-1">
            <a href="#features" class="hidden rounded-lg px-4 py-2 text-sm font-medium text-white/75 transition hover:bg-white/10 hover:text-white md:block">Features</a>
            <a href="#how" class="hidden rounded-lg px-4 py-2 text-sm font-medium text-white/75 transition hover:bg-white/10 hover:text-white md:block">How It Works</a>
            <a href="{{ route('login') }}" class="ml-2 rounded-lg bg-gold px-5 py-2 text-sm font-semibold text-slate-900 shadow-goldlift transition hover:bg-gold-dark">Log In</a>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="relative flex min-h-screen items-center overflow-hidden bg-[linear-gradient(160deg,#060e1a_0%,#0d2137_25%,#0f2b4a_50%,#1e3a5f_80%,#2a4a7a_100%)] px-8 pb-14 pt-24 md:px-8 md:pb-12 md:pt-20">
    <div class="pointer-events-none absolute -right-10 -top-[25%] h-[600px] w-[600px] rounded-full bg-[radial-gradient(circle,rgba(245,166,35,0.07)_0%,transparent_70%)]"></div>
    <div class="pointer-events-none absolute -left-[8%] bottom-[10%] h-[400px] w-[400px] rounded-full bg-[radial-gradient(circle,rgba(245,166,35,0.04)_0%,transparent_65%)]"></div>

    <div class="relative z-10 mx-auto grid w-full max-w-6xl grid-cols-1 items-center gap-10 md:grid-cols-2">
        <div class="text-center md:text-left">
            <div class="mb-5 inline-flex items-center gap-1.5 rounded-full border border-gold/20 bg-gold/10 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-gold">
                <i class="bi bi-shield-check"></i> Bayan ng Solano
            </div>
            <h1 class="mb-5 text-4xl font-black leading-[1.08] text-white md:text-[3.4rem]">
                Fair Rides.<br><em class="text-gold not-italic">Real Feedback.</em>
            </h1>
            <p class="mb-9 max-w-[460px] text-[1.05rem] leading-relaxed text-white/60 md:mx-0 md:mb-9">
                TriFair connects passengers, tricycle operators, and TODA officers through a transparent rating system that builds trust and improves public transportation.
            </p>
            <div class="flex flex-wrap justify-center gap-3 md:justify-start">
                <a href="{{ route('login') }}" class="tw-btn tw-btn-gold tw-btn-lg"><i class="bi bi-box-arrow-in-right"></i> Log In</a>
                <a href="#how" class="tw-btn tw-btn-lg border-[1.5px] border-white/20 bg-white/10 text-white backdrop-blur hover:border-white/30 hover:bg-white/15"><i class="bi bi-play-circle"></i> How It Works</a>
            </div>
        </div>

        <div class="order-first flex justify-center md:order-none">
            <div class="w-[230px] overflow-hidden rounded-3xl bg-white shadow-[0_32px_64px_rgba(0,0,0,0.28),0_0_0_1px_rgba(255,255,255,0.05)] md:w-[270px]">
                <div class="relative bg-gradient-to-br from-navy-600 to-navy-500 px-6 pb-5 pt-8 text-center">
                    <div class="mb-3.5 inline-flex items-center gap-1 rounded-full bg-white/15 px-3 py-1 text-[0.68rem] font-semibold text-white/85">
                        <i class="bi bi-geo-alt-fill"></i> Solano, Nueva Vizcaya
                    </div>
                    <div class="absolute inset-x-0 bottom-0 h-4 rounded-t-2xl bg-white"></div>
                </div>
                <div class="px-6 pb-6 pt-1">
                    <div class="mb-4 rounded-xl bg-slate-50 p-5 text-center">
                        <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg" class="mx-auto block">
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
                        <div class="mt-3 text-xs font-bold uppercase tracking-widest text-slate-400">Scan to Rate</div>
                    </div>
                    <div class="flex items-center justify-center gap-1.5 text-[0.78rem] font-medium text-slate-500">
                        <i class="bi bi-camera text-gold"></i> Point camera at QR code
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="absolute inset-x-0 bottom-0 h-[3px] bg-gradient-to-r from-transparent via-gold to-transparent opacity-60"></div>
</section>

<!-- MAP -->
<section id="map-section">
    <div id="solanoMap" class="h-[300px] w-full md:h-[420px]"></div>
</section>

<!-- STATS -->
<div class="border-b border-slate-100 bg-white px-8 py-6">
    <div class="mx-auto grid max-w-[960px] grid-cols-2 gap-8 text-center md:grid-cols-4">
        <div class="anim"><div class="text-2xl font-black text-navy-600">3</div><div class="mt-0.5 text-xs font-semibold uppercase tracking-widest text-slate-400">User Roles</div></div>
        <div class="anim anim-d1"><div class="text-2xl font-black text-navy-600">QR</div><div class="mt-0.5 text-xs font-semibold uppercase tracking-widest text-slate-400">Scan to Rate</div></div>
        <div class="anim anim-d2"><div class="text-2xl font-black text-navy-600">24/7</div><div class="mt-0.5 text-xs font-semibold uppercase tracking-widest text-slate-400">Always Open</div></div>
        <div class="anim anim-d3"><div class="text-2xl font-black text-navy-600">Free</div><div class="mt-0.5 text-xs font-semibold uppercase tracking-widest text-slate-400">For Everyone</div></div>
    </div>
</div>

<!-- FEATURES -->
<section id="features" class="bg-slate-50 px-8 py-12">
    <div class="mx-auto max-w-[1080px]">
        <div class="mb-6 text-center">
            <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3.5 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark"><i class="bi bi-stars"></i> Features</div>
            <h2 class="mb-2.5 text-3xl font-extrabold text-slate-900">Built for Community Trust</h2>
            <p class="mx-auto max-w-[520px] text-[0.95rem] leading-relaxed text-slate-500">Everything you need to ensure fair, transparent, and accountable tricycle rides.</p>
        </div>
        <div class="mx-auto grid max-w-[380px] grid-cols-1 gap-5 md:max-w-none md:grid-cols-3">
            <div class="anim rounded-2xl border border-slate-100 bg-white p-7 transition-all hover:-translate-y-1 hover:border-gold/20 hover:shadow-soft">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-navy-600/10 text-lg text-navy-600"><i class="bi bi-qr-code-scan"></i></div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">QR Code Ratings</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">Passengers scan a QR code to instantly rate their trip. No app download required.</p>
            </div>
            <div class="anim anim-d1 rounded-2xl border border-slate-100 bg-white p-7 transition-all hover:-translate-y-1 hover:border-gold/20 hover:shadow-soft">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-gold/10 text-lg text-gold-dark"><i class="bi bi-star-half"></i></div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">Star Ratings + Comments</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">Rate operators 1 to 5 stars and leave feedback. Low ratings can include photo/video proof.</p>
            </div>
            <div class="anim anim-d2 rounded-2xl border border-slate-100 bg-white p-7 transition-all hover:-translate-y-1 hover:border-gold/20 hover:shadow-soft">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-600/10 text-lg text-emerald-600"><i class="bi bi-diagram-3"></i></div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">TODA Management</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">Organize operators by tricycle operators' associations. Track per-TODA performance.</p>
            </div>
            <div class="anim rounded-2xl border border-slate-100 bg-white p-7 transition-all hover:-translate-y-1 hover:border-gold/20 hover:shadow-soft">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-red-600/10 text-lg text-red-600"><i class="bi bi-flag"></i></div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">Complaint System</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">Passengers can file complaints with evidence. Admins review and act on flagged rides.</p>
            </div>
            <div class="anim anim-d1 rounded-2xl border border-slate-100 bg-white p-7 transition-all hover:-translate-y-1 hover:border-gold/20 hover:shadow-soft">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-sky-600/10 text-lg text-sky-600"><i class="bi bi-shield-check"></i></div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">Role-Based Access</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">Admins manage on desktop. Operators access from phones. Secure and purpose-built.</p>
            </div>
            <div class="anim anim-d2 rounded-2xl border border-slate-100 bg-white p-7 transition-all hover:-translate-y-1 hover:border-gold/20 hover:shadow-soft">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-navy-600/10 text-lg text-navy-600"><i class="bi bi-bar-chart-line"></i></div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">Reports & Logs</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">Operator performance reports, activity logs, and rating trends at your fingertips.</p>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section id="how" class="bg-white px-8 py-12">
    <div class="mx-auto max-w-[1080px]">
        <div class="mb-6 text-center">
            <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3.5 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark"><i class="bi bi-lightning"></i> Simple Process</div>
            <h2 class="mb-2.5 text-3xl font-extrabold text-slate-900">How It Works</h2>
            <p class="mx-auto max-w-[520px] text-[0.95rem] leading-relaxed text-slate-500">Three easy steps for passengers to rate their ride.</p>
        </div>
        <div class="relative grid max-w-[380px] grid-cols-1 gap-8 md:mx-auto md:max-w-none md:grid-cols-3">
            <div class="absolute left-[16%] right-[16%] top-9 hidden h-0.5 bg-gradient-to-r from-slate-200 via-gold to-slate-200 md:block"></div>
            <div class="anim relative z-10 text-center">
                <div class="mx-auto mb-4.5 flex h-[72px] w-[72px] items-center justify-center rounded-[18px] bg-gradient-to-br from-navy-600 to-navy-500 text-2xl font-black text-white shadow-lift">1</div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">Scan the QR Code</h4>
                <p class="mx-auto max-w-[260px] text-[0.84rem] leading-relaxed text-slate-500">After your ride, scan the operator's QR code displayed inside the tricycle.</p>
            </div>
            <div class="anim anim-d1 relative z-10 text-center">
                <div class="mx-auto mb-4.5 flex h-[72px] w-[72px] items-center justify-center rounded-[18px] bg-gradient-to-br from-navy-600 to-navy-500 text-2xl font-black text-white shadow-lift">2</div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">Rate Your Trip</h4>
                <p class="mx-auto max-w-[260px] text-[0.84rem] leading-relaxed text-slate-500">Select your route, give 1-5 stars, and optionally leave a comment.</p>
            </div>
            <div class="anim anim-d2 relative z-10 text-center">
                <div class="mx-auto mb-4.5 flex h-[72px] w-[72px] items-center justify-center rounded-[18px] bg-gradient-to-br from-navy-600 to-navy-500 text-2xl font-black text-white shadow-lift">3</div>
                <h4 class="mb-1.5 text-[0.95rem] font-bold text-slate-900">Submit & Done</h4>
                <p class="mx-auto max-w-[260px] text-[0.84rem] leading-relaxed text-slate-500">Your feedback is instantly recorded. Help build a better service for your community.</p>
            </div>
        </div>
    </div>
</section>

<!-- ROLES -->
<section class="bg-slate-50 px-8 py-12">
    <div class="mx-auto max-w-[1080px]">
        <div class="mb-6 text-center">
            <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3.5 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark"><i class="bi bi-people"></i> For Everyone</div>
            <h2 class="mb-2.5 text-3xl font-extrabold text-slate-900">Who Uses TriFair?</h2>
            <p class="mx-auto max-w-[520px] text-[0.95rem] leading-relaxed text-slate-500">Designed for every member of the tricycle community.</p>
        </div>
        <div class="mx-auto grid max-w-[380px] grid-cols-1 gap-5 md:max-w-none md:grid-cols-3">
            <div class="anim rounded-2xl border-2 border-slate-100 bg-white p-8 text-center transition-all hover:-translate-y-1 hover:border-gold hover:shadow-soft">
                <div class="mx-auto mb-3.5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gold/10 text-xl text-gold-dark"><i class="bi bi-person-walking"></i></div>
                <h4 class="mb-1 text-[0.95rem] font-bold text-slate-900">Passengers</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">Scan QR, rate your ride, and help improve tricycle services. No account needed.</p>
            </div>
            <div class="anim anim-d1 rounded-2xl border-2 border-slate-100 bg-white p-8 text-center transition-all hover:-translate-y-1 hover:border-gold hover:shadow-soft">
                <div class="mx-auto mb-3.5 flex h-14 w-14 items-center justify-center rounded-2xl bg-navy-600/10 text-xl text-navy-600"><i class="bi bi-bicycle"></i></div>
                <h4 class="mb-1 text-[0.95rem] font-bold text-slate-900">Operators</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">View your ratings, track your performance, and build a strong reputation.</p>
            </div>
            <div class="anim anim-d2 rounded-2xl border-2 border-slate-100 bg-white p-8 text-center transition-all hover:-translate-y-1 hover:border-gold hover:shadow-soft">
                <div class="mx-auto mb-3.5 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-600/10 text-xl text-emerald-600"><i class="bi bi-shield-lock"></i></div>
                <h4 class="mb-1 text-[0.95rem] font-bold text-slate-900">TFRB Officers & Superadmins</h4>
                <p class="text-[0.84rem] leading-relaxed text-slate-500">Manage operators, TODAs, review complaints, and generate reports.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="relative overflow-hidden bg-[linear-gradient(155deg,#060e1a_0%,#0f2b4a_40%,#1e3a5f_80%,#2a4a7a_100%)] px-8 py-12 text-center">
    <div class="pointer-events-none absolute -right-10 -top-[35%] h-[450px] w-[450px] rounded-full bg-[radial-gradient(circle,rgba(245,166,35,0.08)_0%,transparent_65%)]"></div>
    <div class="relative z-10 mx-auto max-w-[560px]">
        <h2 class="mb-2.5 text-3xl font-extrabold text-white">Ready to Get Started?</h2>
        <p class="mb-8 text-[0.95rem] leading-relaxed text-white/60">Log in to access your dashboard and start managing or rating tricycle rides today.</p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ route('login') }}" class="tw-btn tw-btn-gold tw-btn-lg"><i class="bi bi-box-arrow-in-right"></i> Log In Now</a>
            <a href="#features" class="tw-btn tw-btn-lg border-[1.5px] border-white/20 bg-white/10 text-white backdrop-blur hover:border-white/30 hover:bg-white/15"><i class="bi bi-arrow-up-circle"></i> Learn More</a>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="bg-slate-900 px-8 py-7 text-center text-[0.8rem] text-white/40">
    &copy; {{ date('Y') }} <a href="/" class="font-semibold text-gold hover:underline">TriFair</a> &mdash; Tricycle Operator Rating System. Built for Filipino commuters.
</footer>

<script>
    window.addEventListener('scroll', function() {
        document.getElementById('nav').classList.toggle('scrolled', window.scrollY > 40);
    });

    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) { if (e.isIntersecting) { e.target.classList.add('visible'); observer.unobserve(e.target); } });
    }, { threshold: 0.15 });
    document.querySelectorAll('.anim').forEach(function(el) { observer.observe(el); });

    var mhallCoords = [16.5152, 121.1823];
    var lmap = L.map('solanoMap', {
        center: mhallCoords,
        zoom: 15,
        zoomControl: false,
        attributionControl: false,
        dragging: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        touchZoom: false,
        keyboard: false
    });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18
    }).addTo(lmap);

    setTimeout(function() { lmap.invalidateSize(); }, 500);
</script>
</body>
</html>
