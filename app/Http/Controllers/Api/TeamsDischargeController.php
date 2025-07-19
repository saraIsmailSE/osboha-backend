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


    function discharge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
            'reason' => 'required',
            'note' => 'required',
            'current_to' => 'required',
            'leader_email' => 'required_if:current_to,ambassador',
            'supervisor_email' => 'required_if:current_to,leader'

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
            $new_supervisor_email = null;
            if ($request->has('supervisor_email') && !is_null($request->supervisor_email)) {
                $new_supervisor_email = $request->supervisor_email;
            }

            switch ($groupType) {
                case "followup":
                    $leader_id = $this->handleFollowupDischarge($group->id);
                    if ($leader_id === 0) {
                        break;
                    }
                    if ($leader_id) {
                        $response = $this->updateCurrentStatus($request->current_to, $leader_id, $new_leader_email);
                        if ($response) return $response;
                    } else {
                        return $this->jsonResponseWithoutMessage("لا يمكن التفريغ، القائد الحالي مسؤول عن سفراء آخرين", 'data', 200);
                    }
                    break;
                case "supervising":
                    $supervisor_id = $this->handleSupervisingDischarge($group->id, $request->current_to);
                    if ($supervisor_id === 0) {
                        break;
                    }
                    if ($supervisor_id) {
                        $response = $this->updateCurrentStatus($request->current_to, $supervisor_id, $new_leader_email, $new_supervisor_email);
                        if ($response) return $response;
                    } else {
                        return $this->jsonResponseWithoutMessage("لا يمكن التفريغ، المراقب الحالي مسؤول عن قادة أو مستخدمين آخرين", 'data', 200);
                    }
                    break;
                case "advising":
                    $advisor_id = $this->handleAdvisingDischarge($group->id);
                    if ($advisor_id === 0) {
                        break;
                    }
                    if ($advisor_id) {
                        $response = $this->updateCurrentStatus($request->current_to, $advisor_id, $new_leader_email);
                        if ($response) return $response;
                    } else {
                        return $this->jsonResponseWithoutMessage("لا يمكن التفريغ لأن الموجه مسؤول عن مراقبين آخرين", 'data', 200);
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
            //check if leader is a parent or not
            $hasChildren = User::where('parent_id', $leader->id)->exists();
            if ($hasChildren) {
                return false;
            }

            //سحب رتبة القيادة من القائد الحالي
            $leader->removeRole('leader');
            return $currentLeaderInfo->user_id;
        }

        return 0;
    }
    public function handleSupervisingDischarge($group_id, $current_to)
    {
        $currentSupervisorInfo = UserGroup::where('user_type', 'supervisor')->where('group_id', $group_id)->whereNull('termination_reason')->first();
        if ($currentSupervisorInfo) {
            $supervisor = User::find($currentSupervisorInfo->user_id);
            if ($current_to == 'ambassador') {
                //check if supervisor is a parent or not
                $hasChildren = User::where('parent_id', $supervisor->id)->exists();
                if ($hasChildren) {
                    return false;
                }
            }
            //check if supervisor is a parent of leaders or not
            $hasChildren = User::where('parent_id', $supervisor->id)
                ->whereIn('id', function ($query) {
                    $query->select('model_id')
                        ->from('model_has_roles')
                        ->where('role_id', 5)
                        ->where('model_type', 'App\\Models\\User');
                })
                ->exists();
            if ($hasChildren) {
                return false;
            }
            //سحب رتبة الرقابة من المراقب الحالي
            $supervisor->removeRole('supervisor');
            if ($current_to == 'ambassador') {
                $supervisor->removeRole('leader');
            }
            return $currentSupervisorInfo->user_id;
        }

        return 0;
    }
    public function handleAdvisingDischarge($group_id)
    {
        $currentAdvisorInfo = UserGroup::where('user_type', 'advisor')->where('group_id', $group_id)->whereNull('termination_reason')->first();
        if ($currentAdvisorInfo) {
            $advisor = User::find($currentAdvisorInfo->user_id);
            //check if advisor is a parent of supervisors or not
            $hasChildren = User::where('parent_id', $advisor->id)
                ->whereIn('id', function ($query) {
                    $query->select('model_id')
                        ->from('model_has_roles')
                        ->where('role_id', 4)
                        ->where('model_type', 'App\\Models\\User');
                })
                ->exists();
            if ($hasChildren) {
                return false;
            }

            //سحب رتبة التوجيه من الموجه الحالي
            $advisor->removeRole('advisor');
            return $currentAdvisorInfo->user_id;
        }

        return 0;
    }
    public function updateCurrentStatus($current_to, $current_id, $new_leader_email = null, $new_supervisor_email = null)
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
                    $logInfo = ' قام ' . Auth::user()->fullName . " بسحب السفير " . $userGroup->user->fullName . ' من فريق ' . $userGroup->group->name;
                    Log::channel('community_edits')->info($logInfo);
                }

                break;
            case "leader":
                $newSupervisor = User::where('email', $new_supervisor_email)->first();

                if ($newSupervisor) {
                    //check if new supervisor has supervisor role
                    if ($newSupervisor->hasRole('supervisor')) {
                        $newSupervisorGroupId = UserGroup::where('user_id',  $newSupervisor->id)->where('user_type', 'supervisor')->whereNull('termination_reason')
                            ->whereHas('group.type', function ($q) {
                                $q->where('type', '=', 'supervising');
                            })->pluck('group_id')->first();

                        if ($newSupervisorGroupId) {
                            //المسؤول عن القائد الحالي هو المراقب الجديد

                            User::where('id', $current_id)->update(['parent_id' => $newSupervisor->id]);
                            //update user parent recored
                            UserParent::where("user_id", $current_id)->update(["is_active" => 0]);
                            UserParent::create([
                                'user_id' => $current_id,
                                'parent_id' =>  $newSupervisor->id,
                                'is_active' => 1,
                            ]);

                            // deactive old ambassador recored
                            if ($userGroup) {
                                $userGroup->termination_reason = 'team_discharge';
                                $userGroup->save();
                            }

                            // اضافة الحالي إلى مجموعة الرقابة الجديدة كـ سفير
                            UserGroup::create(
                                [
                                    'user_id' => $current_id,
                                    'user_type' => "ambassador",
                                    'group_id' => $newSupervisorGroupId,
                                    'termination_reason' => Null,
                                ]
                            );
                            // اضافة المراقب الجديد كمراقب إلى مجموعة المتابعة
                            $leaderGroup = UserGroup::where('user_id', $current_id)
                                ->where('user_type', 'leader')
                                ->whereNull('termination_reason')->first();
                            if ($leaderGroup) {
                                UserGroup::create(
                                    [
                                        'user_id' => $newSupervisor->id,
                                        'user_type' => "supervisor",
                                        'group_id' => $leaderGroup->group_id,
                                        'termination_reason' => Null,
                                    ]
                                );
                                // الغاء دور المراقب الحالي
                                UserGroup::where("group_id", $leaderGroup->group_id)->where("user_type", "supervisor")->update(["termination_reason" => "supervisor_change"]);
                            }
                        } else {
                            return $this->jsonResponseWithoutMessage("يجب أن يكون المراقب الجديد مسؤولا في فريق رقابي", 'data', 200);
                        }
                    } else {
                        Log::channel('community_edits')->warning("المستخدم لا يحمل دور مراقب");
                        return $this->jsonResponseWithoutMessage("يجب أن يكون مراقب الجديد  له دور مراقب أولاً", 'data', 200);
                    }
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
                            //المسؤول عن الحالي هو القائد الجديد

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
                return null;
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

        $logInfo = ' قام ' . Auth::user()->fullName . " بتفريغ فريق  " . $group->name;
        Log::channel('community_edits')->info($logInfo);

        return;
    }
}
