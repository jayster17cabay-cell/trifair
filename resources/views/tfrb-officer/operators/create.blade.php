@extends('layouts.tfrb-officer')

@section('title', 'Add Operator')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-person-plus mr-2 text-emerald-600"></i>Add New Operator</h1>
        <p class="tw-page-sub">Register a new tricycle operator</p>
    </div>
    <a href="{{ route('tfrb-officer.operators') }}" class="tw-btn tw-btn-outline">
        <i class="bi bi-arrow-left"></i>Back
    </a>
</div>

<div class="tw-card tw-card-pad">
    <form action="{{ route('tfrb-officer.operators.store') }}" method="POST">
        @csrf

        <div class="mb-2 mt-1 text-xs font-bold uppercase tracking-wider text-slate-400">Assignment</div>
        <div class="mb-4">
            <label for="toda_id" class="tw-label">TODA <span class="text-red-600">*</span></label>
            <select class="tw-select @error('toda_id') is-invalid @enderror" id="toda_id" name="toda_id" required>
                <option value="">-- Select TODA --</option>
                @foreach ($todas as $toda)
                    <option value="{{ $toda->id }}" {{ old('toda_id') == $toda->id ? 'selected' : '' }}>{{ $toda->name }}{{ $toda->area ? " ({$toda->area})" : '' }}</option>
                @endforeach
            </select>
            @error('toda_id') <div class="tw-error-text">{{ $message }}</div> @enderror
        </div>

        <div class="mb-2 mt-6 text-xs font-bold uppercase tracking-wider text-slate-400">Account Details</div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="tw-label">Full Name <span class="text-red-600">*</span></label>
                <input type="text" class="tw-input @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="Enter full name">
                @error('name') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="email" class="tw-label">Email Address <span class="text-red-600">*</span></label>
                <input type="email" class="tw-input @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required placeholder="Enter email address">
                @error('email') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="password" class="tw-label">Password <span class="text-red-600">*</span></label>
                <input type="password" class="tw-input @error('password') is-invalid @enderror" id="password" name="password" required placeholder="Min. 8 characters">
                @error('password') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="phone" class="tw-label">Phone Number</label>
                <input type="text" class="tw-input @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="Enter phone number">
                @error('phone') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mb-2 mt-6 text-xs font-bold uppercase tracking-wider text-slate-400">Tricycle Details</div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="plate_number" class="tw-label">Plate Number</label>
                <input type="text" class="tw-input @error('plate_number') is-invalid @enderror" id="plate_number" name="plate_number" value="{{ old('plate_number') }}" placeholder="e.g. ABC-123">
                @error('plate_number') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="body_number" class="tw-label">Body Number</label>
                <input type="text" class="tw-input @error('body_number') is-invalid @enderror" id="body_number" name="body_number" value="{{ old('body_number') }}" placeholder="e.g. 1234">
                @error('body_number') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="tricycle_color" class="tw-label">Tricycle Color</label>
                <input type="text" class="tw-input @error('tricycle_color') is-invalid @enderror" id="tricycle_color" name="tricycle_color" value="{{ old('tricycle_color') }}" placeholder="e.g. Red/White">
                @error('tricycle_color') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="license_number" class="tw-label">License Number</label>
                <input type="text" class="tw-input @error('license_number') is-invalid @enderror" id="license_number" name="license_number" value="{{ old('license_number') }}" placeholder="Enter license number">
                @error('license_number') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="contact_number" class="tw-label">Contact Number</label>
                <input type="text" class="tw-input @error('contact_number') is-invalid @enderror" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" placeholder="Enter contact number">
                @error('contact_number') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mt-4">
            <label for="address" class="tw-label">Address</label>
            <textarea class="tw-textarea @error('address') is-invalid @enderror" id="address" name="address" rows="2" placeholder="Enter complete address">{{ old('address') }}</textarea>
            @error('address') <div class="tw-error-text">{{ $message }}</div> @enderror
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
            <button type="submit" class="tw-btn tw-btn-success px-5">
                <i class="bi bi-save"></i>Create Operator
            </button>
            <a href="{{ route('tfrb-officer.operators') }}" class="tw-btn tw-btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
