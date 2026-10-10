{{--
    Reusable, polished Account Settings panel used by Superadmin, TFRB Officer
    and Operator roles. Requires:
    - $routePrefix string  'superadmin' | 'tfrb-officer' | 'operator'
    - $roleLabel  string   e.g. 'Superadmin', 'TFRB Officer', 'Operator'
    - $operator   ?App\Models\Operator  (extra operator rows when present)
--}}
@php
    $user = Auth::user();
    $roleLabel = $roleLabel ?? ucwords(str_replace(['_', '-'], ' ', $user->role));
    $initial = strtoupper(substr(trim($user->name), 0, 1)) ?: '?';
    $infoRows = collect()
        ->push(['icon' => 'bi-person', 'label' => 'Full Name', 'value' => $user->name])
        ->push(['icon' => 'bi-shield-check', 'label' => 'Role', 'value' => $roleLabel])
        ->push(['icon' => 'bi-envelope', 'label' => 'Email', 'value' => $user->email])
        ->push(['icon' => 'bi-phone', 'label' => 'Contact Number', 'value' => $user->phone ?: 'Not set'])
        ->push(['icon' => 'bi-calendar-check', 'label' => 'Member Since', 'value' => $user->created_at ? $user->created_at->format('M d, Y') : '—']);
    if ($operator) {
        $infoRows = $infoRows
            ->push(['icon' => 'bi-person-vcard', 'label' => 'License Number', 'value' => $operator->license_number ?: 'Not set'])
            ->push(['icon' => 'bi-hash', 'label' => 'Body Number', 'value' => $operator->body_number ?: 'Not set'])
            ->push(['icon' => 'bi-speedometer2', 'label' => 'Plate Number', 'value' => $operator->plate_number ?: 'Not set'])
            ->push(['icon' => 'bi-bricks', 'label' => 'TODA', 'value' => $operator->toda ? $operator->toda->name : 'Unassigned']);
    }
@endphp

