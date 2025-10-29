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
        // Archiver les comptes bloqués dont la date de début est dépassée
        $schedule->job(\App\Jobs\ArchiveExpiredBlockedAccounts::class)
            ->dailyAt('02:00')
            ->name('archive-expired-blocked-accounts')
            ->withoutOverlapping();

        // Désarchiver les comptes bloqués dont la date de fin est dépassée
        $schedule->job(\App\Jobs\UnarchiveExpiredBlockedAccounts::class)
            ->dailyAt('02:30')
            ->name('unarchive-expired-blocked-accounts')
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
