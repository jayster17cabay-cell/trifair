<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\User;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\ActivityLog;
use App\Helpers\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalDrivers = Driver::count();
        $activeDrivers = Driver::where('status', 'active')->count();
        $totalRatings = Rating::valid()->count();
        $averageRating = Rating::valid()->avg('rating');
        $totalComplaints = Rating::valid()->where('rating', '<=', 2)->count();
        $pendingReview = Rating::valid()->where('is_reviewed', false)->count();
        $totalTodas = Toda::count();

        $recentRatings = Rating::valid()->with(['driver.user', 'driver.toda'])
            ->latest()
            ->take(5)
            ->get();

        $recentComplaints = Rating::valid()->with(['driver.user', 'proofs'])
            ->where('rating', '<=', 2)
            ->latest()
            ->take(5)
            ->get();

        $topDrivers = Driver::with('user')
            ->select('drivers.*')
            ->selectRaw('(select avg("ratings"."rating") from "ratings" where "drivers"."id" = "ratings"."driver_id" and "start_location" is not null and "end_location" is not null) as valid_ratings_avg_rating')
            ->selectRaw('(select count(*) from "ratings" where "drivers"."id" = "ratings"."driver_id" and "start_location" is not null and "end_location" is not null) as valid_ratings_count')
            ->groupBy('drivers.id')
            ->havingRaw('(select count(*) from "ratings" where "drivers"."id" = "ratings"."driver_id" and "start_location" is not null and "end_location" is not null) > 0')
            ->orderByDesc('valid_ratings_avg_rating')
            ->take(5)
            ->get();

        $todaStats = Toda::with('drivers')
            ->withCount('drivers')
            ->get();

        foreach ($todaStats as $toda) {
            $toda->active_drivers_count = $toda->drivers->where('status', 'active')->count();
            $toda->avg_rating = Rating::whereIn('driver_id', $toda->drivers->pluck('id'))
                ->whereNotNull('start_location')
                ->whereNotNull('end_location')
                ->avg('rating');
        }

        return view('admin.dashboard', compact(
            'totalDrivers', 'activeDrivers', 'totalRatings', 'averageRating',
            'totalComplaints', 'pendingReview', 'recentRatings', 'recentComplaints',
            'topDrivers', 'totalTodas', 'todaStats'
        ));
    }

    public function drivers()
    {
        $drivers = Driver::with('user', 'toda')->latest()->paginate(10);
        return view('admin.drivers.index', compact('drivers'));
    }

    public function createDriver()
    {
        $todas = Toda::orderBy('name')->get();
        return view('admin.drivers.create', compact('todas'));
    }

    public function storeDriver(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'license_number' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'contact_number' => 'nullable|string|max:20',
            'plate_number' => 'nullable|string|max:20',
            'body_number' => 'nullable|string|max:20',
            'tricycle_color' => 'nullable|string|max:50',
            'toda_id' => 'required|exists:todas,id',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'driver',
            'phone' => $data['phone'] ?? null,
            'is_active' => true,
        ]);

        $qrCode = Str::random(32);

        $driver = Driver::create([
            'user_id' => $user->id,
            'license_number' => $data['license_number'] ?? null,
            'address' => $data['address'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'plate_number' => $data['plate_number'] ?? null,
            'body_number' => $data['body_number'] ?? null,
            'tricycle_color' => $data['tricycle_color'] ?? null,
            'qr_code' => $qrCode,
            'toda_id' => $data['toda_id'],
            'status' => 'active',
        ]);

        ActivityLogger::log('create_driver', "Created driver {$user->name} ({$user->email})", $driver, 'driver');

        return redirect()->route('admin.drivers')
            ->with('success', 'Driver created successfully.')
            ->with('qr_code', $qrCode)
            ->with('driver_name', $user->name);
    }

    public function editDriver(Driver $driver)
    {
        $driver->load('user', 'toda');
        $todas = Toda::orderBy('name')->get();
        return view('admin.drivers.edit', compact('driver', 'todas'));
    }

    public function updateDriver(Request $request, Driver $driver)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $driver->user_id,
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:20',
            'license_number' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'contact_number' => 'nullable|string|max:20',
            'plate_number' => 'nullable|string|max:20',
            'body_number' => 'nullable|string|max:20',
            'tricycle_color' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive',
            'toda_id' => 'required|exists:todas,id',
        ]);

        $driver->user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        if (!empty($data['password'])) {
            $driver->user->update(['password' => Hash::make($data['password'])]);
        }

        $driver->update([
            'license_number' => $data['license_number'] ?? null,
            'address' => $data['address'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'plate_number' => $data['plate_number'] ?? null,
            'body_number' => $data['body_number'] ?? null,
            'tricycle_color' => $data['tricycle_color'] ?? null,
            'status' => $data['status'],
            'toda_id' => $data['toda_id'],
        ]);

        ActivityLogger::log('update_driver', "Updated driver {$driver->user->name} ({$driver->user->email})", $driver, 'driver');

        return redirect()->route('admin.drivers')
            ->with('success', 'Driver updated successfully.');
    }

    public function destroyDriver(Driver $driver)
    {
        $driverName = $driver->user->name;
        $driver->user->delete();
        $driver->delete();

        ActivityLogger::log('delete_driver', "Deleted driver {$driverName}", null, 'driver');

        return redirect()->route('admin.drivers')
            ->with('success', 'Driver deleted successfully.');
    }

    public function showQrCode(Driver $driver)
    {
        $url = route('rate.driver', $driver->qr_code);
        return view('admin.drivers.qrcode', compact('driver', 'url'));
    }

    public function ratings()
    {
        $ratings = Rating::valid()->with(['driver.user', 'proofs', 'response'])
            ->latest()
            ->paginate(15);

        return view('admin.ratings', compact('ratings'));
    }

    public function reports()
    {
        $drivers = Driver::with('user')
            ->with(['validRatings' => function ($query) {
                $query->latest()->take(5);
            }])
            ->select('drivers.*')
            ->selectRaw('(select avg("ratings"."rating") from "ratings" where "drivers"."id" = "ratings"."driver_id" and "start_location" is not null and "end_location" is not null) as valid_ratings_avg_rating')
            ->selectRaw('(select count(*) from "ratings" where "drivers"."id" = "ratings"."driver_id" and "start_location" is not null and "end_location" is not null) as valid_ratings_count')
            ->groupBy('drivers.id')
            ->orderByDesc('valid_ratings_count')
            ->get();

        return view('admin.reports', compact('drivers'));
    }

    public function markReviewed(Rating $rating)
    {
        $rating->update(['is_reviewed' => true]);

        ActivityLogger::log('mark_reviewed', "Marked rating #{$rating->id} as reviewed (driver: {$rating->driver->user->name})", $rating, 'review');

        return back()->with('success', 'Rating marked as reviewed.');
    }

    public function activityLogs(Request $request)
    {
        $category = $request->query('category');

        $query = ActivityLog::with('user');

        if ($category && in_array($category, ['auth', 'admin', 'driver', 'review', 'system'])) {
            $query->where('category', $category);
        }

        $logs = $query->latestFirst()->paginate(20);

        return view('admin.activity-logs', compact('logs', 'category'));
    }

    public function todas()
    {
        $todas = Toda::withCount('drivers')
            ->latest()
            ->paginate(20);

        foreach ($todas as $toda) {
            $toda->active_drivers_count = $toda->drivers()->where('status', 'active')->count();
        }

        return view('admin.todas.index', compact('todas'));
    }
}
