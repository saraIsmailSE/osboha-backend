<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Models\Group;
use App\Models\Mark;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Week;
use Illuminate\Support\Facades\DB;
use App\Traits\GroupTrait;


/**
 * Description: StatisticsController for Osboha general statistics.
 *
 * Methods: 
 * - byWeek
 */

class StatisticsController extends Controller
{

    use ResponseJson, GroupTrait;

    /**
     * Get Statistics By Week ID.
     * @todo update zeros and freezed retrieval
     * @return statistics;
     */

    public function byWeek($week_id = 0)
    {
        // not specified => get previous week
        if ($week_id == 0) {
            $week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->first();
        } else {
            $week = Week::latest()->pluck('id')->first();
        }
        $response['week'] = $week;

        //Total Pages, Theses, Screenshotes
        $response['total_statistics'] = Mark::without('user', 'week')->where('week_id', $response['week']->id)
            ->where('is_freezed', 0)
            ->select(
                DB::raw('avg(reading_mark + writing_mark + support) as total_avg'),
                DB::raw('sum(total_pages) as total_pages'),
                DB::raw('sum(total_thesis) as total_thesis'),
                DB::raw('sum(total_screenshot) as total_screenshot'),
            )->first();

        //Total 100
        $total_100 = Mark::without('user', 'week')->where('week_id', $response['week']->id)
            ->where('is_freezed', 0)
            ->select(
                DB::raw('sum(reading_mark + writing_mark + support) as total_100'),
            )->groupBy('user_id')->get();

        $response['total_100'] = $total_100->where('total_100', 100)->count();
        //Total 0
        $total_0 = Mark::without('user', 'week')->where('week_id', $response['week']->id)
            ->where('is_freezed', 0)
            ->select(
                DB::raw('sum(reading_mark + writing_mark + support) as total_0'),
            )->groupBy('user_id')->get();

        $response['total_0'] = $total_0->where('total_0', 0)->count();

        //Most Read
        $response['most_read'] = Mark::without('user', 'week')->where('week_id', $response['week']->id)
            ->where('is_freezed', 0)
            ->select('user_id', DB::raw('max(total_pages) as max_total_pages'))
            ->groupBy('user_id')
            ->orderBy('max_total_pages', 'desc')
            ->first();
        //Freezed
        $response['freezed'] = Mark::without('user', 'week')->where('week_id', $response['week']->id)
            ->where('is_freezed', 1)
            ->count();
        // Total Users
        $response['total_users'] = User::where('is_excluded', 0)->count();
        //Total Excluded
        $response['is_excluded'] = User::where('is_excluded', 1)
            ->whereBetween('updated_at', [$response['week']->created_at, $response['week']->created_at->addDays(7)])->count();
        //Total New
        $response['is_new'] = User::whereBetween('created_at', [$response['week']->created_at, $response['week']->created_at->addDays(7)])->get()->count();

        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    public function lastWeek()
    {
        $previous_week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->first();
        return $this->jsonResponseWithoutMessage($previous_week, 'data', 200);
    }

    /**
     * Get Statistics
     * 
     * @return statistics;
     */
    public function supervisingStatistics($superviser_id, $week_filter = "current")
    {
        $supervisingGroup = UserGroup::where('user_id', $superviser_id)->where('user_type', 'supervisor')
            ->whereHas('group.type', function ($q) {
                $q->where('type', '=', 'supervising');
            })->first();

        // الفريق الرقابي مع القادة بداخله

        $supervisorGroup = Group::with('userAmbassador')->find($supervisingGroup->group_id);
        if (!$supervisorGroup) {
            throw new NotFound;
        }


        //previous_week
        $previous_week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->first();
        //last_previous_week
        $last_previous_week = Week::orderBy('created_at', 'desc')->skip(2)->take(2)->first();
        $current_week = Week::latest()->first();

        $i = 0;
        $response = [];
        $zero_varible_last = NULL;
        $zero_varible_two_last = NULL;


        //افرقة المتابعة الخاصة بالقادة

        $group_followups = UserGroup::with('user')->where('user_type', 'leader')
            ->whereIn('user_id', $supervisorGroup->userAmbassador->pluck('id'))
            ->whereNull('termination_reason')
            ->get();


        foreach ($group_followups as $key => $group) {

            $leaderInfo['leader_name'] = $group->user->name;
            $leaderInfo['team'] = $group->group->name;

            //for each leader get follow up group with its ambassador 
            $followup = Group::with('leaderAndAmbassadors')->where('id', $group->group_id)->first();
            // Number of Ambassadors
            $leaderInfo['number_ambassadors'] = $followup->leaderAndAmbassadors->count();
            // last week Avg.
            $leaderInfo['week_avg'] = $this->groupAvg($group->group_id,  $previous_week->id, $followup->leaderAndAmbassadors->pluck('id'));


            // Number of freezed users in last week
            $leaderInfo['is_freezed'] = Mark::where('week_id', $previous_week->id)
                ->whereIn('user_id', $followup->leaderAndAmbassadors->pluck('id'))
                ->where('is_freezed', 1)
                ->count();

            // number of excluded users
            $leaderInfo['ambassadors_excluded_in_group'] = UserGroup::where('group_id', $group->group_id)
                ->where('user_type', 'ambassador')
                ->where('termination_reason', 'excluded')
                ->count();

            // number of withdraw users
            $leaderInfo['ambassadors_withdraw_in_group'] = UserGroup::where('group_id', $group->group_id)
                ->where('user_type', 'ambassador')
                ->where('termination_reason', 'withdraw')
                ->count();

            $leaderInfo['new_ambassadors'] = UserGroup::where('group_id', $group->id)
                ->whereBetween('created_at', [$current_week->created_at, $current_week->created_at->addDays(7)])->get()->count();

            foreach ($followup->leaderAndAmbassadors->pluck('id') as $user_id) {
                if ($zero_varible_two_last = Mark::without('user')->where('week_id', $last_previous_week->id)
                    ->where('user_id', $user_id)
                    ->where('is_freezed', 0)
                    ->select('user_id', DB::raw('sum(reading_mark + writing_mark + support) as out_of_100'))
                    ->having('out_of_100', '=', 0)
                    ->groupBy('user_id')
                    ->get()
                ) {

                    $zero_varible_last = Mark::without('user')->where('week_id', $previous_week->id)
                        ->where('user_id', $user_id)
                        ->where('is_freezed', 0)
                        ->select('user_id', DB::raw('sum(reading_mark + writing_mark + support) as out_of_100'))
                        ->havingBetween('out_of_100', [10, 100])
                        ->groupBy('user_id')
                        ->count();


                    if ($zero_varible_last > 0) {
                        $i = $i + 1;
                    }
                }
                $leaderInfo['number_zero_varible'] = $i;
            }
            $response['statistics_data'][$key] = $leaderInfo;
            $i = 0;
        }
        // leaders reading
        $leadersReading = Mark::where('week_id', $previous_week->id)
            ->whereIn('user_id', $supervisorGroup->userAmbassador->pluck('id'))
            ->get();
        $response['leaders_reading'] = $leadersReading;
        $response['supervisor_group'] = $supervisorGroup;


        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }
}
