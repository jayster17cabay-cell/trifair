<?php

namespace App\Services;

use App\Helpers\ActivityLogger;
use App\Helpers\SupabaseStorage;
use App\Mail\ComplaintStatus;
use App\Models\Notification;
use App\Models\Rating;
use App\Models\ResolutionProof;
use App\Models\User;
use App\Services\AdminDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Review-side rating operations shared by the Superadmin and TFRB Officer
 * roles and by TODA presidents (when a complaint is routed to them). Each
 * action enforces the assignment rule from ComplaintRoutingService before
 * touching the row — callers (the controllers) resolve the actor's queue
 * first; TFRB/superadmin and the owning president share these code paths.
 * The only role-specific differences are the wording of the activity
 * log/flash message ("rating" vs "complaint"), parameterized with a $noun
 * instead of being duplicated.
 */
class RatingAdminService
{
    /**
     * Enforce the complaint routing rule before any action runs:
     *
     * - TFRB/superadmin may act only on cases assigned to their queue
     *   (never routed to a president, or already forwarded to TFRB).
     * - The TODA president may act only on complaints routed to them and
     *   only until they are forwarded.
     *
     * Throws a 403 otherwise — this is a hard security boundary, not a UX
     * affordance.
     */
    private function assertCanActOnComplaint(Rating $rating, bool $presidentOnly = false): void
    {
        $role = Auth::user()?->role;
        $routedTo = (int) ($rating->routed_to ?? 0);

        if ($presidentOnly) {
            if ($role !== 'operator_president') {
                abort(403, 'Only the TODA president can forward complaints to the TFRB.');
            }
            if ($routedTo !== (int) Auth::id()) {
                abort(403, 'This complaint is not yours to forward.');
            }
            if ($rating->forwarded_at) {
                abort(403, 'This complaint has already been forwarded to the TFRB.');
            }
            return;
        }

        if (in_array($role, ['tfrb_officer', 'superadmin'], true)) {
            if ($routedTo !== 0 && $rating->forwarded_at === null) {
                abort(403, 'This complaint is with the TODA president and can only be acted on by them, or after being forwarded to the TFRB.');
            }
            return;
        }

        if ($role === 'operator_president') {
            if ($routedTo !== (int) Auth::id()) {
                abort(403, 'This complaint is not assigned to you.');
            }
            if ($rating->forwarded_at !== null) {
                abort(403, 'This complaint has been forwarded to the TFRB — it is no longer yours to act on.');
            }
            return;
        }

        abort(403);
    }

    public function markReviewed(Rating $rating): RedirectResponse
    {
        $rating->update(['is_reviewed' => true]);
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('mark_reviewed', "Marked rating #{$rating->id} as reviewed (operator: {$rating->operator_name})", $rating, 'review');

        return back()->with('success', 'Rating marked as reviewed.');
    }

