{{--
    Reusable rating card. Requires:
    - $rating         App\Models\Rating  (must be eager-loaded with operator.user, proofs, response)
    - $routePrefix    string             'superadmin' | 'tfrb-officer'
    - $reviewRouteName string            Route name for the single mark-reviewed action
    - $showDelete     bool               Whether to render the delete-complaint action
    Collapsed by default; clicking the header reveals the full detail grid.
--}}
@php
    $operator = $rating->operator;
    $operatorName = $operator->user->name ?? 'Unknown';
    $bodyNumber = $operator ? $operator->body_number : null;
    $stars = (int) $rating->rating;

    $borderClass = \App\Models\Rating::ratingBorderClass($stars);

    $avScoreClass = match (true) {
        $stars <= 2 => 'text-red-600',
        $stars == 3 => 'text-amber-600',
        default => 'text-emerald-600',
    };

    $complaintType = $rating->complaint_type;
    $cSeverity = $complaintType ? \App\Models\Rating::complaintSeverity($complaintType) : null;
    $typeChipClass = [
        'danger' => 'border-red-100 bg-red-50 text-red-600',
        'warning' => 'border-amber-100 bg-amber-50 text-amber-700',
        'neutral' => 'border-slate-200 bg-slate-100 text-slate-600',
    ];
@endphp

