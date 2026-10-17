{{-- Shared operator edit form body. Requires: $routePrefix, $operator, $todas --}}

<form action="{{ route($routePrefix . '.operators.update', $operator) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="mb-2 mt-1 text-xs font-bold uppercase tracking-wider text-navy-600">Assignment</div>
    <div class="mb-4">
        <label for="toda_id" class="tw-label">TODA <span class="text-red-600">*</span></label>
        <select class="tw-select @error('toda_id') is-invalid @enderror" id="toda_id" name="toda_id" required>
            <option value="">-- Select TODA --</option>
            @foreach ($todas as $toda)
                <option value="{{ $toda->id }}" {{ (string) old('toda_id', $operator->toda_id) === (string) $toda->id ? 'selected' : '' }}>{{ $toda->name }}{{ $toda->area ? " ({$toda->area})" : '' }}</option>
            @endforeach
        </select>
        @error('toda_id') <div class="tw-error-text" role="alert">{{ $message }}</div> @enderror
    </div>

    @php
        $activeMembershipIds = old(
            'toda_ids',
            $operator->todaMemberships
                ->where('status', \App\Models\OperatorToda::STATUS_ACTIVE)
                ->pluck('toda_id')
                ->map(fn ($id) => (int) $id)
                ->all()
        );
        $activeMembershipIds = is_array($activeMembershipIds) ? array_map('intval', $activeMembershipIds) : [];
        $openMembershipIds = $operator->todaMemberships
            ->whereIn('status', [\App\Models\OperatorToda::STATUS_PENDING, \App\Models\OperatorToda::STATUS_PENDING_ADMIN])
            ->pluck('toda_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    @endphp
    <div class="mb-4">
        <label class="tw-label">TODA Memberships</label>
        <p class="mb-2 text-xs text-slate-500">An operator keeps one account and body number; tick every TODA they may be rated under. The home TODA is always a member.</p>
        <div class="grid gap-2 rounded-lg border border-slate-200 p-3 sm:grid-cols-2">
            @foreach ($todas as $toda)
                @php
                    $isHome = (int) $operator->toda_id === (int) $toda->id;
                    $checked = $isHome || in_array((int) $toda->id, $activeMembershipIds, true) || in_array((int) $toda->id, $openMembershipIds, true);
                @endphp
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="toda_ids[]" value="{{ $toda->id }}" class="rounded border-slate-300 text-navy-600 focus:ring-navy-500"
                           {{ $checked ? 'checked' : '' }} {{ $isHome ? 'disabled' : '' }}>
                    <span>{{ $toda->name }}{{ $isHome ? ' (home)' : '' }}</span>
                    @if (in_array((int) $toda->id, $openMembershipIds, true) && !$isHome)
                        <span class="tw-badge tw-badge-amber">Pending</span>
                    @endif
                </label>
            @endforeach
        </div>
        @error('toda_ids') <div class="tw-error-text" role="alert">{{ $message }}</div> @enderror
        @error('toda_ids.*') <div class="tw-error-text" role="alert">{{ $message }}</div> @enderror
    </div>

    <div class="mb-2 mt-6 text-xs font-bold uppercase tracking-wider text-navy-600">Account Details</div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="name" class="tw-label">Full Name <span class="text-red-600">*</span></label>
            <input type="text" class="tw-input @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $operator->user->name) }}" required>
            @error('name') <div class="tw-error-text" role="alert">{{ $message }}</div> @enderror
        </div>
        <div>
            <label for="email" class="tw-label">Email Address <span class="text-red-600">*</span></label>
            <input type="email" class="tw-input @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $operator->user->email) }}" required>
            @error('email') <div class="tw-error-text" role="alert">{{ $message }}</div> @enderror
        </div>
        <div>
            <label for="password" class="tw-label">New Password</label>
            <input type="password" class="tw-input @error('password') is-invalid @enderror" id="password" name="password" placeholder="Leave empty to keep current">
            <small class="mt-1 block text-xs text-slate-500">Leave blank if you don't want to change.</small>
            @error('password') <div class="tw-error-text" role="alert">{{ $message }}</div> @enderror
        </div>
        <div>
            <label for="phone" class="tw-label">Phone Number</label>
            <input type="text" class="tw-input @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $operator->user->phone) }}">
            @error('phone') <div class="tw-error-text" role="alert">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mb-2 mt-6 text-xs font-bold uppercase tracking-wider text-navy-600">Motorcycle Details</div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label for="plate_number" class="tw-label">Plate Number</label>
            <input type="text" class="tw-input @error('plate_number') is-invalid @enderror" id="plate_number" name="plate_number" value="{{ old('plate_number', $operator->plate_number) }}" placeholder="e.g. ABC-123">
            @error('plate_number') <div class="tw-error-text" role="alert">{{ $message }}</div> @enderror
        </div>
        <div>
            <label for="body_number" class="tw-label">Body Number</label>
            <input type="text" class="tw-input @error('body_number') is-invalid @enderror" id="body_number" name="body_number" value="{{ old('body_number', $operator->body_number) }}" placeholder="e.g. 1234">
            @error('body_number') <div class="tw-error-text" role="alert">{{ $message }}</div> @enderror
        </div>
        <div>
            <label for="motorcycle_model" class="tw-label">Motorcycle Model</label>
            <input type="text" class="tw-input @error('motorcycle_model') is-invalid @enderror" id="motorcycle_model" name="motorcycle_model" value="{{ old('motorcycle_model', $operator->motorcycle_model) }}" placeholder="e.g. Honda Wave 125">
            @error('motorcycle_model') <div class="tw-error-text" role="alert">{{ $message }}</div> @enderror
        </div>
        <div>
            <label for="contact_number" class="tw-label">Contact Number</label>
            <input type="text" class="tw-input @error('contact_number') is-invalid @enderror" id="contact_number" name="contact_number" value="{{ old('contact_number', $operator->contact_number) }}">
            @error('contact_number') <div class="tw-error-text" role="alert">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mt-4">
        <label for="address" class="tw-label">Address</label>
        <textarea class="tw-textarea @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address', $operator->address) }}</textarea>
        @error('address') <div class="tw-error-text" role="alert">{{ $message }}</div> @enderror
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
        <button type="submit" class="tw-btn tw-btn-gold px-5">
            <i class="bi bi-save"></i>Update Operator
        </button>
        <a href="{{ route($routePrefix . '.operators') }}" class="tw-btn tw-btn-ghost">Cancel</a>
    </div>
</form>
