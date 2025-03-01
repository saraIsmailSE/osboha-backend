<?php

namespace App\Traits;

use App\Models\Mark;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Week;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait GroupTrait
{
    function groupAvg($group_id, $week_id, $users_in_group)
    {
        try {
            $marks = Mark::without('user', 'week')
                ->where('week_id', $week_id)
                ->whereIn('user_id', $users_in_group)
                ->where('is_freezed', 0)
                ->distinct('user_id')
                ->select(
                    'user_id',
                    DB::raw('SUM(reading_mark + writing_mark + support) as out_of_100')
                )
                ->get();

            $total_out_of_100 = $marks->sum('out_of_100');

            $total_freezed = Mark::without('user', 'week')
                ->where('week_id', $week_id)
                ->whereIn('user_id', $users_in_group)
                ->where('is_freezed', 1)
                ->distinct('user_id')
                ->count('user_id');

            // Log::channel('newWeek')->info('total_out_of_100: ' . $total_out_of_100);
            // Log::channel('newWeek')->info('count users_in_group: ' . count($users_in_group));
            // Log::channel('newWeek')->info('total_freezed: ' . $total_freezed);

            if ($total_out_of_100 && (count($users_in_group) - $total_freezed) != 0) {
                // Log::channel('newWeek')->info('AVG: ' . $total_out_of_100 / (count($users_in_group) - $total_freezed));
                return $total_out_of_100 / (count($users_in_group) - $total_freezed);
            }

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
        ->distinct('user_id')
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
            ->distinct('user_id')
            ->get();
    }
    function membersReading($membersIDs, $week_id)
    {
        return User::leftJoin('marks', function ($join) use ($week_id) {
            $join->on('users.id', '=', 'marks.user_id')
                ->where('marks.week_id', '=', $week_id);
        })
            ->whereIn('users.id', $membersIDs)
            ->select([
                'users.id AS user_id',
                'users.name AS name',
                DB::raw($week_id . ' AS week_id'),
                DB::raw('COALESCE(marks.reading_mark, 0) AS reading_mark'),
                DB::raw('COALESCE(marks.writing_mark, 0) AS writing_mark'),
                DB::raw('COALESCE(marks.total_pages, 0) AS total_pages'),
                DB::raw('COALESCE(marks.support, 0) AS support'),
                DB::raw('COALESCE(marks.total_thesis, 0) AS total_thesis'),
                DB::raw('COALESCE(marks.total_screenshot, 0) AS total_screenshot'),
                DB::raw('COALESCE(marks.is_freezed, 0) AS is_freezed'),
                'marks.created_at',
                'marks.updated_at',
            ])
            ->get();
    }
}
