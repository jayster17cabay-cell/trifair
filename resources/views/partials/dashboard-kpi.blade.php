{{--
    Compact KPI card. Params: $kpi array:
    - href (optional), icon, iconClass (e.g. 'bg-blue-50 text-blue-600'),
      value (raw HTML ok), label, live (optional data-live key).
--}}
@php
    $kpi = $kpi ?? null;
    if (!$kpi) return;
    $tag = !empty($kpi['href']) ? 'a' : 'div';
    $hrefAttr = !empty($kpi['href']) ? ' href="' . e($kpi['href']) . '"' : '';
    $liveAttr = !empty($kpi['live']) ? ' data-live="' . e($kpi['live']) . '"' : '';
@endphp
<{{ $tag }} {!! $hrefAttr !!} class="flex items-center gap-2.5 rounded-xl border border-slate-100 bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-200 hover:shadow-soft">
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $kpi['iconClass'] }}"><i class="bi {{ $kpi['icon'] }}"></i></div>
    <div class="min-w-0">
        <div class="text-lg font-extrabold leading-tight text-slate-900"{!! $liveAttr !!}>{!! $kpi['value'] !!}</div>
        <div class="truncate text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $kpi['label'] }}</div>
    </div>
</{{ $tag }}>
