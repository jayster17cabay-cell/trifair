{{--
    TODA switcher. Shown only to operators/presidents who are connected to more
    than one TODA (through a membership or their home TODA). Posting the choice
    stores it in the session and the dashboard resolves to the right mode:
    a presidency opens the president console, a plain membership the operator
    view. Rendered in the shared admin shell (president console).
--}}
@php
    $todaSwitchUser = auth()->user();
    $todaSwitchList = collect();
    $todaSwitchCurrent = null;

    if ($todaSwitchUser && ($todaSwitchUser->isOperator() || $todaSwitchUser->isOperatorPresident())) {
        $todaSwitchList = \App\Support\TodaContext::availableTodas($todaSwitchUser);
        $todaSwitchCurrent = \App\Support\TodaContext::currentId($todaSwitchUser);
    }
@endphp

@if ($todaSwitchList->count() > 1)
    <form method="POST" action="{{ route('toda.switch') }}" class="hidden items-center sm:flex">
        @csrf
        <label for="todaSwitchSelect" class="sr-only">Switch TODA</label>
        <div class="relative">
            <select id="todaSwitchSelect" name="toda_id" onchange="this.form.submit()"
                    class="tw-select max-w-[12rem] truncate py-1.5 pr-8 text-sm"
                    title="Switch TODA">
                @foreach ($todaSwitchList as $todaSwitchOption)
                    @php
                        $swIsPresidency = $todaSwitchUser->isPresidentOf($todaSwitchOption->id);
                        $swIsSuspended = $todaSwitchUser->operator
                            && $todaSwitchUser->operator->isSuspendedForToda((int) $todaSwitchOption->id);
                    @endphp
                    <option value="{{ $todaSwitchOption->id }}" @selected((int) $todaSwitchCurrent === (int) $todaSwitchOption->id)>
                        {{ $todaSwitchOption->name }} — {{ $swIsPresidency ? 'President' : 'Member' }}{{ $swIsSuspended ? ' (suspended)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>
@endif
