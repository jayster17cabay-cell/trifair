@extends('layouts.superadmin')

@section('title', 'Edit TODA')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Edit TODA</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Updating: {{ $toda->name }}</p>
    </div>
    <a href="{{ route('superadmin.todas') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="card card-accent-yellow">
    <div class="card-body">
        <form action="{{ route('superadmin.todas.update', $toda) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="name" class="form-label">TODA Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $toda->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="area" class="form-label">Coverage Area</label>
                    <input type="text" class="form-control @error('area') is-invalid @enderror" id="area" name="area" value="{{ old('area', $toda->area) }}">
                    @error('area') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mt-4">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $toda->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row g-4 mt-2">
                <div class="col-md-4">
                    <label for="is_active" class="form-label">Status</label>
                    <select class="form-select @error('is_active') is-invalid @enderror" id="is_active" name="is_active">
                        <option value="1" {{ old('is_active', $toda->is_active) ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ !old('is_active', $toda->is_active) ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-yellow px-4">
                    <i class="bi bi-save me-1"></i> Update TODA
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
