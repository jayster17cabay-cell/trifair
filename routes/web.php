<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SuperadminController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\NotificationController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/drivers', [AdminController::class, 'drivers'])->name('drivers');
    Route::get('/drivers/create', [AdminController::class, 'createDriver'])->name('drivers.create');
    Route::post('/drivers', [AdminController::class, 'storeDriver'])->name('drivers.store');
    Route::get('/drivers/{driver}/edit', [AdminController::class, 'editDriver'])->name('drivers.edit');
    Route::put('/drivers/{driver}', [AdminController::class, 'updateDriver'])->name('drivers.update');
    Route::delete('/drivers/{driver}', [AdminController::class, 'destroyDriver'])->name('drivers.destroy');
    Route::get('/drivers/{driver}/qrcode', [AdminController::class, 'showQrCode'])->name('drivers.qrcode');
    Route::get('/ratings', [AdminController::class, 'ratings'])->name('ratings');
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::patch('/ratings/{rating}/review', [AdminController::class, 'markReviewed'])->name('ratings.review');
    Route::get('/activity-logs', [AdminController::class, 'activityLogs'])->name('activity-logs');
    Route::get('/todas', [AdminController::class, 'todas'])->name('todas');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/dashboard', [SuperadminController::class, 'dashboard'])->name('dashboard');
    Route::get('/drivers', [SuperadminController::class, 'drivers'])->name('drivers');
    Route::get('/drivers/create', [SuperadminController::class, 'createDriver'])->name('drivers.create');
    Route::post('/drivers', [SuperadminController::class, 'storeDriver'])->name('drivers.store');
    Route::get('/drivers/{driver}/edit', [SuperadminController::class, 'editDriver'])->name('drivers.edit');
    Route::put('/drivers/{driver}', [SuperadminController::class, 'updateDriver'])->name('drivers.update');
    Route::delete('/drivers/{driver}', [SuperadminController::class, 'destroyDriver'])->name('drivers.destroy');
    Route::get('/drivers/{driver}/qrcode', [SuperadminController::class, 'showQrCode'])->name('drivers.qrcode');
    Route::get('/admins', [SuperadminController::class, 'admins'])->name('admins');
    Route::get('/admins/create', [SuperadminController::class, 'createAdmin'])->name('admins.create');
    Route::post('/admins', [SuperadminController::class, 'storeAdmin'])->name('admins.store');
    Route::delete('/admins/{user}', [SuperadminController::class, 'destroyAdmin'])->name('admins.destroy');
    Route::get('/complaints', [SuperadminController::class, 'complaints'])->name('complaints');
    Route::patch('/complaints/{rating}/review', [SuperadminController::class, 'markReviewed'])->name('complaints.review');
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::get('/activity-logs', [SuperadminController::class, 'activityLogs'])->name('activity-logs');
    Route::get('/todas', [SuperadminController::class, 'todas'])->name('todas');
    Route::get('/todas/create', [SuperadminController::class, 'createToda'])->name('todas.create');
    Route::post('/todas', [SuperadminController::class, 'storeToda'])->name('todas.store');
    Route::get('/todas/{toda}/edit', [SuperadminController::class, 'editToda'])->name('todas.edit');
    Route::put('/todas/{toda}', [SuperadminController::class, 'updateToda'])->name('todas.update');
    Route::delete('/todas/{toda}', [SuperadminController::class, 'destroyToda'])->name('todas.destroy');
});

Route::middleware(['auth', 'role:driver'])->prefix('driver')->name('driver.')->group(function () {
    Route::get('/dashboard', [DriverController::class, 'dashboard'])->name('dashboard');
    Route::get('/ratings', [DriverController::class, 'ratings'])->name('ratings');
    Route::post('/ratings/{rating}/respond', [DriverController::class, 'respond'])->name('ratings.respond');
});

// Notification routes (accessible by admin & superadmin)
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
});