    /**
     * Mark a complaint as reviewed by the responsible party. A complaint
     * counts towards the operator's star average once it has been reviewed
     * (unless it has been rejected), so reviewing is what makes the rating
     * "land" — analogous to the retired accept step.
     */
    public function complaintsMarkReviewed(Rating $rating): RedirectResponse
    {
        if (!$rating->complaint_type) {
            return back()->withErrors(['error' => 'Only complaints with a complaint type can be reviewed here.']);
        }
        $this->assertCanActOnComplaint($rating);

        $wasReviewed = (bool) $rating->is_reviewed;
        $rating->update(['is_reviewed' => true, 'reviewed_by' => Auth::id()]);
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('review_complaint', "Reviewed complaint #{$rating->id} (operator: {$rating->operator_name})", $rating, 'review');

        if (!$wasReviewed) {
            $emailError = $this->notifyComplaintStatus($rating, 'reviewed');
            $this->notifyPassengerInApp($rating, 'Complaint Reviewed', 'Your complaint has been reviewed by the responsible office. Thank you for helping keep our transport services safe.');
            $this->notifyAdmins($rating, 'Complaint Reviewed', "Complaint {$rating->reference_number} for {$rating->operator_name} has been reviewed and now counts towards the operator's rating.");
        } else {
            $emailError = null;
        }

        return $this->withEmailWarning(
            back()->with('success', 'Complaint marked as reviewed. It now counts towards the operator\'s rating.'),
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
        $this->assertCanActOnComplaint($rating);

        $note = $this->cleanResolutionNote($note);
        $wasRejected = (bool) $rating->rejected_at;

        $rating->update([
            'is_reviewed' => true,
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
            $this->notifyAdmins($rating, 'Complaint Rejected', "Complaint {$rating->reference_number} for {$rating->operator_name} was rejected and will not count towards the operator's rating.");
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
        $this->assertCanActOnComplaint($rating);

        $rating->update([
            'is_reviewed' => false,
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
        $this->assertCanActOnComplaint($rating);

        $note = $this->cleanResolutionNote($note);
        $wasSolved = (bool) $rating->is_solved;
        // Solving implies the complaint was legitimate: it is reviewed (and
        // counts), and any previous rejection marker is superseded.
        $rating->update([
            'is_reviewed' => true,
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
            $this->notifyAdmins($rating, 'Complaint Solved', "Complaint {$rating->reference_number} for {$rating->operator_name} was marked as solved.");
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
        $this->assertCanActOnComplaint($rating);

        // Reopening sends it back into the pending queue, so the reviewed
        // flag and any rejection marker must be cleared too — otherwise it
        // stays "reviewed", shows under the Reviewed filter, and the actions
        // never come back on the card.
        $rating->update([
            'is_solved' => false,
            'solved_at' => null,
            'is_reviewed' => false,
            'rejected_at' => null,
            'reviewed_by' => null,
            'resolution_note' => null,
        ]);
        $this->clearResolutionProofs($rating);
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('reopen_complaint', "Reopened complaint #{$rating->id} (operator: {$rating->operator_name})", $rating, 'review');

        return back()->with('success', 'Complaint reopened.');
    }

    /**
     * Escalate a solved complaint back into the actionable TFRB / superadmin
     * queue for a heavier re-review. This is the post-resolution dissatisfaction
     * path: when the passenger is not satisfied with the resolution that was
     * handed to them, the case leaves the terminal Solved state and returns to
     * the actionable pool so the review team can re-resolve it with a stronger
     * action (re-investigation, stronger remedy, ...).
     */
    public function complaintsEscalate(Rating $rating): RedirectResponse
    {
        if (!$rating->complaint_type) {
            return back()->withErrors(['error' => 'Only complaints with a complaint type can be escalated.']);
        }
        $this->assertCanActOnComplaint($rating);

        if (!$rating->is_solved) {
            return back()->withErrors(['error' => 'Only solved complaints can be escalated.']);
        }

        // Same state reset as a reopen — escalating sends the complaint back
        // into the actionable queue for a heavier re-resolution.
        $rating->update([
            'is_solved' => false,
            'solved_at' => null,
            'is_reviewed' => false,
            'rejected_at' => null,
            'reviewed_by' => null,
            'resolution_note' => null,
        ]);
        $this->clearResolutionProofs($rating);
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('escalate_complaint', "Escalated complaint #{$rating->id} to the TFRB/superadmin for a heavier re-review (operator: {$rating->operator_name})", $rating, 'review');

        $this->notifyAdmins($rating, 'Complaint Escalated', "Complaint {$rating->reference_number} for {$rating->operator_name} was escalated for a heavier re-review.");
        $this->notifyPassengerInApp($rating, 'Complaint Escalated', 'Your complaint was escalated for a heavier re-review by the TFRB/superadmin team. They will follow up with you.');

        return back()->with('success', 'Complaint escalated to the TFRB/superadmin for a heavier re-review.');
    }

    /**
     * President forwards a complaint they cannot resolve to TFRB/superadmin.
     * From here on the president keeps no action rights on the complaint —
     * only the TFRB/superadmin review team can act on it. They are all
     * notified; the passenger gets an email and an in-app notice as well.
     */
    public function complaintsForward(Rating $rating): RedirectResponse
    {
        if (!$rating->complaint_type) {
            return back()->withErrors(['error' => 'Only complaints with a complaint type can be forwarded.']);
        }
        $this->assertCanActOnComplaint($rating, true);

        if ($rating->forwarded_at) {
            return back()->with('success', 'This complaint has already been forwarded to the TFRB.');
        }

        $rating->update([
            'forwarded_at' => now(),
            'forwarded_by' => Auth::id(),
        ]);
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('forward_complaint', "Forwarded complaint #{$rating->id} to TFRB/superadmin (operator: {$rating->operator_name})", $rating, 'review');

        $president = Auth::user();
        $presidentName = $president->name ?? 'TODA president';
        $this->notifyAdminsOnForward($rating, "{$presidentName} forwarded complaint {$rating->reference_number} for {$rating->operator_name} to TFRB/superadmin.");
        $emailError = $this->notifyComplaintStatus($rating, 'forwarded');
        $this->notifyPassengerInApp($rating, 'Complaint Forwarded to TFRB', 'Your complaint has been forwarded to the TFRB/superadmin for further review. They will resolve it from here.');

        return $this->withEmailWarning(
            back()->with('success', 'Complaint forwarded to the TFRB/superadmin. They will handle it from here.'),
            $emailError
        );
    }

    /**
     * Notify the entire review team (officers + superadmins) that a
     * president has escalated a complaint to them.
     */
    private function notifyAdminsOnForward(Rating $rating, string $message): void
    {
        User::whereIn('role', ['tfrb_officer', 'superadmin'])
            ->get()
            ->each(function (User $user) use ($rating, $message) {
                Notification::create([
                    'user_id' => $user->id,
                    'rating_id' => $rating->id,
                    'type' => 'complaint',
                    'title' => 'Complaint Forwarded by President',
                    'message' => $message,
                ]);
            });
    }

    /**
     * Send an update into the complaint's chat thread from the TFRB/superadmin
     * side. The passenger sees it on their dashboard and gets an in-app
     * notification. Once a complaint is solved the chat is closed for both
     * sides — there is nothing left to discuss on a resolved ticket.
     */
    public function complaintsReply(Request $request, Rating $rating): RedirectResponse
    {
        if (!$rating->complaint_type) {
            return back()->withErrors(['error' => 'Only complaints with a complaint type can be replied to here.']);
        }
        $this->assertCanActOnComplaint($rating);

        if ($rating->is_solved) {
            return back()->withErrors(['chat' => 'This complaint is already resolved — the chat is closed.']);
        }

        $data = $request->validate([
            'message' => 'required|string|min:3|max:1000',
        ], [
            'message.required' => 'Enter your update before sending.',
            'message.min' => 'Enter at least 3 characters.',
            'message.max' => 'The message must not exceed 1,000 characters.',
        ]);

        $rating->passengerFollowUps()->create([
            'user_id' => Auth::id(),
            'message' => $data['message'],
            'sender_role' => 'staff',
        ]);

        ActivityLogger::log('reply_complaint', "Replied to complaint #{$rating->id} (operator: {$rating->operator_name})", $rating, 'review');

        $this->notifyPassengerInApp($rating, 'Complaint Update', 'The TFRB team replied to your complaint. Open My Complaints to read their message.');

        return back()->with('success', 'Your update has been sent to the passenger.');
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
            ->whereNull('rejected_at')
            ->where('is_reviewed', false)
            // Only complaints owned by this queue — president-routed cases
            // belong to the TODA president until they are forwarded.
            ->where(function ($query) {
                $query->whereNull('routed_to')->orWhereNotNull('forwarded_at');
            })
            ->with('operator.user')
            ->get()
            ->each(function ($rating) use (&$count, &$emailFailures) {
                $rating->update(['is_reviewed' => true, 'reviewed_by' => Auth::id()]);
                ActivityLogger::log('review_complaint', "Reviewed complaint #{$rating->id} (bulk, operator: {$rating->operator_name})", $rating, 'review');
                if ($this->notifyComplaintStatus($rating, 'reviewed')) {
                    $emailFailures++;
                }
                $this->notifyPassengerInApp($rating, 'Complaint Reviewed', 'Your complaint has been reviewed by the responsible office. Thank you for helping keep our transport services safe.');
                $count++;
            });

        app(AdminDashboardService::class)->flush();

        if ($count > 0) {
            $this->notifyAdminsSummary(
                'Bulk Complaints Reviewed',
                "{$count} complaint" . ($count === 1 ? '' : 's') . ' reviewed in a bulk review.'
            );
        }

        return $this->withEmailWarning(
            back()->with('success', $count > 0
                ? "{$count} complaint" . ($count === 1 ? '' : 's') . ' reviewed.'
                : 'No pending complaints were reviewed.'),
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
     * Keep the whole review team in the loop: every TFRB officer and superadmin
     * gets an in-app notification when a complaint moves through the pipeline,
     * except the officer who performed the action (they already saw the
     * confirmation banner). Presidents acting on their own pipeline don't
     * notify TFRB/superadmin — they only notify on forward.
     */
    private function notifyAdmins(Rating $rating, string $title, string $message): void
    {
        $actorId = Auth::id();
        $actorRole = Auth::user()?->role;

        if ($actorRole !== 'tfrb_officer' && $actorRole !== 'superadmin') {
            // Actions taken by a TODA president stay inside their pipeline
            // unless the case is forwarded to TFRB.
            return;
        }

        User::whereIn('role', ['tfrb_officer', 'superadmin'])
            ->when($actorId, function ($query) use ($actorId) {
                $query->where('id', '!=', $actorId);
            })
            ->get()
            ->each(function (User $officer) use ($rating, $title, $message) {
                Notification::create([
                    'user_id' => $officer->id,
                    'rating_id' => $rating->id,
                    'type' => 'complaint',
                    'title' => $title,
                    'message' => $message,
                ]);
            });
    }

    /**
     * Summary notification for a bulk action, so accepting fifty complaints at
     * once produces one notice per officer instead of flooding the list.
     */
    private function notifyAdminsSummary(string $title, string $message): void
    {
        $actorId = Auth::id();

        User::whereIn('role', ['tfrb_officer', 'superadmin'])
            ->when($actorId, function ($query) use ($actorId) {
                $query->where('id', '!=', $actorId);
            })
            ->get()
            ->each(function (User $officer) use ($title, $message) {
                Notification::create([
                    'user_id' => $officer->id,
                    'type' => 'complaint',
                    'title' => $title,
                    'message' => $message,
                ]);
            });
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
