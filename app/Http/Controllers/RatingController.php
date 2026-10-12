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

    public function showRateForm(Request $request, $qrCode)
    {
        $operator = Operator::with('user', 'toda')
            ->where('qr_code', $qrCode)
            ->where('status', 'active')
            ->notArchived()
            ->firstOrFail();

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

        return view('rate.form', compact('operator', 'staffBlocked'));
    }

    public function submitRating(Request $request, $qrCode)
    {
        $operator = Operator::with('user', 'toda')
            ->where('qr_code', $qrCode)
            ->where('status', 'active')
            ->notArchived()
            ->firstOrFail();

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

        $rating = DB::transaction(function () use ($request, $operator, $clientId, $data, $passengerUser, $passengerEmail) {
            // Re-check inside the transaction (with a row lock) so two
            // simultaneous submissions cannot both pass the dedup check.
            $existing = $this->existingRatingFor($operator, $clientId, true);

            if ($existing) {
                return null;
            }

            $rating = Rating::create([
                'operator_id' => $operator->id,
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

            if ($rating->is_valid) {
                $officers = User::whereIn('role', ['tfrb_officer', 'superadmin'])->get();
                foreach ($officers as $officer) {
                    if ($rating->rating <= 2) {
                        Notification::create([
                            'user_id' => $officer->id,
                            'rating_id' => $rating->id,
                            'type' => 'complaint',
                            'title' => 'New Complaint Report',
                            'message' => "Operator {$operator->user->name} received a {$rating->rating}-star rating (" . ($rating->complaint_type ?? 'no type') . "). Contact: {$rating->passenger_contact}",
                        ]);
                    } else {
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

        // Instant confirmation email: the moment a valid complaint lands, the
        // passenger gets notified on the same address they will later receive
        // the "reviewed" and "solved" updates on. An invalid complaint (missing
        // route data) never reaches the operators, so we do not confirm it as
        // submitted. A delivery failure must never break the submission.
        if ((int) $data['rating'] <= 2 && $passengerEmail && $rating->is_valid) {
            try {
                \Illuminate\Support\Facades\Mail::to($passengerEmail)
                    ->send(new \App\Mail\ComplaintStatus($rating, 'submitted'));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Complaint submitted email failed: ' . get_class($e) . ': ' . $e->getMessage());
            }
        }

        app(\App\Services\AdminDashboardService::class)->flush();

        return redirect()->route('rate.submitted', $operator->qr_code)
            ->with('rating_value', $data['rating'])
            ->with('passenger_name', $data['passenger_name'] ?? null);
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
