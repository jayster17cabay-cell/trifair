{{-- Shared ratings page body. Requires: $routePrefix, $reviewRouteName, $showDelete, $ratings --}}

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-star-half mr-2 text-gold"></i>Ratings & Feedback</h1>
        <p class="tw-page-sub">Review all passenger feedback and complaints</p>
    </div>
</div>

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
    @php
        $totalR = $ratings->total();
        $pendingR = $ratings->getCollection()->filter(function($r){ return $r->rating <= 2 && !$r->is_reviewed; })->count();
        $goodR = $ratings->getCollection()->filter(function($r){ return $r->rating >= 4; })->count();
        $proofsR = $ratings->getCollection()->sum(fn($r) => $r->proofs->count());
    @endphp
    <div class="tw-stat">
        <div class="tw-stat-icon bg-blue-50 text-navy-600"><i class="bi bi-star"></i></div>
        <div class="tw-stat-num">{{ $totalR }}</div>
        <div class="tw-stat-label">Total</div>
    </div>
    <div class="tw-stat">
        <div class="tw-stat-icon bg-emerald-50 text-emerald-600"><i class="bi bi-hand-thumbs-up"></i></div>
        <div class="tw-stat-num">{{ $goodR }}</div>
        <div class="tw-stat-label">Good</div>
    </div>
    <div class="tw-stat">
        <div class="tw-stat-icon bg-red-50 text-red-600"><i class="bi bi-exclamation-circle"></i></div>
        <div class="tw-stat-num">{{ $pendingR }}</div>
        <div class="tw-stat-label">Pending</div>
    </div>
    <div class="tw-stat">
        <div class="tw-stat-icon bg-violet-50 text-violet-600"><i class="bi bi-paperclip"></i></div>
        <div class="tw-stat-num">{{ $proofsR }}</div>
        <div class="tw-stat-label">Proofs</div>
    </div>
</div>

