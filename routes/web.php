<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\TfrbOfficerController;
use App\Http\Controllers\SuperadminController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('landing');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:5,1');

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('operator.pending');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/resend', function (\Illuminate\Http\Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.resend');

Route::middleware(['auth', 'role:tfrb_officer', 'desktop'])->prefix('tfrb-officer')->name('tfrb-officer.')->group(function () {
    Route::get('/dashboard', [TfrbOfficerController::class, 'dashboard'])->name('dashboard');
    Route::get('/operators', [TfrbOfficerController::class, 'operators'])->name('operators');
    Route::get('/operators/create', [TfrbOfficerController::class, 'createOperator'])->name('operators.create');
    Route::post('/operators', [TfrbOfficerController::class, 'storeOperator'])->name('operators.store');
    Route::get('/operators/{operator}/edit', [TfrbOfficerController::class, 'editOperator'])->name('operators.edit');
    Route::put('/operators/{operator}', [TfrbOfficerController::class, 'updateOperator'])->name('operators.update');
    Route::delete('/operators/{operator}', [TfrbOfficerController::class, 'destroyOperator'])->name('operators.destroy');
    Route::get('/operators/{operator}/qrcode', [TfrbOfficerController::class, 'showQrCode'])->name('operators.qrcode');
    Route::get('/ratings', [TfrbOfficerController::class, 'ratings'])->name('ratings');
    Route::get('/reports', [TfrbOfficerController::class, 'reports'])->name('reports');
    Route::get('/reports/operators/{operator}/trips', [TfrbOfficerController::class, 'reportTrips'])->name('reports.trips');
    Route::patch('/ratings/{rating}/review', [TfrbOfficerController::class, 'markReviewed'])->name('ratings.review');
    Route::get('/complaints', [TfrbOfficerController::class, 'complaints'])->name('complaints');
    Route::patch('/complaints/{rating}/review', [TfrbOfficerController::class, 'complaintsMarkReviewed'])->name('complaints.review');
    Route::delete('/complaints/{rating}', [TfrbOfficerController::class, 'destroyComplaint'])->name('complaints.destroy');
    Route::get('/activity-logs', [TfrbOfficerController::class, 'activityLogs'])->name('activity-logs');
    Route::get('/todas', [TfrbOfficerController::class, 'todas'])->name('todas');
    Route::get('/toda/{toda}/members', [TfrbOfficerController::class, 'todaMembers'])->name('todas.members');
    Route::get('/invalid-ratings', [TfrbOfficerController::class, 'invalidRatings'])->name('invalid-ratings');
    Route::patch('/ratings/{rating}/restore', [TfrbOfficerController::class, 'restoreRating'])->name('ratings.restore');
    Route::patch('/operators/{operator}/approve', [TfrbOfficerController::class, 'approveOperator'])->name('operators.approve');
    Route::patch('/operators/{operator}/reject', [TfrbOfficerController::class, 'rejectOperator'])->name('operators.reject');
});

