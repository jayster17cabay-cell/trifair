@extends('layouts.admin')

@section('title', 'TODAs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">TODAs</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">View all Tricycle Operators and Drivers Associations</p>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
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
                        <th>TODA Name</th>
                        <th>Area</th>
                        <th>Drivers</th>
                        <th>Active</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($todas as $toda)
                        <tr>
                            <td style="color: var(--gray-400); font-weight: 500;">{{ $loop->iteration + ($todas->currentPage() - 1) * $todas->perPage() }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background: var(--primary-50); color: var(--primary); font-weight: 800; font-size: 0.8rem; flex-shrink: 0;">
                                        <i class="bi bi-diagram-3"></i>
                                    </div>
                                    <strong style="font-size: 0.9rem;">{{ $toda->name }}</strong>
                                </div>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--gray-600);">{{ $toda->area ?? 'N/A' }}</td>
                            <td style="font-size: 0.85rem; font-weight: 600;">{{ $toda->drivers_count }}</td>
                            <td style="font-size: 0.85rem; font-weight: 600; color: var(--success);">{{ $toda->active_drivers_count }}</td>
                            <td>
                                <span class="badge bg-{{ $toda->is_active ? 'success' : 'secondary' }} bg-opacity-10 text-{{ $toda->is_active ? 'success' : 'secondary' }}">
                                    <i class="bi bi-{{ $toda->is_active ? 'check-circle' : 'x-circle' }} me-1"></i>
                                    {{ $toda->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; background: var(--gray-100);">
                                    <i class="bi bi-diagram-3" style="font-size: 2rem; color: var(--gray-300);"></i>
                                </div>
                                <p class="text-muted mb-0">No TODAs found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($todas->hasPages())
            <div class="px-3 py-3 border-top">
                {{ $todas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
