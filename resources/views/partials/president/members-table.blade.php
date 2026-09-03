{{--
    Members table for the president's TODA.
    Requires: $members (paginator with user relation, ratings_count, complaint_count).
--}}
@if ($members->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Member</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Body #</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Rating</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Trips</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Complaints</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @foreach ($members as $member)
                    @php
                        $memberAvg = $member->ratings()->isValid()->avg('rating');
                        $statusColor = [
                            'active' => 'tw-badge-green',
                            'inactive' => 'tw-badge-gray',
                            'pending' => 'tw-badge-blue',
                            'rejected' => 'tw-badge-red',
                        ][$member->status] ?? 'tw-badge-blue';
                    @endphp
                    <tr class="transition-colors hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="tw-avatar tw-avatar-sm bg-navy-700 text-white">{{ strtoupper(substr($member->user->name, 0, 1)) }}</div>
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-slate-800">{{ $member->user->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $member->plate_number }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $member->body_number ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="text-sm font-bold text-navy-800">{{ $memberAvg ? number_format($memberAvg, 1) : '0.0' }}</span>
                            <div class="flex gap-0.5">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="bi {{ $i <= round((float) $memberAvg) ? 'bi-star-fill text-gold' : 'bi-star text-slate-200' }} text-xs"></i>
                                @endfor
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $member->ratings_count }}</td>
                        <td class="px-4 py-3">
                            @if ($member->complaint_count > 0)
                                <span class="tw-badge tw-badge-red">{{ $member->complaint_count }}</span>
                            @else
                                <span class="text-sm text-slate-400">0</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3"><span class="tw-badge {{ $statusColor }}">{{ ucfirst($member->status) }}</span></td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <button type="button" class="tw-btn tw-btn-gold px-3 py-1.5 text-xs" data-president-member="{{ $member->id }}" data-url="{{ $memberDetailUrl }}/{{ $member->id }}"><i class="bi bi-eye mr-1"></i> View Ratings</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 bg-slate-50/60 px-4 py-3">
        {{ $members->links('pagination::tailwind') }}
    </div>
@else
    <div class="flex flex-col items-center justify-center px-4 py-10 text-center">
        <div class="tw-empty-icon"><i class="bi bi-people"></i></div>
        <h4 class="text-sm font-bold text-slate-700">No members found</h4>
        <p class="mt-1 text-sm text-slate-400">No members match the current search, or your TODA has no members yet.</p>
    </div>
@endif
