<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\Rating;
use App\Models\RatingProof;
use App\Models\Notification;
use App\Models\User;

use App\Helpers\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RatingController extends Controller
{
    /**
     * Violation types offered to passengers on the QR report screen. The
     * president-filed flow keeps its own suspension-specific list; this one
     * covers both suspension and lapsed-franchise operators.
     */
    private const VIOLATION_TYPES = [
        'Operating while suspended',
        'Operating with expired franchise',
        'Operating while inactive',
        'Disregarding suspension order',
        'Others',
    ];

    /**
     * Resolve a stable, signed client identifier so a passenger can be
     * recognized even when their IP changes (e.g. mobile networks).
     * Laravel encrypts cookies via the EncryptCookies middleware.
     */
    private function getOrCreateClientId(Request $request): string
    {
        $clientId = (string) $request->cookie('tf_pid');

        if ($clientId === '') {
            $clientId = Str::random(40);
            Cookie::queue('tf_pid', $clientId, 60 * 24 * 365);
        }

        return $clientId;
    }

    /**
     * One rating per operator per day, identified by the passenger's device
     * cookie (client_id) only.
     *
     * The IP is deliberately NOT used for deduplication: passengers on the same
     * mobile carrier, terminal Wi-Fi or any shared/NAT network all share one
     * public IP, which used to flag everyone after the first person as
     * "already rated" even when they had not rated at all. The signed
     * client_id cookie is a stable per-device identifier instead.
     */
    private function existingRatingFor(Operator $operator, string $clientId, bool $lock = false): ?Rating
    {
        $query = Rating::where('operator_id', $operator->id)
            ->whereDate('created_at', Carbon::now('Asia/Manila')->toDateString())
            ->where('client_id', $clientId)
            ->orderBy('id');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function showRateForm(Request $request, $qrCode, $todaId = null)
    {
        $operator = $this->findRateableOperator($qrCode);
        $todas = $operator->getRateableTodas();

        // A live suspension hides its TODA from the passenger entirely: the
        // picker must only offer TODAs the operator may currently be rated
        // under. A TODA-less suspension hides every TODA.
        $availableTodas = $todas
            ->reject(fn ($toda) => $operator->isSuspendedForToda($toda->id))
            ->values();

        // An operator may serve several TODAs. When more than one applies, the
        // passenger must first pick which TODA this ride belongs to so the
        // rating is filed against the right franchise — and so a suspension in
        // one TODA does not wrongly block the others.
        $selectedToda = null;
        if ($todaId !== null) {
            // A directly-addressed TODA is validated against the full
            // membership list so an explicitly opened (but suspended) TODA
            // still explains itself instead of 404ing.
            $selectedToda = $todas->firstWhere('id', (int) $todaId);
            if (!$selectedToda) {
                abort(404);
            }
        } elseif ($availableTodas->count() === 1) {
            $selectedToda = $availableTodas->first();
        } elseif ($availableTodas->count() > 1) {
            return $this->selectToda($operator, $availableTodas);
        } elseif ($todas->isNotEmpty()) {
            // Every TODA this operator belongs to is under a live suspension —
            // skip the picker and go straight to the report screen.
            $onlyToda = $todas->count() === 1 ? $todas->first() : null;
            return $this->reportView($operator, $onlyToda, [
                'reason' => 'suspended',
                'suspension' => $operator->currentSuspensionForToda($onlyToda?->id),
            ]);
        }

        if ($block = $this->rateabilityBlock($operator, $selectedToda)) {
            return $block;
        }

        $clientId = $this->getOrCreateClientId($request);
        $existing = $this->existingRatingFor($operator, $clientId);

        if ($existing) {
            return redirect()->route('rate.submitted', $operator->qr_code)
                ->with('alreadyRated', true);
        }

        // A signed-in operator/staff account is not a passenger — surface a
        // clear notice instead of letting them (or their Gmail) rate.
        $user = $request->user();
        $staffBlocked = $user && !$user->isPassenger();

        return view('rate.form', compact('operator', 'staffBlocked', 'selectedToda'));
    }

    public function submitRating(Request $request, $qrCode)
    {
        $operator = $this->findRateableOperator($qrCode);
        $todas = $operator->getRateableTodas();

        $selectedToda = null;
        $requestedTodaId = $request->input('toda_id');
        if ($requestedTodaId) {
            $selectedToda = $todas->firstWhere('id', (int) $requestedTodaId);
            if (!$selectedToda) {
                abort(404);
            }
        } elseif ($todas->count() === 1) {
            $selectedToda = $todas->first();
        } elseif ($todas->count() > 1) {
            // The chosen TODA went missing from the POST (expired session,
            // back-button). Send the passenger back to pick one.
            return redirect()->route('rate.operator', $operator->qr_code);
        }

        if ($block = $this->rateabilityBlock($operator, $selectedToda)) {
            return $block;
        }

        // Operator and staff accounts can never submit a passenger rating, even
        // when they are signed in — the rest of the flow would otherwise treat
        // them as guests and their feedback would pollute the passenger data.
        $authUser = $request->user();
        if ($authUser && !$authUser->isPassenger()) {
            return back()->withErrors(['email' => 'Operator and staff accounts cannot submit passenger ratings. Please sign out first.']);
        }

        $clientId = $this->getOrCreateClientId($request);
        $existing = $this->existingRatingFor($operator, $clientId);

        if ($existing) {
            // Redirect (not a 200 view) so a page refresh cannot re-submit the form.
            return redirect()->route('rate.submitted', $operator->qr_code)
                ->with('alreadyRated', true);
        }

        $rules = [
            'rating' => 'required|integer|min:1|max:5',
            'reason' => 'nullable|string|max:1000',
            'start_location' => 'nullable|string|max:500',
            'end_location' => 'nullable|string|max:500',
            'passenger_name' => 'nullable|string|max:100',
            'passenger_contact' => 'nullable|string|max:20',
            'passenger_email' => 'nullable|email|max:255',
            'complaint_type' => 'nullable|string|max:100',
            'complaint_details' => 'nullable|string|max:2000',
        ];

        $passengerUser = $request->user() && $request->user()->isPassenger() ? $request->user() : null;

        // A complaint (1-2 stars) must be fully filled in before it can be
        // submitted: type, an explanation when "Others" is chosen, a contact
        // email (unless the signed-in Google account already provides one) and
        // at least one proof file. Incomplete complaints used to slip straight
        // into the admin queue, so the form-level rules are enforced here too.
        $isComplaint = (int) $request->input('rating') >= 1 && (int) $request->input('rating') <= 2;

        if ($isComplaint) {
            $rules['complaint_type'] = 'required|string|max:100';
            // "Describe your complaint" is a hidden textarea unless the type is
            // "Others", so ConvertEmptyStringsToNull leaves it null for every
            // other type. Without "nullable" that null fails the string rule and
            // the complaint is rejected — the details are only required for
            // "Others".
            $rules['complaint_details'] = 'nullable|required_if:complaint_type,Others|string|max:2000';
            $rules['proofs'] = 'required|array|min:1|max:3';
            $rules['proofs.*'] = 'file|mimes:jpg,jpeg,png,gif,mp4,avi,mov,pdf,doc,docx|max:20480';

            if (!$passengerUser || !$passengerUser->email) {
                $rules['passenger_email'] = 'required|email|max:255';
            }
        } elseif ($request->hasFile('proofs')) {
            // Validate proof files whenever they are actually uploaded (any star
            // rating), so the rules always match the upload loop below and a crafted
            // 3-5 star POST can no longer sneak unvalidated files into storage.
            $rules['proofs'] = 'nullable|array|max:3';
            $rules['proofs.*'] = 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,avi,mov,pdf,doc,docx|max:20480';
        }

        $data = $request->validate($rules);
        // Notifications (including status emails) go to the passenger's linked
        // Google account when they are signed in, so their complaint lands in
        // their own inbox. The typed field is only a fallback for guests.
        $passengerEmail = $passengerUser ? $passengerUser->email : ($data['passenger_email'] ?? null);

        // A guest who types an address that already belongs to a TriFair
        // passenger has that complaint filed straight into the account's list —
        // the same list the status updates are headed for — instead of waiting
        // for a later Google sign-in to claim it. Staff/operator addresses are
        // never picked up here; only passenger accounts qualify, and the
        // address is still what drives the emails either way.
        if (!$passengerUser && filled($passengerEmail)) {
            $match = User::query()
                ->where('role', 'passenger')
                ->where('is_active', true)
                ->whereRaw('LOWER(email) = ?', [strtolower((string) $passengerEmail)])
                ->first();

            if ($match) {
                $passengerUser = $match;
                $passengerEmail = $match->email;
            }
        }

        $rating = DB::transaction(function () use ($request, $operator, $clientId, $data, $passengerUser, $passengerEmail, $selectedToda) {
            // Re-check inside the transaction (with a row lock) so two
            // simultaneous submissions cannot both pass the dedup check.
            $existing = $this->existingRatingFor($operator, $clientId, true);

            if ($existing) {
                return null;
            }

            $rating = Rating::create([
                'operator_id' => $operator->id,
                'franchise_term_id' => $operator->termForToda($selectedToda?->id)?->id,
                'toda_id' => $selectedToda?->id,
                'rating' => $data['rating'],
                'complaint_type' => $data['complaint_type'] ?? null,
                'complaint_details' => $data['complaint_details'] ?? null,
                'reason' => $data['reason'] ?? null,
                'start_location' => Rating::normalizeAddress($data['start_location'] ?? null),
                'end_location' => Rating::normalizeAddress($data['end_location'] ?? null),
                'passenger_contact' => $data['passenger_contact'] ?? null,
                'passenger_email' => $passengerEmail,
                'passenger_user_id' => $passengerUser ? $passengerUser->id : null,
                'passenger_name' => $data['passenger_name'] ?? null,
                'passenger_ip' => $request->ip(),
                'client_id' => $clientId,
                'is_auto' => false,
            ]);

            ActivityLogger::log('submit_rating', "Rating #{$rating->id} submitted (" . ($rating->rating <= 2 ? 'complaint' : $rating->rating . '-star') . ") for operator {$operator->user->name}", $rating, 'review');

            if ($request->hasFile('proofs')) {
                foreach ($request->file('proofs') as $file) {
                    if (!$file || !$file->isValid()) continue;
                    $filename = time() . '_' . uniqid() . '.' . $file->extension();
                    $remotePath = $operator->qr_code . '/' . $filename;

                    $path = null;
                    try {
                        if (class_exists(\App\Helpers\SupabaseStorage::class)) {
                            $saved = \App\Helpers\SupabaseStorage::upload($file, $remotePath);
                            if ($saved) {
                                $path = $remotePath;
                            }
                        }

                        if (!$path) {
                            $dir = 'proofs/' . $operator->qr_code;
                            $file->storeAs($dir, $filename, 'public');
                            $path = $dir . '/' . $filename;
                        }
                    } catch (\Throwable $e) {
                        // A storage outage (Supabase down, disk full on the
                        // host) must never fail the complaint submission —
                        // same "guarded" policy as the confirmation email.
                        \Illuminate\Support\Facades\Log::error('Complaint proof save failed: ' . get_class($e) . ': ' . $e->getMessage(), ['path' => $remotePath]);
                        $path = null;
                    }

                    if (!$path) {
                        continue;
                    }

                    RatingProof::create([
                        'rating_id' => $rating->id,
                        'file_path' => $path,
                        'file_type' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                }
            }

            // Same validity rule as Rating::evaluateValidity() — both locations
            // required, plus proof for low ratings.
            $rating->update(['is_valid' => $rating->evaluateValidity()]);

            // Route a fresh complaint to its owner. The routing rule
            // (president-vs-president / unassigned → TFRB, member complaints
            // → the owning TODA's president) is encoded once in
            // ComplaintRoutingService.
            if ($rating->complaint_type) {
                $routedTo = app(\App\Services\ComplaintRoutingService::class)->assigneeId($operator, $selectedToda?->id);
                if ($routedTo !== null) {
                    $rating->update(['routed_to' => $routedTo]);
                }
            }

            if ($rating->is_valid) {
                if ($rating->rating <= 2) {
                    $routedTo = (int) ($rating->routed_to ?? 0);
                    $targets = $routedTo
                        ? User::where('id', $routedTo)->get()
                        : User::whereIn('role', ['tfrb_officer', 'superadmin'])->get();

                    $assigneeLabel = $routedTo ? 'Assigned to the TODA president.' : 'Assigned to TFRB / superadmin.';

                    foreach ($targets as $target) {
                        Notification::create([
                            'user_id' => $target->id,
                            'rating_id' => $rating->id,
                            'type' => 'complaint',
                            'title' => 'New Complaint Report',
                            'message' => "Operator {$operator->user->name} received a {$rating->rating}-star rating (" . ($rating->complaint_type ?? 'no type') . "). {$assigneeLabel} Contact: {$rating->passenger_contact}",
                        ]);
                    }
                } else {
                    $officers = User::whereIn('role', ['tfrb_officer', 'superadmin'])->get();
                    foreach ($officers as $officer) {
                        Notification::create([
                            'user_id' => $officer->id,
                            'rating_id' => $rating->id,
                            'type' => 'new_rating',
                            'title' => 'New Rating Received',
                            'message' => "Operator {$operator->user->name} received a {$rating->rating}-star rating from a passenger.",
                        ]);
                    }
                }
            }

            return $rating;
        });

        if ($rating === null) {
            // Concurrent duplicate slipped past the earlier (non-transactional)
            // check — treat it the same as a regular duplicate submission.
            return redirect()->route('rate.submitted', $operator->qr_code)
                ->with('alreadyRated', true);
        }

        // Complaints are no longer confirmed by email on submission — the
        // passenger only hears back once the case is solved or rejected.
        app(\App\Services\AdminDashboardService::class)->flush();

        return redirect()->route('rate.submitted', $operator->qr_code)
            ->with('rating_value', $data['rating'])
            ->with('passenger_name', $data['passenger_name'] ?? null);
    }

    /**
     * Decide whether the operator may be rated under the chosen TODA, and if
     * not, why. Returns ['reason' => ..., ...] or null when rating is allowed.
     * Suspensions tied to another TODA do not block this one; a TODA-less
     * suspension blocks every TODA.
     */
    private function rateabilityIssue(Operator $operator, $selectedToda): ?array
    {
        $todaId = $selectedToda?->id;

        $suspension = $operator->currentSuspensionForToda($todaId);
        if ($suspension && !$suspension->isExpired()) {
            return ['reason' => 'suspended', 'suspension' => $suspension];
        }

        // A lapsed franchise cannot collect new ratings until it is renewed.
        // When a TODA is chosen its own term is inspected; otherwise fall back
        // to the operator's current term (legacy single-TODA behaviour).
        if (($term = $operator->termForToda($todaId)) && $term->isExpired()) {
            return ['reason' => 'franchise', 'term' => $term];
        }

        // Inactive for a non-franchise reason (deactivated account): no ratings.
        if ($operator->status !== 'active' && $operator->status !== 'suspended') {
            return ['reason' => 'inactive'];
        }

        return null;
    }

    /**
     * Run the per-TODA availability checks shared by the form and the submit
     * action. Returns a passenger-facing response when rating is not allowed,
     * or null when the operator may be rated under the chosen TODA.
     *
     * Blocked operators get the report-operator screen instead of the star
     * rating: the map and trip context still render, but the 1-5 star input
     * is replaced by a violation report filed to the TFRB queue.
     */
    private function rateabilityBlock(Operator $operator, $selectedToda)
    {
        $issue = $this->rateabilityIssue($operator, $selectedToda);

        return $issue ? $this->reportView($operator, $selectedToda, $issue) : null;
    }

    /**
     * Passenger-facing TODA picker shown when an operator belongs to more than
     * one TODA, so the ride's rating is filed against the correct franchise.
     */
    private function selectToda(Operator $operator, $todas)
    {
        return response()
            ->view('rate.select-toda', compact('operator', 'todas'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Resolve the operator a QR code points at. Archived and not-yet-approved
     * (pending/rejected) accounts are hidden behind a 404 — inactive accounts
     * are returned so the caller can explain why rating is unavailable rather
     * than showing a bare "not found".
     */
    private function findRateableOperator(string $qrCode): Operator
    {
        $operator = Operator::with('user', 'toda')
            ->where('qr_code', $qrCode)
            ->notArchived()
            ->first();

        if (!$operator || !in_array($operator->status, ['active', 'inactive', 'suspended'], true)) {
            abort(404);
        }

        // A suspension that has run its course must not keep the QR blocked.
        $operator->syncSuspensionStatus();

        return $operator;
    }

    /**
     * Passenger-facing screen shown when a suspended / inactive operator's QR
     * is scanned: the trip map still renders, but the 1-5 star rating input is
     * replaced by a violation report that goes to the TFRB review queue. The
     * operator is attached to the report automatically.
     */
    private function reportView(Operator $operator, $selectedToda = null, ?array $issue = null)
    {
        $issue = $issue ?? $this->rateabilityIssue($operator, $selectedToda);
        $reason = $issue['reason'] ?? 'inactive';

        $user = request()->user();
        $linkedPassenger = $user && $user->isPassenger();

        return response()
            ->view('rate.report', [
                'operator' => $operator,
                'selectedToda' => $selectedToda,
                'reason' => $reason,
                'suspension' => $issue['suspension'] ?? null,
                'term' => $issue['term'] ?? null,
                'linkedPassenger' => $linkedPassenger,
                'violationTypes' => self::VIOLATION_TYPES,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * File a passenger violation report against a suspended / inactive
     * operator. The report lands in the same TFRB / superadmin review queue as
     * president-filed reports; the operator (and live suspension, when there
     * is one) is attached automatically.
     */
    public function submitReport(Request $request, $qrCode)
    {
        $operator = $this->findRateableOperator($qrCode);
        $todas = $operator->getRateableTodas();

        $selectedToda = null;
        $requestedTodaId = $request->input('toda_id');
        if ($requestedTodaId) {
            $selectedToda = $todas->firstWhere('id', (int) $requestedTodaId);
            if (!$selectedToda) {
                abort(404);
            }
        } elseif ($todas->count() === 1) {
            $selectedToda = $todas->first();
        }

        // The operator may have been reinstated since the page was rendered —
        // send the passenger to the normal rating flow instead of filing.
        if (!($issue = $this->rateabilityIssue($operator, $selectedToda))) {
            return redirect()->route('rate.operator', $operator->qr_code)
                ->with('status', 'This operator is active again — you can rate them now.');
        }

        // Google sign-in is required: the report is tied to the passenger's
        // account so they can monitor its progress in their dashboard — the
        // same way complaints work. Staff accounts can never file.
        $authUser = $request->user();
        if ($authUser && !$authUser->isPassenger()) {
            return back()->withErrors(['violation_type' => 'Operator and staff accounts cannot file passenger reports. Please sign out first.']);
        }
        $passengerUser = $authUser && $authUser->isPassenger() ? $authUser : null;

        if (!$passengerUser) {
            return back()->withErrors(['violation_type' => 'Please continue with Google to file your violation report.']);
        }

        $rules = [
            'violation_type' => 'required|string|max:100',
            // "Others" must say what the reason is — mirrors complaint_details.
            'other_reason' => 'nullable|required_if:violation_type,Others|string|max:100',
            'details' => 'required|string|max:2000',
            'incident_at' => 'required|date|before_or_equal:now',
            'start_location' => 'nullable|string|max:500',
            'end_location' => 'nullable|string|max:500',
            'toda_id' => 'nullable|integer',
            'proofs' => 'nullable|array|max:5',
            'proofs.*' => 'file|mimes:jpg,jpeg,png,pdf,mp4|max:10240',
        ];

        $data = $request->validate($rules);

        $toda = $selectedToda;
        $todaId = $toda?->id;
        $suspension = $operator->currentSuspensionForToda($todaId);

        $violation = app(\App\Services\SuspensionViolationService::class)->report(
            $operator,
            $toda,
            $passengerUser->id,
            [
                'suspension_id' => $suspension?->id,
                'violation_type' => $data['violation_type'],
                'other_reason' => $data['other_reason'] ?? null,
                'details' => $data['details'],
                'incident_at' => $data['incident_at'],
                'start_location' => Rating::normalizeAddress($data['start_location'] ?? null),
                'end_location' => Rating::normalizeAddress($data['end_location'] ?? null),
            ],
            $request->file('proofs', [])
        );

        // Let the TFRB queue know without leaking passenger details.
        try {
            $officers = User::whereIn('role', ['tfrb_officer', 'superadmin'])->get();
            foreach ($officers as $officer) {
                Notification::create([
                    'user_id' => $officer->id,
                    'type' => 'violation',
                    'title' => 'New Violation Report',
                    'message' => "A passenger reported operator {$operator->user->name} — {$violation->violation_type}.",
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Violation report notification failed: ' . $e->getMessage());
        }

        app(\App\Services\AdminDashboardService::class)->flush();

        return redirect()->route('rate.report.submitted', $operator->qr_code);
    }

    /**
     * Confirmation page shown after a passenger files a violation report.
     */
    public function reportSubmitted($qrCode)
    {
        $operator = Operator::with('user', 'toda')
            ->where('qr_code', $qrCode)
            ->firstOrFail();

        return response()->view('rate.report-submitted', compact('operator'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function showSubmitted($qrCode)
    {
        $operator = Operator::with('user', 'toda')
            ->where('qr_code', $qrCode)
            ->firstOrFail();

        // Where the Close button on the thank-you page should take the user:
        // signed-in passengers go straight back to "My Complaints".
        $exitUrl = auth()->check() && auth()->user()->isPassenger()
            ? route('passenger.dashboard')
            : '/';

        // Signed-in passengers get an extra "View All My Ratings" action that
        // opens the same complaints dashboard, where they can close the tab.
        $viewAllUrl = auth()->check() && auth()->user()->isPassenger()
            ? route('passenger.dashboard')
            : null;

        return response()->view('rate.submitted', compact('operator', 'exitUrl', 'viewAllUrl'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}
