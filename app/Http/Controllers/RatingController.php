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
     * One rating per operator per day, keyed on IP OR the signed client cookie.
     * The cookie layer makes the guard robust against IP spoofing/rotation
     * that the wildcard proxy trust setting on Render would otherwise allow.
     */
    private function existingRatingFor(Operator $operator, string $ip, string $clientId, bool $lock = false): ?Rating
    {
        $query = Rating::where('operator_id', $operator->id)
            ->whereDate('created_at', Carbon::now('Asia/Manila')->toDateString())
            ->where(function ($q) use ($ip, $clientId) {
                $q->where('passenger_ip', $ip)
                    ->orWhere('client_id', $clientId);
            })
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
        $existing = $this->existingRatingFor($operator, (string) $request->ip(), $clientId);

        if ($existing) {
            return redirect()->route('rate.submitted', $operator->qr_code)
                ->with('alreadyRated', true);
        }

        return view('rate.form', compact('operator'));
    }

    public function submitRating(Request $request, $qrCode)
    {
        $operator = Operator::where('qr_code', $qrCode)
            ->where('status', 'active')
            ->notArchived()
            ->firstOrFail();

        $clientId = $this->getOrCreateClientId($request);
        $existing = $this->existingRatingFor($operator, (string) $request->ip(), $clientId);

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
            'complaint_type' => 'nullable|string|max:100',
            'complaint_details' => 'nullable|string|max:2000',
        ];

        if ($request->has('rating') && (int) $request->input('rating') <= 2) {
            $rules['proofs'] = 'nullable|array|max:3';
            $rules['proofs.*'] = 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,avi,mov,pdf,doc,docx|max:20480';
        }

        $data = $request->validate($rules);

        $rating = DB::transaction(function () use ($request, $operator, $clientId, $data) {
            // Re-check inside the transaction (with a row lock) so two
            // simultaneous submissions cannot both pass the dedup check.
            $existing = $this->existingRatingFor($operator, (string) $request->ip(), $clientId, true);

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
                'passenger_name' => $data['passenger_name'] ?? null,
                'passenger_ip' => $request->ip(),
                'client_id' => $clientId,
                'is_auto' => false,
            ]);

            ActivityLogger::log('submit_rating', "Rating #{$rating->id} submitted (" . ($rating->rating <= 2 ? 'complaint' : $rating->rating . '-star') . ") for operator {$operator->user->name}", $rating, 'review');

            if ($request->hasFile('proofs')) {
                foreach ($request->file('proofs') as $file) {
                    if (!$file || !$file->isValid()) continue;
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $remotePath = $operator->qr_code . '/' . $filename;

                    $saved = false;
                    if (class_exists(\App\Helpers\SupabaseStorage::class)) {
                        $result = \App\Helpers\SupabaseStorage::upload($file, $remotePath);
                        if ($result) {
                            $saved = true;
                            $path = $remotePath;
                        }
                    }

                    if (!$saved) {
                        $dir = 'proofs/' . $operator->qr_code;
                        $file->storeAs($dir, $filename, 'public');
                        $path = $dir . '/' . $filename;
                    }

                    RatingProof::create([
                        'rating_id' => $rating->id,
                        'file_path' => $path,
                        'file_type' => $file->getMimeType(),
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

        return response()->view('rate.submitted', compact('operator'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}
