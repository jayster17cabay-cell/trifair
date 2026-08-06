@extends('layouts.superadmin')

@section('title', 'TODA')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title">TODA</h1>
        <p class="tw-page-sub">Tricycle Operators and Drivers Association management</p>
    </div>
    <a href="{{ route('superadmin.todas.create') }}" class="tw-btn tw-btn-gold">
        <i class="bi bi-plus-circle"></i>Add TODA
    </a>
</div>

<div class="tw-table-wrap">
    <div class="overflow-x-auto">
        <table class="tw-table">
            <thead>
                <tr>
                    <th class="tw-th">#</th>
                    <th class="tw-th">TODA Name</th>
                    <th class="tw-th">Area</th>
                    <th class="tw-th">Drivers</th>
                    <th class="tw-th">Active</th>
                    <th class="tw-th">Status</th>
                    <th class="tw-th text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($todas as $toda)
                    <tr class="tw-tr-hover">
                        <td class="tw-td text-slate-500">{{ $loop->iteration + ($todas->currentPage() - 1) * $todas->perPage() }}</td>
                        <td class="tw-td">
                            <div class="flex items-center gap-2.5">
                                <div class="tw-avatar tw-avatar-sm bg-blue-50 text-navy-600"><i class="bi bi-diagram-3"></i></div>
                                <span class="cursor-pointer text-sm font-bold text-navy-600 underline underline-offset-2 hover:text-navy-700"
                                      onclick="showTodaMembers({{ $toda->id }}, @js($toda->name))">{{ $toda->name }}</span>
                            </div>
                        </td>
                        <td class="tw-td text-sm text-slate-500">{{ $toda->area ?? 'N/A' }}</td>
                        <td class="tw-td text-sm font-semibold">{{ $toda->operators_count }}</td>
                        <td class="tw-td text-sm font-semibold text-emerald-600">{{ $toda->active_operators_count }}</td>
                        <td class="tw-td">
                            @if ($toda->is_active)
                                <span class="tw-badge tw-badge-green"><i class="bi bi-check-circle"></i>Active</span>
                            @else
                                <span class="tw-badge tw-badge-gray"><i class="bi bi-x-circle"></i>Inactive</span>
                            @endif
                        </td>
                        <td class="tw-td text-right">
                            <div class="inline-flex gap-1.5">
                                <a href="{{ route('superadmin.todas.edit', $toda) }}" class="tw-btn tw-btn-sm tw-btn-outline" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('superadmin.todas.destroy', $toda) }}" method="POST" onsubmit="return confirm('Delete this TODA? Drivers must be reassigned first.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline text-red-600" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center">
                            <div class="tw-empty">
                                <div class="tw-empty-icon"><i class="bi bi-diagram-3"></i></div>
                                <p class="text-sm text-slate-500">No TODA yet. Create one to organize your operators.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($todas->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $todas->links('pagination::tailwind') }}
        </div>
    @endif
</div>

@include('partials.toda-members-modal', ['membersUrl' => url('/superadmin/toda')])
@endsection
