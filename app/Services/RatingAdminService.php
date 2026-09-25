<?php

namespace App\Services;

use App\Helpers\ActivityLogger;
use App\Helpers\SupabaseStorage;
use App\Mail\ComplaintStatus;
use App\Models\Notification;
use App\Models\Rating;
use App\Services\AdminDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Review-side rating operations shared by the Superadmin and TFRB Officer
 * roles. The only differences between the two roles were the wording of the
 * activity log/flash message, so those are parameterized with a $noun
 * ("rating" vs "complaint") instead of being duplicated.
 */
class RatingAdminService
{
    public function markReviewed(Rating $rating): RedirectResponse
    {
        $rating->update(['is_reviewed' => true]);
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('mark_reviewed', "Marked rating #{$rating->id} as reviewed (operator: {$rating->operator->user->name})", $rating, 'review');

        return back()->with('success', 'Rating marked as reviewed.');
    }

    public function complaintsMarkReviewed(Rating $rating): RedirectResponse
    {
        $wasReviewed = (bool) $rating->is_reviewed;
        $rating->update(['is_reviewed' => true]);
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('mark_reviewed', "Marked complaint #{$rating->id} as reviewed (operator: {$rating->operator->user->name})", $rating, 'review');

        if (!$wasReviewed) {
            $this->notifyComplaintStatus($rating, 'reviewed');
            $this->notifyPassengerInApp($rating, 'Complaint Reviewed', 'Your complaint has been reviewed by the TFRB.');
        }

        return back()->with('success', 'Complaint marked as reviewed.');
    }

    /**
     * Mark a complaint as solved. Solving also implies the complaint has been
     * reviewed. Always notifies the passenger (only when transitioning out of
     * the solved state) so a re-solve does not spam their inbox.
     */
    public function complaintsMarkSolved(Rating $rating): RedirectResponse
    {
        $wasSolved = (bool) $rating->is_solved;
        $rating->update(['is_reviewed' => true, 'is_solved' => true, 'solved_at' => now()]);
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('mark_solved', "Marked complaint #{$rating->id} as solved (operator: {$rating->operator->user->name})", $rating, 'review');

        if (!$wasSolved) {
            $this->notifyComplaintStatus($rating, 'solved');
            $this->notifyPassengerInApp($rating, 'Complaint Solved', 'Your complaint has been marked as solved.');
        }

        return back()->with('success', 'Complaint marked as solved.');
    }

    public function complaintsReopen(Rating $rating): RedirectResponse
    {
        $rating->update(['is_solved' => false, 'solved_at' => null]);
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('reopen_complaint', "Reopened complaint #{$rating->id} (operator: {$rating->operator->user->name})", $rating, 'review');

        return back()->with('success', 'Complaint reopened.');
    }

    public function complaintsBulkReview(Request $request): RedirectResponse
    {
        $raw = $request->input('ids');
        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
        $ids = collect(is_array($decoded) ? $decoded : [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($ids->isEmpty()) {
            return back()->with('error', 'No complaints selected.');
        }

        $count = 0;
        Rating::whereIn('id', $ids)
            ->isValid()
            ->whereNotNull('complaint_type')
            ->where('is_reviewed', false)
            ->with('operator.user')
            ->get()
            ->each(function ($rating) use (&$count) {
                $rating->update(['is_reviewed' => true]);
                ActivityLogger::log('mark_reviewed', "Marked complaint #{$rating->id} as reviewed (bulk, operator: {$rating->operator->user->name})", $rating, 'review');
                $this->notifyComplaintStatus($rating, 'reviewed');
                $count++;
            });

        app(AdminDashboardService::class)->flush();

        return back()->with('success', $count > 0
            ? "{$count} complaint" . ($count === 1 ? '' : 's') . ' marked as reviewed.'
            : 'No pending complaints were marked.');
    }

    /**
     * Email the passenger the moment their complaint changes status, but only if
     * they left an email address. A delivery failure must never break the admin
     * action, so the send is guarded and the error is logged instead.
     */
    private function notifyComplaintStatus(Rating $rating, string $status): void
    {
        // Prefer the address stored on the complaint, which for connected
        // passengers is their linked Google account email.
        $email = $rating->passenger_email ?: ($rating->passenger_user ? $rating->passenger_user->email : null);
        if (!$email) {
            return;
        }

        try {
            Mail::to($email)->send(new ComplaintStatus($rating, $status));
        } catch (\Throwable $e) {
            Log::error('Complaint status email failed: ' . get_class($e) . ': ' . $e->getMessage());
        }
    }

    /**
     * In-app notification for a linked passenger account so they see the status
     * change on their dashboard without relying on email delivery.
     */
    private function notifyPassengerInApp(Rating $rating, string $title, string $message): void
    {
        if (!$rating->passenger_user_id) {
            return;
        }

        Notification::create([
            'user_id' => $rating->passenger_user_id,
            'rating_id' => $rating->id,
            'type' => 'complaint_status',
            'title' => $title,
            'message' => $message,
        ]);
    }

    public function ratingsBulkReview(Request $request): RedirectResponse
    {
        $raw = $request->input('ids');
        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
        $ids = collect(is_array($decoded) ? $decoded : [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($ids->isEmpty()) {
            return back()->with('error', 'No ratings selected.');
        }

        $count = 0;
        Rating::whereIn('id', $ids)
            ->isValid()
            ->where('is_reviewed', false)
            ->with('operator.user')
            ->get()
            ->each(function ($rating) use (&$count) {
                $rating->update(['is_reviewed' => true]);
                ActivityLogger::log('mark_reviewed', "Marked rating #{$rating->id} as reviewed (bulk, operator: {$rating->operator->user->name})", $rating, 'review');
                $count++;
            });

        app(AdminDashboardService::class)->flush();

        return back()->with('success', $count > 0
            ? "{$count} rating" . ($count === 1 ? '' : 's') . ' marked as reviewed.'
            : 'No pending ratings were marked.');
    }

    public function destroyComplaint(Rating $rating, string $noun = 'complaint'): RedirectResponse
    {
        $operatorName = $rating->operator->user->name ?? 'Unknown';

        foreach ($rating->proofs as $proof) {
            SupabaseStorage::delete($proof->file_path);
        }

        $rating->proofs()->delete();
        $rating->response()->delete();
        Notification::where('rating_id', $rating->id)->delete();
        $rating->delete();
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('delete_complaint', "Deleted {$noun} #{$rating->id} (operator: {$operatorName})", null, 'review');

        return back()->with('success', 'Complaint deleted successfully.');
    }

    public function restore(Rating $rating): RedirectResponse
    {
        $rating->update(['is_valid' => $rating->evaluateValidity()]);
        app(AdminDashboardService::class)->flush();

        $restoredAsValid = $rating->is_valid ? 'Restored rating ' : 'Attempted to restore rating ';
        ActivityLogger::log('restore_rating', "{$restoredAsValid}#{$rating->id} (now " . ($rating->is_valid ? 'valid' : 'still invalid') . ", operator: {$rating->operator->user->name})", $rating, 'review');

        $message = $rating->is_valid
            ? "Rating restored as valid. It will count towards the operator's average again."
            : 'Rating still missing required data (route location and/or proof for low ratings) and remains invalid.';

        return redirect()->back()->with('success', $message);
    }
}
