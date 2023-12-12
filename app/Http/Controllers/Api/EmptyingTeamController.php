<?php

namespace App\Http\Controllers\Api;
use App\Models\Group;
use App\Models\User;
use App\Models\EmptyingGroup;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class EmptyingTeamController extends Controller
{
    use ResponseJson;
    function allAmbassadorForEmptying(Request $request){
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }   

        if (Auth::user()->hasanyrole(['admin','consultant'])) {
            $group = Group::where('id',$request->group_id)->with('leaderAndAmbassadors')->first();
            if($group){
                if($group->leaderAndAmbassadors->count() > 0){
                    return $this->jsonResponseWithoutMessage($group->leaderAndAmbassadors, 'data',200);
                } else {
                    return $this->jsonResponseWithoutMessage("تم نقل كل السفراء، بإمكانك تفريغ الفريق الآن", 'data',200);
                }
            } else {
                throw new NotFound;
            }
        }   else  {
            throw new NotAuthorized;
        }       
    }

    function moveAmbassadorsForEmptying(Request $request){
        $validator = Validator::make($request->all(), [
            'ambassador_ids' => 'required',
            'newLeaderEmail' =>  'required|email',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        } 

        if (Auth::user()->hasanyrole(['admin','consultant'])) {
            $new_leader_id = User::where('email',$request->newLeaderEmail)->pluck('id')->first();
            if (!$new_leader_id) {
                return $this->jsonResponseWithoutMessage("القائد غير موجود", 'data', 200);
            }
            $newGroup_id = UserGroup::where('user_id',$new_leader_id)->where('user_type','leader')->pluck('group_id')->first();
            if (!$new_leader_id) {
                return $this->jsonResponseWithoutMessage("الفريق غير موجود", 'data', 200);
            }
            //  termination_reason تعديل حالة 
            $ambassadors = UserGroup::whereIn('user_id',$request->ambassador_ids)->whereIn('user_type',['ambassador','leader'])
            ->update(array('termination_reason'=>'emptying group'));
            // اذا القائد ضمن مجموعة السفراء الحالية الذين سيتم نقلهم 
            if($ambassadors > count($request->ambassador_ids)){
                $old_leader_id = UserGroup::whereIn('user_id',$request->ambassador_ids)->where('user_type','leader')
                ->pluck('user_id')->first();  
                if($old_leader_id){          
                    User::where('id',$old_leader_id)->first()->removeRole('leader');
                }
            }
            foreach ($request->ambassador_ids as $ambassador_id) {
               //create  new UserGroup for ambassadors
                UserGroup::updateOrCreate([
                    'user_id'  => $ambassador_id,
                    'group_id'  => $newGroup_id,
                    'user_type' => 'ambassador',
                ]);
            }
            //تعديل parent_id
            User::whereIn('id',$request->ambassador_ids)->update(['parent_id'=>$new_leader_id]);
            
            return $this->jsonResponseWithoutMessage('done', 'data',200);
        }   else  {
            throw new NotAuthorized;
        }
             
    }

    function EmptyingFollowupTeam(Request $request){
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
            'reason' => 'required',
        ]); 
        
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->hasanyrole(['admin','consultant'])) {
            $group = Group::where('id',$request->group_id)->with('leaderAndAmbassadors')->first();
                if($group){
                    if($group->leaderAndAmbassadors->count() > 0){
                        return $this->jsonResponseWithoutMessage("يجب نقل القائد وكل السفراء قبل اتمام عملية التفريغ", 'data',200);
                    } else{
                       $group = Group::findOrFail($request->group_id);
                        //create  new UserGroup for ambassadors
                        EmptyingGroup::updateOrCreate([
                            'user_id'  =>1, //Auth::id(),
                            'group_id'  => $request->group_id,
                            'reason' => $request->reason,
                            'note' =>$request->note,
                        ]);
                        $group->delete();
                        return $this->jsonResponseWithoutMessage("تم تفريغ الفريق بنجاح", 'data',200);
                    }
                } else {
                    throw new NotFound;
                }    
        }   else {
          throw new NotAuthorized;
       }
    }
}
