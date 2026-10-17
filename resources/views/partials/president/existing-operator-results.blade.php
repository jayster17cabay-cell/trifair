{{--
    AJAX results for the president's "Add Existing Operator" search. Each row
    adds the SAME operator account (same email, same body number) to the TODA.
    Requires: $operators (Operator collection with user + toda relations).
--}}
@if ($operators->isEmpty())
    <div class="px-4 py-8 text-center">
        <div class="tw-empty-icon"><i class="bi bi-search"></i></div>
        <p class="mt-2 text-sm text-slate-500">No matching operators. Try a name, body number, or email.</p>
    </div>
@else
    <ul class="divide-y divide-slate-100">
        @foreach ($operators as $operator)
            <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-800">{{ $operator->user->name ?? 'Unknown' }}</div>
                    <div class="text-xs text-slate-500">
                        {{ $operator->user->email ?? '' }}
                        @if ($operator->body_number) · Body #{{ $operator->body_number }} @endif
                        @if ($operator->plate_number) · {{ $operator->plate_number }} @endif
                    </div>
                    <div class="text-xs text-slate-400">Current TODA: {{ $operator->toda->name ?? 'None' }}</div>
                </div>
                <form method="POST" action="{{ route('president.members.addExisting') }}">
                    @csrf
                    <input type="hidden" name="operator_id" value="{{ $operator->id }}">
                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-gold">
                        <i class="bi bi-plus-lg"></i> Add to TODA
                    </button>
                </form>
            </li>
        @endforeach
    </ul>
@endif
