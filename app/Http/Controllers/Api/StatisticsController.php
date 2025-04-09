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
use App\Traits\UserParentTrait;
use Illuminate\Support\Facades\Log;

use function PHPUnit\Framework\returnSelf;

/**
 * Description: StatisticsController for Osboha general statistics.
 *
 * Methods:
 * - byWeek
 */

class StatisticsController extends Controller
{

    use ResponseJson, GroupTrait, UserParentTrait;

    /**
     * Get Statistics By Week ID.
     *
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

        // Total Users
        $response['total_users'] = User::where('is_excluded', 0)->count();

        //Total Pages, Theses, Screenshotes
        $response['total_statistics'] = Mark::without('user', 'week')->where('week_id', $response['week']->id)
            ->where('is_freezed', 0)
            ->select(
                DB::raw('sum(reading_mark + writing_mark + support) as total_sum'),
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
        $response['total_incompleat'] = $total_100->where('total_100', '!=', 100)->count();

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

        //Total 0
        $response['total_0'] = $response['total_users'] - ($response['total_100'] +   $response['total_incompleat'] + $response['freezed']);

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
    public function supervisingStatistics($superviser_id, $week_id)
    {
        $supervisingGroup = UserGroup::where('user_id', $superviser_id)->where('user_type', 'supervisor')
            ->whereHas('group.type', function ($q) {
                $q->where('type', '=', 'supervising');
            })->whereNull('termination_reason')->first();

        // الفريق الرقابي مع القادة بداخله

        $supervisorGroup = Group::without('type', 'Timeline')->find($supervisingGroup->group_id);
        if (!$supervisorGroup) {
            throw new NotFound;
        }

        //weekInfo
        $weekInfo = Week::find($week_id);
        //weekInfoInfo
        $previous_weekInfo = Week::where('created_at', '<', $weekInfo->created_at)
            ->orderBy('created_at', 'desc')
            ->first();

        $response = [];
        //$leaders = $this->usersByWeek($supervisingGroup->group_id, $weekInfo->id, ['ambassador']);
        $leaders = $this->childrensByWeek($superviser_id, $weekInfo->id, ['leader']);

        $leadersIDs =  $leaders->pluck('user_id');
        //افرقة المتابعة الخاصة بالقادة
        $group_followups = UserGroup::with('user')->where('user_type', 'leader')
            ->whereIn('user_id', $leadersIDs)
            ->where('user_id', '!=', $superviser_id)
            ->whereNull('termination_reason')
            ->get();

        foreach ($group_followups as $key => $group) {
            $response['leaders_followup_team'][$key] = $this->followupTeamStatistics($group, $weekInfo, $previous_weekInfo);
        }
        // leaders reading
        $response['leaders_reading'] = $this->membersReading($leadersIDs, $weekInfo->id);

        $supervisor_followup_team = UserGroup::with('user')->where('user_type', 'leader')
            ->where('user_id', $superviser_id)
            ->whereNull('termination_reason')
            ->first();
        $response['supervisor_own_followup_team'] = $this->followupTeamStatistics($supervisor_followup_team, $weekInfo, $previous_weekInfo);


        $allLeadersAndAmbassadors = $this->groupsUsersByWeek($group_followups->pluck('group_id'), $weekInfo->id, ['leader', 'ambassador']);
        $allLeadersAndAmbassadorsIDS = $allLeadersAndAmbassadors->pluck('user_id');
        $response['week_general_avg'] = $this->groupAvg(1,  $weekInfo->id, $allLeadersAndAmbassadorsIDS);


        $response['supervisor_group'] = $supervisorGroup;


        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    public function advisorsStatistics($advisor_id, $week_id)
    {

        $advisingGroup = UserGroup::where('user_id', $advisor_id)->where('user_type', 'advisor')
            ->whereHas('group.type', function ($q) {
                $q->where('type', '=', 'advising');
            })->whereNull('termination_reason')->first();

        if (!$advisingGroup) {

            return $this->jsonResponseWithoutMessage('لا يوجد فريق توجيه', 'data', 200);
        }
        $advisorGroup = Group::without('type', 'Timeline')->find($advisingGroup->group_id);

        if (!$advisorGroup) {
            throw new NotFound;
        }


        //weekInfo
        $weekInfo = Week::find($week_id);
        //previous_weekInfo
        $previous_weekInfo = Week::where('created_at', '<', $weekInfo->created_at)
            ->orderBy('created_at', 'desc')
            ->first();

        $response = [];

        // $supervisors = $this->usersByWeek($advisorGroup->id, $weekInfo->id, ['ambassador']);
        $supervisors = $this->childrensByWeek($advisor_id, $weekInfo->id, ['supervisor']);


        $supervisorsIDs = $supervisors->pluck('user_id');

        //جرد أفرقة المتابعة الخاصة بالمراقبين
        $group_followups = UserGroup::with('user')->where('user_type', 'leader')
            ->whereIn('user_id', $supervisorsIDs)
            ->whereNull('termination_reason')
            ->get();

        foreach ($group_followups as $key => $group) {
            if ($group) {
                $response['supervisor_own_followup_team'][$key] = $this->followupTeamStatistics($group, $weekInfo, $previous_weekInfo);
            }
        }

        //جرد أفرقة المتابعة التي يشرف عليها المراقبين

        foreach ($supervisorsIDs as $key => $superviser_id) {
            $superviser = User::find($superviser_id);
            $response['supervisor_followup_teams'][$key] = $this->totalFollowupTeamStatistics($superviser, $weekInfo, $previous_weekInfo);
        }

        // Supervisors reading
        $response['supervisors_reading'] = $this->membersReading($supervisorsIDs, $weekInfo->id);

        $response['advisor_group'] = $advisorGroup;


        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    public function consultantsStatistics($consultant_id, $week_filter = "current")
    {

        $consultantingGroup = UserGroup::where('user_id', $consultant_id)->where('user_type', 'consultant')
            ->whereHas('group.type', function ($q) {
                $q->where('type', '=', 'consultation');
            })->whereNull('termination_reason')->first();

        if (!$consultantingGroup) {
            throw new NotFound;
        }

        //previous_week
        $previous_week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->first();
        //last_previous_week
        $last_previous_week = Week::orderBy('created_at', 'desc')->skip(2)->take(2)->first();

        $response = [];

        $advisors = User::without('userProfile')->where('parent_id', $consultant_id)->get();

        //جرد أفرقة المتابعة التي يشرف عليها الموجهين
        $i = 0;
        foreach ($advisors as $advisor) {
            $advisingGroup = UserGroup::where('user_id', $advisor->id)->where('user_type', 'advisor')
                ->whereHas('group.type', function ($q) {
                    $q->where('type', '=', 'advising');
                })->first();


            $supervisors = User::without('userProfile')->where('parent_id', $advisor->id)->role('supervisor')->get();
            $week_avg = 0;
            $advisor_statistics = [];
            $advisor_statistics['number_of_leaders'] = 0;

            $advisor_statistics['is_freezed'] = 0;


            $advisor_statistics['ambassadors_excluded_in_group'] = 0;


            $advisor_statistics['ambassadors_withdraw_in_group'] = 0;

            $advisor_statistics['new_ambassadors'] = 0;
            $advisor_statistics['number_zero_varible'] = 0;

            foreach ($supervisors as $key => $superviser) {

                $supervisorStatistics['supervisor_followup_teams'][$key] = $this->totalFollowupTeamStatistics($superviser, $previous_week, $last_previous_week);

                if (!is_null($supervisorStatistics['supervisor_followup_teams'][$key]['team'])) {
                    $advisor_statistics['number_of_leaders'] += $supervisorStatistics['supervisor_followup_teams'][$key]['number_of_leaders'];

                    $week_avg += $supervisorStatistics['supervisor_followup_teams'][$key]['week_avg'];

                    $advisor_statistics['is_freezed'] += $supervisorStatistics['supervisor_followup_teams'][$key]['is_freezed'];


                    $advisor_statistics['ambassadors_excluded_in_group'] += $supervisorStatistics['supervisor_followup_teams'][$key]['ambassadors_excluded_in_group'];


                    $advisor_statistics['ambassadors_withdraw_in_group'] += $supervisorStatistics['supervisor_followup_teams'][$key]['ambassadors_withdraw_in_group'];

                    $advisor_statistics['new_ambassadors']  += $supervisorStatistics['supervisor_followup_teams'][$key]['new_ambassadors'];
                    $advisor_statistics['number_zero_varible']  += $supervisorStatistics['supervisor_followup_teams'][$key]['number_zero_varible'];
                }
            }


            $advisor_statistics['advisor_name'] = $advisor->name;
            $advisor_statistics['advisor_id'] = $advisor->id;
            $advisor_statistics['team'] = $advisingGroup ? $advisingGroup->group->name : 'لا يوجد فريق توجيه';

            $advisor_statistics['number_of_supervisors'] = $supervisors->count();
            $advisor_statistics['week_avg'] = $advisor_statistics['number_of_supervisors'] ? $week_avg / $advisor_statistics['number_of_supervisors'] : 0;

            $response['advisor_statistics'][$i] = $advisor_statistics;
            $i++;
        }

        // Supervisors reading
        $response['advisors_reading'] = $this->membersReading($advisors->pluck('id'), $previous_week->id);

        $response['consultant_group'] = $consultantingGroup->group;


        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    public function administratorStatistics($administrator_id, $week_filter = "current")
    {

        $administrationGroup = UserGroup::where('user_id', $administrator_id)->where('user_type', 'admin')
            ->whereHas('group.type', function ($q) {
                $q->where('type', '=', 'Administration');
            })->whereNull('termination_reason')->first();

        if (!$administrationGroup) {
            throw new NotFound;
        }

        //previous_week
        $previous_week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->first();
        //last_previous_week
        $last_previous_week = Week::orderBy('created_at', 'desc')->skip(2)->take(2)->first();

        $response = [];

        $consultants = User::without('userProfile')->where('parent_id', $administrator_id)->get();

        //جرد أفرقة المتابعة التي يشرف عليها الموجهين
        $i = 0;

        foreach ($consultants as $consultant) {
            $consultationGroup = UserGroup::where('user_id', $consultant->id)->where('user_type', 'consultant')
                ->whereHas('group.type', function ($q) {
                    $q->where('type', '=', 'consultation');
                })->first();

            $week_avg = 0;
            $number_ambassadors = 0;
            $consultant_statistics = [];

            $consultant_statistics['consultant_name'] = $consultant->name;
            $consultant_statistics['consultant_id'] = $consultant->id;
            $consultant_statistics['team'] = $consultationGroup ? $consultationGroup->group->name : 'لا يوجد فريق';
            $consultant_statistics['number_of_advisors'] = User::where('parent_id', $consultant->id)->role('advisor')->count();

            $response['consultant_statistics'][$i] = $consultant_statistics;
            $i++;
        }

        // Supervisors reading
        $response['consultants_reading'] = $this->membersReading($consultants->pluck('id'), $previous_week->id);

        $response['administrator_group'] = $administrationGroup->group;


        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }


    private function followupTeamStatistics($group, $previous_week, $last_previous_week)
    {
        // $group->group_id=1235;
        if (!$group) {
            $teamStatistics['team'] = 'لا يوجد فريق متابعة';

            return $teamStatistics;
        }

        $teamStatistics['leader_name'] = $group->user->name;
        $teamStatistics['team'] = $group->group->name;

        //for each leader get follow up group with its ambassador
        $followup = Group::without('type', 'Timeline')->where('id', $group->group_id)->first();

        $leaderAndAmbassadors = $this->usersByWeek($group->group_id, $previous_week->id, ['leader', 'ambassador']);
        // Number of Ambassadors
        $teamStatistics['number_ambassadors'] = $leaderAndAmbassadors->count();
        // last week Avg.
        $teamStatistics['week_avg'] = $this->groupAvg($group->group_id,  $previous_week->id, $leaderAndAmbassadors->pluck('user_id'));


        // Number of freezed users in last week
        $teamStatistics['is_freezed'] = Mark::without('user')->where('week_id', $previous_week->id)
            ->whereIn('user_id', $leaderAndAmbassadors->pluck('user_id'))
            ->where('is_freezed', 1)
            ->count();

        $memberCounts = $this->excluded_and_withdraw($previous_week, $group->group_id);

        // number of excluded users
        $teamStatistics['ambassadors_excluded_in_group'] = $memberCounts->excluded_members;

        // number of withdraw users
        $teamStatistics['ambassadors_withdraw_in_group'] = $memberCounts->withdraw_members;

        $teamStatistics['new_ambassadors'] = $this->newMembers($previous_week, $group->group_id);

        $followupLeaderAndAmbassadors = $leaderAndAmbassadors->pluck('user_id');

        $markChanges = $this->markChanges($last_previous_week, $previous_week, $followupLeaderAndAmbassadors);

        // Count the number of users with mark changes
        $teamStatistics['number_zero_varible'] = $markChanges;

        return $teamStatistics;
    }

    private function totalFollowupTeamStatistics($superviser, $previous_week, $last_previous_week)
    {

        $teamStatistics['superviser_name'] = $superviser->name;
        $teamStatistics['supervisor_id'] = $superviser->id;

        $supervisingGroup = UserGroup::where('user_id', $superviser->id)->where('user_type', 'supervisor')
            ->whereHas('group.type', function ($q) {
                $q->where('type', '=', 'supervising');
            })->whereNull('termination_reason')->first();

        if (!$supervisingGroup) {
            $teamStatistics['team'] = null;

            return $teamStatistics;
        }
        $supervisorGroup = Group::without('type', 'Timeline')->with('userAmbassador')->find($supervisingGroup->group_id);


        $leaders = $this->usersByWeek($supervisingGroup->group_id, $previous_week->id, ['ambassador']);

        $leadersIDs =  $leaders->pluck('user_id');

        // Number of Leaders
        $teamStatistics['number_of_leaders'] = $leaders->count();

        //افرقة المتابعة الخاصة بالقادة
        $group_followups = UserGroup::where('user_type', 'leader')
            ->whereIn('user_id', $leadersIDs)
            ->whereNull('termination_reason')
            ->get();
        $teamStatistics['group_followups'] = $group_followups;

        $allLeadersAndAmbassadors = $this->groupsUsersByWeek($group_followups->pluck('group_id'), $previous_week->id, ['leader', 'ambassador']);

        $allLeadersAndAmbassadorsIDS = $allLeadersAndAmbassadors->pluck('user_id');
        $teamStatistics['team'] = $supervisorGroup->name;


        //for each supervisor get leaders follow up group with its ambassador
        $followups = Group::without('type', 'Timeline')->with('leaderAndAmbassadors')->whereIn('id', $group_followups->pluck('group_id'))->get();

        // last week Avg.
        $teamStatistics['week_avg'] = $this->groupAvg(1,  $previous_week->id, $allLeadersAndAmbassadorsIDS);


        // Number of freezed users in last week
        $teamStatistics['is_freezed'] = Mark::without('user')->where('week_id', $previous_week->id)
            ->whereIn('user_id', $allLeadersAndAmbassadorsIDS)
            ->where('is_freezed', 1)
            ->count();

        // $memberCounts = $this->excluded_and_withdraw($previous_week, $allLeadersAndAmbassadors->pluck('group_id'));

        $memberCounts = UserGroup::whereIn('group_id', $allLeadersAndAmbassadors->pluck('group_id'))->where('updated_at', '>=', $previous_week->created_at)
            ->where('user_type', 'ambassador')->select(
                DB::raw("SUM(CASE WHEN termination_reason = 'excluded' THEN 1 ELSE 0 END) as excluded_members"),
                DB::raw("SUM(CASE WHEN termination_reason = 'withdraw' THEN 1 ELSE 0 END) as withdraw_members")
            )
            ->first();


        // number of excluded users
        $teamStatistics['ambassadors_excluded_in_group'] = $memberCounts->excluded_members;

        // number of withdraw users
        $teamStatistics['ambassadors_withdraw_in_group'] = $memberCounts->withdraw_members;

        $teamStatistics['new_ambassadors'] = UserGroup::whereIn('group_id', $allLeadersAndAmbassadors->pluck('group_id'))
            ->whereBetween('created_at', [$previous_week->created_at, $previous_week->created_at->addDays(7)])->get()->count();

        $markChanges = $this->markChanges($last_previous_week, $previous_week, $allLeadersAndAmbassadorsIDS);

        // Count the number of users with mark changes
        $teamStatistics['number_zero_varible'] = $markChanges;


        $supervisor_followup_team = UserGroup::with('user')->where('user_type', 'leader')
            ->where('user_id', $superviser->id)
            ->whereNull('termination_reason')
            ->first();
        $teamStatistics['supervisor_own_followup_team'] = $this->followupTeamStatistics($supervisor_followup_team, $previous_week, $last_previous_week);

        return $teamStatistics;
    }

    private function markChanges($last_previous_week, $previous_week, $followupLeaderAndAmbassadors)
    {
        $secondLastWeekMarks = Mark::where('week_id', $last_previous_week->id)
            ->whereIn('user_id', $followupLeaderAndAmbassadors)
            ->pluck('user_id');

        $secondLastWeekZeroMarks = collect($followupLeaderAndAmbassadors)->diff($secondLastWeekMarks)->values();

        $markChanges = Mark::where('week_id', $previous_week->id)
            ->whereIn('user_id', $secondLastWeekZeroMarks)
            ->count();

        return $markChanges;
    }

    private function newMembers($previous_week, $group_id)
    {
        return UserGroup::without('user')->where('group_id', $group_id)
            ->whereBetween('created_at', [$previous_week->created_at, $previous_week->created_at->addDays(7)])->get()->count();
    }

    private function excluded_and_withdraw($previous_week, $group_id)
    {
        $current_week = Week::orderBy('created_at', 'desc')->first();

        return UserGroup::where('group_id', $group_id)
            ->where('updated_at', '>=', $previous_week->created_at)
            ->where('updated_at', '<', $current_week->created_at)
            ->where('user_type', 'ambassador')
            ->select(
                DB::raw("COALESCE(SUM(CASE WHEN termination_reason = 'excluded' THEN 1 ELSE 0 END), 0) as excluded_members"),
                DB::raw("COALESCE(SUM(CASE WHEN termination_reason = 'withdraw' THEN 1 ELSE 0 END), 0) as withdraw_members")
            )
            ->first();
    }
}
