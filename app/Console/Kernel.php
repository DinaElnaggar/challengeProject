<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Nightly at 01:00 roll up yesterday's logins
        $schedule->command('analytics:rollup-login-daily')->dailyAt('01:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}

