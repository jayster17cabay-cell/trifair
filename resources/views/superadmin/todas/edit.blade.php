@extends('layouts.superadmin')

@section('title', 'Edit TODA')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title">Edit TODA</h1>
        <p class="tw-page-sub">Updating: {{ $toda->name }}</p>
    </div>
    <a href="{{ route('superadmin.todas') }}" class="tw-btn tw-btn-outline">
        <i class="bi bi-arrow-left"></i>Back
    </a>
</div>

<div class="tw-card tw-card-pad max-w-2xl">
    <form action="{{ route('superadmin.todas.update', $toda) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="tw-label">TODA Name <span class="text-red-600">*</span></label>
                <input type="text" class="tw-input @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $toda->name) }}" required>
                @error('name') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="area" class="tw-label">Coverage Area</label>
                <input type="text" class="tw-input @error('area') is-invalid @enderror" id="area" name="area" value="{{ old('area', $toda->area) }}">
                @error('area') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mt-4">
            <label for="description" class="tw-label">Description</label>
            <textarea class="tw-textarea @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $toda->description) }}</textarea>
            @error('description') <div class="tw-error-text">{{ $message }}</div> @enderror
        </div>

        <div class="mt-4">
            <label for="is_active" class="tw-label">Status</label>
            <select class="tw-select @error('is_active') is-invalid @enderror" id="is_active" name="is_active">
                <option value="1" {{ old('is_active', $toda->is_active) ? 'selected' : '' }}>Active</option>
                <option value="0" {{ !old('is_active', $toda->is_active) ? 'selected' : '' }}>Inactive</option>
            </select>
            @error('is_active') <div class="tw-error-text">{{ $message }}</div> @enderror
        </div>

        <div class="mt-6 border-t border-slate-100 pt-4">
            <button type="submit" class="tw-btn tw-btn-gold px-5">
                <i class="bi bi-save"></i>Update TODA
            </button>
        </div>
    </form>
</div>
@endsection
