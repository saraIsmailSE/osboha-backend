<?php

namespace App\Console;

use App\Console\Commands\generateAuditMark;
use App\Console\Commands\ModifyTimer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected  $commands = [generateAuditMark::class];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //type php artisan schedule:work in the terminal to run (run the test part and stop the main part)
        $schedule->command('weekly:marks')->weekly()->saturdays()->at('21:00'); //main part
        //auditMark
        $schedule->command('generate:auditMark')->weekly()->sundays()->at('09:00');
        $schedule->command('ModifyTimer:Week')->weekly()->tuesdays()->at('09:00');
        //finishedException 
        
        // $schedule->command('userException:finished')->weekly()->sundays()->at('8:00');

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
