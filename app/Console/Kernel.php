<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Reconcile operator status with the franchise expiry date: lapsed
        // terms become inactive, valid terms stay/return active.
        $schedule->command('franchise:sync-status')->dailyAt('00:05');

        // Members whose TODA suspension has run its course are switched back to
        // active so a lapsed suspension never leaves them locked out.
        $schedule->command('suspension:sync-status')->dailyAt('00:06');

        // Solved complaints that went un-acted on past the post-resolution
        // SLA window are auto-escalated back into the TFRB/superadmin
        // actionable queue for a heavier re-review.
        $schedule->command('complaints:auto-escalate')->dailyAt('00:07');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
