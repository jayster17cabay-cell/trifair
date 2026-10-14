<?php

namespace App\Console\Commands;

use App\Models\Operator;
use Illuminate\Console\Command;

class SyncSuspensionStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suspension:sync-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reactivate members whose TODA suspension has already ended';

    public function handle(): int
    {
        $changed = Operator::endExpiredSuspensions();

        $this->info("Reconciled {$changed} suspended member(s) whose suspension has ended.");

        return self::SUCCESS;
    }
}
