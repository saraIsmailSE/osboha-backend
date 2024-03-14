<?php

namespace App\Traits;

use App\Models\Group;
use App\Models\Mark;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Week;
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

            if ($avg && (count($users_in_group) - $total_freezed) !=0 )
                return $avg->out_of_100 / (count($users_in_group) - $total_freezed);

            return 0;
        } catch (\Error $e) {
            report($e);
            return false;
        }
    }

    function usersByWeek($group_id, $week_id, $user_type)
    {
        $week = Week::find($week_id);
        $weekPlusSevenDays = $week->created_at->addDays(7);

        return UserGroup::where(function ($query) use ($week, $weekPlusSevenDays, $group_id) {
            $query->where('created_at', '<=', $week->created_at)
                ->where('updated_at', '>=', $weekPlusSevenDays)
                ->where('group_id', $group_id);
        })
            ->orWhere(function ($query) use ($weekPlusSevenDays, $group_id) {
                $query->where('created_at', '<=', $weekPlusSevenDays)
                    ->whereNull('termination_reason')
                    ->where('group_id', $group_id);
            })
            ->whereIn('user_type', $user_type)
            ->get();
    }

    function groupsUsersByWeek($groups_id, $week_id, $user_type)
    {
        $week = Week::find($week_id);
        $weekPlusSevenDays = $week->created_at->addDays(7);

        return UserGroup::where(function ($query) use ($week, $weekPlusSevenDays, $groups_id) {
            $query->where('created_at', '<=', $week->created_at)
                ->where('updated_at', '>=', $weekPlusSevenDays)
                ->whereIn('group_id', $groups_id);
        })
            ->orWhere(function ($query) use ($weekPlusSevenDays, $groups_id) {
                $query->where('created_at', '<=', $weekPlusSevenDays)
                    ->whereNull('termination_reason')
                    ->whereIn('group_id', $groups_id);
            })
            ->whereIn('user_type', $user_type)
            ->get();
    }
}
