<?php

namespace App\Listeners;

use App\Models\User;
use App\Models\UserStatistic;

use App\Events\NewUserStats;
use App\Events\UpdateUserStats;
use App\Models\Week;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AddUserToStatistic
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\UserStats  $event
     * @return void
     */
    public function total_new_users()
    {
        $current_week = Week::latest()->pluck('id')->first();
        $user_stats = UserStatistic::where('week_id', $current_week)->first();
        $user_stats->total_new_users +=  1;
        $user_stats->save();
    }
    public function update_statistic_users($event)
    {
        $user = $event->user;
        $old_user = $event->old_user;

        $current_week = Week::latest()->pluck('id')->first();
        $user_stats = UserStatistic::where('week_id', $current_week)->first();
        if ($user['is_excluded'] == 1) {
            $user_stats->total_excluded_users +=  1;
        } else {
            $user_stats->total_excluded_users -=  1;
        }
        $user_stats->save();
    }
    public function subscribe($events)
    {
        $events->listen(
            NewUserStats::class,
            'App\Listeners\AddUserToStatistic@total_new_users'
        );
        $events->listen(
            UpdateUserStats::class,
            'App\Listeners\AddUserToStatistic@update_statistic_users'
        );
    }
}