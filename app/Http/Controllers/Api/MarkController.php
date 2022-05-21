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

 
class MarkController extends Controller
{
    use ResponseJson;

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

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'out_of_90' => 'required', 
            'out_of_100' => 'required', 
            'total_pages' => 'required',  
            'support' => 'required', 
            'total_thesis' => 'required', 
            'total_screenshot' => 'required',
            'mark_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if(Auth::user()->can('edit mark')){
            $mark = Mark::find($request->mark_id);
            if($mark){
                $mark->update($request->all());
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


    /*
        generateAuditMarks Function
        generate audit marks for supervisor and advisor for each group in current week
    */
    
    public function generateAuditMarks()
    {
        if(Auth::user()->can('audit mark')){

            $current_week =Week::latest()->pluck('id')->first();
            $weekAuditMarks = AuditMark::where('week_id',$current_week)->exists();

            if (!$weekAuditMarks){

                $groupsID = Group::where('type' ,'reading')->pluck('id'); 

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
        
        } else {
            throw new NotAuthorized;
        }
    }


    /*
        leadersAuditmarks Function
        To show all leaders marks for auditor
        Return: leaders marks for current auditor in current week
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

    /*
        showAuditmarks Function
        To show audit marks and note of a specific leader
        Take: leader_id
        Return: audit marks & note & status for specific leader in current week
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

 

    /*
        To add note and status to audit marks
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



    

}
