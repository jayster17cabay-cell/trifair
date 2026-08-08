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
        'complaints' => ['border-l-nv-red',   'bg-nv-red-light text-nv-red'],
        'default'    => ['border-l-gold',     'bg-gold-50 text-gold-800'],
    ];
    $t = $tone[$labelKey] ?? $tone['default'];
@endphp
<{{ $tag }} {!! $hrefAttr !!} class="flex items-center gap-2.5 rounded-xl border border-slate-100 border-l-4 bg-white p-3 shadow-sm transition-shadow duration-200 hover:-translate-y-0.5 hover:border-slate-200 hover:shadow-md {{ $t[0] }}">
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $t[1] }}"><i class="bi {{ $kpi['icon'] }}"></i></div>
    <div class="min-w-0">
        <div class="text-3xl font-bold leading-tight text-slate-900"{!! $liveAttr !!}>{!! $kpi['value'] !!}</div>
        <div class="mt-0.5 truncate text-xs font-medium uppercase tracking-wide text-slate-400">{{ $kpi['label'] }}</div>
    </div>
</{{ $tag }}>
