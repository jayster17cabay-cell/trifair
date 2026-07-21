@extends('layouts.superadmin')

@section('title', 'Edit Driver')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Edit Driver</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Updating: {{ $driver->user->name }}</p>
    </div>
    <a href="{{ route('superadmin.drivers') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="card card-accent-yellow">
    <div class="card-body">
        <form action="{{ route('superadmin.drivers.update', $driver) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-md-4">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $driver->user->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $driver->user->email) }}" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="toda_id" class="form-label">TODA <span class="text-danger">*</span></label>
                    <select class="form-select @error('toda_id') is-invalid @enderror" id="toda_id" name="toda_id" required>
                        <option value="">-- Select TODA --</option>
                        @foreach ($todas as $toda)
                            <option value="{{ $toda->id }}" {{ old('toda_id', $driver->toda_id) == $toda->id ? 'selected' : '' }}>{{ $toda->name }}{{ $toda->area ? " ({$toda->area})" : '' }}</option>
                        @endforeach
                    </select>
                    @error('toda_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-md-6">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Leave empty to keep current">
                    <small class="form-text">Leave blank if you don't want to change the password.</small>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $driver->user->phone) }}">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-md-6">
                    <label for="license_number" class="form-label">License Number</label>
                    <input type="text" class="form-control @error('license_number') is-invalid @enderror" id="license_number" name="license_number" value="{{ old('license_number', $driver->license_number) }}">
                    @error('license_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                        <option value="active" {{ old('status', $driver->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $driver->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <a href="{{ route('superadmin.drivers.qrcode', $driver) }}" class="btn btn-outline-yellow w-100">
                        <i class="bi bi-qr-code me-1"></i> View QR Code
                    </a>
                </div>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-md-4">
                    <label for="plate_number" class="form-label">Plate Number</label>
                    <input type="text" class="form-control @error('plate_number') is-invalid @enderror" id="plate_number" name="plate_number" value="{{ old('plate_number', $driver->plate_number) }}" placeholder="e.g. ABC-123">
                    @error('plate_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="body_number" class="form-label">Body Number</label>
                    <input type="text" class="form-control @error('body_number') is-invalid @enderror" id="body_number" name="body_number" value="{{ old('body_number', $driver->body_number) }}" placeholder="e.g. 1234">
                    @error('body_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="tricycle_color" class="form-label">Tricycle Color</label>
                    <input type="text" class="form-control @error('tricycle_color') is-invalid @enderror" id="tricycle_color" name="tricycle_color" value="{{ old('tricycle_color', $driver->tricycle_color) }}" placeholder="e.g. Red/White">
                    @error('tricycle_color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mt-4">
                <label for="contact_number" class="form-label">Contact Number</label>
                <input type="text" class="form-control @error('contact_number') is-invalid @enderror" id="contact_number" name="contact_number" value="{{ old('contact_number', $driver->contact_number) }}">
                @error('contact_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mt-4">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address', $driver->address) }}</textarea>
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-yellow px-4">
                    <i class="bi bi-save me-1"></i> Update Driver
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
