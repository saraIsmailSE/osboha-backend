<?php

namespace App\Console;

use App\Console\Commands\generateAuditMark;
use App\Console\Commands\ModifyTimer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use Stringable;

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
        ########## Sunday ##########

        //insert New Week
        ##########  00	09	*	*	7 ##########
        $schedule->command('weekly:marks')->weekly()->sundays()->at('12:00'); //main part

        //exclude new users
        ##########  30	09	*	*	7 ##########
        $schedule->command('users:exclude_new')->weekly()->sundays()->at('12:30');

        //auditMark
        ##########  00	19	*	*	7 ##########
        $schedule->command('generate:auditMark')->weekly()->sundays()->at('22:00');

        //Finish Exceptions
        ##########  30	19	*	*	7 ##########
        $schedule->command('userException:finished')->weekly()->sundays()->at('22:30');

        //Set Mark For Exceptional Freeze
        ##########  00	20	*	*	7 ##########
        $schedule->command('exceptions:setMarkForExceptionalFreeze')->weekly()->sundays()->at('23:00');

        //delete old media
        ##########  30	20	*	*	7 ##########
        $schedule->command('media:deleteOld')->weekly()->sundays()->at('23:30');

        ########## 05	19	*	*	3	 ##########
        $schedule->command('ModifyTimer:Week')->weekly()->wednesdays()->at('22:05');


        //accept support for all
        // $schedule->command('support:accept')->weekly()->fridays()->at('22:30');

        //exclude users
        ########## 15	19	*	*	5 ##########
        $schedule->command('users:exclude')->weekly()->fridays()->at('22:15');

        //ramadan day create
        ########## 00	03	*	*	* ##########
        $schedule->command('ramadan:createDay')->dailyAt('06:00');
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
