<?php

namespace App\Http\Controllers\Api;

use App\Models\Group;
use App\Models\User;
use App\Models\TeamsDischarge;
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
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class TeamsDischargeController extends Controller
{
    use ResponseJson;

    //return all Members For Group
    function allMembersForTeamsDischarge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if (Auth::user()->hasanyrole('admin|advisor|consultant')) {

            $response = [];
            $group = Group::find($request->group_id);
            if ($group) {
                $response['group'] = $group;
                $response['groupMembers'] = UserGroup::where('group_id', $request->group_id)->whereNull('termination_reason')->with('user')->get();
                return $this->jsonResponseWithoutMessage($response, 'data', 200);
            } else {
                return $this->jsonResponseWithoutMessage("عذراً، الفريق غير موجود", 'data', 200);
            }
        } else {
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

                    if ($newLeaderGroupId) {
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
                                array_push($response['not_exists'], $leaderToMove);
                            }
                        }
                        return $this->jsonResponseWithoutMessage($response, 'data', 200);
                    } else {
                        return $this->jsonResponseWithoutMessage('فريق المتابعة الجديد غير موجود ', 'data', 200);
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

    function moveGroupOfSupervisors(Request $request)
    {
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

                    if ($request->newConsultantGroupId) {
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

                                        $logInfo = ' قام ' . Auth::user()->name . " بتفريغ فريق المتابعة " . $group->name;
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
                        return $this->jsonResponseWithoutMessage('فريق التوجيه الجديد غير موجود ', 'data', 200);
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



    function TeamsDischarge(Request $request)
    {

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
            $members = UserGroup::where('group_id', $request->group_id)->whereNull('termination_reason')->get();

            if ($group->type->type == 'consultation' && $members->where('user_type', 'advisor')->count() > 0) {
                return $this->jsonResponseWithoutMessage("يجب نقل كل الموجهين قبل اتمام عملية التفريغ", 'data', 200);
            }

            if ($members->where('user_type', 'ambassador')->count() > 0) {
                return $this->jsonResponseWithoutMessage("يجب نقل كل السفراء قبل اتمام عملية التفريغ", 'data', 200);
            }

            if ($group->type->type == 'followup') {
                $currentLeaderId = $members->where('user_type', 'leader')->pluck('user_id');
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

                    if ($request->current_to == "ambassador") {
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
                    UserGroup::where('group_id', $request->group_id)->whereNull('termination_reason')->update(['termination_reason' => 'leader_withdrawn']);
                    $administratorsId = $members->pluck('user_id');
                    UserParent::whereIn("user_id", $administratorsId)->update(["is_active" => 0]);

                    // حذف الفريق - soft delete

                    TeamsDischarge::create([
                        'user_id'  => Auth::id(),
                        'group_id'  => $request->group_id,
                        'reason' => $request->reason,
                    ]);
                    $group->delete();

                    DB::commit();

                    $logInfo = ' قام ' . Auth::user()->name . " بتفريغ فريق المتابعة " . $group->name;
                    Log::channel('community_edits')->info($logInfo);

                    return $this->jsonResponseWithoutMessage("تم التفريغ ", 'data', 200);
                } catch (\Exception $e) {
                    DB::rollBack();

                    return $e->getMessage();
                }
            } elseif ($group->type->type == 'supervising') {
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
            } elseif ($group->type->type == 'advising') {
            } else {
                return $this->jsonResponseWithoutMessage("عذراً، لا يمكن تفريغ هذا النوع من المجموعات", 'data', 200);
            }
        } else {
            throw new NotAuthorized;
        }
    }

    function discharge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
            'reason' => 'required',
            'note' => 'required',
            'current_to' => 'required',
            'leader_email' => 'required_if:current_to,ambassador'

        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (!Auth::user()->hasanyrole('admin|advisor|consultant|special_care_coordinator')) {
            throw new NotAuthorized;
        }

        //Allowed To Discharge

        $group = Group::with('userAmbassador', 'groupAdministrators')->withCount('userAmbassador as ambassadors_count')->findOrFail($request->group_id);
        $groupType = $group->type->type;

        //check if a team have ambassadors
        if ($group->ambassadors_count != 0) {
            return $this->jsonResponseWithoutMessage("يجب نقل كل السفراء قبل اتمام عملية التفريغ", 'data', 200);
        }

        try {
            DB::beginTransaction();
            $new_leader_email = null;
            if ($request->has('leader_email') && !is_null($request->leader_email)) {
                $new_leader_email = $request->leader_email;
            }

            switch ($groupType) {
                case "followup":
                    $leader_id = $this->handleFollowupDischarge($group->id);
                    if ($leader_id) {
                        $this->updateCurrentStatus($request->current_to, $leader_id, $new_leader_email);
                    }
                    break;
                default:
                    return $this->jsonResponseWithoutMessage("عذراً، لا يمكن تفريغ هذا النوع من المجموعات", 'data', 200);
            }

            $this->handelDischarge($request->group_id, $request->reason, $request->note);
            DB::commit();

            return $this->jsonResponseWithoutMessage("تم التفريغ", 'data', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }
    }

    public function handleFollowupDischarge($group_id)
    {
        $currentLeaderInfo = UserGroup::where('user_type', 'leader')->where('group_id', $group_id)->whereNull('termination_reason')->first();
        if ($currentLeaderInfo) {
            $leader = User::find($currentLeaderInfo->user_id);
            //سحب رتبة القيادة من القائد الحالي
            $leader->removeRole('leader');

            //check if leader is a parent or not

            return $currentLeaderInfo->user_id;
        }

        return false;
    }
    public function updateCurrentStatus($current_to, $current_id, $new_leader_email = null)
    {
        //get where the current is ambassador
        $userGroup = UserGroup::where('user_id', $current_id)
            ->where('user_type', 'ambassador')
            ->whereNull('termination_reason')->first();

        switch ($current_to) {
            case "withdrawn":
                //update user status
                User::where('id', $current_id)->update(['is_hold' => 1, 'parent_id' => null]);
                //Update User Parent
                UserParent::where("user_id", $current_id)->update(["is_active" => 0]);

                //update termination_reason where the current is ambassador to withdrawn
                if ($userGroup) {
                    $userGroup->termination_reason = 'withdrawn';
                    $userGroup->save();
                    $logInfo = ' قام ' . Auth::user()->name . " بسحب السفير " . $userGroup->user->name . ' من فريق ' . $userGroup->group->name;
                    Log::channel('community_edits')->info($logInfo);
                }

                break;

            default:
                // current to ambassador
                $newLeader = User::where('email', $new_leader_email)->first();
                if ($newLeader) {
                    //check if new leader has leader role
                    if ($newLeader->hasRole('leader')) {
                        $newLeaderGroupId = UserGroup::where('user_type', 'leader')->where('user_id',  $newLeader->id)->whereNull('termination_reason')->pluck('group_id')->first();
                        if ($newLeaderGroupId) {
                            //المسؤول عن القائد الحالي هو القائد الجديد

                            User::where('id', $current_id)->update(['parent_id' => $newLeader->id]);
                            //update user parent recored
                            UserParent::where("user_id", $current_id)->update(["is_active" => 0]);
                            UserParent::create([
                                'user_id' => $current_id,
                                'parent_id' =>  $newLeader->id,
                                'is_active' => 1,
                            ]);

                            // deactive old ambassador recored
                            if ($userGroup) {
                                $userGroup->termination_reason = 'team_discharge';
                                $userGroup->save();
                            }


                            // اضافة الحالي إلى مجموعة المتابعة الجديدة كـ سفير
                            UserGroup::create(
                                [
                                    'user_id' => $current_id,
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
                    break;
                }
                return true;
        }
    }

    public function handelDischarge($group_id, $reason, $note)
    {
        $group = Group::find($group_id);
        $group->is_active = 0;
        $group->save();

        TeamsDischarge::create([
            'user_id'  => Auth::id(),
            'group_id'  => $group_id,
            'reason' => $reason,
            'note' => $note,
        ]);

        UserGroup::where('group_id', $group_id)->whereNull('termination_reason')->update(['termination_reason' => 'team_discharge']);

        $logInfo = ' قام ' . Auth::user()->name . " بتفريغ فريق  " . $group->name;
        Log::channel('community_edits')->info($logInfo);

        return;
    }
}
