{{-- Shared activity-logs page body. Requires: $routePrefix, $logs, $category --}}

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-clock-history mr-2 text-amber-500"></i>Activity Logs</h1>
        <p class="tw-page-sub">Audit trail of all system actions</p>
    </div>
    <form method="GET" action="{{ route($routePrefix . '.activity-logs.export') }}" class="flex items-center gap-1.5">
        <input type="hidden" name="category" value="{{ $category }}">
        <select name="format" class="rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs font-medium text-slate-700 focus:border-navy focus:ring-1 focus:ring-navy">
            <option value="csv">CSV</option>
            <option value="word">Word</option>
            <option value="pdf">PDF</option>
        </select>
        <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline"><i class="bi bi-download"></i>Export</button>
    </form>
</div>

@php
    $categories = [
        '' => 'All',
        'auth' => 'Auth',
        'operator' => 'Operators',
        'tfrb_officer' => 'Officers',
        'review' => 'Reviews',
    ];
    $categoryColors = [
        'auth' => 'text-blue-600',
        'operator' => 'text-amber-600',
        'tfrb_officer' => 'text-emerald-600',
        'review' => 'text-emerald-600',
        'system' => 'text-slate-500',
    ];
    $categoryBgs = [
        'auth' => 'bg-blue-50',
        'operator' => 'bg-amber-50',
        'tfrb_officer' => 'bg-emerald-50',
        'review' => 'bg-emerald-50',
        'system' => 'bg-slate-100',
    ];
@endphp

<div class="mb-4 flex flex-wrap gap-2">
    @foreach ($categories as $key => $label)
        <a href="{{ $key ? route($routePrefix . '.activity-logs', ['category' => $key]) : route($routePrefix . '.activity-logs') }}"
           class="{{ ($category ?: '') === $key ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="tw-table-scroll-wrap">
    <table class="tw-table min-w-[44rem]">
        <thead class="tw-thead-sticky">
            <tr>
                <th class="tw-th w-12"></th>
                <th class="tw-th">User</th>
                <th class="tw-th">Description</th>
                <th class="tw-th">Action</th>
                <th class="tw-th">IP Address</th>
                <th class="tw-th text-right">Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                @php
                    $col = $categoryColors[$log->category] ?? 'text-slate-500';
                    $bg = $categoryBgs[$log->category] ?? 'bg-slate-100';
                @endphp
                    <tr class="tw-tr-hover">
                        <td class="tw-td text-center">
                            <div class="tw-avatar tw-avatar-sm {{ $bg }} {{ $col }} inline-flex text-sm">
                                @switch($log->action)
                                    @case('login') <i class="bi bi-box-arrow-in-right"></i> @break
                                    @case('logout') <i class="bi bi-box-arrow-right"></i> @break
                                    @case('create_operator') <i class="bi bi-person-plus"></i> @break
                                    @case('update_operator') <i class="bi bi-pencil"></i> @break
                                    @case('delete_operator') <i class="bi bi-person-x"></i> @break
                                    @case('create_tfrb_officer') <i class="bi bi-shield-plus"></i> @break
                                    @case('delete_tfrb_officer') <i class="bi bi-shield-x"></i> @break
                                    @case('mark_reviewed') <i class="bi bi-check-circle"></i> @break
                                    @case('submit_rating') <i class="bi bi-star"></i> @break
                                    @case('operator_respond') <i class="bi bi-chat-dots"></i> @break
                                    @case('update_operator_response') <i class="bi bi-chat-square-text"></i> @break
                                    @default <i class="bi bi-circle"></i>
                                @endswitch
                            </div>
                        </td>
                        <td class="tw-td"><span class="text-sm font-bold">{{ $log->user->name ?? 'Unknown' }}</span></td>
                        <td class="tw-td max-w-[300px] text-sm text-slate-500">{{ $log->description }}</td>
                        <td class="tw-td">
                            <span class="tw-badge {{ $bg }} {{ $col }}"><i class="bi bi-tag"></i>{{ str_replace('_', ' ', ucfirst($log->action)) }}</span>
                        </td>
                        <td class="tw-td text-xs text-slate-500">{{ $log->ip_address ?? '—' }}</td>
                        <td class="tw-td text-right text-xs whitespace-nowrap text-slate-500" title="{{ $log->created_at->format('M d, Y h:i A') }}">
                            {{ $log->created_at->diffForHumans() }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center">
                            <div class="tw-empty">
                                <div class="tw-empty-icon"><i class="bi bi-clock-history"></i></div>
                                <h3 class="tw-empty-title">No Activity Logs Yet</h3>
                                <p class="text-sm text-slate-500">System events and changes will appear here.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
        </tbody>
    </table>
</div>

@if ($logs->hasPages())
    <div class="mt-3">
        {{ $logs->withQueryString()->links('pagination::tailwind') }}
    </div>
@endif