// Serve storage files via Laravel (fix for Windows/XAMPP symlink issue)
Route::get('/file-storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        abort(404);
    }
    return response()->file($fullPath);
})->where('path', '.*')->name('storage.serve');

Route::get('/_diag', function () {
    try {
        $results = [];
        $results[] = 'DB: ' . config('database.default');

        $results[] = 'Users: ' . \App\Models\User::count();
        $results[] = 'Drivers: ' . \App\Models\Driver::count();
        $results[] = 'Ratings: ' . \App\Models\Rating::count();
        $results[] = 'Valid Ratings: ' . \App\Models\Rating::valid()->count();
        $results[] = 'Todas: ' . \App\Models\Toda::count();

        $results[] = 'Notification check...';
        $results[] = 'Notifications: ' . \App\Models\Notification::count();

        $results[] = 'Top drivers query...';
        $topDrivers = \App\Models\Driver::with('user')
            ->select('drivers.*')
            ->selectRaw('(select avg("ratings"."rating") from "ratings" where "drivers"."id" = "ratings"."driver_id" and "start_location" is not null and "end_location" is not null) as valid_ratings_avg_rating')
            ->selectRaw('(select count(*) from "ratings" where "drivers"."id" = "ratings"."driver_id" and "start_location" is not null and "end_location" is not null) as valid_ratings_count')
            ->groupBy('drivers.id')
            ->having('valid_ratings_count', '>', 0)
            ->orderByDesc('valid_ratings_avg_rating')
            ->take(5)
            ->get();
        $results[] = 'Top drivers: ' . $topDrivers->count();

        $results[] = 'Toda stats query...';
        $todaStats = \App\Models\Toda::with('drivers')->withCount('drivers')->get();
        $results[] = 'Toda stats: ' . $todaStats->count();

        $results[] = 'Recent ratings query...';
        $recentRatings = \App\Models\Rating::valid()->with(['driver.user', 'driver.toda'])->latest()->take(10)->get();
        $results[] = 'Recent ratings: ' . $recentRatings->count();

        $results[] = 'Recent complaints query...';
        $recentComplaints = \App\Models\Rating::valid()->with(['driver.user', 'proofs'])->where('rating', '<=', 2)->latest()->take(5)->get();
        $results[] = 'Recent complaints: ' . $recentComplaints->count();

        $results[] = 'Admins count...';
        $totalAdmins = \App\Models\User::where('role', 'admin')->count();
        $results[] = 'Admins: ' . $totalAdmins;

        $results[] = 'Active drivers...';
        $activeDrivers = \App\Models\Driver::where('status', 'active')->count();
        $results[] = 'Active: ' . $activeDrivers;

        $results[] = '--- Now trying to render the view ---';
        $totalDrivers = \App\Models\Driver::count();
        $totalRatings = \App\Models\Rating::valid()->count();
        $averageRating = \App\Models\Rating::valid()->avg('rating');
        $totalComplaints = \App\Models\Rating::valid()->where('rating', '<=', 2)->count();
        $totalTodas = \App\Models\Toda::count();

        // Try rendering without layout
        $view = view('superadmin.dashboard', compact(
            'totalDrivers', 'activeDrivers', 'totalRatings', 'averageRating',
            'totalAdmins', 'recentRatings', 'topDrivers', 'totalComplaints',
            'recentComplaints', 'totalTodas', 'todaStats'
        ))->render();
        $results[] = 'View rendered! Length: ' . strlen($view);

        return implode("\n", $results);
    } catch (\Throwable $e) {
        return 'ERROR: ' . $e->getMessage() . "\nFile: " . $e->getFile() . ':' . $e->getLine();
    }
});

// Shared
Route::get('/rate/{qrCode}', [RatingController::class, 'showRateForm'])->name('rate.driver');
Route::post('/rate/{qrCode}', [RatingController::class, 'submitRating'])->name('rate.submit');
