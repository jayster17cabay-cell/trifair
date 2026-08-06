@extends('layouts.superadmin')

@section('title', 'Manage TFRB Officers')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title">TFRB Officer Management</h1>
        <p class="tw-page-sub">Manage TFRB Officers</p>
    </div>
    <a href="{{ route('superadmin.officers.create') }}" class="tw-btn tw-btn-gold">
        <i class="bi bi-shield-plus"></i>Add Officer
    </a>
</div>

<div class="tw-table-wrap">
    <div class="overflow-x-auto">
        <table class="tw-table">
            <thead>
                <tr>
                    <th class="tw-th">#</th>
                    <th class="tw-th">Name</th>
                    <th class="tw-th">Email</th>
                    <th class="tw-th">Status</th>
                    <th class="tw-th">Joined</th>
                    <th class="tw-th text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($officers as $officer)
                    <tr class="tw-tr-hover">
                        <td class="tw-td text-slate-500">{{ $loop->iteration + ($officers->currentPage() - 1) * $officers->perPage() }}</td>
                        <td class="tw-td">
                            <div class="flex items-center gap-2.5">
                                <div class="tw-avatar tw-avatar-sm bg-blue-50 text-navy-600">{{ strtoupper(substr($officer->name, 0, 1)) }}</div>
                                <strong class="text-sm">{{ $officer->name }}</strong>
                            </div>
                        </td>
                        <td class="tw-td text-sm text-slate-500">{{ $officer->email }}</td>
                        <td class="tw-td">
                            <span class="tw-badge tw-badge-navy"><i class="bi bi-check-circle"></i>Active</span>
                        </td>
                        <td class="tw-td text-sm text-slate-500">{{ $officer->created_at->format('M d, Y') }}</td>
                        <td class="tw-td text-right">
                            <form action="{{ route('superadmin.officers.destroy', $officer) }}" method="POST" onsubmit="return confirm('Remove this officer? They will lose all system access.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline text-red-600" title="Remove Officer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center">
                            <div class="tw-empty">
                                <div class="tw-empty-icon"><i class="bi bi-shield"></i></div>
                                <p class="text-sm text-slate-500">No officers found.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($officers->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $officers->links('pagination::tailwind') }}
        </div>
    @endif
</div>
@endsection
