<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mark;
use App\Models\AuditMark;
use App\Models\UserGroup;
use App\Models\Group;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Http\Resources\MarkResource;
use App\Models\User;
use App\Models\Week;
use App\Events\MarkStats;
use App\Models\Thesis;

class MarkController extends Controller
{
    use ResponseJson;

     /**
     * Read all  marks in the current week in the system(“audit mark” permission is required)
     * 
     * @return jsonResponseWithoutMessage;
     */
    public function index()
    {
        if(Auth::user()->can('audit mark')){
            $current_week = Week::latest()->first();
            $marks = Mark::where('week_id', $current_week->id)->get();
        
            if($marks){
                return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data',200);
            }
            else{
               throw new NotFound;
            }
        } else {
            throw new NotAuthorized;   
        }
    }

    /**
     * Find and show an existing  mark in the system by its id  ( “audit mark” permission is required).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mark_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        if(Auth::user()->can('audit mark')){
            $mark = Mark::find($request->mark_id);
            if($mark){
                return $this->jsonResponseWithoutMessage(new MarkResource($mark), 'data',200);
            }
            else{
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;   
        } 
    }

    /**
     * Update an existing mark ( “edit mark” permission is required).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'out_of_90' => 'required', 
            'out_of_100' => 'required', 
            // 'total_pages' => 'required',  
            // 'support' => 'required', 
            'total_thesis' => 'required', 
            // 'total_screenshot' => 'required',
            'mark_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if(!Auth::user()->can('edit mark')){
            $mark = Mark::find($request->mark_id);
            $old_mark = $mark->getOriginal();
            if($mark){
               $mark->update($request->all());
                event(new MarkStats($mark,$old_mark));
                return $this->jsonResponseWithoutMessage("Mark Updated Successfully", 'data', 200);
            }
            else{
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized;   
        }
    }
    /**
     * Return list of user mark ( audit mark” permission is required OR request user_id == Auth).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function list_user_mark(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required_without:week_id',
            'week_id' => 'required_without:user_id'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        
        if((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
            && $request->has('week_id') && $request->has('user_id'))
        {
            $marks = Mark::where('user_id', $request->user_id)
                        ->where('week_id', $request->week_id)->get();
            if($marks){
                return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data',200);
            }
            else{
                throw new NotFound;
            }
        } 
        else if((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
                && $request->has('week_id'))
        {
            $marks = Mark::where('week_id', $request->week_id)->get();
            if($marks){
                return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data',200);
            }
            else{
                throw new NotFound;
            }
        }
        else if((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
                && $request->has('user_id'))
        {
            $mark = Mark::where('user_id', $request->user_id)->latest()->first();
            if($mark){
                return $this->jsonResponseWithoutMessage(new MarkResource($mark), 'data',200);
            }
            else{
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized;   
        }
    }


    /**
     * Generate audit marks for supervisor and advisor for each reading group in the current week,
     * (if this week is not vacation) automatically on Sunday at 6:00 A.M Saudi Arabia time.
     * 
     * @return jsonResponseWithoutMessage;
     */
    
