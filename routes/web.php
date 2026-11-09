<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetOtpController;
use App\Http\Controllers\Auth\ForcePasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PresidentComplaintsController;
use App\Http\Controllers\TfrbOfficerController;
use App\Http\Controllers\SuperadminController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\PresidentController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\GeocodeController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PassengerController;
use App\Http\Controllers\TodaSelectionController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/landing-stats', [LandingController::class, 'stats'])->name('landing.stats');
// Render health checks must not depend on Supabase or the session database.
Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');



Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// No `guest` middleware here on purpose: a passenger signed in with one
// Google account comes back to switch to another one (their complaints are
// kept per account), and the callback simply logs the newly chosen account in
// over the old session.
Route::get('/auth/google', [SocialiteController::class, 'redirect'])->name('login.google');
Route::get('/auth/google/callback', [SocialiteController::class, 'callback'])->name('login.google.callback');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:5,1');

Route::get('/forgot-password', [PasswordResetOtpController::class, 'showRequestForm'])->middleware('guest')->name('password.request');
Route::post('/forgot-password', [PasswordResetOtpController::class, 'sendOtp'])->middleware(['guest', 'throttle:6,1'])->name('password.email');
Route::get('/forgot-password/verify', [PasswordResetOtpController::class, 'showVerifyForm'])->middleware('guest')->name('password.otp');
Route::post('/forgot-password/verify', [PasswordResetOtpController::class, 'verifyAndReset'])->middleware(['guest', 'throttle:6,1'])->name('password.otp.verify');

// Accounts created with the shared default password must replace it before
// they can reach any other page (see EnsurePasswordChanged middleware).
Route::middleware('auth')->group(function () {
    Route::get('/password/change', [ForcePasswordController::class, 'show'])->name('password.force');
    Route::put('/password/change', [ForcePasswordController::class, 'update'])->name('password.force.update');
});

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::post('/email/verify-code', function (\Illuminate\Http\Request $request) {
    $user = $request->user();

    if ($user->hasVerifiedEmail()) {
        return redirect()->intended('/');
    }

    $request->validate([
        'code' => ['required', 'string', 'size:6'],
    ]);

    if (!$user->verifyEmailCode($request->input('code'))) {
        return back()->withErrors(['code' => 'The verification code is invalid or has expired.']);
    }

    $user = $request->user()->fresh();

    if ($user->role === 'operator') {
        $operator = $user->operator;
        if ($operator && $operator->status === 'active') {
            return redirect()->route('operator.dashboard');
        }
        return redirect()->route('operator.pending');
    }

    if ($user->role === 'tfrb_officer' || $user->role === 'superadmin') {
        return redirect()->route('tfrb-officer.dashboard');
    }

    if ($user->role === 'operator_president') {
        return redirect()->route('dashboard');
    }

    return redirect('/');
})->middleware(['auth', 'throttle:6,1'])->name('verification.code');

Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
    $request->fulfill();
    $user = $request->user();

    if ($user->role === 'operator') {
        $operator = $user->operator;
        if ($operator && $operator->status === 'active') {
            return redirect()->route('operator.dashboard');
        }
        return redirect()->route('operator.pending');
    }

    if ($user->role === 'tfrb_officer' || $user->role === 'superadmin') {
        return redirect()->route('tfrb-officer.dashboard');
    }

    if ($user->role === 'operator_president') {
        return redirect()->route('dashboard');
    }

    return redirect('/');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/resend', function (\Illuminate\Http\Request $request) {
    $user = $request->user();

    if ($user->hasVerifiedEmail()) {
        return back()->with('message', 'Your email address is already verified.');
    }

    try {
        $user->sendEmailVerificationNotification();
    } catch (\Throwable $e) {
        Log::error('Email verification resend failed: ' . get_class($e) . ': ' . $e->getMessage());

        return back()->withErrors([
            'email' => 'We could not send the verification email right now. Please try again in a few minutes.',
        ]);
    }

    return back()->with('message', 'Verification code sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.resend');

