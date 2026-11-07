<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Rating;
use App\Services\RatingAdminService;
use Illuminate\Console\Command;

class AutoEscalateComplaints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'complaints:auto-escalate {--days=7 : Escalate solved complaints resolved this many days ago without an intervening review action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-escalate solved complaints into the TFRB/superadmin actionable queue after the post-resolution SLA window';

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $candidates = Rating::isValid()
            ->isComplaint()
            ->where('is_solved', true)
            ->where('solved_at', '<', $cutoff)
            ->with(['operator.user'])
            ->get();

        $escalated = 0;
        $skipped = 0;

        foreach ($candidates as $complaint) {
            // A follow-up review action after solving means the review team
            // already picked the case back up — never auto-escalate those.
            $followUp = ActivityLog::query()
                ->where('model_type', Rating::class)
                ->where('model_id', $complaint->id)
                ->where('category', 'review')
                ->where('created_at', '>', $complaint->solved_at)
                ->orderByDesc('created_at')
                ->first();

            if ($followUp) {
                $skipped++;
                continue;
            }

            app(RatingAdminService::class)->escalateSolvedComplaint($complaint);
            $escalated++;
        }

        $this->info("Auto-escalated {$escalated} solved complaint(s) older than {$days} day(s). Skipped {$skipped} with a follow-up action already recorded.");

        return self::SUCCESS;
    }
}
