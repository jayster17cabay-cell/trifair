@extends('layouts.operator')

@section('title', 'My Profile')

@section('header-body')
<div class="op-header-greet">
    <h1 class="op-header-title">My Profile</h1>
    <p class="op-header-sub">Your registration details and performance snapshot</p>
</div>
@endsection

@section('content')
<div class="grid gap-5 lg:grid-cols-3">
    <div class="lg:col-span-1">
        <div class="tw-card">
            <div class="flex flex-col items-center px-6 py-8 text-center">
                <div class="tw-avatar tw-avatar-lg bg-gradient-to-br from-navy-600 to-navy-500 text-white">
                    {{ strtoupper(substr($operator->user->name, 0, 1)) }}
                </div>
                <h3 class="mt-4 text-lg font-extrabold text-slate-800">{{ $operator->user->name }}</h3>
                <p class="text-sm text-slate-500">{{ $operator->user->email }}</p>
                <div class="mt-3">
                    @if ($operator->status === 'active')
                        <span class="tw-badge tw-badge-green"><i class="bi bi-check-circle-fill"></i>Active</span>
                    @elseif ($operator->status === 'pending')
                        <span class="tw-badge tw-badge-amber"><i class="bi bi-hourglass-split"></i>Pending</span>
                    @elseif ($operator->status === 'rejected')
                        <span class="tw-badge tw-badge-red"><i class="bi bi-x-circle-fill"></i>Rejected</span>
                    @else
                        <span class="tw-badge tw-badge-amber"><i class="bi bi-pause-circle-fill"></i>Inactive</span>
                    @endif
                </div>
                @if ($operator->toda)
                    <span class="tw-badge tw-badge-navy mt-2"><i class="bi bi-diagram-3"></i>{{ $operator->toda->name }}</span>
                @endif
                <div class="mt-4 w-full border-t border-slate-100 pt-4 text-left text-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-navy-600 font-semibold">Body #</span>
                        <span class="font-bold text-slate-800">{{ $operator->body_number ?? '—' }}</span>
                    </div>
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-navy-600 font-semibold">Plate #</span>
                        <span class="font-bold text-slate-800">{{ $operator->plate_number ?? '—' }}</span>
                    </div>
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-navy-600 font-semibold">License #</span>
                        <span class="font-bold text-slate-800">{{ $operator->license_number ?? '—' }}</span>
                    </div>
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-navy-600 font-semibold">Motorcycle Model</span>
                        <span class="font-bold text-slate-800">{{ $operator->motorcycle_model ?? '—' }}</span>
                    </div>
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-navy-600 font-semibold">Contact</span>
                        <span class="font-bold text-slate-800">{{ $operator->contact_number ?? '—' }}</span>
                    </div>
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-navy-600 font-semibold">Address</span>
                        <span class="font-bold text-slate-800">{{ $operator->address ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-navy-600 font-semibold">Member Since</span>
                        <span class="font-bold text-slate-800">{{ $operator->created_at?->format('M d, Y') ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="tw-stat">
                <div class="tw-stat-icon tw-stat-icon-navy"><i class="bi bi-star"></i></div>
                <div class="tw-stat-num">{{ $stats['totalRatings'] }}</div>
                <div class="tw-stat-label">Total Ratings</div>
            </div>
            <div class="tw-stat">
                <div class="tw-stat-icon tw-stat-icon-amber"><i class="bi bi-stars"></i></div>
                <div class="tw-stat-num">{{ $stats['averageRating'] ? number_format($stats['averageRating'], 1) : '—' }}</div>
                <div class="tw-stat-label">Average Rating</div>
            </div>
            <div class="tw-stat">
                <div class="tw-stat-icon tw-stat-icon-red"><i class="bi bi-exclamation-circle"></i></div>
                <div class="tw-stat-num">{{ $stats['totalComplaints'] }}</div>
                <div class="tw-stat-label">Complaints</div>
            </div>
            <div class="tw-stat">
                <div class="tw-stat-icon tw-stat-icon-emerald"><i class="bi bi-chat-dots"></i></div>
                <div class="tw-stat-num">{{ $stats['responseRate'] }}</div>
                <div class="tw-stat-label">Responses</div>
            </div>
        </div>

        <div class="tw-card">
            <div class="tw-card-pad border-b border-slate-100">
                <h5 class="tw-card-title"><i class="bi bi-shield-check mr-2 text-navy-600"></i>Operator Details</h5>
            </div>
            <div class="tw-card-pad">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="tw-label text-[0.8rem] uppercase tracking-widest text-navy-600">Full Name</label>
                        <p class="font-semibold text-slate-800">{{ $operator->user->name }}</p>
                    </div>
                    <div>
                        <label class="tw-label text-[0.8rem] uppercase tracking-widest text-navy-600">Email</label>
                        <p class="font-semibold text-slate-800">{{ $operator->user->email }}</p>
                    </div>
                    <div>
                        <label class="tw-label text-[0.8rem] uppercase tracking-widest text-navy-600">TODA Organization</label>
                        <p class="font-semibold text-slate-800">{{ $operator->toda->name ?? 'Unassigned' }}</p>
                    </div>
                    <div>
                        <label class="tw-label text-[0.8rem] uppercase tracking-widest text-navy-600">Phone</label>
                        <p class="font-semibold text-slate-800">{{ $operator->user->phone ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="tw-label text-[0.8rem] uppercase tracking-widest text-navy-600">License Number</label>
                        <p class="font-semibold text-slate-800">{{ $operator->license_number ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="tw-label text-[0.8rem] uppercase tracking-widest text-navy-600">QR Code</label>
                        <p class="font-semibold text-slate-800">
                            <code class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-600">{{ $operator->qr_code }}</code>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