Route::middleware(['auth', 'role:superadmin', 'desktop'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/dashboard', [SuperadminController::class, 'dashboard'])->name('dashboard');
    Route::get('/operators', [SuperadminController::class, 'operators'])->name('operators');
    Route::get('/operators/create', [SuperadminController::class, 'createOperator'])->name('operators.create');
    Route::post('/operators', [SuperadminController::class, 'storeOperator'])->name('operators.store');
    Route::get('/operators/{operator}/edit', [SuperadminController::class, 'editOperator'])->name('operators.edit');
    Route::put('/operators/{operator}', [SuperadminController::class, 'updateOperator'])->name('operators.update');
    Route::delete('/operators/{operator}', [SuperadminController::class, 'destroyOperator'])->name('operators.destroy');
    Route::get('/operators/{operator}/qrcode', [SuperadminController::class, 'showQrCode'])->name('operators.qrcode');
    Route::get('/officers', [SuperadminController::class, 'officers'])->name('officers');
    Route::get('/officers/create', [SuperadminController::class, 'createOfficer'])->name('officers.create');
    Route::post('/officers', [SuperadminController::class, 'storeOfficer'])->name('officers.store');
    Route::delete('/officers/{user}', [SuperadminController::class, 'destroyOfficer'])->name('officers.destroy');
    Route::get('/complaints', [SuperadminController::class, 'complaints'])->name('complaints');
    Route::patch('/complaints/{rating}/review', [SuperadminController::class, 'markReviewed'])->name('complaints.review');
    Route::delete('/complaints/{rating}', [SuperadminController::class, 'destroyComplaint'])->name('complaints.destroy');
    Route::get('/ratings', [SuperadminController::class, 'ratings'])->name('ratings');
    Route::get('/reports', [SuperadminController::class, 'reports'])->name('reports');
    Route::get('/reports/operators/{operator}/trips', [SuperadminController::class, 'reportTrips'])->name('reports.trips');
    Route::get('/activity-logs', [SuperadminController::class, 'activityLogs'])->name('activity-logs');
    Route::get('/todas', [SuperadminController::class, 'todas'])->name('todas');
    Route::get('/todas/create', [SuperadminController::class, 'createToda'])->name('todas.create');
    Route::post('/todas', [SuperadminController::class, 'storeToda'])->name('todas.store');
    Route::get('/todas/{toda}/edit', [SuperadminController::class, 'editToda'])->name('todas.edit');
    Route::put('/todas/{toda}', [SuperadminController::class, 'updateToda'])->name('todas.update');
    Route::delete('/todas/{toda}', [SuperadminController::class, 'destroyToda'])->name('todas.destroy');
    Route::get('/toda/{toda}/members', [SuperadminController::class, 'todaMembers'])->name('todas.members');
    Route::get('/invalid-ratings', [SuperadminController::class, 'invalidRatings'])->name('invalid-ratings');
    Route::patch('/ratings/{rating}/restore', [SuperadminController::class, 'restoreRating'])->name('ratings.restore');
    Route::patch('/operators/{operator}/approve', [SuperadminController::class, 'approveOperator'])->name('operators.approve');
    Route::patch('/operators/{operator}/reject', [SuperadminController::class, 'rejectOperator'])->name('operators.reject');
});

Route::middleware(['auth', 'role:operator'])->prefix('operator')->name('operator.')->group(function () {
    Route::get('/pending', function () {
        $operator = Auth::user()->operator;
        if ($operator && $operator->status === 'active') {
            return redirect()->route('operator.dashboard');
        }
        return view('operator.pending');
    })->name('pending');

    Route::middleware(['verified', 'operator.active'])->group(function () {
        Route::get('/dashboard', [OperatorController::class, 'dashboard'])->name('dashboard');
        Route::get('/ratings', [OperatorController::class, 'ratings'])->name('ratings');
        Route::post('/ratings/{rating}/respond', [OperatorController::class, 'respond'])->name('ratings.respond');
        Route::get('/settings', [OperatorController::class, 'showSettings'])->name('settings');
        Route::put('/settings/password', [OperatorController::class, 'updatePassword'])->name('settings.password');
    });
});

// Notification routes (accessible by TFRB Officer & Superadmin)
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
});

// Serve storage files (path traversal protected, signed URL required)
Route::get('/file-storage/{path}', function ($path) {
    $baseDir = realpath(storage_path('app/public'));
    $candidates = [
        $baseDir !== false ? $baseDir . '/' . $path : null,
        $baseDir !== false ? storage_path('app/public/proofs/' . $path) : null,
    ];
    foreach ($candidates as $localPath) {
        if ($localPath === null) continue;
        $resolved = realpath($localPath);
        if ($resolved !== false && str_starts_with($resolved, $baseDir)) {
            $mime = mime_content_type($resolved) ?: 'application/octet-stream';
            return response()->file($resolved, ['Content-Type' => $mime]);
        }
    }
    if (class_exists(\App\Helpers\SupabaseStorage::class)) {
        $url = \App\Helpers\SupabaseStorage::getPublicUrl($path);
        return redirect()->away($url);
    }
    abort(404);
})->where('path', '.*')->middleware('signed')->name('storage.serve');

