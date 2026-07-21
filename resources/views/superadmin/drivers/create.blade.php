@extends('layouts.superadmin')

@section('title', 'Add Driver')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Add New Driver</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Register a new tricycle driver</p>
    </div>
    <a href="{{ route('superadmin.drivers') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="card card-accent-yellow">
    <div class="card-body">
        <form action="{{ route('superadmin.drivers.store') }}" method="POST">
            @csrf

            <div class="row g-4 mb-2">
                <div class="col-md-12">
                    <label for="toda_id" class="form-label">TODA <span class="text-danger">*</span></label>
                    <select class="form-select @error('toda_id') is-invalid @enderror" id="toda_id" name="toda_id" required>
                        <option value="">-- Select TODA --</option>
                        @foreach ($todas as $toda)
                            <option value="{{ $toda->id }}" {{ old('toda_id') == $toda->id ? 'selected' : '' }}>{{ $toda->name }}{{ $toda->area ? " ({$toda->area})" : '' }}</option>
                        @endforeach
                    </select>
                    @error('toda_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="Enter full name">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required placeholder="Enter email address">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-md-6">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required placeholder="Min. 8 characters">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="Enter phone number">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-md-6">
                    <label for="license_number" class="form-label">License Number</label>
                    <input type="text" class="form-control @error('license_number') is-invalid @enderror" id="license_number" name="license_number" value="{{ old('license_number') }}" placeholder="Enter license number">
                    @error('license_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="contact_number" class="form-label">Contact Number</label>
                    <input type="text" class="form-control @error('contact_number') is-invalid @enderror" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" placeholder="Enter contact number">
                    @error('contact_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-md-4">
                    <label for="plate_number" class="form-label">Plate Number</label>
                    <input type="text" class="form-control @error('plate_number') is-invalid @enderror" id="plate_number" name="plate_number" value="{{ old('plate_number') }}" placeholder="e.g. ABC-123">
                    @error('plate_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="body_number" class="form-label">Body Number</label>
                    <input type="text" class="form-control @error('body_number') is-invalid @enderror" id="body_number" name="body_number" value="{{ old('body_number') }}" placeholder="e.g. 1234">
                    @error('body_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="tricycle_color" class="form-label">Tricycle Color</label>
                    <input type="text" class="form-control @error('tricycle_color') is-invalid @enderror" id="tricycle_color" name="tricycle_color" value="{{ old('tricycle_color') }}" placeholder="e.g. Red/White">
                    @error('tricycle_color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mt-4">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2" placeholder="Enter complete address">{{ old('address') }}</textarea>
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-yellow px-4">
                    <i class="bi bi-save me-1"></i> Create Driver
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