Route::middleware(['auth', 'role:tfrb_officer'])->prefix('tfrb-officer')->name('tfrb-officer.')->group(function () {
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
    Route::post('/ratings/bulk-review', [TfrbOfficerController::class, 'ratingsBulkReview'])->name('ratings.bulkReview');
    Route::get('/complaints', [TfrbOfficerController::class, 'complaints'])->name('complaints');
    Route::get('/complaints/source-counts', [TfrbOfficerController::class, 'complaintsSourceCounts'])->name('complaints.sourceCounts');
    Route::get('/complaints/{rating}', [TfrbOfficerController::class, 'complaint'])->whereNumber('rating')->name('complaints.show');
    Route::patch('/complaints/{rating}/review', [TfrbOfficerController::class, 'complaintsMarkReviewed'])->name('complaints.review');
    Route::patch('/complaints/{rating}/reject', [TfrbOfficerController::class, 'complaintsReject'])->name('complaints.reject');
    Route::patch('/complaints/{rating}/reset', [TfrbOfficerController::class, 'complaintsResetReview'])->name('complaints.reset');
    Route::patch('/complaints/{rating}/solve', [TfrbOfficerController::class, 'complaintsMarkSolved'])->name('complaints.solve');
    Route::patch('/complaints/{rating}/reopen', [TfrbOfficerController::class, 'complaintsReopen'])->name('complaints.reopen');
    Route::patch('/complaints/{rating}/escalate', [TfrbOfficerController::class, 'complaintsEscalate'])->name('complaints.escalate');
    Route::post('/complaints/{rating}/reply', [TfrbOfficerController::class, 'complaintsReply'])->name('complaints.reply');
    Route::delete('/complaints/{rating}', [TfrbOfficerController::class, 'destroyComplaint'])->name('complaints.destroy');
    Route::post('/complaints/bulk-review', [TfrbOfficerController::class, 'complaintsBulkReview'])->name('complaints.bulkReview');
    Route::get('/violations', [TfrbOfficerController::class, 'violations'])->name('violations');
    Route::patch('/violations/{violation}/review', [TfrbOfficerController::class, 'violationsReview'])->name('violations.review');
    Route::patch('/violations/{violation}/solve', [TfrbOfficerController::class, 'violationsSolve'])->name('violations.solve');
    Route::patch('/violations/{violation}/reopen', [TfrbOfficerController::class, 'violationsReopen'])->name('violations.reopen');
    Route::post('/violations/{violation}/reply', [TfrbOfficerController::class, 'violationsReply'])->name('violations.reply');
    Route::get('/activity-logs', [TfrbOfficerController::class, 'activityLogs'])->name('activity-logs');
    Route::get('/todas', [TfrbOfficerController::class, 'todas'])->name('todas');
    Route::get('/toda/{toda}/members', [TfrbOfficerController::class, 'todaMembers'])->name('todas.members');
    Route::patch('/operators/{operator}/approve', [TfrbOfficerController::class, 'approveOperator'])->name('operators.approve');
    Route::patch('/operators/{operator}/reject', [TfrbOfficerController::class, 'rejectOperator'])->name('operators.reject');
    Route::patch('/operators/{operator}/archive', [TfrbOfficerController::class, 'archiveOperator'])->name('operators.archive');
    Route::patch('/operators/{operator}/restore', [TfrbOfficerController::class, 'restoreOperator'])->name('operators.restore');
    Route::post('/operators/{operator}/suspend', [TfrbOfficerController::class, 'suspendOperator'])->name('operators.suspend');
    Route::patch('/operators/{operator}/suspend/end', [TfrbOfficerController::class, 'endOperatorSuspension'])->name('operators.suspend.end');
    Route::post('/operators/{operator}/assign-president', [TfrbOfficerController::class, 'assignPresident'])->name('operators.assignPresident');
    Route::post('/operators/{operator}/reset-password', [TfrbOfficerController::class, 'resetPassword'])->name('operators.resetPassword');
    Route::post('/operators/{operator}/franchise', [TfrbOfficerController::class, 'issueFranchise'])->name('operators.franchise.issue');
    Route::put('/operators/{operator}/franchise', [TfrbOfficerController::class, 'updateFranchise'])->name('operators.franchise.update');
    Route::post('/operators/{operator}/franchise/renew', [TfrbOfficerController::class, 'renewFranchise'])->name('operators.franchise.renew');
    Route::get('/operators/export', [TfrbOfficerController::class, 'exportOperators'])->name('operators.export');
    Route::get('/reports/export', [TfrbOfficerController::class, 'exportReports'])->name('reports.export');
    Route::get('/ratings/export', [TfrbOfficerController::class, 'exportRatings'])->name('ratings.export');
    Route::get('/complaints/export', [TfrbOfficerController::class, 'exportComplaints'])->name('complaints.export');
    Route::get('/activity-logs/export', [TfrbOfficerController::class, 'exportActivityLogs'])->name('activity-logs.export');
    Route::get('/settings', [TfrbOfficerController::class, 'showSettings'])->name('settings');
    Route::put('/settings/password', [TfrbOfficerController::class, 'updatePassword'])->name('settings.password');
    Route::get('/presidents', [TfrbOfficerController::class, 'presidents'])->name('presidents');
    Route::delete('/presidents/{user}', [TfrbOfficerController::class, 'destroyPresident'])->name('presidents.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
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
    Route::get('/presidents', [SuperadminController::class, 'presidents'])->name('presidents');
    Route::delete('/presidents/{user}', [SuperadminController::class, 'destroyPresident'])->name('presidents.destroy');
    Route::get('/complaints', [SuperadminController::class, 'complaints'])->name('complaints');
    Route::get('/complaints/source-counts', [SuperadminController::class, 'complaintsSourceCounts'])->name('complaints.sourceCounts');
    Route::get('/complaints/{rating}', [SuperadminController::class, 'complaint'])->whereNumber('rating')->name('complaints.show');
        Route::patch('/complaints/{rating}/review', [SuperadminController::class, 'complaintsMarkReviewed'])->name('complaints.review');
        Route::patch('/complaints/{rating}/reject', [SuperadminController::class, 'complaintsReject'])->name('complaints.reject');
        Route::patch('/complaints/{rating}/reset', [SuperadminController::class, 'complaintsResetReview'])->name('complaints.reset');
    Route::patch('/complaints/{rating}/solve', [SuperadminController::class, 'complaintsMarkSolved'])->name('complaints.solve');
    Route::patch('/complaints/{rating}/reopen', [SuperadminController::class, 'complaintsReopen'])->name('complaints.reopen');
    Route::patch('/complaints/{rating}/escalate', [SuperadminController::class, 'complaintsEscalate'])->name('complaints.escalate');
    Route::post('/complaints/{rating}/reply', [SuperadminController::class, 'complaintsReply'])->name('complaints.reply');
    Route::delete('/complaints/{rating}', [SuperadminController::class, 'destroyComplaint'])->name('complaints.destroy');
    Route::post('/complaints/bulk-review', [SuperadminController::class, 'complaintsBulkReview'])->name('complaints.bulkReview');
    Route::get('/violations', [SuperadminController::class, 'violations'])->name('violations');
    Route::patch('/violations/{violation}/review', [SuperadminController::class, 'violationsReview'])->name('violations.review');
    Route::patch('/violations/{violation}/solve', [SuperadminController::class, 'violationsSolve'])->name('violations.solve');
    Route::patch('/violations/{violation}/reopen', [SuperadminController::class, 'violationsReopen'])->name('violations.reopen');
    Route::post('/violations/{violation}/reply', [SuperadminController::class, 'violationsReply'])->name('violations.reply');
    Route::get('/ratings', [SuperadminController::class, 'ratings'])->name('ratings');
    Route::patch('/ratings/{rating}/review', [SuperadminController::class, 'markReviewed'])->name('ratings.review');
    Route::post('/ratings/bulk-review', [SuperadminController::class, 'ratingsBulkReview'])->name('ratings.bulkReview');
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
    Route::patch('/operators/{operator}/approve', [SuperadminController::class, 'approveOperator'])->name('operators.approve');
    Route::patch('/operators/{operator}/reject', [SuperadminController::class, 'rejectOperator'])->name('operators.reject');
    Route::patch('/operators/{operator}/archive', [SuperadminController::class, 'archiveOperator'])->name('operators.archive');
    Route::patch('/operators/{operator}/restore', [SuperadminController::class, 'restoreOperator'])->name('operators.restore');
    Route::post('/operators/{operator}/suspend', [SuperadminController::class, 'suspendOperator'])->name('operators.suspend');
    Route::patch('/operators/{operator}/suspend/end', [SuperadminController::class, 'endOperatorSuspension'])->name('operators.suspend.end');
    Route::post('/operators/{operator}/assign-president', [SuperadminController::class, 'assignPresident'])->name('operators.assignPresident');
    Route::post('/operators/{operator}/reset-password', [SuperadminController::class, 'resetPassword'])->name('operators.resetPassword');
    Route::post('/operators/{operator}/franchise', [SuperadminController::class, 'issueFranchise'])->name('operators.franchise.issue');
    Route::put('/operators/{operator}/franchise', [SuperadminController::class, 'updateFranchise'])->name('operators.franchise.update');
    Route::post('/operators/{operator}/franchise/renew', [SuperadminController::class, 'renewFranchise'])->name('operators.franchise.renew');
    Route::get('/operators/export', [SuperadminController::class, 'exportOperators'])->name('operators.export');
    Route::get('/reports/export', [SuperadminController::class, 'exportReports'])->name('reports.export');
    Route::get('/ratings/export', [SuperadminController::class, 'exportRatings'])->name('ratings.export');
    Route::get('/complaints/export', [SuperadminController::class, 'exportComplaints'])->name('complaints.export');
    Route::get('/activity-logs/export', [SuperadminController::class, 'exportActivityLogs'])->name('activity-logs.export');
    Route::get('/settings', [SuperadminController::class, 'showSettings'])->name('settings');
    Route::put('/settings/complaint-escalation', [SuperadminController::class, 'updateComplaintEscalation'])->name('settings.complaintEscalation');
    Route::put('/settings/password', [SuperadminController::class, 'updatePassword'])->name('settings.password');
});

// Smart landing after login: presidents open their TODA console, everyone else
// the operator app. The TODA switcher changes which TODA the account acts on.
// The picker itself deliberately sits OUTSIDE the 'toda.selection' middleware
// (applied below to the operator and president route groups) so it cannot loop.
Route::middleware(['auth', 'role:operator,operator_president'])->group(function () {
    Route::get('/toda/select', [TodaSelectionController::class, 'create'])->name('toda.select');
    Route::post('/toda/select', [TodaSelectionController::class, 'store'])->name('toda.select.store');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/toda/switch', [DashboardController::class, 'switchToda'])->name('toda.switch');
});

Route::middleware(['auth', 'role:operator,operator_president'])->prefix('operator')->name('operator.')->group(function () {
    Route::get('/pending', function () {
        $operator = Auth::user()->operator;
        if ($operator && $operator->status === 'active') {
            return redirect()->route('operator.dashboard');
        }
        return view('operator.pending', ['operator' => $operator]);
    })->name('pending');

    Route::middleware(['verified', 'operator.active', 'toda.selection'])->group(function () {
        Route::get('/dashboard', [OperatorController::class, 'dashboard'])->name('dashboard');
        Route::get('/ratings', [OperatorController::class, 'ratings'])->name('ratings');
        Route::post('/ratings/{rating}/respond', [OperatorController::class, 'respond'])->name('ratings.respond');
        Route::get('/profile', [OperatorController::class, 'profile'])->name('profile');
        // Self-service request to join another TODA. The TODA president is the
        // sole approver — once they approve, the membership is active.
        Route::post('/todas/join', [OperatorController::class, 'joinToda'])->name('todas.join');
        Route::get('/settings', [OperatorController::class, 'showSettings'])->name('settings');
        Route::put('/settings/password', [OperatorController::class, 'updatePassword'])->name('settings.password');
    });
});

Route::middleware(['auth', 'role:operator_president', 'president.active', 'toda.selection'])->prefix('president')->name('president.')->group(function () {
    Route::get('/dashboard', [PresidentController::class, 'dashboard'])->name('dashboard');
    // The president's own ratings and complaints (as an operator), listed like
    // the operator ratings page but scoped to the TODA they govern.
    Route::get('/ratings', [PresidentController::class, 'ratings'])->name('ratings');
    Route::post('/ratings/{rating}/respond', [OperatorController::class, 'respond'])->name('ratings.respond');
    Route::get('/members', [PresidentController::class, 'members'])->name('members');
    // President can onboard a new member (active immediately — the president
    // is the approver for their own TODA) and remove one (they become a
    // TODA-less operator).
    Route::post('/members', [PresidentController::class, 'storeMember'])->name('members.store');
    Route::get('/members/{member}', [PresidentController::class, 'memberDetail'])->name('members.detail');
    Route::post('/members/{member}/suspend', [PresidentController::class, 'suspendMember'])->name('members.suspend');
    Route::patch('/members/{member}/suspend/end', [PresidentController::class, 'endSuspension'])->name('members.suspend.end');
    Route::delete('/members/{member}/remove', [PresidentController::class, 'removeMember'])->name('members.remove');
    // Take the TODA president's decision on join requests (final approval).
    Route::patch('/memberships/{membership}/approve', [PresidentController::class, 'approveMembership'])->name('memberships.approve');
    Route::patch('/memberships/{membership}/reject', [PresidentController::class, 'rejectMembership'])->name('memberships.reject');
    // Passenger complaints routed to this TODA's president — they can
    // review, solve, reject or forward them (forward is one-way).
    Route::get('/complaints', [PresidentComplaintsController::class, 'index'])->name('complaints');
    Route::get('/complaints/{rating}', [PresidentComplaintsController::class, 'complaint'])->whereNumber('rating')->name('complaints.show');
    Route::patch('/complaints/{rating}/review', [PresidentComplaintsController::class, 'markReviewed'])->name('complaints.review');
    Route::patch('/complaints/{rating}/solve', [PresidentComplaintsController::class, 'markSolved'])->name('complaints.solve');
    Route::patch('/complaints/{rating}/reject', [PresidentComplaintsController::class, 'reject'])->name('complaints.reject');
    Route::patch('/complaints/{rating}/reset', [PresidentComplaintsController::class, 'reset'])->name('complaints.reset');
    Route::patch('/complaints/{rating}/reopen', [PresidentComplaintsController::class, 'reopen'])->name('complaints.reopen');
    Route::patch('/complaints/{rating}/escalate', [PresidentComplaintsController::class, 'forward'])->name('complaints.escalate');
    Route::patch('/complaints/{rating}/forward', [PresidentComplaintsController::class, 'forward'])->name('complaints.forward');
    Route::post('/complaints/{rating}/reply', [PresidentComplaintsController::class, 'reply'])->name('complaints.reply');
});

// Notification routes (TFRB Officer, Superadmin & TODA President — operators'
// own staff users have no notification UI)
Route::middleware(['auth', 'role:superadmin,tfrb_officer,operator_president'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markReadAjax'])->name('notifications.readAjax');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
});

// Known proof paths are additionally checked against the authenticated
// user's resource ownership. Guest links remain supported for signed email
// links and public complaint follow-up links.
$authorizeProofPath = static function (string $path): void {
    $user = request()->user();
    if (!$user) {
        return;
    }

    if ($user->isTfrbOfficerOrSuperadmin()) {
        return;
    }

    $rating = null;
    foreach ([
        \App\Models\RatingProof::class,
        \App\Models\OperatorProof::class,
        \App\Models\ResolutionProof::class,
    ] as $proofModel) {
        $proof = $proofModel::where('file_path', $path)->with('rating.operator')->first();
        if ($proof) {
            $rating = $proof->rating;
            break;
        }
    }

    if ($rating) {
        $allowed = ($user->isPassenger() && (int) $rating->passenger_user_id === (int) $user->id)
            || (($user->isOperator() || $user->isOperatorPresident()) && (int) optional($rating->operator)->user_id === (int) $user->id)
            || ($user->isOperatorPresident() && $user->isPresidentOf($rating->toda_id));

        if (!$allowed) {
            abort(403);
        }

        return;
    }

    $violationProof = \App\Models\SuspensionViolationProof::where('file_path', $path)
        ->with('violation.operator')
        ->first();
    if ($violationProof) {
        $violation = $violationProof->violation;
        $isSubject = (int) optional($violation?->operator)->user_id === (int) $user->id;
        $allowed = $user->isTfrbOfficerOrSuperadmin()
            || (int) optional($violation)->reported_by === (int) $user->id
            || $isSubject
            || ($user->isOperatorPresident() && $user->isPresidentOf($violation?->toda_id));
        if (!$allowed) {
            abort(403);
        }
        return;
    }
};

// Serve storage files (path traversal protected, signed URL required)
Route::get('/file-storage/{path}', function ($path) use ($authorizeProofPath) {
    $authorizeProofPath($path);
    $baseDir = realpath(storage_path('app/public'));
    $baseDir = realpath(storage_path('app/public'));
    $candidates = [
        $baseDir !== false ? $baseDir . '/' . $path : null,
        $baseDir !== false ? storage_path('app/public/proofs/' . $path) : null,
    ];
    foreach ($candidates as $localPath) {
        if ($localPath === null) continue;
        $resolved = realpath($localPath);
        if ($resolved !== false && str_starts_with($resolved, $baseDir . DIRECTORY_SEPARATOR)) {
            $mime = mime_content_type($resolved) ?: 'application/octet-stream';
            return response()->file($resolved, ['Content-Type' => $mime]);
        }
    }
    if (class_exists(\App\Helpers\SupabaseStorage::class)) {
        $url = \App\Helpers\SupabaseStorage::getAccessUrl($path);
        if ($url) return redirect()->away($url);
    }
    abort(404);
})->where('path', '.*')->middleware('signed')->name('storage.serve');

// Serve proof inline (path traversal protected, signed URL required)
Route::get('/proof/{path}', function ($path) use ($authorizeProofPath) {
    $authorizeProofPath($path);
    $baseDir = realpath(storage_path('app/public'));
    $candidates = [
        $baseDir !== false ? $baseDir . '/' . $path : null,
        $baseDir !== false ? storage_path('app/public/proofs/' . $path) : null,
    ];
    foreach ($candidates as $localPath) {
        if ($localPath === null) continue;
        $resolved = realpath($localPath);
        if ($resolved !== false && str_starts_with($resolved, $baseDir . DIRECTORY_SEPARATOR)) {
            $mime = mime_content_type($resolved) ?: 'application/octet-stream';
            return response()->file($resolved, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }
    if (class_exists(\App\Helpers\SupabaseStorage::class)) {
        $url = \App\Helpers\SupabaseStorage::getAccessUrl($path);
        if ($url) return redirect()->away($url);
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

// Session keepalive — a passenger can leave the rate form open while the
// map loads and the GPS warms up. Touching the session keeps the CSRF token
// fresh so the final POST does not expire into a 419 mid-submit.
// NOTE: must be registered BEFORE /rate/{qrCode} so the literal "keepalive"
// segment is not swallowed by the parameterized rate-form route.
Route::get('/rate/keepalive', function () {
    return response('', 204);
})->name('rate.keepalive');

// Shared
Route::get('/rate/{qrCode}', [RatingController::class, 'showRateForm'])->name('rate.operator');
// An operator may belong to several TODAs; this dedicated, refresh-safe URL
// renders the rating form scoped to the chosen TODA.
Route::get('/rate/{qrCode}/toda/{todaId}', [RatingController::class, 'showRateForm'])->name('rate.toda');
Route::post('/rate/{qrCode}', [RatingController::class, 'submitRating'])->name('rate.submit')->middleware('throttle:rate-submit');
Route::get('/rate/{qrCode}/submitted', [RatingController::class, 'showSubmitted'])->name('rate.submitted');
// Blocked (suspended / inactive) operators: the QR page shows a violation
// report form instead of the star rating. The report is attached to the
// operator automatically and queued for TFRB / superadmin review.
Route::post('/rate/{qrCode}/report', [RatingController::class, 'submitReport'])->name('rate.report.submit')->middleware('throttle:rate-submit');
Route::get('/rate/{qrCode}/report/submitted', [RatingController::class, 'reportSubmitted'])->name('rate.report.submitted');

Route::group(['prefix' => 'passenger', 'middleware' => ['auth', 'role:passenger']], function () {
    Route::get('/dashboard', [PassengerController::class, 'dashboard'])->name('passenger.dashboard');
    Route::post('/violations/{violation}/follow-up', [PassengerController::class, 'violationFollowUp'])->name('passenger.violations.followUp');
    Route::get('/complaints/{rating}', [PassengerController::class, 'complaint'])->name('passenger.complaints.show');
    // A passenger follows up on their own complaint while it is still open.
    Route::post('/complaints/{rating}/follow-up', [PassengerController::class, 'followUp'])
        ->middleware('throttle:10,1')
        ->name('passenger.complaints.followUp');
});

// Server-side route lookup for the passenger map (cached)
Route::get('/route', [RouteController::class, 'fetch'])->middleware('throttle:60,1');

// Server-side geocoding proxy for the passenger map (cached)
Route::get('/geocode/reverse', [GeocodeController::class, 'reverse'])->middleware('throttle:60,1');
Route::get('/geocode/search', [GeocodeController::class, 'search'])->middleware('throttle:60,1');
