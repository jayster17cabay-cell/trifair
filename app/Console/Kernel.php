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
        // Operators whose franchise has lapsed are switched to inactive so the
        // admin lists and login checks agree with the franchise expiry date.
        $schedule->command('franchise:sync-status')->dailyAt('00:05');

        // Members whose TODA suspension has run its course are switched back to
        // active so a lapsed suspension never leaves them locked out.
        $schedule->command('suspension:sync-status')->dailyAt('00:06');
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
