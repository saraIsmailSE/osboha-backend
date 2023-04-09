<?php

namespace App\Listeners;

use App\Models\Mark;
use App\Models\MarkStatistic;
use App\Models\Week;


use App\Events\MarkStats;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AddMarkToStatistic
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
     * @param  \App\Events\MarkStats  $event
     * @return void
     */
    public function handle($event)
    {
        $this->updateStatisticMark($event->mark, $event->old_mark);
    }
    public function updateStatisticMark($mark, $old_mark)
    {
        $mark_stats = MarkStatistic::latest()->first();

        //updated by asmaa
        $old_mark_out_of_100 = $old_mark->reading_mark + $old_mark->writing_mark + $old_mark->support;
        $mark_out_of_100 = $mark->reading_mark + $mark->writing_mark + $mark->support;

        //Total Users Have 100 Marks
        if ($old_mark_out_of_100 >= 0 && $mark_out_of_100 == 100) {
            if ($old_mark_out_of_100 != 100)
                $mark_stats->total_users_have_100 += 1;
        } else if ($old_mark_out_of_100 == 100 && $mark_out_of_100 != 100) {
            $mark_stats->total_users_have_100 -= 1;
        }
        //Total Pages
        if ($old_mark['total_pages'] == 0) {
            $mark_stats->total_pages += $mark['total_pages'];
        } else {
            $mark_stats->total_pages -= $old_mark['total_pages'];
            $mark_stats->total_pages += $mark['total_pages'];
        }
        //Total Thesises
        if ($old_mark['total_thesis'] == 0) {
            $mark_stats->total_thesises += $mark['total_thesis'];
        } else {
            $mark_stats->total_thesises -= $old_mark['total_thesis'];
            $mark_stats->total_thesises += $mark['total_thesis'];
        }
        $current_week = Week::latest()->pluck('id')->first();
        $count_users_have_mark = Mark::where('week_id', $current_week)
            ->where('reading_mark', '>', 0)
            ->count();
        //Total Marks of Users
        if ($old_mark_out_of_100 == 0) {
            $total_marks_users  =  $mark_stats->total_marks_users;
            $total_marks_users += $mark_out_of_100;
        } else {
            $total_marks_users  =  $mark_stats->total_marks_users;
            $total_marks_users -=  $old_mark_out_of_100;
            $total_marks_users += $mark_out_of_100;
        }
        //General Average of Reading
        $mark_stats->total_marks_users = $total_marks_users;
        $mark_stats->general_average_reading = ($total_marks_users) / ($count_users_have_mark);
        $mark_stats->save();
    }
}