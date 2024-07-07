<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Models\MarathonWeek;
use App\Models\Mark;
use App\Models\Thesis;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\MarathonWeeksResource;
use Illuminate\Http\Request;



class MarathonWeekController extends Controller
{    use ResponseJson;

    public function set_weeks(Request $request){
         //validate requested data
         $validator = Validator::make($request->all(), [
            'weeks_id'     => 'required|array',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
      // if (Auth::user()->can('edit week')){
        foreach ($request->weeks_id as $week_id) {
            $marathon_week = MarathonWeek::updateOrCreate(
            ['week_id' => $week_id],
            );
        } 
     //  }
        // } else {
        //     throw new NotAuthorized;
        // }
          
    }
    public function  listMarathonWeeks(){
      //  if (Auth::user()->can('edit week') ) {
         $marathon_week = MarathonWeek::where('is_active', 1)->get();
            if ($marathon_week) {
                return $this->jsonResponseWithoutMessage(MarathonWeeksResource::collection($marathon_week), 'data', 200);
            } else {
                throw new NotFound;
            }
        // } else {
        //     throw new NotAuthorized;
        // }

    }
    public function endMarathon(){
        MarathonWeek::where('is_active',1)->update(['is_active'=> 0]);
        return $this->jsonResponseWithoutMessage("End Marathon Successfully", 'data', 200);


    }
    public function calculateMarkMarathon($user_id,$weekIds)
    {
    // get all weeks of marathon in ascending order
    $week_marathon = Week::whereIn('id', $weekIds)
                        ->orderBy('created_at', 'ASC')
                        ->get();

    $response['point_first_week'] = $this->calculatePoint($week_marathon[0], $user_id,$maximum_total_pages = 14);

    $response['point_second_week'] = $this->calculatePoint($week_marathon[1], $user_id,$maximum_total_pages = 29);

    $response['point_third_week'] = $this->calculatePoint($week_marathon[2], $user_id,$maximum_total_pages = 39);

    $response['point_fourth_week'] = $this->calculatePoint($week_marathon[3], $user_id,$maximum_total_pages = 49);

    return $response;        

    }


    public function calculatePoint($week_marathon, $user_id,$maximum_total_pages){
    $days  = [];
    $point = 0;
    $day = new Carbon($week_marathon->created_at);
    for ($i = 0; $i < 5; $i++) {
        //get  the dates of the five days in week one.
        $days[] = $day->copy()->addDays($i)->format('Y-m-d'); 
 
    }
    $mark = Mark::where('user_id', $user_id)
            ->where('week_id', $week_marathon->id)
            ->first();
    if ($mark) {
    $theses = Thesis::where('mark_id', $mark->id)
                    ->whereIn(DB::raw('DATE(created_at)'), $days)
                    ->get();
    foreach  ($theses  as  $thises ) {
        $total_pages =  $thises->end_page - $thises->start_page + 1;
        if($total_pages >  $maximum_total_pages  ){
            $point = 10 + $point;
        }
    }
    return $point;

}
    
 
   }
}
    

    



        
    
    

