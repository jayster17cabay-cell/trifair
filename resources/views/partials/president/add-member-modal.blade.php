{{--
    Add Member modal (president). Onboards a new TODA member; the account is
    created as pending and still needs superadmin/TFRB approval. Opened by the
    [data-tw-modal-open="presidentAddMemberModal"] button in the members section.
--}}
<div id="presidentAddMemberModal" class="tw-modal-backdrop hidden">
    <div class="tw-modal" role="dialog" aria-modal="true" aria-labelledby="addMemberModalTitle">
        <div class="tw-modal-head">
            <div class="min-w-0 flex-1">
                <div id="addMemberModalTitle" class="truncate text-base font-bold text-slate-900">Add Member</div>
                <div class="truncate text-xs text-slate-500">The account will be created as <strong>pending</strong> and must be approved by superadmin/TFRB.</div>
            </div>
            <button type="button" class="tw-modal-close" data-tw-modal-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('president.members.store') }}">
            @csrf
            <div class="tw-modal-body">
                <div class="space-y-4">
                    <div>
                        <label for="addMemberName" class="tw-stat-label mb-1 block">Full name</label>
                        <input type="text" id="addMemberName" name="name" value="{{ old('name') }}" required maxlength="255" class="tw-input" placeholder="Juan Dela Cruz">
                    </div>
                    <div>
                        <label for="addMemberEmail" class="tw-stat-label mb-1 block">Email</label>
                        <input type="email" id="addMemberEmail" name="email" value="{{ old('email') }}" required maxlength="255" class="tw-input" placeholder="juan@example.com">
                    </div>
                    <div>
                        <label for="addMemberContact" class="tw-stat-label mb-1 block">Contact number</label>
                        <input type="text" id="addMemberContact" name="contact_number" value="{{ old('contact_number') }}" required maxlength="20" class="tw-input" placeholder="09171234567">
                    </div>
                    <div class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <label for="addMemberPlate" class="tw-stat-label mb-1 block">Plate number</label>
                            <input type="text" id="addMemberPlate" name="plate_number" value="{{ old('plate_number') }}" required maxlength="20" class="tw-input" placeholder="ABC 1234">
                        </div>
                        <div>
                            <label for="addMemberBody" class="tw-stat-label mb-1 block">Body number</label>
                            <input type="text" id="addMemberBody" name="body_number" value="{{ old('body_number') }}" required maxlength="20" class="tw-input" placeholder="TODA-01">
                        </div>
                    </div>
                    <div>
                        <label for="addMemberModel" class="tw-stat-label mb-1 block">Motorcycle model</label>
                        <input type="text" id="addMemberModel" name="motorcycle_model" value="{{ old('motorcycle_model') }}" required maxlength="100" class="tw-input" placeholder="Honda Click 125i">
                    </div>
                    <div class="flex items-start gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3.5 py-3 text-xs leading-relaxed text-blue-700">
                        <i class="bi bi-key mt-0.5 shrink-0"></i>
                        <span>The account starts with the default password <strong>{{ \App\Models\User::DEFAULT_OPERATOR_PASSWORD }}</strong>. The member will be required to change it the first time they sign in.</span>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-6 py-4">
                <button type="submit" class="tw-btn tw-btn-sm tw-btn-gold">
                    <i class="bi bi-person-plus"></i> Add Member
                </button>
                <button type="button" class="tw-btn tw-btn-sm tw-btn-ghost ml-auto" data-tw-modal-close>
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>
