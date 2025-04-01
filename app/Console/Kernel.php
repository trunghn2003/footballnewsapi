<?php

namespace App\Console;

use App\Jobs\SyncFixtureJob;
use App\Services\FixtureService;
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
        // $schedule->command('inspire')->hourly();
        $schedule->command('send:match-reminders')->everyMinute();

        $schedule->command('sync:fixtures')->dailyAt('18:00');
        $schedule->command('sync:fixtures')->dailyAt('19:00');
        $schedule->command('sync:fixtures')->dailyAt('20:00');
        $schedule->command('sync:fixtures')->dailyAt('21:00');
        $schedule->command('sync:fixtures')->dailyAt('22:00');
        $schedule->command('sync:fixtures')->dailyAt('23:00');
        $schedule->command('sync:fixtures')->dailyAt('00:00');
        $schedule->command('sync:fixtures')->dailyAt('01:00');
        $schedule->command('sync:fixtures')->dailyAt('02:00');
        $schedule->command('sync:fixtures')->dailyAt('03:00');
        $schedule->command('sync:fixtures')->dailyAt('04:00');
        $schedule->command('sync:fixtures')->dailyAt('05:00');
        $schedule->command('sync:fixtures')->dailyAt('06:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}