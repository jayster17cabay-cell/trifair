@extends('layouts.app')

@section('title', 'Select your TODA')

@section('content')
@php
    // Greeting shows the first name only, matching the operator app header.
    $firstName = explode(' ', $user->name)[0];
@endphp
<div class="min-h-screen bg-slate-50 px-4 py-8 sm:px-6" style="background-image: linear-gradient(160deg, #071e42 0%, #0A2A5E 42%, #0d2f66 100%);">
    <div class="mx-auto w-full max-w-lg">
        <div class="mb-6 text-center">
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl text-2xl text-navy-800" style="background-image: linear-gradient(135deg, #f5b800 0%, #e0a300 100%);">
                <i class="bi bi-diagram-3-fill"></i>
            </div>
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gold">TriFair</p>
            <h1 class="mt-1 text-xl font-extrabold tracking-tight text-white sm:text-2xl">
                Which TODA, {{ $firstName }}?
            </h1>
            <p class="mt-1 text-sm text-slate-300">
                Your account belongs to {{ $todas->count() }} TODAs. Pick one — your dashboard, role and ratings will follow that TODA.
            </p>
        </div>

        @if (session('error'))
            <div class="tw-alert tw-alert-danger mb-4">
                <i class="bi bi-exclamation-triangle-fill tw-alert-icon"></i>
                <span>{{ session('error') }}</span>
                <button type="button" class="tw-alert-close" data-tw-dismiss aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>
            </div>
        @endif

        <div class="space-y-3">
            @foreach ($todas as $toda)
                @php
                    $isPresidency = in_array((int) $toda->id, $presidencyIds, true);
                    $isSuspended = $operator && $operator->isSuspendedForToda((int) $toda->id);
                @endphp
                <form method="POST" action="{{ route('toda.select.store') }}">
                    @csrf
                    <input type="hidden" name="toda_id" value="{{ $toda->id }}">
                    <button type="submit"
                            class="group flex w-full items-center gap-3 rounded-2xl border border-white/15 bg-white/10 px-4 py-4 text-left transition hover:border-gold/60 hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-gold">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $isPresidency ? 'bg-gold text-navy-800' : 'bg-white/15 text-white' }}">
                            <i class="bi {{ $isPresidency ? 'bi-award-fill' : 'bi-people' }} text-lg"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-extrabold text-white">{{ $toda->name }}</span>
                            <span class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[11px] font-semibold text-slate-300">
                                @if ($toda->area)
                                    <span class="truncate"><i class="bi bi-geo-alt mr-1"></i>{{ $toda->area }}</span>
                                @endif
                                @if ($isSuspended)
                                    <span class="tw-badge tw-badge-red"><i class="bi bi-slash-circle"></i> Suspended</span>
                                @endif
                            </span>
                        </span>
                        <span class="shrink-0">
                            <span class="tw-badge {{ $isPresidency ? '!bg-gold !text-navy-800' : 'tw-badge-gray !bg-white/15 !text-white' }}">
                                {{ $isPresidency ? 'President' : 'Member' }}
                            </span>
                        </span>
                        <i class="bi bi-chevron-right shrink-0 text-lg text-white/50 transition group-hover:translate-x-0.5 group-hover:text-gold"></i>
                    </button>
                </form>
            @endforeach
        </div>

        <p class="mt-5 text-center text-xs text-slate-400">
            <i class="bi bi-shield-check mr-1"></i>
            You can change TODA any time from your dashboard without signing out.
        </p>
    </div>
</div>
@endsection
