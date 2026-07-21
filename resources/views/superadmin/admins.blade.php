@extends('layouts.superadmin')

@section('title', 'Manage Admins')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Admin Management</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Manage system administrators</p>
    </div>
    <a href="{{ route('superadmin.admins.create') }}" class="btn btn-yellow">
        <i class="bi bi-shield-plus me-1"></i> Add Admin
    </a>
</div>

<div class="card card-accent-blue">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($admins as $admin)
                        <tr>
                            <td style="color: var(--gray-400); font-weight: 500;">{{ $loop->iteration + ($admins->currentPage() - 1) * $admins->perPage() }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background: var(--primary-50); color: var(--primary); font-weight: 800; font-size: 0.85rem;">
                                        {{ strtoupper(substr($admin->name, 0, 1)) }}
                                    </div>
                                    <strong style="font-size: 0.9rem;">{{ $admin->name }}</strong>
                                </div>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--gray-600);">{{ $admin->email }}</td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary">
                                    <i class="bi bi-check-circle me-1"></i> Active
                                </span>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--gray-600);">{{ $admin->created_at->format('M d, Y') }}</td>
                            <td class="text-end">
                                <form action="{{ route('superadmin.admins.destroy', $admin) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Remove this admin? They will lose all system access.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove Admin">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; background: var(--gray-100);">
                                    <i class="bi bi-shield" style="font-size: 2rem; color: var(--gray-300);"></i>
                                </div>
                                <p class="text-muted mb-0">No admins found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($admins->hasPages())
            <div class="px-3 py-3 border-top">
                {{ $admins->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
