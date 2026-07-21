@extends('layouts.superadmin')

@section('title', 'Add TODA')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Add New TODA</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Create a Tricycle Operators and Drivers Association</p>
    </div>
    <a href="{{ route('superadmin.todas') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="card card-accent-yellow">
    <div class="card-body">
        <form action="{{ route('superadmin.todas.store') }}" method="POST">
            @csrf

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="name" class="form-label">TODA Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Brgy. San Antonio TODA">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="area" class="form-label">Coverage Area</label>
                    <input type="text" class="form-control @error('area') is-invalid @enderror" id="area" name="area" value="{{ old('area') }}" placeholder="e.g. Brgy. San Antonio, Sampaloc">
                    @error('area') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mt-4">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Brief description about this TODA">{{ old('description') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-yellow px-4">
                    <i class="bi bi-save me-1"></i> Create TODA
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
