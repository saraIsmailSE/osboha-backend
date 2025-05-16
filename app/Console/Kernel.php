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

        //delete notifications older than 2 weeks
        ##########  00	10	*	*	7 ##########
        $schedule->command('notifications:deleteOldNotifications')->weekly()->sundays()->at('13:00');

        //delete posts of type announcement  older than 3 weeks
        ##########  00	09	*	*	7 ##########
        // $schedule->command('post:deleteOldAnnouncement')->weekly()->sundays()->at('13:10');

        //exclude new users
        ##########  30	09	*	*	7 ##########
        $schedule->command('users:exclude_new')->weekly()->sundays()->at('12:30');

        //auditMark
        ##########  00	19	*	*	7 ##########
        $schedule->command('generate:auditMark')->weekly()->sundays()->at('22:00');

        //delete old media
        ##########  30	20	*	*	7 ##########
        $schedule->command('media:deleteOld')->weekly()->sundays()->at('23:30');
        // $schedule->command('media:deleteOld')->dailyAt('16:04');

        ########## 05	19	*	*	3	 ##########
        $schedule->command('ModifyTimer:Week')->weekly()->wednesdays()->at('22:05');


        ########## mondays ##########

        //Finish Exceptions
        ##########  00	21	*	*	2 ##########
        // wed 00:00
        $schedule->command('userException:finished')->weekly()->wednesdays()->at('00:00');

        // wed 00:30
        //Set Mark For Exceptional Freeze
        ##########  30	21	*	*	2 ##########
        $schedule->command('exceptions:setMarkForExceptionalFreeze')->weekly()->wednesdays()->at('00:30');


        ########## fridays ##########

        //accept support for all
        // $schedule->command('support:accept')->weekly()->fridays()->at('22:30');

        //exclude users
        ########## 15	19	*	*	5 ##########
        $schedule->command('users:exclude')->weekly()->fridays()->at('22:15');


        //ramadan day create
        ########## 00	06	*	*	* ##########
        $schedule->command('ramadan:closeDay')->dailyAt('09:00');

        //remove old questions
        $schedule->command('questions:removeOld')->lastDayOfMonth('23:59');

        // $schedule->command('posts:cleanup')->weekly()->sundays()->at('23:00');
        // $schedule->command('posts:cleanup')->dailyAt('16:05');
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
