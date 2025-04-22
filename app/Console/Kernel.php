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
     */    protected function schedule(Schedule $schedule)
    {
        $schedule->command('send:match-reminders')->everyOddHour();
        $schedule->command('sync:fixtures')->cron('15 1,3,5,7,9,11,13,15,17,19,21,23 * * *');
        $schedule->command('sync:news')->cron('30 */3 * * *');
        $schedule->command('sync:standings')->dailyAt('00:10');
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
