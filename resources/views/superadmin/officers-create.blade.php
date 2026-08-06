@extends('layouts.superadmin')

@section('title', 'Add TFRB Officer')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title">Add New TFRB Officer</h1>
        <p class="tw-page-sub">Create a new TFRB Officer account</p>
    </div>
    <a href="{{ route('superadmin.officers') }}" class="tw-btn tw-btn-outline">
        <i class="bi bi-arrow-left"></i>Back
    </a>
</div>

<div class="tw-card tw-card-pad max-w-2xl">
    <form action="{{ route('superadmin.officers.store') }}" method="POST">
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
                <label for="password" class="tw-label">Password</label>
                <input type="password" class="tw-input @error('password') is-invalid @enderror" id="password" name="password" required placeholder="Min. 8 characters">
                @error('password') <div class="tw-error-text">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="mt-6 border-t border-slate-100 pt-4">
            <button type="submit" class="tw-btn tw-btn-gold px-5">
                <i class="bi bi-shield-plus"></i>Create Officer
            </button>
        </div>
    </form>
</div>
@endsection
