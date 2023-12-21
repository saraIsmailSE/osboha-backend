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
    public function Statistics($group_id)
    {
        
        //previous_week
        $previous_week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->first();
        //last_previous_week
        $last_previous_week = Week::orderBy('created_at', 'desc')->skip(2)->take(2)->first();
        $supervising_group_id = $group_id;
        $i = 0;
        $responseOfsara =[ ];
        $zero_varible_last=NULL ;$zero_varible_two_last=NULL;
        $group = Group::find($supervising_group_id);
        if (!$group) {
            throw new NotFound;
        }
        $week = Week::latest()->first();
        //القادة(سفراء) في الفريق الرقابي
         $all_leaders_in_group = Group::with('ambassadors')
            ->where('id', $supervising_group_id)
            ->first();
     
        //افرقة المتابعة الخاصة بالقادة
        $group_followups = UserGroup::with('user')->where('user_type', 'leader')
            ->whereIn('user_id', $all_leaders_in_group->ambassadors->pluck('pivot.user_id'))
            ->whereNull('termination_reason')
            ->get();
        
        foreach($group_followups as $key =>$group)    {

           $response['leader_name']        = $group->user->name;
           $response['group_name']         = $group->group->name;
           $users_in_group = Group::with('ambassadors')->where('id', $group->group->id)->first();

            
           $response['number_ambassadors'] =   UserGroup::where('group_id',$group->group->id)
                                                            ->where('user_type', 'ambassador')
                                                            ->whereNull('termination_reason')
                                                            ->count();

           
            $general_average = Mark::where('week_id', $previous_week->id)
                                    ->join('user_groups', 'marks.user_id', '=', 'user_groups.user_id')
                                    ->where('user_groups.group_id', '=', $group->group->id)
                                    ->whereNull('user_groups.termination_reason')
                                    ->where('is_freezed', 0)
                                    ->select(
                                        DB::raw('avg(reading_mark + writing_mark + support) as team_out_of_100'),
                                    )->get();
            $response['general_average'] = $general_average->pluck('team_out_of_100')->first();
        

           $response['is_freezed'] = Mark::where('week_id', $previous_week->id)
                                       ->join('user_groups', 'marks.user_id', '=', 'user_groups.user_id')
                                        ->where('user_groups.group_id', '=', $group->group->id)
                                        ->where('is_freezed', 1)
                                        ->count();

           
            $response['ambassadors_withdraw_in_group'] = UserGroup::where('group_id', $group->group->id)
                                                                ->where('user_type', 'ambassador')
                                                                ->whereNotNull('termination_reason')
                                                                ->count();    

            $response['is_new_followup'] = UserGroup::where('group_id', $group->id)
            ->whereBetween('created_at', [$week->created_at, $week->created_at->addDays(7)])->get()->count();
            
            foreach($users_in_group->ambassadors->pluck('id') as $user_id){
                if($zero_varible_two_last= Mark::without('user')->where('week_id', $last_previous_week)
                                                ->where('user_id', $user_id)
                                                ->where('is_freezed', 0)
                                                ->select('user_id', DB::raw('sum(reading_mark + writing_mark + support) as out_of_100'))
                                                ->having('out_of_100', '=', 0)
                                                ->groupBy('user_id')
                                                ->get() )
                {

                    $zero_varible_last= Mark::without('user')->where('week_id', $previous_week)
                                        ->where('user_id', $user_id)
                                        ->where('is_freezed', 0)
                                        ->select('user_id', DB::raw('sum(reading_mark + writing_mark + support) as out_of_100'))
                                        ->havingBetween('out_of_100', [10, 100])
                                        ->groupBy('user_id')
                                        ->count();


                    if($zero_varible_last > 0) {
                        $i = $i+1;
                    }
                } 
                $response['number_zero_varible'] = $i;
            }
            // خاص بالقادة
            $total_pages_leader = Mark::without('week')->where('week_id', $previous_week)
                ->where('user_id', $group->user->id)
                ->where('is_freezed', 0)
                ->select(
                    DB::raw('sum(total_pages) as total_pages')
                )->get();
            $response['total_pages_leader'] = $total_pages_leader->pluck('total_pages')->first();

            $total= Mark::without('week')->where('week_id', $previous_week)
                                    ->where('user_id', $group->user->id)
                                    ->where('is_freezed', 0)
                                    ->select('total_thesis','total_screenshot')
                                    ->get();

            if($total->pluck('total_thesis')->first() > 0)
            {
                $response['total_thesis_or_screenshot'] = 'thesis';
            }
            if($total->pluck('total_screenshot')->first() > 0)
            {
                $response['total_thesis_or_screenshot'] = 'screenshot';
            }
            $responseOfsara[$key] = $response;
            $i =0;   

            
        }
        return  $responseOfsara;
          
        
       
        

       
        //return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }
}
