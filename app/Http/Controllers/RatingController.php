<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Rating;
use App\Models\RatingProof;
use App\Models\Notification;
use App\Models\User;
use App\Helpers\ActivityLogger;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RatingController extends Controller
{
    public function showRateForm($qrCode)
    {
        $driver = Driver::with('user')
            ->where('qr_code', $qrCode)
            ->where('status', 'active')
            ->firstOrFail();

        $ip = request()->ip();
        $today = Carbon::today();

        // Check if this IP already rated this driver today (auto or manual)
        $existing = Rating::where('driver_id', $driver->id)
            ->where('passenger_ip', $ip)
            ->whereDate('created_at', $today)
            ->first();

        if ($existing) {
            return view('rate.form', compact('driver'))
                ->with('alreadyRated', true)
                ->with('existingRating', $existing);
        }

        // Auto-create 5-star rating on scan
        $autoRating = Rating::create([
            'driver_id' => $driver->id,
            'rating' => 5,
            'passenger_ip' => $ip,
            'is_auto' => true,
        ]);

        return view('rate.form', compact('driver'))
            ->with('autoRating', $autoRating);
    }

    public function submitRating(Request $request, $qrCode)
    {
        $driver = Driver::where('qr_code', $qrCode)
            ->where('status', 'active')
            ->firstOrFail();

        $ip = $request->ip();
        $today = Carbon::today();

        // Find the auto-rating for this driver/IP/today
        $rating = Rating::where('driver_id', $driver->id)
            ->where('passenger_ip', $ip)
            ->where('is_auto', true)
            ->whereDate('created_at', $today)
            ->first();

        if (!$rating) {
            return back()->with('error', 'No pending rating found. Please scan the QR code again.');
        }

        $rules = [
            'rating' => 'required|integer|min:1|max:5',
            'reason' => 'nullable|string|max:1000',
            'start_location' => 'nullable|string|max:500',
            'end_location' => 'nullable|string|max:500',
            'passenger_name' => 'nullable|string|max:100',
        ];

        if ($request->rating <= 2) {
            $rules['proofs'] = 'required|array|min:1';
            $rules['proofs.*'] = 'required|file|mimes:jpg,jpeg,png,gif,mp4,avi,mov,pdf,doc,docx|max:20480';
            $rules['passenger_contact'] = 'nullable|string|max:20';
        } else {
            $rules['proofs'] = 'nullable|array';
            $rules['proofs.*'] = 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,avi,mov,pdf,doc,docx|max:20480';
            $rules['passenger_contact'] = 'nullable|string|max:20';
        }

        $data = $request->validate($rules);

        $rating->update([
            'rating' => $data['rating'],
            'reason' => $data['reason'] ?? null,
            'start_location' => $data['start_location'] ?? null,
            'end_location' => $data['end_location'] ?? null,
            'passenger_contact' => $data['passenger_contact'] ?? null,
            'passenger_name' => $data['passenger_name'] ?? null,
            'is_auto' => false,
        ]);

        ActivityLogger::log('submit_rating', "Rating #{$rating->id} submitted (" . ($rating->rating <= 2 ? 'complaint' : $rating->rating . '-star') . ") for driver {$driver->user->name}", $rating, 'review');

        if ($request->hasFile('proofs')) {
            foreach ($request->file('proofs') as $file) {
                $path = $file->store('proofs/' . $driver->qr_code, 'public');
                RatingProof::create([
                    'rating_id' => $rating->id,
                    'file_path' => $path,
                    'file_type' => $file->getMimeType(),
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        if ($rating->rating <= 2) {
            $admins = User::whereIn('role', ['admin', 'superadmin'])->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'rating_id' => $rating->id,
                    'type' => 'complaint',
                    'title' => 'New Complaint Report',
                    'message' => "Driver {$driver->user->name} received a {$rating->rating}-star rating with proof attached. Contact: {$rating->passenger_contact}",
                ]);
            }
        }

        return back()->with('success', 'Thank you for your feedback!');
    }
}
