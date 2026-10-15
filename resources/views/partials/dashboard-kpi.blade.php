{{--
    Compact KPI card. Params: $kpi array:
    - href (optional), icon,
      value (raw HTML ok), label, live (optional data-live key).
    Card tint (border + icon chip) is derived from the label:
    Active -> green, Complaints -> red, everything else -> gold.
--}}
@php
    $kpi = $kpi ?? null;
    if (!$kpi) return;
    $tag = !empty($kpi['href']) ? 'a' : 'div';
    $hrefAttr = !empty($kpi['href']) ? ' href="' . e($kpi['href']) . '"' : '';
    $liveAttr = !empty($kpi['live']) ? ' data-live="' . e($kpi['live']) . '"' : '';

    // Category tone derived from the card label (Nueva Vizcaya palette):
    // Active -> green, Complaints -> red, everything else -> brand gold.
    $labelKey = strtolower($kpi['label'] ?? '');
    $tone = [
        'active'     => ['border-l-nv-green', 'bg-nv-green-light text-nv-green'],
        'inactive'   => ['border-l-slate-300', 'bg-slate-100 text-slate-500'],
        'suspended'  => ['border-l-amber-400', 'bg-amber-100 text-amber-700'],
        'complaints' => ['border-l-nv-red',   'bg-nv-red-light text-nv-red'],
        'default'    => ['border-l-gold',     'bg-gold-50 text-gold-800'],
    ];
    $t = $tone[$labelKey] ?? $tone['default'];
@endphp
<{{ $tag }} {!! $hrefAttr !!} class="flex items-center gap-3 rounded-xl border border-slate-100 border-l-4 bg-white p-3.5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-200 hover:shadow-md {{ $t[0] }}">
    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-base {{ $t[1] }}"><i class="bi {{ $kpi['icon'] }}"></i></div>
    <div class="min-w-0">
        <div class="text-2xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-3xl"{!! $liveAttr !!}>{!! $kpi['value'] !!}</div>
        <div class="mt-0.5 truncate text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $kpi['label'] }}</div>
    </div>
</{{ $tag }}>