    public function generateAuditMarks()
    {
        $current_week =Week::latest()->pluck('id')->first();
        $weekAuditMarks = AuditMark::where('week_id',$current_week)->exists();

        if (!$weekAuditMarks){

            $groupsID = Group::where('type_id' ,1)->pluck('id'); //type = reading

            foreach ($groupsID as $key => $groupID) {

                $leaderID =UserGroup::where('group_id',$groupID )
                                ->where('user_type','Leader ')
                                ->pluck('user_id')
                                ->first();
                $supervisorID =UserGroup::where('group_id',$groupID )
                                ->where('user_type','supervisor ')
                                ->pluck('user_id')
                                ->first();
                $advisorID =UserGroup::where('group_id',$groupID )
                                ->where('user_type','advisor ')
                                ->pluck('user_id')
                                ->first();

                $allAmbassadorsID =UserGroup::where('group_id',$groupID )
                                ->where('user_type','Ambassador ')
                                ->pluck('user_id');

                // Get 10% of Full Mark
                $highMark = Mark::whereIn('user_id',$allAmbassadorsID)
                            ->where('out_of_100' , 100)
                            ->count();

                $rateHighMarkToAudit = $highMark * 10 /100;
                $highMarkToAudit = Mark::whereIn('user_id',$allAmbassadorsID)
                            ->where('out_of_100' , 100)
                            ->inRandomOrder()
                            ->limit($rateHighMarkToAudit)
                            ->pluck('id')->toArray();
                    
                //Get 10% of not Full Mark
                $lowMark = Mark::whereIn('user_id',$allAmbassadorsID)
                            ->where('out_of_100', '<' , 100)
                            ->count();
                
                $rateLowMarkToAudit = $lowMark * 10 /100;
                $lowMarkToAudit = Mark::whereIn('user_id',$allAmbassadorsID)
                            ->where('out_of_100','!=' , 100)
                            ->inRandomOrder()
                            ->limit($rateLowMarkToAudit)
                            ->pluck('id')->toArray();
                $supervisorAuditMarks = array_merge($highMarkToAudit ,$lowMarkToAudit);

                // Save Marks for supervisors
                $auditMarks=new AuditMark; 
                $auditMarks->week_id=$current_week;
                $auditMarks->aduitor_id=$supervisorID;
                $auditMarks->leader_id=$leaderID;
                $auditMarks->aduitMarks=serialize($supervisorAuditMarks);
                $auditMarks->save();

                //Get 10% of supervisor Marks
                $supervisorMark = Mark::whereIn('id',$supervisorAuditMarks)
                            ->count();
                
                $rateSupervisorMark1  = $lowMark * 10 /100;
                $advisorMarks1 = Mark::whereIn('id',$supervisorAuditMarks)
                            ->inRandomOrder()
                            ->limit($rateSupervisorMark1)
                            ->pluck('id')->toArray();

                //Get 5% of not supervisor Marks
                $rateSupervisorMark2 = count($allAmbassadorsID) * 5 /100;

                $advisorMarks2 = Mark::whereIn('user_id',$allAmbassadorsID)
                                    ->whereNotIn('user_id',$supervisorAuditMarks)
                                    ->limit($rateSupervisorMark2)
                                    ->pluck('id')->toArray();

                $advisorAuditMarks = array_merge($advisorMarks1 ,$advisorMarks2);

                    // Save Marks for advisor
                $auditMarks=new AuditMark;
                $auditMarks->week_id=$current_week;
                $auditMarks->aduitor_id=$advisorID;
                $auditMarks->leader_id=$leaderID;
                $auditMarks->aduitMarks=serialize($advisorAuditMarks);
                $auditMarks->save();

            }

            return $this->jsonResponseWithoutMessage("Audit Marks Are Generated Successfully", 'data', 200);

        } else {
            return $this->jsonResponseWithoutMessage("Audit Marks Already Exist For This Week", 'data', 200);
        }
    }


