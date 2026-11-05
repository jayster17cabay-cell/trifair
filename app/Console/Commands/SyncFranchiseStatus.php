<?php

namespace App\Console\Commands;

use App\Models\Operator;
use Illuminate\Console\Command;

class SyncFranchiseStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'franchise:sync-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync operator status with the current franchise expiry';

    public function handle(): int
    {
        $deactivated = Operator::deactivateExpiredFranchises();
        $reactivated = Operator::reactivateValidFranchises();

        $this->info("Deactivated {$deactivated} operator(s) with an expired franchise.");
        $this->info("Reactivated {$reactivated} operator(s) with a valid franchise.");

        return self::SUCCESS;
    }
}
