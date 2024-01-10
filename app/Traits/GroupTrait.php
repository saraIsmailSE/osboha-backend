<?php

namespace App\Traits;

use App\Models\Group;
use App\Models\Mark;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Image;

trait GroupTrait
{
    function groupAvg($group_id, $week_id, $users_in_group)
    {
        try {
            $avg = Mark::without('user,week')->where('week_id', $week_id)
                ->whereIn('user_id', $users_in_group)
                ->where('is_freezed', 0)
                ->select(
                    DB::raw('SUM(reading_mark + writing_mark + support) as out_of_100')
                )->first();
                
                $total_freezed =
                Mark::without('user,week')->where('week_id', $week_id)
                ->whereIn('user_id', $users_in_group)
                ->where('is_freezed', 1)
                ->count();
    
            if ($avg)
                return $avg->out_of_100 / (count($users_in_group) - $total_freezed);

            return 0;
        } catch (\Error $e) {
            report($e);
            return false;
        }
    }
}