@forelse ($ratings as $rating)
    @php
        $rr = $rating->rating;
        if ($rr >= 4) { $avBg = '#059669'; }
        elseif ($rr <= 2) { $avBg = '#dc2626'; }
        else { $avBg = '#d97706'; }
    @endphp
    <div class="tw-card mb-3 {{ $rr <= 2 && !$rating->is_reviewed ? 'border-l-4 !border-l-amber-500' : '' }}">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-xl text-[0.95rem] font-extrabold text-white" style="background: {{ $avBg }};">
                    {{ strtoupper(substr($rating->operator->user->name ?? 'U', 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <div class="truncate text-sm font-bold text-slate-800">{{ $rating->operator->user->name ?? 'Unknown' }}</div>
                    <div class="text-xs text-slate-500">
                        @if($rating->operator->body_number) #{{ $rating->operator->body_number }} &middot; @endif
                        {{ $rating->created_at->format('M d, Y \a\t h:i A') }}
                    </div>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <div class="flex gap-0.5">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="bi {{ $i <= $rr ? 'bi-star-fill' : 'bi-star' }}" style="color: {{ $i <= $rr ? '#f59e0b' : '#e5e7eb' }};"></i>
                    @endfor
                </div>
                @if ($rating->is_reviewed)
                    <span class="tw-badge tw-badge-green"><i class="bi bi-check-circle-fill"></i> Reviewed</span>
                @elseif ($rr <= 2)
                    <span class="tw-badge tw-badge-amber"><i class="bi bi-clock-fill"></i> Pending</span>
                @endif
            </div>
        </div>

        <div class="space-y-1 px-5 pb-4">
            @if ($rating->complaint_type)
                <div class="flex items-start gap-2 border-b border-slate-50 py-2 text-sm text-slate-600">
                    <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-red-100 text-sm text-red-600"><i class="bi bi-exclamation-triangle"></i></div>
                    <div class="min-w-0 flex-1">
                        <div class="tw-stat-label mb-0.5">Complaint Type</div>
                        <div class="text-slate-700">{{ $rating->complaint_type }}</div>
                        @if ($rating->complaint_details)
                            <div class="mt-1 italic text-slate-500">"{{ $rating->complaint_details }}"</div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($rating->reason)
                <div class="flex items-start gap-2 border-b border-slate-50 py-2 text-sm text-slate-600">
                    <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-sm text-amber-600"><i class="bi bi-chat-dots"></i></div>
                    <div class="min-w-0 flex-1">
                        <div class="tw-stat-label mb-0.5">Reason</div>
                        <div class="text-slate-700">{{ $rating->reason }}</div>
                    </div>
                </div>
            @endif

            @if ($rating->start_location || $rating->end_location)
                <div class="flex items-start gap-2 border-b border-slate-50 py-2 text-sm text-slate-600">
                    <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-sm text-blue-600"><i class="bi bi-signpost-2"></i></div>
                    <div class="min-w-0 flex-1">
                        <div class="tw-stat-label mb-1">Route</div>
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
                    </div>
                </div>
            @endif

            @if ($rating->passenger_name || $rating->passenger_contact)
                <div class="flex items-start gap-2 border-b border-slate-50 py-2 text-sm text-slate-600">
                    <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-sm text-violet-600"><i class="bi bi-person"></i></div>
                    <div class="min-w-0 flex-1">
                        <div class="tw-stat-label mb-0.5">Passenger</div>
                        <div class="text-slate-700">
                            {{ $rating->passenger_name ?: 'Anonymous' }}
                            @if ($rating->passenger_contact)
                                &middot; <a href="tel:{{ $rating->passenger_contact }}" class="font-semibold text-navy-600 hover:underline">{{ $rating->passenger_contact }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex items-start gap-2 py-2 text-sm text-slate-600">
                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-sm text-amber-600"><i class="bi bi-paperclip"></i></div>
                <div class="min-w-0 flex-1">
                    <div class="tw-stat-label mb-1">Evidence</div>
                    @if ($rating->proofs->count() > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach ($rating->proofs as $proof)
                                <a href="{{ URL::signedRoute('proof.serve', ['path' => $proof->file_path]) }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1.5 rounded-lg border-[1.5px] border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-600 transition hover:border-blue-300 hover:bg-blue-100">
                                    <i class="bi bi-{{ str_contains($proof->file_type ?? '', 'image') ? 'image' : (str_contains($proof->file_type ?? '', 'video') ? 'play-circle' : 'file-earmark') }}"></i>
                                    View Proof
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-slate-300">No evidence attached</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-b-2xl bg-slate-50 px-5 py-3">
            @if ($rating->response)
                <div class="min-w-0 flex-1">
                    <div class="rounded-xl bg-navy-600/10 px-4 py-3">
                        <div class="mb-1 flex flex-wrap items-center gap-1.5">
                            <i class="bi bi-reply-fill text-navy-600"></i>
                            <span class="text-[0.7rem] font-bold uppercase tracking-widest text-navy-600">Operator's Response</span>
                            <span class="ml-auto text-[0.65rem] text-slate-500">{{ $rating->response->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mb-0 text-sm text-slate-700">{{ $rating->response->message }}</p>
                    </div>
                </div>
            @else
                <span class="text-xs text-slate-400"><i class="bi bi-hourglass mr-1"></i> No response yet</span>
            @endif

            <div class="flex shrink-0 gap-1.5">
                @if (!$rating->is_reviewed && $rr <= 2)
                    <form action="{{ route($reviewRouteName, $rating) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="tw-btn tw-btn-sm bg-gradient-to-r from-gold to-amber-600 text-slate-900 hover:from-gold-dark hover:to-amber-700">
                            <i class="bi bi-check-lg"></i>Mark Reviewed
                        </button>
                    </form>
                @endif
                @if ($showDelete && !$rating->is_reviewed)
                    <form action="{{ route($routePrefix . '.complaints.destroy', $rating) }}" method="POST" onsubmit="return confirm('Delete this complaint?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tw-btn tw-btn-sm tw-btn-danger" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="tw-empty py-16">
        <div class="tw-empty-icon"><i class="bi bi-star"></i></div>
        <h3 class="tw-card-title mb-1">No Ratings Yet</h3>
        <p class="text-sm text-slate-500">No passenger feedback has been submitted yet.</p>
    </div>
@endforelse

@if ($ratings->hasPages())
    <div class="mt-4">
        {{ $ratings->links('pagination::tailwind') }}
    </div>
@endif
