@extends('layouts.superadmin')

@section('title', 'Add TODA President')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title">Add New TODA President</h1>
        <p class="tw-page-sub">Create a president account and assign them to a TODA</p>
    </div>
    <a href="{{ route('superadmin.presidents') }}" class="tw-btn tw-btn-outline">
        <i class="bi bi-arrow-left"></i>Back
    </a>
</div>

<div class="tw-card tw-card-pad max-w-2xl">
    <form action="{{ route('superadmin.presidents.store') }}" method="POST">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="tw-label">Full Name</label>
                <input type="text" class="tw-input @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="Enter full name">
                @error('name') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="email" class="tw-label">Email Address</label>
                <input type="email" class="tw-input @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required placeholder="Enter email address">
                @error('email') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="toda_id" class="tw-label">TODA to Oversee</label>
                <select class="tw-input @error('toda_id') is-invalid @enderror" id="toda_id" name="toda_id" required>
                    <option value="" disabled {{ old('toda_id') ? '' : 'selected' }}>Select a TODA…</option>
                    @foreach ($todas as $toda)
                        <option value="{{ $toda->id }}" @selected((int) old('toda_id') === $toda->id)>
                            {{ $toda->name }}{{ $toda->area ? ' — ' . $toda->area : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">The president can only see members belonging to this TODA.</p>
                @error('toda_id') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="password" class="tw-label">Password</label>
                <input type="password" class="tw-input @error('password') is-invalid @enderror" id="password" name="password" required placeholder="Min. 8 characters">
                @error('password') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="phone" class="tw-label">Phone Number</label>
                <input type="text" class="tw-input @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="Enter phone number">
                @error('phone') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="mt-6 border-t border-slate-100 pt-4">
            <button type="submit" class="tw-btn tw-btn-gold px-5">
                <i class="bi bi-award"></i>Create President
            </button>
        </div>
    </form>
</div>
@endsection
