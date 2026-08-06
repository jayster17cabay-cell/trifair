@extends('layouts.superadmin')

@section('title', 'Add TODA')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title">Add New TODA</h1>
        <p class="tw-page-sub">Create a Tricycle Operators and Drivers Association</p>
    </div>
    <a href="{{ route('superadmin.todas') }}" class="tw-btn tw-btn-outline">
        <i class="bi bi-arrow-left"></i>Back
    </a>
</div>

<div class="tw-card tw-card-pad max-w-2xl">
    <form action="{{ route('superadmin.todas.store') }}" method="POST">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="tw-label">TODA Name <span class="text-red-600">*</span></label>
                <input type="text" class="tw-input @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Brgy. San Antonio TODA">
                @error('name') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="area" class="tw-label">Coverage Area</label>
                <input type="text" class="tw-input @error('area') is-invalid @enderror" id="area" name="area" value="{{ old('area') }}" placeholder="e.g. Brgy. San Antonio, Sampaloc">
                @error('area') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mt-4">
            <label for="description" class="tw-label">Description</label>
            <textarea class="tw-textarea @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Brief description about this TODA">{{ old('description') }}</textarea>
            @error('description') <div class="tw-error-text">{{ $message }}</div> @enderror
        </div>

        <div class="mt-6 border-t border-slate-100 pt-4">
            <button type="submit" class="tw-btn tw-btn-gold px-5">
                <i class="bi bi-save"></i>Create TODA
            </button>
        </div>
    </form>
</div>
@endsection
