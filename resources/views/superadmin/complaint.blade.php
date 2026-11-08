@extends('layouts.superadmin')

@section('title', 'Complaint ' . $rating->reference_number)

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Complaint details</p>
            <h1 class="mt-1 text-xl font-extrabold text-slate-900 sm:text-2xl">{{ $rating->reference_number }}</h1>
            <p class="mt-1 text-sm text-slate-500">Review the complete complaint record and take action.</p>
        </div>
        <a href="{{ route('superadmin.complaints', ['view' => 'action']) }}" class="tw-btn tw-btn-sm tw-btn-outline">
            <i class="bi bi-arrow-left"></i><span class="hidden sm:inline">Back to complaints</span><span class="sm:hidden">Back</span>
        </a>
    </div>

    @include('partials.admin.complaint-card', [
        'rating' => $rating,
        'routePrefix' => 'superadmin',
        'actor' => 'staff',
        'readOnly' => false,
        'standalone' => true,
    ])
@endsection
