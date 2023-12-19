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

/**
 * Description: StatisticsController for Osboha general statistics.
 *
 * Methods: 
 * - byWeek
 */

class StatisticsController extends Controller
{

    use ResponseJson;

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
    public function supervisingStatistics($superviser_id,$week_filter = "current")
    {
        
        $supervisingGroup = UserGroup::where('user_id', $superviser_id)->where('user_type', 'supervisor')
            ->whereHas('group.type', function ($q) {
                $q->where('type', '=', 'supervising');
            })->first();
          

        $group = Group::with('userAmbassador')->find($supervisingGroup->group_id);
        if (!$group) {
            throw new NotFound;
        }
       
        $response['all_leaders_in_group'] = $group->userAmbassador;
      
       

        $response['week'] = Week::latest()->first();

        //افرقة المتابعة الخاصة بالقادة
        $response['group_followups'] = UserGroup::with('group')->whereIn('user_id', $response['all_leaders_in_group']->pluck('pivot.user_id'))
            ->where('user_type', 'leader')
            ->get();
            
        $responseOfSara = [];
        foreach ($response['group_followups'] as $key =>  $group) { //for each audit of advisor 
            $is_freezed['group_name'] = $group->group->name;
            $is_freezed['is_freezed'] = Mark::where('week_id', $response['week']->id)
                ->join('user_groups', 'marks.user_id', '=', 'user_groups.user_id')
                ->where('user_groups.group_id', '=', $group->id)
                ->where('is_freezed', 1)
                ->count();
            $responseOfSara[$key] = $is_freezed;
        }
        // السفراء بكل فريق متابعة  
        foreach ($response['group_followups']->pluck('group_id') as $group_id) {
            $response['ambassadors_in_group'][$group_id] = UserGroup::where('group_id', $group_id)
                ->where('user_type', 'ambassador')
                ->get();
            $response['is_zero'][$group_id] = Mark::whereBetween('created_at', [$response['week']->created_at, $response['week']->created_at->addDays(-7)])
                ->wherein('user_id',$response['ambassadors_in_group'][$group_id]->pluck('user_id'))
                ->count();
        }
    
        $response['week'] = Week::latest()->first();

        //عدد القادة في كل فريق رقابي   
        $response['number_leaders_in_group'] = $response['all_leaders_in_group']->count();
        //القادةالمنسحبين في الفريق الرقابي
        $response['all_leadersWithdrawn_in_group'] = Group::with('ambassadorsWithdrawn')
            ->where('id', $superviser_id)
            ->first();
        //عدد القادةالمنسحبين في كل فريق رقابي   
        $response['number_leadersWithdrawn_in_group'] = $response['all_leadersWithdrawn_in_group']->ambassadorsWithdrawn->count();

        //افرقة المتابعة الخاصة بالقادة
        $response['group_followups'] = UserGroup::where('user_type', 'leader')
            ->whereIn('user_id', $response['all_leaders_in_group']->pluck('pivot.user_id'))
            ->get();

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
        foreach ($response['all_leaders_in_group']->pluck('id') as $user_id) {
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
        $response['is_new_supervising'] = UserGroup::where('group_id', $superviser_id)
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

    public function advisorsStatistics( $advisor_id,$week_filter = "current")
    {
        
       
        $advisingGroup = UserGroup::where('user_id', $advisor_id)->where('user_type', 'advisor')
            ->whereHas('group.type', function ($q) {
                $q->where('type', '=', 'advising');
            })->first();
            
        $group = Group::with('userAmbassador')->find($advisingGroup->group_id);
        if (!$group) {
            throw new NotFound;
        }
       
        //المراقبين(سفراء) في فريق التوجيه 

        $response['all_supervisors_in_group'] = $group->userAmbassador;
       
        $response['week'] = Week::latest()->first();

        //افرقة المتابعة الخاصة بالمراقبين
        $response['group_followups'] = UserGroup::with('group')->whereIn('user_id', $response['all_supervisors_in_group']->pluck('pivot.user_id'))
            ->where('user_type', 'supervisor')
            ->get();
            
        $responseOfSara = [];
        foreach ($response['group_followups'] as $key =>  $group) { //for each audit of advisor 
            $is_freezed['group_name'] = $group->group->name;
            $is_freezed['is_freezed'] = Mark::where('week_id', $response['week']->id)
                ->join('user_groups', 'marks.user_id', '=', 'user_groups.user_id')
                ->where('user_groups.group_id', '=', $group->id)
                ->where('is_freezed', 1)
                ->count();
            $responseOfSara[$key] = $is_freezed;
        }

        $response['week'] = Week::latest()->first();

        
        //عدد المراقبين في كل فريق التوجيه   
        $response['number_supervisors_in_group'] = $response['all_supervisors_in_group']->count();
       

        //المراقبين المنسحبين في الفريق التوجيه
        $response['all_supervisorsleadersWithdrawn_in_group'] = Group::with('ambassadorsWithdrawn')
            ->where('id', $advisor_id)
            ->first();
            
        //عدد المراقبين المنسحبين في كل فريق التوجيه   
        if( $response['all_supervisorsleadersWithdrawn_in_group'])
        $response['number_leadersWithdrawn_in_group'] = $response['all_supervisorsleadersWithdrawn_in_group']->ambassadorsWithdrawn->count();

        //افرقة المتابعة الخاصة بالموجه
        $response['group_supervisors'] = UserGroup::where('user_type', 'supervisor')
            ->whereIn('user_id', $response['all_supervisors_in_group']->pluck('pivot.user_id'))
            ->get();
   
        // السفراء بكل فريق مراقبة  
        foreach ($response['group_supervisors']->pluck('group_id') as $group_id) {
            $response['ambassadors_in_group'][$group_id] = UserGroup::where('group_id', $group_id)
                ->where('user_type', 'ambassador')
                ->get();
            $response['is_zero'][$group_id] = Mark::whereBetween('created_at', [$response['week']->created_at, $response['week']->created_at->addDays(-7)])
                ->wherein('user_id',$response['ambassadors_in_group'][$group_id]->pluck('user_id'))
                ->count();    
        }

        //تجميد كل فريق على حدا                      
        foreach ($response['group_supervisors']->pluck('group_id') as $group_id) {
            $response['is_freezed'][$group_id] = Mark::where('week_id', $response['week']->id)
                ->join('user_groups', 'marks.user_id', '=', 'user_groups.user_id')
                ->where('user_groups.group_id', '=', $group_id)
                ->where('is_freezed', 1)
                ->count();
        }

        //اصفار كل فريق على حدا                      
        foreach ($response['group_supervisors']->pluck('group_id') as $group_id) {
            $response['marks_zero'][$group_id] = Mark::where('week_id', $response['week']->id)
                ->join('user_groups', 'marks.user_id', '=', 'user_groups.user_id')
                ->where('user_groups.group_id', '=', $group_id)
                ->select(DB::raw('(reading_mark + writing_mark + support) as out_of_100'))
                ->having('out_of_100', 0)
                ->count();
        }
        //احصائيات العلامات والتجميد لكل فريق على حدا
        foreach ($response['group_supervisors']->pluck('group_id') as $group_id) {
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

        //عدد الصفحات والدعم و الاطروحات  لكل مراقب
        foreach ($response['all_supervisors_in_group']->pluck('id') as $user_id) {
            $response['total_statistics_leader'][$user_id] = Mark::without('week')->where('week_id', 1)
                ->where('user_id', $user_id)
                ->where('is_freezed', 0)
                ->select(
                    DB::raw('avg(support) as team_support_mark'),
                    DB::raw('sum(total_pages) as total_pages'),
                    DB::raw('sum(total_thesis) as total_thesis'),
                )->get();
        }

        //الاعضاء الجدد في فرق الرقابة
        foreach ($response['group_supervisors']->pluck('group_id') as $group_id) {
            $response['is_new_supervisor'][$group_id] = UserGroup::where('group_id', $group_id)
                ->whereBetween('created_at', [$response['week']->created_at, $response['week']->created_at->addDays(7)])->get()->count();
        }
        //الاعضاء الجدد في فريق التوجيه
        $response['is_new_advising'] = UserGroup::where('group_id', $advisor_id)
            ->whereBetween('created_at', [$response['week']->created_at, $response['week']->created_at->addDays(7)])->get()->count();
         

        //عدد السفراءالمنسحبين بكل فريق توجيه
        foreach ($response['group_supervisors']->pluck('group_id') as $group_id) {
            $response['ambassadors_in_group'][$group_id] = UserGroup::where('group_id', $group_id)
                ->where('user_type', 'ambassador')
                ->whereNotNull('termination_reason')
                ->count();
        }
        
        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }
    public function consultantStatistics($consultant_id,$week_filter = "current")
    {
        
            
            $consultantGroup = UserGroup::where('user_id', $consultant_id)->where('user_type', 'consultant')
                ->whereHas('group.type', function ($q) {
                    $q->where('type', '=', 'consultation');
                })->first();
             
            $group = Group::with('userAmbassador')->find($consultantGroup->group_id);
            if (!$group) {
                throw new NotFound;
            }
        
           
            $response['all_advisors_in_group'] = $group->userAmbassador;
            
            $response['week'] = Week::latest()->first();
        
            //افرقة المتابعة الخاصة بالمراقبين
               $response['group_followups'] = UserGroup::with('group')->whereIn('user_id', $response['all_advisors_in_group']->pluck('pivot.user_id'))
               ->where('user_type', 'advisor')
               ->get();
            $responseOfSara = [];
            foreach ($response['group_followups'] as $key =>  $group) { //for each audit of advisor 
                $is_freezed['group_name'] = $group->group->name;
                $is_freezed['is_freezed'] = Mark::where('week_id', $response['week']->id)
                    ->join('user_groups', 'marks.user_id', '=', 'user_groups.user_id')
                    ->where('user_groups.group_id', '=', $group->id)
                    ->where('is_freezed', 1)
                    ->count();
                $responseOfSara[$key] = $is_freezed;
            }
        
            $response['week'] = Week::latest()->first();
        
            //عدد المراقبين في كل فريق استشارة   
            $response['number_supervisors_in_group'] = $response['all_advisors_in_group']->count();
        
            //المراقبين المنسحبين في الفريق استشارة
            $response['all_supervisorsleadersWithdrawn_in_group'] = Group::with('ambassadorsWithdrawn')
                ->where('id', $consultant_id)
                ->first();
        
            //عدد المراقبين المنسحبين في كل فريق استشارة   
            if( $response['all_supervisorsleadersWithdrawn_in_group'])
            $response['number_leadersWithdrawn_in_group'] = $response['all_supervisorsleadersWithdrawn_in_group']->ambassadorsWithdrawn->count();
          
        
            //افرقة المتابعة الخاصة بالمستشار
            $response['group_consultation'] = UserGroup::where('user_type', 'advisor')
                ->whereIn('user_id', $response['all_advisors_in_group']->pluck('pivot.user_id'))
                ->get();
               
            // السفراء بكل فريق استشارة  
            foreach ($response['group_consultation']->pluck('group_id') as $group_id) {
                $response['ambassadors_in_group'][$group_id] = UserGroup::where('group_id', $group_id)
                    ->where('user_type', 'ambassador')
                    ->get();
                $response['is_zero'][$group_id] = Mark::whereBetween('created_at', [$response['week']->created_at, $response['week']->created_at->addDays(-7)])
                    ->wherein('user_id',$response['ambassadors_in_group'][$group_id]->pluck('user_id'))
                    ->count();
            }
        
            //تجميد كل فريق على حدا                      
            foreach ($response['group_consultation']->pluck('group_id') as $group_id) {
                $response['is_freezed'][$group_id] = Mark::where('week_id', $response['week']->id)
                    ->join('user_groups', 'marks.user_id', '=', 'user_groups.user_id')
                    ->where('user_groups.group_id', '=', $group_id)
                    ->where('is_freezed', 1)
                    ->count();
            }
        
            //اصفار كل فريق على حدا                      
            foreach ($response['group_consultation']->pluck('group_id') as $group_id) {
                $response['marks_zero'][$group_id] = Mark::where('week_id', $response['week']->id)
                    ->join('user_groups', 'marks.user_id', '=', 'user_groups.user_id')
                    ->where('user_groups.group_id', '=', $group_id)
                    ->select(DB::raw('(reading_mark + writing_mark + support) as out_of_100'))
                    ->having('out_of_100', 0)
                    ->count();
            }
            //احصائيات العلامات والتجميد لكل فريق على حدا
            foreach ($response['group_consultation']->pluck('group_id') as $group_id) {
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
            //عدد الصفحات والدعم و الاطروحات  لكل موجه
            foreach ($response['all_advisors_in_group']->pluck('id') as $user_id) {
                $response['total_statistics_leader'][$user_id] = Mark::without('week')->where('week_id', 1)
                    ->where('user_id', $user_id)
                    ->where('is_freezed', 0)
                    ->select(
                        DB::raw('avg(support) as team_support_mark'),
                        DB::raw('sum(total_pages) as total_pages'),
                        DB::raw('sum(total_thesis) as total_thesis'),
                    )->get();
            }
        
            //الاعضاء الجدد في فرق الاستشارة
            foreach ($response['group_consultation']->pluck('group_id') as $group_id) {
                $response['is_new_supervisor'][$group_id] = UserGroup::where('group_id', $group_id)
                    ->whereBetween('created_at', [$response['week']->created_at, $response['week']->created_at->addDays(7)])->get()->count();
            }
            //الاعضاء الجدد في فريق الاستشارة
            $response['is_new_consultant'] = UserGroup::where('group_id', $consultant_id)
                ->whereBetween('created_at', [$response['week']->created_at, $response['week']->created_at->addDays(7)])->get()->count();
        
            //عدد السفراءالمنسحبين بكل فريق استشارة
            foreach ($response['group_consultation']->pluck('group_id') as $group_id) {
                $response['ambassadors_in_group'][$group_id] = UserGroup::where('group_id', $group_id)
                    ->where('user_type', 'ambassador')
                    ->whereNotNull('termination_reason')
                    ->count();
            }
            
            return $this->jsonResponseWithoutMessage($response, 'data', 200);
        }
    
  
}
