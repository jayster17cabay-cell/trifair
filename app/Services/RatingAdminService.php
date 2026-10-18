<?php

namespace App\Services;

use App\Helpers\ActivityLogger;
use App\Helpers\SupabaseStorage;
use App\Mail\ComplaintStatus;
use App\Models\Notification;
use App\Models\Rating;
use App\Models\ResolutionProof;
use App\Services\AdminDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        ActivityLogger::log('mark_reviewed', "Marked rating #{$rating->id} as reviewed (operator: {$rating->operator_name})", $rating, 'review');

        return back()->with('success', 'Rating marked as reviewed.');
    }

    /**
     * Accept a complaint: the officer has reviewed it and considers it a real,
     * legitimate report. Only accepted 1-2 star complaints count towards the
     * operator's star average, so accepting is what makes the rating "land".
     */
    public function complaintsAccept(Rating $rating): RedirectResponse
    {
        if (!$rating->complaint_type) {
            return back()->withErrors(['error' => 'Only complaints with a complaint type can be reviewed here.']);
        }

        $wasAccepted = $rating->is_accepted === true;
        $rating->update(['is_reviewed' => true, 'is_accepted' => true]);
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('accept_complaint', "Accepted complaint #{$rating->id} (operator: {$rating->operator_name})", $rating, 'review');

        if (!$wasAccepted) {
            $emailError = $this->notifyComplaintStatus($rating, 'accepted');
            $this->notifyPassengerInApp($rating, 'Complaint Accepted', 'Your complaint has been verified and accepted by the TFRB. Thank you for helping keep our transport services safe.');
        } else {
            $emailError = null;
        }

        return $this->withEmailWarning(
            back()->with('success', 'Complaint accepted. It now counts towards the operator\'s rating.'),
            $emailError
        );
    }

    /**
     * Reject a complaint: the officer has reviewed it and found it is not a
     * valid report (e.g. fake or unverifiable). A rejected complaint is a dead
     * end — it is never solved and never counts towards the operator's rating.
     */
    public function complaintsReject(Rating $rating, ?string $note = null): RedirectResponse
    {
        if (!$rating->complaint_type) {
            return back()->withErrors(['error' => 'Only complaints with a complaint type can be reviewed here.']);
        }

        $note = $this->cleanResolutionNote($note);
        $wasRejected = $rating->is_accepted === false;

        $rating->update([
            'is_reviewed' => true,
            'is_accepted' => false,
            'is_solved' => false,
            'solved_at' => null,
            'rejected_at' => now(),
            'reviewed_by' => Auth::id(),
            'resolution_note' => $note,
        ]);
        // A rejection carries no resolution proof, so drop any evidence left
        // over from a previous "solved" state to avoid showing stale files.
        $this->clearResolutionProofs($rating);
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('reject_complaint', "Rejected complaint #{$rating->id} (operator: {$rating->operator_name})", $rating, 'review');

        if (!$wasRejected) {
            $emailError = $this->notifyComplaintStatus($rating, 'rejected');
            $message = 'After review, your complaint was not validated and will not affect the operator\'s record. Thank you for your report.';
            if ($note) {
                $message .= ' Reason: ' . $note;
            }
            $this->notifyPassengerInApp($rating, 'Complaint Rejected', $message);
        } else {
            $emailError = null;
        }

        return $this->withEmailWarning(
            back()->with('success', 'Complaint rejected. It will not count towards the operator\'s rating.'),
            $emailError
        );
    }

    /**
     * Undo a review decision and return the complaint to the pending queue so
     * it can be reviewed again.
     */
    public function complaintsResetReview(Rating $rating): RedirectResponse
    {
        if (!$rating->complaint_type) {
            return back()->withErrors(['error' => 'Only complaints with a complaint type can be reset here.']);
        }

        $rating->update([
            'is_reviewed' => false,
            'is_accepted' => null,
            'is_solved' => false,
            'solved_at' => null,
            'rejected_at' => null,
            'reviewed_by' => null,
            'resolution_note' => null,
        ]);
        $this->clearResolutionProofs($rating);
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('reset_complaint', "Returned complaint #{$rating->id} to pending review (operator: {$rating->operator_name})", $rating, 'review');

        return back()->with('success', 'Complaint returned to pending review.');
    }

    /**
     * Mark a complaint as solved. Solving also implies the complaint has been
     * reviewed. The passenger is notified only when the complaint transitions
     * out of the solved state so a re-solve does not spam their inbox. An
     * optional resolution note and proof attachments are stored so the
     * passenger can see how the matter was resolved.
     */
    public function complaintsMarkSolved(Rating $rating, ?string $note = null, array $files = []): RedirectResponse
    {
        if (!$rating->complaint_type) {
            return back()->withErrors(['error' => 'Only complaints with a complaint type can be marked solved here.']);
        }

        $note = $this->cleanResolutionNote($note);
        $wasSolved = (bool) $rating->is_solved;
        // Solving implies the complaint was legitimate, so it is accepted too:
        // a resolved complaint must always count towards the operator's record.
        $rating->update([
            'is_reviewed' => true,
            'is_accepted' => true,
            'is_solved' => true,
            'solved_at' => now(),
            'rejected_at' => null,
            'reviewed_by' => Auth::id(),
            'resolution_note' => $note,
        ]);

        // Only replace the evidence when fresh files are supplied, so re-saving
        // a solved complaint (e.g. editing the note) keeps the existing proof.
        if (!empty($files)) {
            $this->clearResolutionProofs($rating);
            $this->storeResolutionProofs($rating, $files);
        }
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('mark_solved', "Marked complaint #{$rating->id} as solved (operator: {$rating->operator_name})", $rating, 'review');

        if (!$wasSolved) {
            $emailError = $this->notifyComplaintStatus($rating, 'solved');
            $message = 'Your complaint has been marked as solved.';
            if ($note) {
                $message .= ' Details: ' . $note;
            }
            $this->notifyPassengerInApp($rating, 'Complaint Solved', $message);
        } else {
            $emailError = null;
        }

        return $this->withEmailWarning(
            back()->with('success', 'Complaint marked as solved.'),
            $emailError
        );
    }

    public function complaintsReopen(Rating $rating): RedirectResponse
    {
        if (!$rating->complaint_type) {
            return back()->withErrors(['error' => 'Only complaints with a complaint type can be reopened.']);
        }

        // Reopening sends it back into the pending queue, so the reviewed and
        // accepted flags must be cleared too — otherwise it stays "accepted",
        // is invisible under the Pending filter, and the "Mark Reviewed" action
        // never comes back on the card.
        $rating->update([
            'is_solved' => false,
            'solved_at' => null,
            'is_reviewed' => false,
            'is_accepted' => null,
            'rejected_at' => null,
            'reviewed_by' => null,
            'resolution_note' => null,
        ]);
        $this->clearResolutionProofs($rating);
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('reopen_complaint', "Reopened complaint #{$rating->id} (operator: {$rating->operator_name})", $rating, 'review');

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
        $emailFailures = 0;
        Rating::whereIn('id', $ids)
            ->isValid()
            ->whereNotNull('complaint_type')
            ->whereNull('is_accepted')
            ->with('operator.user')
            ->get()
            ->each(function ($rating) use (&$count, &$emailFailures) {
                $rating->update(['is_reviewed' => true, 'is_accepted' => true]);
                ActivityLogger::log('accept_complaint', "Accepted complaint #{$rating->id} (bulk, operator: {$rating->operator_name})", $rating, 'review');
                if ($this->notifyComplaintStatus($rating, 'accepted')) {
                    $emailFailures++;
                }
                $this->notifyPassengerInApp($rating, 'Complaint Accepted', 'Your complaint has been verified and accepted by the TFRB. Thank you for helping keep our transport services safe.');
                $count++;
            });

        app(AdminDashboardService::class)->flush();

        return $this->withEmailWarning(
            back()->with('success', $count > 0
                ? "{$count} complaint" . ($count === 1 ? '' : 's') . ' accepted.'
                : 'No pending complaints were accepted.'),
            $emailFailures > 0 ? "{$emailFailures} passenger" . ($emailFailures === 1 ? '' : 's') . ' could not be emailed about this update. Check the server log.' : null
        );
    }

    /**
     * Email the passenger the moment their complaint changes status, but only if
     * they left an email address. A delivery failure must never break the admin
     * action, so the send is guarded: the error is logged and also returned so
     * the caller can surface it, instead of failing silently where nobody sees it.
     */
    private function notifyComplaintStatus(Rating $rating, string $status): ?string
    {
        // Prefer the address stored on the complaint, which for connected
        // passengers is their linked Google account email.
        $email = $rating->passenger_email ?: ($rating->passenger?->email);
        if (!$email) {
            return null;
        }

        try {
            Mail::to($email)->send(new ComplaintStatus($rating, $status));

            return null;
        } catch (\Throwable $e) {
            Log::error('Complaint status email failed: ' . get_class($e) . ': ' . $e->getMessage());

            return "The passenger could not be emailed about this update ({$e->getMessage()}).";
        }
    }

    /**
     * Attach an email-delivery failure to the redirect as a warning banner so the
     * officer immediately sees that the passenger was not notified.
     */
    private function withEmailWarning(RedirectResponse $redirect, ?string $emailError): RedirectResponse
    {
        return $emailError ? $redirect->with('warning', $emailError) : $redirect;
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

    /**
     * Normalise a resolution note coming from the review forms. Empty strings
     * collapse to null so the column stays clean and the "has a note" checks
     * elsewhere keep working.
     */
    private function cleanResolutionNote(?string $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : $note;
    }

    /**
     * Persist officer-supplied resolution evidence, mirroring the storage
     * strategy used for passenger/operator proofs: Supabase Storage when
     * available, with a public-disk fallback so nothing is lost in tests or
     * when the remote bucket is unreachable.
     */
    private function storeResolutionProofs(Rating $rating, array $files): void
    {
        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $filename = time() . '_' . uniqid() . '.' . $file->extension();
            $remotePath = 'resolution-proofs/' . $rating->id . '/' . $filename;
            $path = null;

            try {
                if (class_exists(SupabaseStorage::class) && SupabaseStorage::upload($file, $remotePath)) {
                    $path = $remotePath;
                }
                if (!$path) {
                    $file->storeAs('resolution-proofs/' . $rating->id, $filename, 'public');
                    $path = 'resolution-proofs/' . $rating->id . '/' . $filename;
                }
            } catch (\Throwable $e) {
                Log::error('Resolution proof save failed: ' . get_class($e) . ': ' . $e->getMessage(), ['path' => $remotePath]);
                $path = null;
            }

            if (!$path) {
                continue;
            }

            ResolutionProof::create([
                'rating_id' => $rating->id,
                'file_path' => $path,
                'file_type' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        $rating->unsetRelation('resolutionProofs');
    }

    /**
     * Remove every stored resolution proof for a complaint (rows + remote
     * files). Used when rejecting, undoing a review or replacing evidence so
     * stale attachments never linger.
     */
    private function clearResolutionProofs(Rating $rating): void
    {
        $proofs = $rating->resolutionProofs;

        foreach ($proofs as $proof) {
            try {
                SupabaseStorage::delete($proof->file_path);
            } catch (\Throwable $e) {
                Log::error('Resolution proof delete failed: ' . get_class($e) . ': ' . $e->getMessage());
            }
        }

        if ($proofs->isNotEmpty()) {
            $rating->resolutionProofs()->delete();
        }

        $rating->unsetRelation('resolutionProofs');
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
            ->whereNull('complaint_type')
            ->where('is_reviewed', false)
            ->with('operator.user')
            ->get()
            ->each(function ($rating) use (&$count) {
                $rating->update(['is_reviewed' => true]);
                ActivityLogger::log('mark_reviewed', "Marked rating #{$rating->id} as reviewed (bulk, operator: {$rating->operator_name})", $rating, 'review');
                $count++;
            });

        app(AdminDashboardService::class)->flush();

        return back()->with('success', $count > 0
            ? "{$count} rating" . ($count === 1 ? '' : 's') . ' marked as reviewed.'
            : 'No pending ratings were marked.');
    }

    public function destroyComplaint(Rating $rating, string $noun = 'complaint'): RedirectResponse
    {
        // The superadmin ratings page reuses this endpoint's button, but that
        // page lists regular (3-5 star, no complaint type) ratings. Hard-deleting
        // those here without a complaint type would erase legitimate feedback, so
        // the endpoint is restricted to real complaints.
        if (!$rating->complaint_type) {
            return back()->withErrors(['error' => 'Only complaints can be deleted here.']);
        }

        $operatorName = $rating->operator->user->name ?? 'Unknown';

        foreach ($rating->proofs as $proof) {
            SupabaseStorage::delete($proof->file_path);
        }
        foreach ($rating->operatorProofs as $proof) {
            SupabaseStorage::delete($proof->file_path);
        }

        $rating->proofs()->delete();
        $rating->operatorProofs()->delete();
        $rating->response()->delete();
        Notification::where('rating_id', $rating->id)->delete();
        $rating->delete();
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('delete_complaint', "Deleted {$noun} #{$rating->id} (operator: {$operatorName})", null, 'review');

        return back()->with('success', 'Complaint deleted successfully.');
    }
}
