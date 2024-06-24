<?php

namespace App\Http\Controllers\Api;
use App\Models\Group;
use App\Models\User;
use App\Models\EmptyingGroup;
use App\Models\UserGroup;
use App\Models\UserParent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

class EmptyingGroupController extends Controller
{
    use ResponseJson;

    //return all Members For Group
    function allMembersForEmptyingGroup(Request $request){
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  
        if (Auth::user()->hasanyrole('admin|advisor|consultant')) {
            
            $response = [];
            $group = Group::find($request->group_id);
            if($group){
                $response['group'] = $group;
                $response['groupMembers'] = UserGroup::where('group_id', $request->group_id)->whereNull('termination_reason')->with('user')->get();
                return $this->jsonResponseWithoutMessage($response, 'data',200);
            } else {
                return $this->jsonResponseWithoutMessage("عذراً، الفريق غير موجود", 'data',200);
            }
        }  else  {
            throw new NotAuthorized;
        }
    }

    public function moveGroupOfAmbassadors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ambassadors' => 'required|array',
            'ambassadors.*' => 'required|email',
            'NewleaderEmail' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->hasanyrole('admin|advisor|consultant')) {
            //get new leader info
            $newLeader = User::where('email', $request->NewleaderEmail)->with('roles')->first();

                if ($newLeader) {
                    // check if new leader has leader role
                    if ($newLeader->hasRole('leader')) {
                       
                        //get the followup group id of new leader
                        $newLeaderGroupId = UserGroup::whereIn('user_type', ['leader', 'special_care_leader'])
                        ->whereNull('termination_reason')->where('user_id', $newLeader->id)->pluck('group_id')->first();

                        if($newLeaderGroupId){
                            $response = [];
                            $response['not_exists'] = [];

                            ##### START WORKING WITH Ambassadors #####
                            foreach ($request->ambassadors as $index => $ambassadorToMove) {                                
                                $ambassador = User::where('email', $ambassadorToMove)->first();
                                if ($ambassador) {
                                                         
                                    //جعل القائد الجديد مسؤل عن السفير
                                    $ambassador->update(['parent_id'  => $newLeader->id]);
                                    //Update User Parent
                                    UserParent::where("user_id", $ambassador->id)->update(["is_active" => 0]);
                                    UserParent::updateOrCreate([
                                        'user_id' => $ambassador->id,
                                        'parent_id' =>  $newLeader->id,
                                        'is_active' => 1,
                                    ]);
                                    //move ambassador to new followup group
                                    UserGroup::where('user_type', 'ambassador')->where('user_id',  $ambassador->id)->update(['termination_reason'  => 'leader_withdrawn']);
                                    UserGroup::updateOrCreate(
                                        [
                                            'user_id' => $ambassador->id,
                                            'user_type' => "ambassador",
                                            'group_id' => $newLeaderGroupId,
                                            'termination_reason' => Null,
                                        ]
                                    );

                                    $logInfo = ' قام ' . Auth::user()->name . " بنقل  " . $ambassador->name . ' إلى القائد ' .  $newLeader->name;
                                    Log::channel('community_edits')->info($logInfo);
                                    $response['message'] = 'تم النقل';
                                } else {
                                    array_push($response['not_exists'], $ambassadorToMove);
                                }
                            }
                            return $this->jsonResponseWithoutMessage($response, 'data', 200);
                            
                        } else {
                          return $this->jsonResponseWithoutMessage( 'فريق المتابعة الجديد غير موجود ', 'data', 200);
                        }
                    } else {
                        return $this->jsonResponseWithoutMessage('يجب أن يكون القائد الجديد قائداً أولاً', 'data', 200);
                    }
                } else {
                    return $this->jsonResponseWithoutMessage('القائد الجديد غير موجود', 'data', 200);
                }
            
        } else {
            throw new NotAuthorized;
        }
    }

    function moveGroupOfSupervisors(Request $request) {
        $validator = Validator::make($request->all(), [
            'supervisors' => 'required|array',
            'supervisors.*' =>'required|email',
            'newAdvisorEmail' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->hasanyrole('admin|advisor|consultant|special_care_coordinator')) {
            $response = [];
            $response['need_promotion'] = [];
            $response['not_exists'] = [];

                //get new supervisor info
            $newAdvisor = User::where('email', $request->newAdvisorEmail)->first();
            if ($newAdvisor) {
                //check if new supervisor has supervisor role
                if ($newAdvisor->hasRole('advisor')) {
                    //get supervising group
                    $newAdvisingGroupId = Group::whereHas('type', function ($q) {
                        $q->where('type', 'advising');
                    })
                        ->whereHas('users', function ($q) use ($newAdvisor) {
                            $q->where('user_id', $newAdvisor->id);
                        })
                        ->pluck('id')
                        ->first();
                    if ($newAdvisingGroupId) {

                            ##### START WORKING WITH Supervisors #####
                            foreach ($request->supervisors as $index => $supervisorToMove) {
                            $supervisor = User::where('email', $supervisorToMove)->first();
                            if ($supervisor) {
                                //check if supervisor has supervisor role
                                if ($supervisor->hasRole('supervisor')) {

                                    //1- get supervisor`s groups
                                    $groupsID = UserGroup::where('user_id', $supervisor->id)->whereNull('termination_reason')->where('user_type','!=','ambassador')->pluck('group_id');

                                    // 2- update advisor                                  
                                    foreach ($groupsID as $key => $id) {
                                        UserGroup::updateOrCreate(
                                            [
                                                'group_id' => $id,
                                                'user_type' => 'advisor',
                                            ],
                                            [
                                                'user_id' => $newAdvisor->id,
                                            ]
                                        );
                                    }

                                    // جعل المراقب سفير في غروب التوجيه الجديد
                                    UserGroup::where('user_type', 'ambassador')->where('user_id',  $supervisor->id)->update(['termination_reason'  => 'transfer_advisor']);
                                    UserGroup::create(
                                        [
                                            'user_id' => $supervisor->id,
                                            'user_type' => "ambassador",
                                            'group_id' => $newAdvisingGroupId
                                        ]
                                    );

                                        //جعل الموجه الجديد مسؤل عن المراقب
                                        $supervisor->update(['parent_id'  => $newAdvisor->id]);
                                        //Update User Parent
                                        UserParent::where("user_id", $supervisor->id)->update(["is_active" => 0]);
                                        UserParent::create([
                                            'user_id' => $supervisor->id,
                                            'parent_id' =>  $newAdvisor->id,
                                            'is_active' => 1,
                                        ]);

                                    
                                    $logInfo = ' قام ' . Auth::user()->name . " بنقل المراقب " . $supervisor->name . ' إلى الموجه ' .  $newAdvisor->name;
                                    Log::channel('community_edits')->info($logInfo);

                                    $response['message'] = 'تم النقل';

                                
                                } else {
                                    array_push($response['need_promotion'], $leaderToMove['leader_email']);
                                }
                            } else {
                                array_push($response['not_exists'], $supervisorToMove['leader_email']);
                            }
                        }
                        return $this->jsonResponseWithoutMessage($response, 'data', 200);
                        

                    } else {
                        $response['message'] = 'فريق التوجيه الجديد غير موجود ';

                        return $this->jsonResponseWithoutMessage($response, 'data', 200);
                    }
                    
                } else {
                    $response['message'] = 'يجب أن يكون الموجه الجديد موجهاً أولاً';

                    return $this->jsonResponseWithoutMessage($response, 'data', 200);
                }

            } else {
                $response['message'] = 'الموجه الجديد غير موجود';

                return $this->jsonResponseWithoutMessage($response, 'data', 200);
            }

        } else {
            throw new NotAuthorized;
        }
    
    }

    public function moveGroupOfAdvisors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'advisors' => 'required|array',
            'advisors.*' => 'required|email',
            'NewConsultantEmail' => 'required|email',
            'newConsultantGroupId' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }


        if (Auth::user()->hasanyrole('admin|advisor|consultant')) {
         

            //get new newConsultant info
            $newConsultant = User::where('email', $request->NewConsultantEmail)->with('roles')->first();
                if ($newConsultant) {
                    // check if new Consultant has Consultant role
                    if ($newConsultant->hasRole('consultant')) {
                       
                        //get the followup group id of new Consultant 
                        ### مشكلة المستشار قد يكون مسؤول عن أكثر من غروب متابعة لذلك يجيب تحديد الغروب ###
                        
                        if($request->newConsultantGroupId){
                            $response = [];
                            $response['need_promotion'] = [];
                            $response['not_exists'] = [];
                            DB::beginTransaction();
                            try {
                            
                                ##### START WORKING WITH Ambassadors #####
                                foreach ($request->advisors as $index => $advisorToMove) {                                
                                    $advisor = User::where('email', $advisorToMove)->first();

                                    if ($advisor) {
                                        //check if new advisor has advisor role
                                        if ($advisor->hasRole('advisor')) {
                                                                
                                            //جعل المستشار الجديد مسؤل عن الموجه
                                            $advisor->update(['parent_id'  => $newConsultant->id]);
                                            //Update User Parent
                                            UserParent::where("user_id", $advisor->id)->update(["is_active" => 0]);
                                            UserParent::updateOrCreate([
                                                'user_id' => $advisor->id,
                                                'parent_id' =>  $newConsultant->id,
                                                'is_active' => 1,
                                            ]);
                                            //move advisor to new consultation group
                                            $advising_group = UserGroup::where('user_id', $advisor->id)->where('user_type', 'advisor')->whereHas('group.type', function ($q) {
                                                $q->where('type', 'consultation');
                                            })->update(['termination_reason'  => 'consultant_withdrawn']);
                                            UserGroup::updateOrCreate(
                                                [
                                                    'user_id' => $advisor->id,
                                                    'user_type' => "advisor",
                                                    'group_id' => $request->newConsultantGroupId,
                                                    'termination_reason' => Null,
                                                ]
                                            );

                                            DB::commit();
                        
                                            $logInfo = ' قام ' . Auth::user()->name . " بتفريغ فريق المتابعة " .$group->name;
                                            Log::channel('community_edits')->info($logInfo);

                                        
                                            $response['message'] = 'تم النقل';
                                        } else {
                                            array_push($response['need_promotion'], $advisorToMove);
                                        }
                                    } else {
                                        array_push($response['not_exists'], $advisorToMove);
                                    }
                                }
                            } catch (\Exception $e) {
                                DB::rollBack();

                                return $e->getMessage();
                            }
                            return $this->jsonResponseWithoutMessage($response, 'data', 200);
                            
                        } else {
                          return $this->jsonResponseWithoutMessage( 'فريق التوجيه الجديد غير موجود ', 'data', 200);
                        }
                    } else {
                        return $this->jsonResponseWithoutMessage('يجب أن يكون المستشار الجديد مستشاراً أولاً', 'data', 200);
                    }
                } else {
                    return $this->jsonResponseWithoutMessage('المستشار الجديد غير موجود', 'data', 200);
                }
            
        } else {
            throw new NotAuthorized;
        }
    }


   
     function EmptyingGroup(Request $request){

        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
            'reason' => 'required',
            'current_to' => 'required',
            'NewleaderEmail' =>  'email',
        ]); 
        
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->hasanyrole('admin|advisor|consultant|special_care_coordinator')) {
            $group = Group::findOrFail($request->group_id);
            $members = UserGroup::where('group_id',$request->group_id)->whereNull('termination_reason')->get();  

            if($group->type->type == 'consultation' && $members->where('user_type','advisor')->count() > 0 ) {
                return $this->jsonResponseWithoutMessage("يجب نقل كل الموجهين قبل اتمام عملية التفريغ", 'data',200);
            } 

            if($members->where('user_type','ambassador')->count() > 0 ) {
                return $this->jsonResponseWithoutMessage("يجب نقل كل السفراء قبل اتمام عملية التفريغ", 'data',200);
            } 
            
            if($group->type->type == 'followup'){
                $currentLeaderId = $members->where('user_type','leader')->pluck('user_id');
                $currentLeader = User::where('id', $currentLeaderId)->first();

                DB::beginTransaction();
                try {

                    //سحب رتبة القيادة من القائد الحالي
                    $currentLeader->removeRole('leader');
                    //Update User Parent
                    UserParent::where("user_id", $currentLeader->id)->update(["is_active" => 0]);
                    //وإزالة القائد الحالي من مجموعة المتابعة
                    UserGroup::where('user_type', 'leader')->where('user_id', $currentLeader->id)->whereNull('termination_reason')->update(['termination_reason'  => 'leader_withdrawn']);
                    //وإزالة القائد الحالي من مجموعة الرقابة
                    UserGroup::where('user_type', 'ambassador')->where('user_id',  $currentLeader->id)->whereNull('termination_reason')->update(['termination_reason'  => 'leader_withdrawn']);
                    
                    if($request->current_to == "ambassador"){
                        $newLeader = User::where('email', $request->NewleaderEmail)->first();
                        if ($newLeader) {
                            //check if new leader has leader role
                            if ($newLeader->hasRole('leader')) {
                                $newLeaderGroupId = UserGroup::where('user_type', 'leader')->where('user_id',  $newLeader->id)->whereNull('termination_reason')->pluck('group_id')->first();
                                if ($newLeaderGroupId) {
                                    //المسؤول عن القائد الحالي هو القائد الجديد
                                    $currentLeader->update(['parent_id'  => $newLeader->id]);
                                        
                                    UserParent::updateOrCreate([
                                        'user_id' => $currentLeader->id,
                                        'parent_id' =>  $newLeader->id,
                                        'is_active' => 1,
                                    ]);
                                    
                                    // اضافة القائد الحالي إلى مجموعة المتابعة الجديدة كـ سفير
                                    UserGroup::updateOrCreate(
                                        [
                                            'user_id' => $currentLeader->id,
                                            'user_type' => "ambassador",
                                            'group_id' => $newLeaderGroupId,
                                            'termination_reason' => Null,
                                        ]
                                    );


                                } else {
                                    return $this->jsonResponseWithoutMessage("يجب أن يكون القائد الجديد قائد لفريق متابعة", 'data', 200);
                                }
                            } else {
                                return $this->jsonResponseWithoutMessage("يجب أن يكون القائد الجديد  له دور قائد أولاً", 'data', 200);
                            }
        
                        } else {
                            return $this->jsonResponseWithoutMessage("القائد  الجديد غير موجود", 'data', 200);
                        }

                    } 
                    
                    // تفريغ باقي الأعضاء - المسؤولون
                    UserGroup::where('group_id',$request->group_id)->whereNull('termination_reason')->update(['termination_reason' => 'leader_withdrawn']);
                    $administratorsId = $members->pluck('user_id');
                    UserParent::whereIn("user_id", $administratorsId)->update(["is_active" => 0]);

                    // حذف الفريق - soft delete

                        EmptyingGroup::create([
                            'user_id'  => Auth::id(),
                            'group_id'  => $request->group_id,
                            'reason' => $request->reason,
                        ]);
                        $group->delete();

                    DB::commit();
                    
                    $logInfo = ' قام ' . Auth::user()->name . " بتفريغ فريق المتابعة " .$group->name;
                    Log::channel('community_edits')->info($logInfo);

                    return $this->jsonResponseWithoutMessage("تم التفريغ ", 'data', 200);
                } catch (\Exception $e) {
                    DB::rollBack();

                    return $e->getMessage();
                }
               
                 
            } elseif ($group->type->type == 'supervising'){
                /*
                    $currentSupervisorId = $members->where('user_type','supervisor')->pluck('user_id');
                    $currentSupervisor = User::where('id', $currentSupervisorId)->first();
                    if ($currentSupervisor) {
                        // سحب رتبة الرقابة من المراقب الحالي
                         $currentSupervisor->removeRole("supervisor");
                         
                        if($request->current_to == 'leader'){
                                //get new supervisor info
                                $newSupervisor = User::where('email', $request->NewleaderEmail)->first();
                                if($newSupervisor){
                                    if ($newSupervisor->hasRole('supervisor')) {
                                    $newSupervisorGroupId = UserGroup::where('user_type', 'supervisor')->where('user_id',  $newSupervisor->id)->whereNull('termination_reason')->pluck('group_id')->first();
                                    if($newSupervisorGroupId){
                                        //* اضافة المراقب الحالي كـ سفير في مجموعة الرقابة الخاصة بالمراقب الجديد
                                        $supervisingGroupTypeID = GroupType::where('type', 'supervising')->pluck('id')->first();
                                    
                                        $newSupervisor_SupervisingGroupID = UserGroup::where('user_id', $newSupervisor->id)
                                            ->whereHas('group', function ($q)  use ($supervisingGroupTypeID) {
                                            $q->where('type_id', '=', $supervisingGroupTypeID);
                                            })->where('user_type', 'supervisor')->pluck('group_id')->first();
    
                                        UserGroup::where('user_type', 'ambassador')->where('user_id',  $currentSupervisor->id)->update(['termination_reason'  => 'supervisor_withdrawn']);
                                        UserGroup::create(
                                            [
                                                'user_id' => $currentSupervisor->id,
                                                'user_type' => "ambassador",
                                                'group_id' => $newSupervisor_SupervisingGroupID
                                            ]
                                        );
    
                                        //* المسؤول عن المراقب الحالي يصبح المراقب الجديد
                                        $currentSupervisor->parent_id = $newSupervisor->id;
                                        $currentSupervisor->save();
        
                                        //Update User Parent
                                        UserParent::where("user_id", $currentSupervisor->id)->update(["is_active" => 0]);
                                        UserParent::create([
                                            'user_id' => $currentSupervisor->id,
                                            'parent_id' =>  $newSupervisor->id,
                                            'is_active' => 1,
                                        ]);
    
                                        //* اضافة المراقب الجديد إلى  مجموعة المتابعة الخاصة بالمراقب الحالي
                                        UserGroup::where('user_id', $currentSupervisor->id)
                                        ->where('user_type', "supervisor")
                                        ->update(['user_id'  => $newSupervisor->id]);
    
    
                                    } else {
                                        return $this->jsonResponseWithoutMessage("يجب أن يكون المراقب الجديد مراقب لفريق رقابة", 'data', 200);
                                    }
                                } else {
                                    return $this->jsonResponseWithoutMessage("يجب أن يكون المراقب الجديد  له دور مراقب أولاً", 'data', 200);
                                }
    
                            } else{
                                return $this->jsonResponseWithoutMessage("المراقب  الجديد غير موجود", 'data', 200);
                            }
    
                        
                        } elseif ($request->current_to == 'ambassador') {
                  
                            
                        } elseif ($request->current_to == 'withdrawn') {
                            //* سحب رتبة الرقابة والقيادة من المراقب الحالي
                            $currentSupervisor->removeRole("supervisor");
                            $currentSupervisor->removeRole("leader");
            
                            //Update User Parent
                            UserParent::where("user_id", $ambassador->id)->update(["is_active" => 0]);
                            //* ازالة المراقب الحالي من الأفرقة الموجود فيها (الرقابة)
                            UserGroup::where('user_id', $currentSupervisor->id)->whereNull('termination_reason')->update(['termination_reason'  => 'supervisor_withdrawn']);  
                        }
                    }

            */
                }   elseif($group->type->type == 'advising'){

            } else {
                return $this->jsonResponseWithoutMessage("عذراً، لا يمكن تفريغ هذا النوع من المجموعات", 'data',200);
            }

            

        } else {
          throw new NotAuthorized;
       }
    }
}
