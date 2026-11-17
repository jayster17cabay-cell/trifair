<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\RatingAdminService;
use Illuminate\Console\Command;

class AutoForwardStaleComplaints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'complaints:auto-forward-stale {--days= : Forward complaints unresolved with a TODA president for this many days. Defaults to the configured auto-forward window (15).}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-forward complaints that the TODA president did not resolve within the SLA window to the TFRB / superadmin.';

    public function handle(RatingAdminService $service): int
    {
        $days = $this->option('days') !== null && $this->option('days') !== ''
            ? (int) $this->option('days')
            : (int) Setting::getValue('complaint_auto_forward_days', 15);

        if ($days <= 0) {
            $this->info('Auto-forward disabled (days = 0).');
            return self::SUCCESS;
        }

        $days = max(1, $days);

        $result = $service->autoForwardStaleComplaints($days);

        $this->info("Auto-forwarded {$result['forwarded']} stale complaint(s) older than {$days} day(s). Skipped {$result['skipped']} already-resolved/forwarded.");

        return self::SUCCESS;
    }
}