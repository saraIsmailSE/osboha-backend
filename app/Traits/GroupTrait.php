<?php

namespace App\Traits;

use App\Models\Mark;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait GroupTrait
{
    /**
     * Calculate the average weekly performance score for a specific group.
     *
     * This function computes the average score for a group based on:
     * - The total sum of reading, writing, and support marks for active users (non-frozen).
     * - The total number of users in the group, excluding frozen users.
     * - If there are no valid users (non-frozen), the function returns 0.
     *
     * @param int $group_id The ID of the group.
     * @param int $week_id The ID of the week.
     * @param array $users_in_group The list of user IDs in the group.
     * @return float|int|bool The calculated average score, 0 if no valid users, or false on error.
     */
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

            if ($total_out_of_100 && (count($users_in_group) - $total_freezed) != 0) {
                return $total_out_of_100 / (count($users_in_group) - $total_freezed);
            }

            return 0;
        } catch (\Error $e) {
            report($e);
            return false;
        }
    }

    /**
     * Retrieve users from a specific group who were active during a given week.
     *
     * This function fetches users who:
     * - Joined the group before or at the start of the given week.
     * - Remained active in the group for at least 7 days after the week's start.
     * - Have not been terminated (i.e., `termination_reason` is NULL).
     * - Belong to the specified user type.
     *
     * @param int $group_id The ID of the group.
     * @param int $week_id The ID of the week.
     * @param array $user_type The user types to filter.
     * @return \Illuminate\Database\Eloquent\Collection The list of users.
     */
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


    /**
     * Retrieve users from multiple groups who were active during a given week.
     *
     * This function works similarly to `usersByWeek`, but allows filtering across multiple groups.
     * It fetches users who:
     * - Joined any of the specified groups before or at the start of the given week.
     * - Remained active in at least one group for at least 7 days after the week's start.
     * - Have not been terminated (i.e., `termination_reason` is NULL).
     * - Belong to the specified user type.
     *
     * @param array $groups_id The IDs of the groups.
     * @param int $week_id The ID of the week.
     * @param array $user_type The user types to filter.
     * @return \Illuminate\Database\Eloquent\Collection The list of users.
     */

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

    /**
     * Retrieve users from a specific group who were active during a given month.
     *
     * This function fetches users who:
     * - Joined the group before or within the given month.
     * - Remained active in the group at least until the end of the month.
     * - Have not been terminated (`termination_reason` is NULL).
     * - Belong to the specified user type.
     *
     * @param int $group_id The ID of the group.
     * @param int $month The month (1-12).
     * @param int $year The year (e.g., 2024).
     * @param array $user_type The user types to filter.
     * @return \Illuminate\Database\Eloquent\Collection The list of users.
     */
    function usersByMonth($group_id, $month, $year, $user_type)
    {
        // Calculate the start and end of the given month
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

        return UserGroup::where(function ($query) use ($startOfMonth, $endOfMonth, $group_id) {
            $query->where('created_at', '<=', $startOfMonth)
                ->where('updated_at', '>=', $endOfMonth)
                ->where('group_id', $group_id);
        })
            ->orWhere(function ($query) use ($endOfMonth, $group_id) {
                $query->where('created_at', '<=', $endOfMonth)
                    ->whereNull('termination_reason')
                    ->where('group_id', $group_id);
            })
            ->whereIn('user_type', $user_type)
            ->distinct('user_id')
            ->get();
    }
    /**
     * Retrieve reading-related data for a list of ambassadors (members) for a specific week.
     *
     * This function fetches members' reading-related marks by:
     * - Left joining the `marks` table to get the marks for the given `week_id`.
     * - Ensuring that all requested members (`membersIDs`) are included, even if they don't have marks.
     * - Using `COALESCE` to return `0` when no marks exist for a user in the specified week.
     *
     * @param array $membersIDs The list of ambassador IDs to fetch data for.
     * @param int $week_id The ID of the week.
     * @return \Illuminate\Database\Eloquent\Collection The list of members with their reading-related marks.
     */
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
