{{--}}
    Reusable export dropdown panel.
    Requires:
        $exportRoute     string         The export form action URL
        $activeOperators ?Collection    Optional – adds operator filter select
        $preservedParams ?array         Optional – hidden inputs to carry forward (key => value)
--}}

@php
    $dropdownId = 'export_' . md5($exportRoute);
    $preservedParams = $preservedParams ?? [];
@endphp

<div class="relative inline-block text-left">
    <button type="button" data-tw-dropdown="{{ $dropdownId }}" class="tw-btn tw-btn-sm tw-btn-outline-navy">
        <i class="bi bi-download"></i>Export
    </button>

    <div id="{{ $dropdownId }}" data-tw-dropdown-menu class="tw-dropdown right-0 top-full z-50 mt-2 w-80 p-0">
        <form method="GET" action="{{ $exportRoute }}">
            <div class="px-4 pt-4 pb-3">
                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Export Options</p>

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

                <div>
                    <p class="mb-1.5 text-xs font-semibold text-slate-600">Format</p>
                    <div class="flex gap-1.5">
                        @foreach (['csv' => 'CSV', 'word' => 'Word', 'pdf' => 'PDF'] as $val => $lbl)
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="format" value="{{ $val }}" {{ $val === 'csv' ? 'checked' : '' }} class="peer sr-only">
                                <span class="flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-500 transition hover:border-slate-300 peer-checked:border-navy-600 peer-checked:bg-navy-50 peer-checked:text-navy-700">
                                    @if ($val === 'csv') <i class="bi bi-filetype-csv text-sm"></i>
                                    @elseif ($val === 'word') <i class="bi bi-file-earmark-word text-sm"></i>
                                    @else <i class="bi bi-filetype-pdf text-sm"></i>
                                    @endif
                                    {{ $lbl }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            @foreach ($preservedParams as $key => $value)
                @if ($value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="border-t border-slate-100 px-4 py-3">
                <button type="submit" class="tw-btn tw-btn-sm tw-btn-gold w-full justify-center">
                    <i class="bi bi-download"></i>Download
                </button>
            </div>
        </form>
    </div>
</div>
