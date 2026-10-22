{{--
    Chat thread for one complaint. Requires:
    - $rating         App\Models\Rating (passengerFollowUps loaded)
    - $viewSide       string            'passenger' | 'staff'
    - $composerAction string            POST route for sending a message
    - $chatOpen       bool    (opt)     render expanded (e.g. after a validation error)

    Collapsed by default; clicking the header reveals the thread and, while
    the ticket is open, the composer. Passenger messages sit on the right
    (violet), TFRB/superadmin updates on the left (navy). Once the complaint
    is solved the composer is gone for both sides.
--}}
@php
    $thread = $rating->passengerFollowUps;
    $canChat = !$rating->is_solved;
    $isStaffView = $viewSide === 'staff';
    $chatOpen = $chatOpen ?? false;
    $composerError = $isStaffView
        ? ($errors->first('chat') ?: $errors->first('message'))
        : (($errors->has('message') && old('follow_up_rating_id') == $rating->id) ? $errors->first('message') : null);
@endphp
<div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
    <button type="button"
            onclick="var b=this.parentElement.querySelector('[data-chat-body]');var o=b.classList.toggle('hidden')===false;this.setAttribute('aria-expanded',o?'true':'false');var c=this.querySelector('[data-chat-chevron]');if(c)c.classList.toggle('rotate-180',o);var t=b.querySelector('[data-chat-thread]');if(o&&t)t.scrollTop=t.scrollHeight;"
            aria-expanded="{{ $chatOpen ? 'true' : 'false' }}"
            class="flex w-full items-center gap-1.5 bg-slate-50/70 px-4 py-2.5 text-left transition-colors hover:bg-slate-100">
        <i class="bi bi-chat-left-text-fill text-violet-600" aria-hidden="true"></i>
        <span class="text-[0.7rem] font-bold uppercase tracking-widest text-violet-700">
            {{ $isStaffView ? 'Passenger Follow-ups' : 'Your follow-ups' }} ({{ $thread->count() }})
        </span>
        @if ($canChat)
            <span class="inline-flex items-center gap-1 text-[0.65rem] font-semibold text-emerald-600">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span> Open
            </span>
        @else
            <span class="rounded bg-slate-200 px-1.5 py-0.5 text-[0.58rem] font-bold uppercase tracking-wider text-slate-500">Closed</span>
        @endif
        <i data-chat-chevron class="bi bi-chevron-down ml-auto text-slate-400 transition-transform duration-200 {{ $chatOpen ? 'rotate-180' : '' }}" aria-hidden="true"></i>
    </button>

    <div data-chat-body class="{{ $chatOpen ? '' : 'hidden' }} border-t border-slate-100">
        <div id="chat-thread-{{ $rating->id }}" data-chat-thread class="max-h-80 space-y-2.5 overflow-y-auto px-3.5 py-3">
            @forelse ($thread as $followUp)
                @if ($followUp->isFromStaff())
                    @php
                        $sender = $followUp->user;
                        $staffLabel = $sender && $sender->isSuperadmin()
                            ? 'Superadmin'
                            : ($sender && $sender->isTfrbOfficer() ? 'TFRB Officer' : 'TFRB Staff');
                    @endphp
                    <div class="flex justify-start">
                        <div class="max-w-[85%] rounded-2xl rounded-tl-md border border-navy-100 bg-navy-50 px-3.5 py-2">
                            <div class="mb-0.5 flex flex-wrap items-center gap-1.5 text-[0.65rem] font-bold text-navy-600">
                                <i class="bi bi-person-badge" aria-hidden="true"></i>
                                {{ $staffLabel }}
                                <span class="rounded bg-navy-600 px-1.5 py-px text-[0.55rem] font-bold uppercase tracking-wider text-white">{{ $staffLabel === 'Superadmin' ? 'Admin' : 'TFRB' }}</span>
                            </div>
                            <p class="text-sm leading-relaxed text-slate-700">{{ $followUp->message }}</p>
                            <div class="mt-1 text-right text-[0.62rem] text-slate-400">{{ $followUp->created_at->format('M j, g:i A') }}</div>
                        </div>
                    </div>
                @else
                    <div class="flex justify-end">
                        <div class="max-w-[85%] rounded-2xl rounded-tr-md bg-violet-600 px-3.5 py-2 text-white">
                            <div class="mb-0.5 text-[0.65rem] font-bold text-violet-200">
                                {{ $isStaffView ? ($rating->passenger_name ?: 'Passenger') : 'You' }}
                            </div>
                            <p class="text-sm leading-relaxed">{{ $followUp->message }}</p>
                            <div class="mt-1 text-right text-[0.62rem] text-violet-200">{{ $followUp->created_at->format('M j, g:i A') }}</div>
                        </div>
                    </div>
                @endif
            @empty
                <p class="py-2 text-center text-xs text-slate-400">No messages yet — start the conversation below.</p>
            @endforelse
        </div>

        @if ($canChat)
            <form action="{{ $composerAction }}" method="POST" class="border-t border-slate-100 px-3.5 py-3">
                @csrf
                @if (!$isStaffView)
                    <input type="hidden" name="follow_up_rating_id" value="{{ $rating->id }}">
                @endif
                <label for="chat-input-{{ $rating->id }}" class="tw-label mb-1">
                    <i class="bi bi-send-fill mr-1 text-violet-500"></i>
                    {{ $isStaffView ? 'Send an update to the passenger' : 'Follow up this complaint' }}
                </label>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                    <textarea id="chat-input-{{ $rating->id }}" name="message" rows="2" maxlength="1000"
                              class="tw-textarea flex-1"
                              placeholder="{{ $isStaffView ? 'Type an update for the passenger...' : 'Add details or request a status update...' }}">{{ (!$isStaffView && old('follow_up_rating_id') == $rating->id) ? old('message') : '' }}</textarea>
                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-navy shrink-0 self-stretch sm:self-start">
                        <i class="bi bi-send-fill"></i> {{ $isStaffView ? 'Send Update' : 'Send' }}
                    </button>
                </div>
                @if ($composerError)
                    <span class="tw-error-text" role="alert">{{ $composerError }}</span>
                @endif
            </form>
        @else
            <div class="border-t border-slate-100 px-4 py-2.5 text-xs text-slate-400">
                <i class="bi bi-check2-all mr-1 text-emerald-500"></i>
                {{ $isStaffView
                    ? 'This complaint is resolved — the chat is closed.'
                    : 'This complaint has been resolved — you can no longer follow up.' }}
            </div>
        @endif
    </div>
    @if ($chatOpen)
        <script>(function(){var t=document.getElementById('chat-thread-{{ $rating->id }}');if(t)t.scrollTop=t.scrollHeight;})()</script>
    @endif
</div>