// Serve proof inline (path traversal protected, signed URL required)
Route::get('/proof/{path}', function ($path) {
    $baseDir = realpath(storage_path('app/public'));
    $candidates = [
        $baseDir !== false ? $baseDir . '/' . $path : null,
        $baseDir !== false ? storage_path('app/public/proofs/' . $path) : null,
    ];
    foreach ($candidates as $localPath) {
        if ($localPath === null) continue;
        $resolved = realpath($localPath);
        if ($resolved !== false && str_starts_with($resolved, $baseDir)) {
            $mime = mime_content_type($resolved) ?: 'application/octet-stream';
            return response()->file($resolved, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }
    if (class_exists(\App\Helpers\SupabaseStorage::class)) {
        $url = \App\Helpers\SupabaseStorage::getPublicUrl($path);
        return redirect()->away($url);
    }
    abort(404);
})->where('path', '.*')->middleware('signed')->name('proof.serve');

// Cleanup route — local only (never exposed on production; use the artisan
// command `php artisan operators:cleanup` on the server instead)
Route::get('/cleanup', function () {
    if (!app()->environment('local')) {
        abort(404);
    }
    $token = (string) config('services.cleanup_token');
    if ($token === '' || !hash_equals($token, (string) request('token'))) {
        abort(404);
    }
    $output = '';
    $email = request('email');
    if ($email) {
        $user = \App\Models\User::where('email', $email)->first();
        if ($user) {
            \App\Models\Operator::where('user_id', $user->id)->delete();
            $user->delete();
            $output .= "Deleted user + operator for: $email\n";
        } else {
            $output .= "No user found with email: $email\n";
        }
    } else {
        $deleted = 0;
        \App\Models\Operator::with('user')->whereIn('status', ['pending', 'rejected'])->chunk(50, function ($operators) use (&$deleted) {
            foreach ($operators as $op) {
                if ($op->user && $op->user->role === 'operator') {
                    $op->user->delete();
                }
                $op->delete();
                $deleted++;
            }
        });
        $output .= "Deleted $deleted pending/rejected operators and their users.\n";
    }
    return '<pre>' . $output . '</pre>';
});

// Setup route — local only (run migration + cleanup orphaned users)
Route::get('/setup', function () {
    if (!app()->environment('local')) {
        abort(404);
    }
    $output = '';
    try {
        Artisan::call('migrate');
        $output .= Artisan::output() . "\n";
    } catch (\Exception $e) {
        $output .= 'Migration Error: ' . $e->getMessage() . "\n";
    }
    try {
        \Illuminate\Support\Facades\DB::statement("DELETE FROM users WHERE role = 'operator' AND id NOT IN (SELECT user_id FROM operators)");
        $output .= "Orphaned users cleaned up.\n";
    } catch (\Exception $e) {
        $output .= 'Cleanup Error: ' . $e->getMessage() . "\n";
    }
    return '<pre>' . $output . '</pre>';
});
Route::post('/setup', function () {
    if (!app()->environment('local')) {
        abort(404);
    }
    $output = '';
    try {
        Artisan::call('migrate:fresh', ['--seed' => true]);
        $output .= Artisan::output() . "\n";
    } catch (\Exception $e) {
        $output .= 'Migration Error: ' . $e->getMessage() . "\n";
    }
    return '<pre>' . $output . '</pre>';
});

// Shared
Route::get('/rate/{qrCode}', [RatingController::class, 'showRateForm'])->name('rate.operator');
Route::post('/rate/{qrCode}', [RatingController::class, 'submitRating'])->name('rate.submit')->middleware('throttle:30,1');
Route::get('/rate/{qrCode}/submitted', [RatingController::class, 'showSubmitted'])->name('rate.submitted');

// Server-side route lookup for the passenger map (cached)
Route::get('/route', [RouteController::class, 'fetch'])->middleware('throttle:30,1');


