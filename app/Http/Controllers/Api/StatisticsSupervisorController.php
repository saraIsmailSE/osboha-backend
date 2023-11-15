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
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Mark;
use App\Models\Week;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\JoinClause;



class StatisticsSupervisorController extends Controller
{

    use ResponseJson;

    /**
     * Get Statistics
     * 
     * @return statistics;
     */
    public function Statistics($supervising_group_id, $week_filter = "current")
    {
        $group = Group::find($supervising_group_id);
        if (!$group) {
            throw new NotFound;
        }
        $response['week'] = Week::latest()->first();
        //القادة(سفراء) في الفريق الرقابي
        $response['all_leaders_in_group'] = Group::with('ambassadors')
            ->where('id', $supervising_group_id)
            ->first();
        //عدد القادة في كل فريق رقابي   
        $response['number_leaders_in_group'] = $response['all_leaders_in_group']->ambassadors->count();
        //القادةالمنسحبين في الفريق الرقابي
        $response['all_leadersWithdrawn_in_group'] = Group::with('ambassadorsWithdrawn')
            ->where('id', $supervising_group_id)
            ->first();
        //عدد القادةالمنسحبين في كل فريق رقابي   
        $response['number_leadersWithdrawn_in_group'] = $response['all_leadersWithdrawn_in_group']->ambassadorsWithdrawn->count();

        //افرقة المتابعة الخاصة بالقادة
        $response['group_followups'] = UserGroup::where('user_type', 'leader')
            ->whereIn('user_id', $response['all_leaders_in_group']->ambassadors->pluck('pivot.user_id'))
            ->get();
        // السفراء بكل فريق متابعة  
        foreach ($response['group_followups']->pluck('group_id') as $group_id) {
            $response['ambassadors_in_group'][$group_id] = UserGroup::where('group_id', $group_id)
                ->where('user_type', 'ambassador')
                ->get();
        }
        //تجميد كل فريق على حدا                      
        foreach ($response['group_followups']->pluck('group_id') as $group_id) {
            $response['is_freezed'][$group_id] = Mark::where('week_id', $response['week']->id)
                ->join('user_groups', 'marks.user_id', '=', 'user_groups.user_id')
                ->where('user_groups.group_id', '=', $group_id)
                ->where('is_freezed', 1)
                ->count();
        }

        //اصفار كل فريق على حدا                      
        foreach ($response['group_followups']->pluck('group_id') as $group_id) {
            $response['marks_zero'][$group_id] = Mark::where('week_id', $response['week']->id)
                ->join('user_groups', 'marks.user_id', '=', 'user_groups.user_id')
                ->where('user_groups.group_id', '=', $group_id)
                ->select(DB::raw('(reading_mark + writing_mark + support) as out_of_100'))
                ->having('out_of_100', 0)
                ->count();
        }
        //احصائيات العلامات والتجميد لكل فريق على حدا
        foreach ($response['group_followups']->pluck('group_id') as $group_id) {
            $response['total_statistics'][$group_id] = Mark::without('user,week')->where('week_id', 1)
                ->join('user_groups', 'marks.user_id', '=', 'user_groups.user_id')
                ->where('user_groups.group_id', '=', $group_id)
                ->where('is_freezed', 0)
                ->select(
                    DB::raw('avg(reading_mark + writing_mark + support) as team_out_of_100'),
                    DB::raw('avg(reading_mark) as team_reading_mark'),
                    DB::raw('avg(writing_mark) as team_writing_mark'),
                    DB::raw('avg(support) as team_support_mark'),
                    DB::raw('sum(total_pages) as total_pages'),
                    DB::raw('sum(total_thesis) as total_thesis'),
                    DB::raw('sum(total_screenshot) as total_screenshot'),
                )->get();
        }
        //عدد الصفحات والدعم و الاطروحات  لكل قائد
        foreach ($response['all_leaders_in_group']->ambassadors->pluck('id') as $user_id) {
            $response['total_statistics_leader'][$user_id] = Mark::without('week')->where('week_id', 1)
                ->where('user_id', $user_id)
                ->where('is_freezed', 0)
                ->select(
                    DB::raw('avg(support) as team_support_mark'),
                    DB::raw('sum(total_pages) as total_pages'),
                    DB::raw('sum(total_thesis) as total_thesis'),
                )->get();
        }

        //الاعضاء الجدد في فرق المتابعة
        foreach ($response['group_followups']->pluck('group_id') as $group_id) {
            $response['is_new_fllowup'][$group_id] = UserGroup::where('group_id', $group_id)
                ->whereBetween('created_at', [$response['week']->created_at, $response['week']->created_at->addDays(7)])->get()->count();
        }
        //الاعضاء الجدد في فريق الرقابة
        $response['is_new_supervising'] = UserGroup::where('group_id', $supervising_group_id)
            ->whereBetween('created_at', [$response['week']->created_at, $response['week']->created_at->addDays(7)])->get()->count();

        //عدد السفراءالمنسحبين بكل فريق متابعة
        foreach ($response['group_followups']->pluck('group_id') as $group_id) {
            $response['ambassadors_in_group'][$group_id] = UserGroup::where('group_id', $group_id)
                ->where('user_type', 'leader')
                ->whereNotNull('termination_reason')
                ->count();
        }
        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }
}
