<?php

namespace App\Services;

use App\Helpers\ActivityLogger;
use App\Helpers\SupabaseStorage;
use App\Models\Notification;
use App\Models\Rating;
use Illuminate\Http\RedirectResponse;

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
