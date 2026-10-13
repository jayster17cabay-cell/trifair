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
    protected $description = 'Mark operators inactive when their current franchise has expired';

    public function handle(): int
    {
        $changed = Operator::deactivateExpiredFranchises();

        $this->info("Deactivated {$changed} operator(s) with an expired franchise.");

        return self::SUCCESS;
    }
}