{{-- Profile hero --}}
<div class="tw-card mb-5 overflow-hidden">
    <div class="relative overflow-hidden bg-gradient-to-br from-navy-800 via-navy-700 to-navy-900 px-5 py-6 text-white sm:px-6">
        <div class="pointer-events-none absolute -right-12 -top-16 h-52 w-52 rounded-full bg-white/5"></div>
        <div class="pointer-events-none absolute -bottom-20 right-28 h-44 w-44 rounded-full bg-gold/10"></div>

        <div class="relative flex flex-wrap items-center gap-4">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-gold to-amber-400 text-2xl font-black text-navy-900 shadow-lg sm:h-[4.5rem] sm:w-[4.5rem] sm:text-3xl">
                {{ $initial }}
            </div>
            <div class="min-w-0 flex-1">
                <h2 class="truncate text-xl font-bold leading-tight sm:text-2xl">{{ $user->name }}</h2>
                <p class="mt-0.5 truncate text-sm text-navy-200">{{ $user->email }}</p>
                <span class="mt-2.5 inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold ring-1 ring-white/10">
                    <i class="bi bi-shield-check"></i>{{ $roleLabel }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 divide-x divide-slate-100 border-t border-slate-100 sm:grid-cols-4">
        <div class="px-5 py-4">
            <div class="tw-stat-label"><i class="bi bi-calendar-check mr-1 text-navy-500"></i>Member since</div>
            <div class="mt-0.5 text-sm font-bold text-slate-800">{{ $user->created_at ? $user->created_at->format('M d, Y') : '—' }}</div>
        </div>
        <div class="px-5 py-4">
            <div class="tw-stat-label"><i class="bi bi-activity mr-1 text-navy-500"></i>Account status</div>
            <div class="mt-0.5 flex items-center gap-1.5 text-sm font-bold {{ $user->is_active ? 'text-emerald-600' : 'text-red-500' }}">
                <i class="bi bi-{{ $user->is_active ? 'check-circle-fill' : 'x-circle-fill' }}"></i>{{ $user->is_active ? 'Active' : 'Disabled' }}
            </div>
        </div>
        <div class="px-5 py-4">
            <div class="tw-stat-label"><i class="bi bi-phone mr-1 text-navy-500"></i>Contact</div>
            <div class="mt-0.5 text-sm font-bold text-slate-800">{{ $user->phone ?? '—' }}</div>
        </div>
        <div class="px-5 py-4">
            <div class="tw-stat-label"><i class="bi bi-patch-check mr-1 text-navy-500"></i>Email verified</div>
            <div class="mt-0.5 text-sm font-bold {{ $user->email_verified_at ? 'text-emerald-600' : 'text-amber-600' }}">
                {{ $user->email_verified_at ? 'Verified' : 'Unverified' }}
            </div>
        </div>
    </div>
</div>

<div class="grid gap-5 lg:grid-cols-2">
    {{-- Account information --}}
    <div class="tw-card overflow-hidden self-start">
        <div class="tw-card-pad border-b border-slate-100">
            <h5 class="tw-card-title"><i class="bi bi-person-vcard mr-2 text-navy-600"></i>Account Information</h5>
            <p class="text-xs text-slate-500">Your details in the {{ config('app.name') }} system.</p>
        </div>
        <div class="tw-card-pad">
            <div class="overflow-hidden rounded-xl border border-slate-100">
                @foreach ($infoRows as $i => $row)
                    <div class="flex items-center justify-between gap-3 px-4 py-3 {{ $i % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' }}">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-navy-50 text-sm text-navy-600">
                                <i class="bi {{ $row['icon'] }}"></i>
                            </span>
                            <span class="truncate text-sm font-medium text-slate-500">{{ $row['label'] }}</span>
                        </div>
                        <span class="truncate text-right text-sm font-bold text-slate-800">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Change password --}}
    <div class="tw-card overflow-hidden self-start">
        <div class="tw-card-pad border-b border-slate-100">
            <h5 class="tw-card-title"><i class="bi bi-shield-lock-fill mr-2 text-navy-600"></i>Change Password</h5>
            <p class="text-xs text-slate-500">Keep your account secure by updating it regularly.</p>
        </div>
        <div class="tw-card-pad">
            <form action="{{ route($routePrefix . '.settings.password') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="settings_current_password" class="tw-label">Current Password</label>
                    <div class="tw-input-group">
                        <input type="password" name="current_password" id="settings_current_password"
                            class="tw-input @error('current_password') is-invalid @enderror"
                            placeholder="Enter current password" required autocapitalize="none" autocomplete="current-password">
                        <button type="button" data-pw-toggle="#settings_current_password" class="inline-flex items-center px-3.5 text-slate-400 transition hover:text-navy-600" tabindex="-1" aria-label="Toggle password visibility">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @error('current_password')
                        <span class="tw-error-text">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="settings_new_password" class="tw-label">New Password</label>
                    <div class="tw-input-group">
                        <input type="password" name="new_password" id="settings_new_password"
                            class="tw-input @error('new_password') is-invalid @enderror"
                            placeholder="At least 8 characters" required autocomplete="new-password">
                        <button type="button" data-pw-toggle="#settings_new_password" class="inline-flex items-center px-3.5 text-slate-400 transition hover:text-navy-600" tabindex="-1" aria-label="Toggle password visibility">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @error('new_password')
                        <span class="tw-error-text">{{ $message }}</span>
                    @enderror

                    {{-- Live strength meter (progressive enhancement) --}}
                    <div id="settings-pw-meter" class="mt-2 hidden">
                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-[0.65rem] font-bold uppercase tracking-widest text-slate-400">Password strength</span>
                            <span id="settings-pw-label" class="text-[0.7rem] font-bold text-slate-500"></span>
                        </div>
                        <div class="flex gap-1">
                            @for ($i = 0; $i < 4; $i++)
                                <span class="pw-bar h-1.5 flex-1 rounded-full bg-slate-200 transition-colors"></span>
                            @endfor
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="settings_new_password_confirmation" class="tw-label">Confirm New Password</label>
                    <div class="tw-input-group">
                        <input type="password" name="new_password_confirmation" id="settings_new_password_confirmation"
                            class="tw-input"
                            placeholder="Re-enter new password" required autocomplete="new-password">
                        <button type="button" data-pw-toggle="#settings_new_password_confirmation" class="inline-flex items-center px-3.5 text-slate-400 transition hover:text-navy-600" tabindex="-1" aria-label="Toggle password visibility">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="tw-btn tw-btn-gold w-full tw-btn-lg">
                    <i class="bi bi-check-lg"></i> Update Password
                </button>

                <div class="mt-4 flex items-start gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3.5 py-3 text-xs leading-relaxed text-blue-700">
                    <i class="bi bi-lightbulb mt-0.5 shrink-0"></i>
                    <span>Use at least 8 characters with a mix of upper &amp; lower case, numbers and symbols. Never reuse this password on other accounts.</span>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        var input = document.getElementById('settings_new_password');
        var meter = document.getElementById('settings-pw-meter');
        var label = document.getElementById('settings-pw-label');
        if (!input || !meter || !label) return;
        var bars = meter.querySelectorAll('.pw-bar');

        var COLORS = [
            { bar: 'bg-red-500', txt: 'text-red-600', label: 'Weak' },
            { bar: 'bg-red-500', txt: 'text-red-600', label: 'Weak' },
            { bar: 'bg-amber-400', txt: 'text-amber-600', label: 'Fair' },
            { bar: 'bg-emerald-500', txt: 'text-emerald-600', label: 'Good' },
            { bar: 'bg-emerald-500', txt: 'text-emerald-600', label: 'Strong' },
        ];

        function strength(value) {
            var score = 0;
            if (value.length >= 8) score++;
            if (value.length >= 12) score++;
            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
            if (/\d/.test(value)) score++;
            if (/[^A-Za-z0-9]/.test(value)) score++;
            return Math.min(4, Math.max(0, score));
        }

        function clearClasses(el) {
            el.classList.remove('bg-red-500', 'bg-amber-400', 'bg-emerald-500', 'bg-slate-200');
        }

        input.addEventListener('input', function () {
            var v = input.value;
            if (v.length === 0) {
                meter.classList.add('hidden');
                return;
            }
            meter.classList.remove('hidden');
            var level = strength(v);
            var palette = COLORS[level];
            label.textContent = palette.label;
            label.className = 'text-[0.7rem] font-bold ' + palette.txt;
            bars.forEach(function (bar, i) {
                clearClasses(bar);
                bar.classList.add(i < level ? palette.bar : 'bg-slate-200');
            });
        });
    })();
</script>