<div class="tw-card mb-3 overflow-hidden border-l-4 {{ $borderClass }}" data-rating-card>
    <div class="flex cursor-pointer select-none items-center gap-3 px-4 py-3.5 transition-colors hover:bg-slate-50 sm:px-5" data-rating-toggle role="button" tabindex="0" aria-expanded="false">
        <input type="checkbox" class="tw-check rating-check" value="{{ $rating->id }}" data-rating-check aria-label="Select rating #{{ $rating->id }}">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                <span class="truncate text-sm font-bold text-slate-800">{{ $operatorName }}</span>
                <span class="text-xs font-semibold text-slate-400">#{{ $rating->id }}</span>
                @if ($bodyNumber)
                    <span class="text-xs text-slate-400">B#{{ $bodyNumber }}</span>
                @endif
                <span class="inline-flex items-center gap-1 text-sm font-extrabold {{ $avScoreClass }}">
                    {{ $stars }}.0
                </span>
            </div>
            <div class="mt-0.5 text-xs text-slate-500">{{ $rating->created_at->format('M d, Y \a\t h:i A') }}</div>
        </div>
        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            <div class="hidden gap-0.5 sm:flex">
                @for ($i = 1; $i <= 5; $i++)
                    <i class="bi {{ $i <= $stars ? 'bi-star-fill text-amber-400' : 'bi-star text-slate-200' }}"></i>
                @endfor
            </div>
            <i class="bi bi-chevron-down text-slate-400 transition-transform duration-200" data-rating-chevron></i>
        </div>
    </div>

    <div class="hidden border-t border-slate-100" data-rating-details>
        <div class="grid gap-x-8 gap-y-4 p-4 sm:p-5 md:grid-cols-2">
            <div class="space-y-4">
                <div>
                    <div class="tw-stat-label mb-1"><i class="bi bi-star mr-1 text-amber-500"></i>Rating</div>
                    <div class="flex items-center gap-2">
                        <span class="flex gap-0.5">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bi text-xl {{ $i <= $stars ? 'bi-star-fill text-amber-400' : 'bi-star text-slate-200' }}"></i>
                            @endfor
                        </span>
                        <span class="text-sm font-extrabold text-slate-700">{{ $stars }} / 5</span>
                    </div>
                    @if ($complaintType)
                        <span class="mt-2 inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-[0.7rem] font-semibold {{ $typeChipClass[$cSeverity] }}">
                            <i class="bi bi-exclamation-triangle"></i>{{ $complaintType }}
                        </span>
                    @endif
                </div>

                @if ($rating->reason)
                    <div>
                        <div class="tw-stat-label mb-1"><i class="bi bi-chat-dots mr-1 text-blue-500"></i>Feedback</div>
                        <p class="text-sm italic leading-relaxed text-slate-500">"{{ $rating->reason }}"</p>
                    </div>
                @endif

                <div>
                    <div class="tw-stat-label mb-1"><i class="bi bi-person mr-1 text-violet-500"></i>Passenger</div>
                    <div class="text-sm text-slate-700">
                        {{ $rating->passenger_name ?: 'Anonymous' }}
                        @if ($rating->passenger_contact)
                            &middot; <a href="tel:{{ $rating->passenger_contact }}" class="font-semibold text-navy-600 hover:underline">{{ $rating->passenger_contact }}</a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <div class="tw-stat-label mb-1"><i class="bi bi-signpost-2 mr-1 text-blue-500"></i>Route</div>
                    @if ($rating->start_location || $rating->end_location)
                        <div class="flex flex-wrap items-center gap-1.5">
                            @if ($rating->start_location)
                                <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                    <i class="bi bi-circle-fill text-[0.55rem] text-emerald-600"></i> {{ $rating->start_location }}
                                </span>
                            @endif
                            @if ($rating->start_location && $rating->end_location)
                                <i class="bi bi-arrow-right text-[0.7rem] text-slate-300"></i>
                            @endif
                            @if ($rating->end_location)
                                <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                    <i class="bi bi-circle-fill text-[0.55rem] text-red-600"></i> {{ $rating->end_location }}
                                </span>
                            @endif
                        </div>
                    @else
                        <div class="text-sm text-slate-400">No route data</div>
                    @endif
                </div>
                @if ($rating->rating <= 2)
                    <div>
                        <div class="tw-stat-label mb-1"><i class="bi bi-paperclip mr-1 text-amber-500"></i>Evidence</div>
                        @if ($rating->proofs->count() > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach ($rating->proofs as $proof)
                                    <a href="{{ URL::signedRoute('proof.serve', ['path' => $proof->file_path]) }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg border-[1.5px] border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-600 transition hover:border-blue-300 hover:bg-blue-100">
                                        <i class="bi bi-{{ str_contains($proof->file_type ?? '', 'image') ? 'image' : (str_contains($proof->file_type ?? '', 'video') ? 'play-circle' : 'file-earmark') }}"></i>
                                        View Proof
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="text-sm text-slate-400">No evidence attached</div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        @if ($rating->response)
            <div class="mx-4 mb-4 rounded-xl bg-navy-600/10 px-4 py-3 sm:mx-5">
                <div class="mb-1 flex flex-wrap items-center gap-1.5">
                    <i class="bi bi-reply-fill text-navy-600"></i>
                    <span class="text-[0.7rem] font-bold uppercase tracking-widest text-navy-600">Operator's Response</span>
                    <span class="ml-auto text-[0.65rem] text-slate-500">{{ $rating->response->created_at->diffForHumans() }}</span>
                </div>
                <p class="mb-0 text-sm text-slate-700">{{ $rating->response->message }}</p>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/70 px-4 py-3 sm:px-5">
            @if ($rating->response)
                <span class="text-xs text-slate-400"><i class="bi bi-check2-all mr-1 text-emerald-500"></i>Operator responded</span>
            @else
                <span class="text-xs text-slate-400"><i class="bi bi-hourglass mr-1"></i>No response yet</span>
            @endif
            <div class="flex shrink-0 gap-1.5">
                @if (!$rating->is_reviewed)
                    <form action="{{ route($reviewRouteName, $rating) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="tw-btn tw-btn-sm tw-btn-gold">
                            <i class="bi bi-check-lg"></i>Mark Reviewed
                        </button>
                    </form>
                @endif
                @if ($showDelete && !$rating->is_reviewed)
                    <form action="{{ route($routePrefix . '.complaints.destroy', $rating) }}" method="POST" onsubmit="return confirm('Delete this complaint?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline-danger" title="Delete complaint">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
