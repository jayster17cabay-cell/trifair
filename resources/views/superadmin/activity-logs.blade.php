@extends('layouts.superadmin')

@section('title', 'Activity Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Activity Logs</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Audit trail of all system actions</p>
    </div>
</div>

@php
    $categories = [
        '' => 'All',
        'auth' => 'Logins',
        'driver' => 'Drivers',
        'admin' => 'Admins',
        'review' => 'Reviews',
    ];
    $categoryColors = [
        'auth' => 'var(--primary)',
        'driver' => 'var(--secondary-dark)',
        'admin' => 'var(--info)',
        'review' => 'var(--warning)',
        'system' => 'var(--gray-600)',
    ];
    $categoryBgs = [
        'auth' => 'var(--primary-50)',
        'driver' => 'var(--secondary-light)',
        'admin' => 'rgba(13, 202, 240, 0.1)',
        'review' => 'var(--warning-light)',
        'system' => 'var(--gray-100)',
    ];
@endphp

<div class="d-flex gap-2 mb-3 flex-wrap">
    @foreach ($categories as $key => $label)
        <a href="{{ $key ? route('superadmin.activity-logs', ['category' => $key]) : route('superadmin.activity-logs') }}"
           class="btn btn-sm rounded-pill px-3 {{ ($category ?: '') === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="card card-accent-yellow">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-clock-history" style="color: var(--secondary);"></i>
        <span>System Audit Trail</span>
        @if ($category)
            <span class="badge bg-secondary ms-auto" style="font-size: 0.65rem;">{{ $categories[$category] ?? $category }}</span>
        @endif
    </div>
    <div class="card-body p-0">
        @forelse ($logs as $log)
            <div class="list-item-premium p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 38px; height: 38px;
                                background: {{ $categoryBgs[$log->category] ?? 'var(--gray-100)' }};
                                color: {{ $categoryColors[$log->category] ?? 'var(--gray-500)' }};
                                font-weight: 700; font-size: 0.85rem;">
                        @switch($log->action)
                            @case('login') <i class="bi bi-box-arrow-in-right"></i> @break
                            @case('logout') <i class="bi bi-box-arrow-right"></i> @break
                            @case('create_driver') <i class="bi bi-person-plus"></i> @break
                            @case('update_driver') <i class="bi bi-pencil"></i> @break
                            @case('delete_driver') <i class="bi bi-person-x"></i> @break
                            @case('create_admin') <i class="bi bi-shield-plus"></i> @break
                            @case('delete_admin') <i class="bi bi-shield-x"></i> @break
                            @case('mark_reviewed') <i class="bi bi-check-circle"></i> @break
                            @case('submit_rating') <i class="bi bi-star"></i> @break
                            @case('driver_respond') <i class="bi bi-chat-dots"></i> @break
                            @case('update_driver_response') <i class="bi bi-chat-square-text"></i> @break
                            @default <i class="bi bi-circle"></i>
                        @endswitch
                    </div>
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong style="font-size: 0.85rem;">{{ $log->user->name ?? 'Unknown' }}</strong>
                            <small class="text-muted" style="font-size: 0.7rem;" title="{{ $log->created_at->format('M d, Y h:i A') }}">{{ $log->created_at->diffForHumans() }}</small>
                        </div>
                        <p class="mb-0 mt-1" style="font-size: 0.8rem; color: var(--gray-600);">{{ $log->description }}</p>
                        <div class="d-flex gap-2 mt-1 flex-wrap">
                            <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.65rem;">
                                <i class="bi bi-tag me-1"></i>{{ str_replace('_', ' ', ucfirst($log->action)) }}
                            </span>
                            @if ($log->ip_address)
                                <span class="badge bg-light text-muted" style="font-size: 0.65rem;">
                                    <i class="bi bi-globe me-1"></i>{{ $log->ip_address }}
                                </span>
                            @endif
                            <span class="badge bg-light text-muted" style="font-size: 0.65rem;">
                                <i class="bi bi-person-badge me-1"></i>{{ $log->user->role ?? 'Unknown' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; background: var(--gray-100);">
                    <i class="bi bi-clock-history" style="font-size: 2rem; color: var(--gray-400);"></i>
                </div>
                <p class="text-muted mb-0" style="font-size: 0.9rem;">No activity logs found.</p>
            </div>
        @endforelse
    </div>
</div>

<div class="mt-3">
    {{ $logs->withQueryString()->links() }}
</div>
@endsection
