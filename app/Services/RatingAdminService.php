<?php

namespace App\Services;

use App\Helpers\ActivityLogger;
use App\Helpers\SupabaseStorage;
use App\Models\Notification;
use App\Models\Rating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        ActivityLogger::log('mark_reviewed', "Marked rating #{$rating->id} as reviewed (operator: {$rating->operator->user->name})", $rating, 'review');

        return back()->with('success', 'Rating marked as reviewed.');
    }

    public function complaintsMarkReviewed(Rating $rating): RedirectResponse
    {
        $rating->update(['is_reviewed' => true]);

        ActivityLogger::log('mark_reviewed', "Marked complaint #{$rating->id} as reviewed (operator: {$rating->operator->user->name})", $rating, 'review');

        return back()->with('success', 'Complaint marked as reviewed.');
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
            ->where('rating', '<=', 2)
            ->where('is_reviewed', false)
            ->with('operator.user')
            ->get()
            ->each(function ($rating) use (&$count) {
                $rating->update(['is_reviewed' => true]);
                ActivityLogger::log('mark_reviewed', "Marked complaint #{$rating->id} as reviewed (bulk, operator: {$rating->operator->user->name})", $rating, 'review');
                $count++;
            });

        return back()->with('success', $count > 0
            ? "{$count} complaint" . ($count === 1 ? '' : 's') . ' marked as reviewed.'
            : 'No pending complaints were marked.');
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

        ActivityLogger::log('delete_complaint', "Deleted {$noun} #{$rating->id} (operator: {$operatorName})", null, 'review');

        return back()->with('success', 'Complaint deleted successfully.');
    }

    public function restore(Rating $rating): RedirectResponse
    {
        $rating->update(['is_valid' => $rating->evaluateValidity()]);

        ActivityLogger::log('restore_rating', "Restored rating #{$rating->id} as valid (operator: {$rating->operator->user->name})", $rating, 'review');

        $message = $rating->is_valid
            ? "Rating restored as valid. It will count towards the operator's average again."
            : 'Rating still missing required data (route location and/or proof for low ratings) and remains invalid.';

        return redirect()->back()->with('success', $message);
    }
}
