<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Daily backup at 2:00 AM
        $schedule->command('backup:create daily')
            ->dailyAt('02:00')
            ->name('daily-backup')
            ->withoutOverlapping()
            ->runInBackground();

        // Weekly backup on Sunday at 3:00 AM
        $schedule->command('backup:create weekly')
            ->weeklyOn(0, '03:00')
            ->name('weekly-backup')
            ->withoutOverlapping()
            ->runInBackground();

        // Monthly backup on the 1st at 4:00 AM
        $schedule->command('backup:create monthly')
            ->monthlyOn(1, '04:00')
            ->name('monthly-backup')
            ->withoutOverlapping()
            ->runInBackground();

        // Cleanup old backups daily at 5:00 AM
        $schedule->command('backup:cleanup')
            ->dailyAt('05:00')
            ->name('backup-cleanup')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
