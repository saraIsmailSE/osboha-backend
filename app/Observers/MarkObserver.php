<?php

namespace App\Observers;

use App\Models\Mark;
use App\Models\Week;

use App\Models\MarkStatistic;

class MarkObserver
{
   

    /**
     * Handle the Mark "updated" event.
     *
     * @param  \App\Models\Mark  $mark
     * @return void
     */
    public function updated(Mark $mark)
    {
        // $marks = $mark->get();
        // $mark_stat = MarkStatistic::latest()->first();
        // $mark_stat->total_users_have_100 = $mark->where('out_of_100',100)->count();
        // foreach($marks as $mark) {
        //     $mark_stat->total_pages += $mark['total_pages'];
        //     $mark_stat->total_thesis += $mark['total_thesis'];

        //   }
        // $count_user_have_100 =$mark->where('out_of_100',100)->count();
        // $count_users = $mark->count();
        // $mark_stat->general_average_reeding = $count_user_have_100 / $count_users *100 ;
        // $mark_stat->save();

        $old_mark = $mark->getOriginal();//$mark::where('updated_at','this week')->getOriginal();    
        $mark_stat = MarkStatistic::latest()->first();

        if($mark->out_of_100 == '100'){
            $mark_stat->total_users_have_100 += 1;
        }
        if($old_mark['out_of_100'] == '100'){
            if($mark_stat->total_users_have_100 !=0){
                $mark_stat->total_users_have_100 -= 1;
            }
        }
        $current_week =Week::latest()->first();
        if($old_mark['updated_at'] < $current_week->data()){
            $mark_stat->total_pages +=$mark['total_pages'];
            $mark_stat->total_thesis += $mark['total_thesis'];
            $count_users_have_mark = $mark->get()->count();
            $general_average_reeding = $mark['out_of_100'] / $count_users_have_mark *100;
            $mark_stat->general_average_reeding += $general_average_reeding;
        }
   
       else{
            $mark_stat->total_pages -= $old_mark['total_pages'];
            $mark_stat->total_thesis -= $old_mark['total_thesis'];
            $count_users_have_mark = $mark->get()->count();
            $general_average_reeding = $old_mark['out_of_100'] / $count_users_have_mark *100;
            $mark_stat->general_average_reeding -= $general_average_reeding;

            $mark_stat->total_pages +=$mark['total_pages'];
            $mark_stat->total_thesis += $mark['total_thesis'];
            $count_users_have_mark = $mark->get()->count();
            $general_average_reeding = $mark['out_of_100'] / $count_users_have_mark *100;
            $mark_stat->general_average_reeding += $general_average_reeding;
            
        }
        
        $mark_stat->save();

    }
}
