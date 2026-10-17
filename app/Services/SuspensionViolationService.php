<?php

namespace App\Services;

use App\Helpers\ActivityLogger;
use App\Helpers\SupabaseStorage;
use App\Models\SuspensionViolation;
use App\Models\SuspensionViolationProof;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class SuspensionViolationService
{
    /**
     * Store a suspension-violation report filed by a TODA president. The report
     * flags a member who was suspended but is still observed operating, and is
     * queued for review by a TFRB officer or superadmin.
     *
     * @param array $data  violation_type, details, incident_at, location, suspension_id
     * @param UploadedFile[] $files proof attachments
     */
    public function report(\App\Models\Operator $member, \App\Models\Toda $toda, int $reportedBy, array $data, array $files = []): SuspensionViolation
    {
        $violation = SuspensionViolation::create([
            'operator_id' => $member->id,
            'toda_id' => $toda->id,
            'reported_by' => $reportedBy,
            'suspension_id' => $data['suspension_id'] ?? null,
            'violation_type' => $data['violation_type'],
            'details' => $data['details'],
            'incident_at' => $data['incident_at'],
            'location' => $data['location'] ?? null,
            'status' => 'pending',
        ]);

        $this->storeProofs($violation, $files);

        ActivityLogger::log(
            'report_violation',
            "Reported a suspension violation against {$member->user->name} ({$member->user->email}) in {$toda->name}",
            $violation,
            ActivityLogger::CATEGORY_OPERATOR
        );

        return $violation;
    }

    /**
     * Mark a violation as reviewed by a TFRB officer / superadmin. The record
     * stays open until it is solved with a penalty, but the president (and the
     * queue) can see that it has been acknowledged.
     */
    public function markReviewed(SuspensionViolation $violation, int $reviewedBy, ?string $note = null): SuspensionViolation
    {
        $violation->update([
            'reviewed_by' => $reviewedBy,
            'is_reviewed' => true,
            'status' => 'reviewed',
            'review_note' => $this->cleanNote($note),
        ]);

        ActivityLogger::log(
            'review_violation',
            "Reviewed suspension violation #{$violation->id} against {$violation->operator->user->name}",
            $violation,
            ActivityLogger::CATEGORY_REVIEW
        );

        return $violation;
    }

    /**
     * Solve a violation, optionally recording the penalty imposed and any
     * supporting proof files. Solving also implies the report was reviewed.
     */
    public function markSolved(SuspensionViolation $violation, int $reviewedBy, array $data = [], array $files = []): SuspensionViolation
    {
        $violation->update([
            'reviewed_by' => $violation->reviewed_by ?? $reviewedBy,
            'is_reviewed' => true,
            'is_solved' => true,
            'status' => 'solved',
            'solved_at' => now(),
            'resolution_note' => $this->cleanNote($data['resolution_note'] ?? null),
            'recommended_action' => $this->cleanNote($data['recommended_action'] ?? null),
            'penalty_imposed' => $this->cleanNote($data['penalty_imposed'] ?? null),
        ]);

        if (!empty($files)) {
            $this->storeProofs($violation, $files);
        }

        ActivityLogger::log(
            'solve_violation',
            "Solved suspension violation #{$violation->id} against {$violation->operator->user->name}",
            $violation,
            ActivityLogger::CATEGORY_REVIEW
        );

        return $violation;
    }

    /**
     * Reopen a solved violation so it can be re-examined. Proof attachments are
     * left intact — they are the original evidence filed by the president and
     * must survive the report being reopened.
     */
    public function reopen(SuspensionViolation $violation): SuspensionViolation
    {
        $violation->update([
            'is_solved' => false,
            'solved_at' => null,
            'is_reviewed' => false,
            'status' => 'pending',
            'reviewed_by' => null,
            'review_note' => null,
            'resolution_note' => null,
            'recommended_action' => null,
            'penalty_imposed' => null,
        ]);

        ActivityLogger::log(
            'reopen_violation',
            "Reopened suspension violation #{$violation->id} against {$violation->operator->user->name}",
            $violation,
            ActivityLogger::CATEGORY_REVIEW
        );

        return $violation;
    }

    /**
     * Persist proof attachments using the same Supabase-first, public-disk
     * fallback strategy used by the rating-resolution proofs.
     */
    private function storeProofs(SuspensionViolation $violation, array $files): void
    {
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                continue;
            }

            $filename = time() . '_' . uniqid() . '.' . $file->extension();
            $remotePath = 'violation-proofs/' . $violation->id . '/' . $filename;
            $path = null;

            try {
                if (class_exists(SupabaseStorage::class) && SupabaseStorage::upload($file, $remotePath)) {
                    $path = $remotePath;
                }
                if (!$path) {
                    $file->storeAs('violation-proofs/' . $violation->id, $filename, 'public');
                    $path = 'violation-proofs/' . $violation->id . '/' . $filename;
                }
            } catch (\Throwable $e) {
                Log::error('Violation proof save failed: ' . get_class($e) . ': ' . $e->getMessage(), ['path' => $remotePath]);
                $path = null;
            }

            if (!$path) {
                continue;
            }

            SuspensionViolationProof::create([
                'violation_id' => $violation->id,
                'file_path' => $path,
                'file_type' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
                'original_name' => $file->getClientOriginalName(),
            ]);
        }
    }

    private function clearProofs(SuspensionViolation $violation): void
    {
        SuspensionViolationProof::where('violation_id', $violation->id)->delete();
    }

    private function cleanNote(?string $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : $note;
    }
}
