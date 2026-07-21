@extends('layouts.admin')

@section('title', 'Drivers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Drivers</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Manage all registered tricycle drivers</p>
    </div>
    <a href="{{ route('admin.drivers.create') }}" class="btn btn-yellow">
        <i class="bi bi-person-plus me-1"></i> Add Driver
    </a>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        @if (session('qr_code'))
            <div class="mt-2 ps-3">
                <strong>Driver:</strong> {{ session('driver_name') }}<br>
                <a href="{{ route('rate.driver', session('qr_code')) }}" class="btn btn-sm btn-yellow mt-2">
                    <i class="bi bi-qr-code me-1"></i> View Rating Page
                </a>
            </div>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>TODA</th>
                        <th>Email</th>
                        <th>Plate #</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th class="text-center">QR Code</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($drivers as $driver)
                        <tr>
                            <td style="color: var(--gray-400); font-weight: 500;">{{ $loop->iteration + ($drivers->currentPage() - 1) * $drivers->perPage() }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background: var(--secondary-light); color: var(--secondary-dark); font-weight: 800; font-size: 0.85rem; flex-shrink: 0;">
                                        {{ strtoupper(substr($driver->user->name, 0, 1)) }}
                                    </div>
                                    <strong style="font-size: 0.9rem;">{{ $driver->user->name }}</strong>
                                </div>
                            </td>
                            <td style="font-size: 0.85rem;">
                                @if ($driver->toda)
                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                        <i class="bi bi-diagram-3 me-1"></i>{{ $driver->toda->name }}
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size: 0.8rem;">Unassigned</span>
                                @endif
                            </td>
                            <td style="font-size: 0.85rem; color: var(--gray-600);">{{ $driver->user->email }}</td>
                            <td style="font-size: 0.85rem; font-weight: 600;">{{ $driver->plate_number ?? 'N/A' }}</td>
                            <td style="font-size: 0.85rem;">{{ $driver->contact_number ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-{{ $driver->status === 'active' ? 'primary' : 'warning' }} bg-opacity-10 text-{{ $driver->status === 'active' ? 'primary' : 'warning' }}">
                                    <i class="bi bi-{{ $driver->status === 'active' ? 'check-circle' : 'x-circle' }} me-1"></i>
                                    {{ ucfirst($driver->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.drivers.qrcode', $driver) }}" class="btn btn-sm btn-outline-yellow" title="View QR Code">
                                    <i class="bi bi-qr-code"></i>
                                </a>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.drivers.edit', $driver) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.drivers.destroy', $driver) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this driver? This action cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; background: var(--gray-100);">
                                    <i class="bi bi-inbox" style="font-size: 2rem; color: var(--gray-300);"></i>
                                </div>
                                <p class="text-muted mb-0">No drivers found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($drivers->hasPages())
            <div class="px-3 py-3 border-top">
                {{ $drivers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
