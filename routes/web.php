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

Route::get('/debug-error', function () {
    try {
        \Illuminate\Support\Facades\Auth::attempt(['email' => 'superadmin@trifair.com', 'password' => 'admin123']);
        return response()->json(['auth' => 'ok', 'user' => \Illuminate\Support\Facades\Auth::user()?->name ?? 'none']);
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    }
});

Route::get('/debug-config', function () {
    return response()->json([
        'db_default' => config('database.default'),
        'db_host' => config('database.connections.mysql.host'),
        'db_database' => config('database.connections.sqlite.database'),
        'app_key_set' => !empty(config('app.key')),
        'app_debug' => config('app.debug'),
    ]);
});

Route::get('/debug-dashboard', function () {
    try {
        $results = [];

        $results['totalDrivers'] = \App\Models\Driver::count();
        $results['activeDrivers'] = \App\Models\Driver::where('status', 'active')->count();
        $results['totalRatings'] = \App\Models\Rating::valid()->count();
        $results['averageRating'] = \App\Models\Rating::valid()->avg('rating');
        $results['totalAdmins'] = \App\Models\User::where('role', 'admin')->count();
        $results['totalComplaints'] = \App\Models\Rating::valid()->where('rating', '<=', 2)->count();
        $results['totalTodas'] = \App\Models\Toda::count();

        $results['topDrivers'] = \App\Models\Driver::with('user')
            ->withAvg('validRatings', 'rating')
            ->withCount('validRatings')
            ->having('valid_ratings_count', '>', 0)
            ->orderByDesc('valid_ratings_avg_rating')
            ->take(5)
            ->get()
            ->toArray();

        $results['todaStats'] = \App\Models\Toda::with('drivers')->withCount(['drivers', 'activeDrivers' => function ($q) {
            $q->where('status', 'active');
        }])->get()
            ->toArray();

        return response()->json($results);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()) . ':' . $e->getLine(),
        ]);
    }
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

// Shared
Route::get('/rate/{qrCode}', [RatingController::class, 'showRateForm'])->name('rate.driver');
Route::post('/rate/{qrCode}', [RatingController::class, 'submitRating'])->name('rate.submit');
