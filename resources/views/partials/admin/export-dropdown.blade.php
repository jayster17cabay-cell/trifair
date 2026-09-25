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
    $exportSub = $exportSub ?? 'Save a copy of this data.';
    $hasScope = collect($preservedParams)->filter()->isNotEmpty();
    // If the page already scopes to one operator, carry it silently instead of
    // showing a second (conflicting) operator selector inside this form.
    $scopedOperatorId = $preservedParams['operator_id'] ?? null;
@endphp

<div class="relative inline-block text-left">
    <button type="button" data-tw-dropdown="{{ $dropdownId }}"
            aria-haspopup="true" aria-expanded="false" aria-controls="{{ $dropdownId }}"
            class="tw-btn tw-btn-sm tw-btn-gold">
        <i class="bi {{ $exportIcon }}"></i>Export {{ $exportLabel }}
        <i class="bi bi-chevron-down ml-0.5 text-[10px]"></i>
    </button>

    <div id="{{ $dropdownId }}" data-tw-dropdown-menu
         class="tw-dropdown right-0 top-full z-50 mt-2 w-[21rem] max-w-[calc(100vw_-_2rem)] p-0">
        <div class="flex items-start gap-3 border-b border-slate-100 bg-slate-50/70 px-4 py-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gold/15 text-gold-700">
                <i class="bi {{ $exportIcon }}"></i>
            </div>
            <div class="min-w-0">
                <h4 class="text-sm font-bold leading-tight text-slate-900">Export {{ $exportLabel }}</h4>
                <p class="text-xs leading-snug text-slate-500">{{ $exportSub }}</p>
            </div>
        </div>

        <form method="GET" action="{{ $exportRoute }}" data-export-form>
            <div class="px-4 py-3">
                @if ($hasScope)
                    <div class="mb-3 flex items-start gap-2 rounded-lg bg-blue-50 px-3 py-2 text-[11px] font-medium leading-snug text-blue-700">
                        <i class="bi bi-funnel-fill mt-0.5"></i>
                        <span>Your current filters are applied, so the download matches what you see on screen.</span>
                    </div>
                @endif

                @if (($activeOperators ?? null) && ! $scopedOperatorId)
                    <div class="mb-3">
                        <label for="{{ $dropdownId }}_op" class="mb-1 block text-xs font-semibold text-slate-600">
                            Limit to operator <span class="font-normal text-slate-400">(optional)</span>
                        </label>
                        <select id="{{ $dropdownId }}_op" name="operator_id" class="tw-select py-2 text-xs">
                            <option value="">All operators</option>
                            @foreach ($activeOperators as $op)
                                <option value="{{ $op->id }}">{{ $op->user->name ?? 'Unknown' }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <p class="mb-1.5 text-xs font-semibold text-slate-600">Choose a format</p>
                <div class="grid gap-1.5">
                    @foreach ([
                        'csv' => ['CSV', 'bi-filetype-csv', 'Spreadsheet (Excel / Google Sheets)'],
                        'word' => ['Word', 'bi-file-earmark-word', 'Editable .doc document'],
                        'pdf' => ['PDF', 'bi-filetype-pdf', 'Print-ready, opens in a new tab'],
                    ] as $val => [$lbl, $icon, $desc])
                        <label class="block cursor-pointer">
                            <input type="radio" name="format" value="{{ $val }}" {{ $val === 'csv' ? 'checked' : '' }} class="peer sr-only">
                            <span class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 transition hover:border-slate-300 peer-checked:border-gold peer-checked:bg-gold-50 peer-focus-visible:ring-2 peer-focus-visible:ring-gold/40">
                                <i class="bi {{ $icon }} text-lg text-slate-400 peer-checked:text-gold-700"></i>
                                <span class="min-w-0">
                                    <span class="block text-xs font-bold text-slate-700 peer-checked:text-gold-800">{{ $lbl }}</span>
                                    <span class="block text-[11px] leading-tight text-slate-400">{{ $desc }}</span>
                                </span>
                                <i class="bi bi-check-circle-fill ml-auto text-base text-gold-700 opacity-0 transition peer-checked:opacity-100"></i>
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
                <button type="submit" data-export-submit class="tw-btn tw-btn-sm tw-btn-gold w-full justify-center">
                    <i class="bi bi-download" data-export-icon></i><span data-export-text>Download {{ $exportLabel }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