    /**
     *  Return all leader marks for auth auditor in current week.
     * 
     *  @return jsonResponseWithoutMessage;
     */
    public function leadersAuditmarks()
    {
        if(Auth::user()->can('audit mark')){
            $current_week = Week::latest()->pluck('id')->first();
            $leadersAuditMarksId = AuditMark::where('aduitor_id', Auth::id())
                            ->where('week_id',$current_week)
                            ->pluck('leader_id');
            if($leadersAuditMarksId){ 
                $leadersMark = Mark::whereIn('user_id',$leadersAuditMarksId)
                                ->where('week_id',$current_week) 
                                ->get();
                return $this->jsonResponseWithoutMessage(MarkResource::collection($leadersMark), 'data',200);

            } else {
                throw new NotFound;
            }  

        } else {
            throw new NotAuthorized;
        }

    }
    /**
     *  Return audit marks & note & status for a specific leader in current week 
     *  by leader_id with “audit mark” permission.
     *  
     *  @return jsonResponseWithoutMessage;
    */
    public function showAuditmarks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leader_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if(Auth::user()->can('audit mark')){
            $current_week = Week::latest()->pluck('id')->first();
            $auditMarksId = AuditMark::where('leader_id', $request->leader_id)
                            ->where('week_id',$current_week)
                            ->where('aduitor_id',Auth::id())
                            ->pluck('id')
                            ->first(); 

            if($auditMarksId){
                $auditMarksIds = AuditMark::where('id',$auditMarksId)
                            ->pluck('aduitMarks')
                            ->first();

                $note = AuditMark::select('note','status')
                            ->where('id',$auditMarksId)
                            ->first();

                $marksId = unserialize($auditMarksIds);
                $aduitMarks = Mark::whereIn('id',$marksId)->get();
                $aduitMarks = MarkResource::collection($aduitMarks);
                $marksAndNote = $aduitMarks->merge($note);  

                return $this->jsonResponseWithoutMessage($marksAndNote, 'data',200);

            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }

 
    /**
     * Update note and status for existing audit marks by its id with “audit mark” permission.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function updateAuditMark(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'auditMark_id' => 'required',
            'note' => 'required',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if(Auth::user()->can('audit mark')){
            $auditMarks = AuditMark::where('id' , $request->auditMark_id)->first();
            if($auditMarks){
                $auditMarks->note = $request->note;
                $auditMarks->status = $request->status;
                $auditMarks->update();
                return $this->jsonResponseWithoutMessage("Audit Mark Updated Successfully", 'data', 200);
            } else {
                throw new NotFound;
            }

        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * get user month achievement
     * 
     * @param  $user_id,$filter
     * @return month achievement;
     */
    public function userMonthAchievement($user_id,$filter){

        if($filter == 'current'){
            $currentMonth = date('m');
        }
        if($filter == 'previous'){
            $currentMonth = date('m')-1;
        }
        
    $weeksInMonth=Week::whereRaw('MONTH(created_at) = ?',[$currentMonth])->get();
    $month_achievement= Mark::where('user_id', $user_id)->whereIn('week_id', $weeksInMonth->pluck('id'))->get();
    $response['month_achievement']=  $month_achievement->pluck('out_of_100','week.title');
    $response['month_achievement_title']= Week::whereIn('id', $weeksInMonth->pluck('id'))->pluck('title')->first();
    return $this->jsonResponseWithoutMessage($response, 'data', 200);
}
    /**
     * get user week achievement
     * 
     * @param  $user_id,$filter
     * @return month achievement;
     */
    public function userWeekAchievement($user_id,$filter){

        if($filter == 'current'){
            $week= Week::latest()->pluck('id')->toArray();
        }
        if($filter == 'previous'){
            $week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->pluck('id')->toArray();
        }
        if($filter == 'in_a_month'){
            $currentMonth = date('m');
            $week=Week::whereRaw('MONTH(created_at) = ?',[$currentMonth])->pluck('id')->toArray();

        }
        
       $response['week_mark'] = Mark::whereIn('week_id', $week)->where('user_id', $user_id)->first();
    return $this->jsonResponseWithoutMessage($response, 'data', 200);
}

    /**
     * get user mark with theses => list only for group administrators
     * 
     * @param  $user_id
     * @return mark;
     */
    public function ambassadorMark($user_id){
        $group_id = UserGroup::where('user_id', $user_id)->where('user_type', 'ambassador')->pluck('group_id')->first();
        $response['group'] = Group::where('id', $group_id)->with('groupAdministrators')->first();

        $response['currentWeek']= Week::latest()->first();
        $response['mark']= Mark::where('user_id',$user_id)->where('week_id',$response['currentWeek']->id)->first();
        $response['theses']=Thesis::with('book')->where('mark_id',  $response['mark']->id)->get();
        return $this->jsonResponseWithoutMessage($response, 'data', 200);

        // if (in_array(Auth::id(), $response['group']->groupAdministrators->pluck('id')->toArray())) {


        // }
        // else {
        //     throw new NotAuthorized;
        // }


    }
}