@extends('layouts.superadmin')

@section('title', 'Activity Logs')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title">Activity Logs</h1>
        <p class="tw-page-sub">Audit trail of all system actions</p>
    </div>
</div>

@php
    $categories = [
        '' => 'All',
        'auth' => 'Logins',
        'operator' => 'Operators',
        'tfrb_officer' => 'TFRB Officers',
        'review' => 'Reviews',
    ];
    $categoryColors = [
        'auth' => 'text-navy-600',
        'operator' => 'text-amber-700',
        'tfrb_officer' => 'text-sky-700',
        'review' => 'text-violet-700',
        'system' => 'text-slate-500',
    ];
    $categoryBgs = [
        'auth' => 'bg-blue-50',
        'operator' => 'bg-amber-50',
        'tfrb_officer' => 'bg-sky-50',
        'review' => 'bg-violet-50',
        'system' => 'bg-slate-100',
    ];
@endphp

<div class="mb-4 flex flex-wrap gap-2">
    @foreach ($categories as $key => $label)
        <a href="{{ $key ? route('superadmin.activity-logs', ['category' => $key]) : route('superadmin.activity-logs') }}"
           class="tw-btn tw-btn-sm rounded-full {{ ($category ?: '') === $key ? 'tw-btn-navy' : 'tw-btn-outline' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="tw-card overflow-hidden">
    <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3.5">
        <i class="bi bi-clock-history text-amber-500"></i>
        <span class="text-sm font-bold text-slate-800">System Audit Trail</span>
        @if ($category)
            <span class="tw-badge tw-badge-amber ml-auto">{{ $categories[$category] ?? $category }}</span>
        @endif
    </div>
    <div class="divide-y divide-slate-100">
        @forelse ($logs as $log)
            <div class="flex items-start gap-3 px-5 py-4">
                <div class="tw-avatar {{ $categoryBgs[$log->category] ?? 'bg-slate-100' }} {{ $categoryColors[$log->category] ?? 'text-slate-400' }} text-base">
                    @switch($log->action)
                        @case('login') <i class="bi bi-box-arrow-in-right"></i> @break
                        @case('logout') <i class="bi bi-box-arrow-right"></i> @break
                        @case('create_operator') <i class="bi bi-person-plus"></i> @break
                        @case('update_operator') <i class="bi bi-pencil"></i> @break
                        @case('delete_operator') <i class="bi bi-person-x"></i> @break
                        @case('create_tfrb_officer') <i class="bi bi-shield-plus"></i> @break
                        @case('delete_tfrb_officer') <i class="bi bi-shield-x"></i> @break
                        @case('mark_reviewed') <i class="bi bi-check-circle"></i> @break
                        @case('submit_rating') <i class="bi bi-star"></i> @break
                        @case('operator_respond') <i class="bi bi-chat-dots"></i> @break
                        @case('update_operator_response') <i class="bi bi-chat-square-text"></i> @break
                        @default <i class="bi bi-circle"></i>
                    @endswitch
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <strong class="text-sm">{{ $log->user->name ?? 'Unknown' }}</strong>
                        <small class="shrink-0 text-xs text-slate-400" title="{{ $log->created_at->format('M d, Y h:i A') }}">{{ $log->created_at->diffForHumans() }}</small>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">{{ $log->description }}</p>
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        <span class="tw-badge tw-badge-gray"><i class="bi bi-tag"></i>{{ str_replace('_', ' ', ucfirst($log->action)) }}</span>
                        @if ($log->ip_address)
                            <span class="tw-badge tw-badge-gray"><i class="bi bi-globe"></i>{{ $log->ip_address }}</span>
                        @endif
                        <span class="tw-badge tw-badge-gray"><i class="bi bi-person-badge"></i>{{ $log->user->role ?? 'Unknown' }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-5">
                <div class="tw-empty">
                    <div class="tw-empty-icon"><i class="bi bi-clock-history"></i></div>
                    <p class="text-sm text-slate-500">No activity logs found.</p>
                </div>
            </div>
        @endforelse
    </div>
</div>

<div class="mt-4">
    {{ $logs->withQueryString()->links('pagination::tailwind') }}
</div>
@endsection
