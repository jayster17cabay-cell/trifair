@extends('layouts.superadmin')

@section('title', 'Edit Operator')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title">Edit Operator</h1>
        <p class="tw-page-sub">Updating: {{ $operator->user->name }}</p>
    </div>
    <a href="{{ route('superadmin.operators') }}" class="tw-btn tw-btn-outline">
        <i class="bi bi-arrow-left"></i>Back
    </a>
</div>

<div class="tw-card tw-card-pad">
    <form action="{{ route('superadmin.operators.update', $operator) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="name" class="tw-label">Full Name</label>
                <input type="text" class="tw-input @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $operator->user->name) }}" required>
                @error('name') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="email" class="tw-label">Email Address</label>
                <input type="email" class="tw-input @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $operator->user->email) }}" required>
                @error('email') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="toda_id" class="tw-label">TODA <span class="text-red-600">*</span></label>
                <select class="tw-select @error('toda_id') is-invalid @enderror" id="toda_id" name="toda_id" required>
                    <option value="">-- Select TODA --</option>
                    @foreach ($todas as $toda)
                        <option value="{{ $toda->id }}" {{ old('toda_id', $operator->toda_id) == $toda->id ? 'selected' : '' }}>{{ $toda->name }}{{ $toda->area ? " ({$toda->area})" : '' }}</option>
                    @endforeach
                </select>
                @error('toda_id') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="password" class="tw-label">New Password</label>
                <input type="password" class="tw-input @error('password') is-invalid @enderror" id="password" name="password" placeholder="Leave empty to keep current">
                <small class="mt-1 block text-xs text-slate-400">Leave blank if you don't want to change the password.</small>
                @error('password') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="phone" class="tw-label">Phone Number</label>
                <input type="text" class="tw-input @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $operator->user->phone) }}">
                @error('phone') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="status" class="tw-label">Status</label>
                <select class="tw-select @error('status') is-invalid @enderror" id="status" name="status">
                    <option value="active" {{ old('status', $operator->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $operator->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="plate_number" class="tw-label">Plate Number</label>
                <input type="text" class="tw-input @error('plate_number') is-invalid @enderror" id="plate_number" name="plate_number" value="{{ old('plate_number', $operator->plate_number) }}" placeholder="e.g. ABC-123">
                @error('plate_number') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="body_number" class="tw-label">Body Number</label>
                <input type="text" class="tw-input @error('body_number') is-invalid @enderror" id="body_number" name="body_number" value="{{ old('body_number', $operator->body_number) }}" placeholder="e.g. 1234">
                @error('body_number') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="tricycle_color" class="tw-label">Tricycle Color</label>
                <input type="text" class="tw-input @error('tricycle_color') is-invalid @enderror" id="tricycle_color" name="tricycle_color" value="{{ old('tricycle_color', $operator->tricycle_color) }}" placeholder="e.g. Red/White">
                @error('tricycle_color') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mt-4">
            <label for="contact_number" class="tw-label">Contact Number</label>
            <input type="text" class="tw-input @error('contact_number') is-invalid @enderror" id="contact_number" name="contact_number" value="{{ old('contact_number', $operator->contact_number) }}">
            @error('contact_number') <div class="tw-error-text">{{ $message }}</div> @enderror
        </div>

        <div class="mt-4">
            <label for="address" class="tw-label">Address</label>
            <textarea class="tw-textarea @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address', $operator->address) }}</textarea>
            @error('address') <div class="tw-error-text">{{ $message }}</div> @enderror
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
            <a href="{{ route('superadmin.operators.qrcode', $operator) }}" class="tw-btn tw-btn-outline">
                <i class="bi bi-qr-code"></i>View QR Code
            </a>
            <button type="submit" class="tw-btn tw-btn-gold px-5">
                <i class="bi bi-save"></i>Update Operator
            </button>
        </div>
    </form>
</div>
@endsection
