{{--
    TODA switcher. Shown only to operators/presidents who are connected to more
    than one TODA (through a membership or their home TODA). Renders a single
    "Change TODA" button that opens the full picker — changing TODA never needs
    a sign-out. Rendered in the shared admin shell (president console).
--}}
@php
    $todaSwitchUser = auth()->user();
    $todaSwitchList = collect();

    if ($todaSwitchUser && ($todaSwitchUser->isOperator() || $todaSwitchUser->isOperatorPresident())) {
        $todaSwitchList = \App\Support\TodaContext::availableTodas($todaSwitchUser);
    }
@endphp

@if ($todaSwitchList->count() > 1)
    <a href="{{ route('toda.select') }}" class="tw-topbar-icon-btn" title="Change TODA" aria-label="Change TODA">
        <i class="bi bi-arrow-left-right text-base"></i>
    </a>
@endif
