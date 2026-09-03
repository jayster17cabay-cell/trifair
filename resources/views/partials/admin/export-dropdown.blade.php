{{--}}
    Reusable export dropdown panel.
    Optional params:
        $exportRoute     string          The export form action URL
        $exportLabel     string          Label shown on the button + panel header (e.g. "Ratings", "Complaints")
        $exportSub       ?string         Short helper text under the header
        $exportIcon      ?string         Bootstrap icon class (e.g. "bi-star")
        $activeOperators ?Collection     Adds the operator filter select
        $preservedParams ?array          Hidden inputs carried forward (key => value)
--}}

@php
    $dropdownId = 'export_' . md5($exportRoute);
    $preservedParams = $preservedParams ?? [];
    $exportLabel = $exportLabel ?? 'Data';
    $exportIcon = $exportIcon ?? 'bi-download';
    $exportSub = $exportSub ?? 'Choose a format below to save a copy.';
@endphp

<div class="relative inline-block text-left">
    <button type="button" data-tw-dropdown="{{ $dropdownId }}" class="tw-btn tw-btn-sm tw-btn-gold">
        <i class="bi {{ $exportIcon }}"></i>Download {{ $exportLabel }}
        <i class="bi bi-chevron-down ml-0.5 text-[10px]"></i>
    </button>

    <div id="{{ $dropdownId }}" data-tw-dropdown-menu class="tw-dropdown right-0 top-full z-50 mt-2 w-[22rem] p-0">
        <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/70 px-4 py-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gold/15 text-gold-700">
                <i class="bi {{ $exportIcon }}"></i>
            </div>
            <div class="min-w-0">
                <h4 class="text-sm font-bold leading-tight text-slate-900">Export {{ $exportLabel }}</h4>
                <p class="truncate text-xs text-slate-500">{{ $exportSub }}</p>
            </div>
        </div>

        <form method="GET" action="{{ $exportRoute }}">
            <div class="px-4 py-3">
                @if ($activeOperators ?? null)
                    <div class="mb-3">
                        <label for="{{ $dropdownId }}_op" class="mb-1 block text-xs font-semibold text-slate-600">Operator</label>
                        <select id="{{ $dropdownId }}_op" name="operator_id" class="tw-select py-2 text-xs">
                            <option value="">All Operators</option>
                            @foreach ($activeOperators as $op)
                                <option value="{{ $op->id }}">{{ $op->user->name ?? 'Unknown' }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <p class="mb-1.5 text-xs font-semibold text-slate-600">Format</p>
                <div class="flex gap-1.5">
                    @foreach (['csv' => ['CSV', 'bi-filetype-csv'], 'word' => ['Word', 'bi-file-earmark-word'], 'pdf' => ['PDF', 'bi-filetype-pdf']] as $val => [$lbl, $icon])
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="format" value="{{ $val }}" {{ $val === 'csv' ? 'checked' : '' }} class="peer sr-only">
                            <span class="flex flex-col items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs font-semibold text-slate-500 transition hover:border-slate-300 peer-checked:border-gold/60 peer-checked:bg-gold-50 peer-checked:text-gold-800">
                                <i class="bi {{ $icon }} text-sm"></i>
                                {{ $lbl }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            @foreach ($preservedParams as $key => $value)
                @if ($value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="border-t border-slate-100 px-4 py-3">
                <button type="submit" class="tw-btn tw-btn-sm tw-btn-gold w-full justify-center">
                    <i class="bi bi-download"></i>Download {{ $exportLabel }}
                </button>
            </div>
        </form>
    </div>
</div>
