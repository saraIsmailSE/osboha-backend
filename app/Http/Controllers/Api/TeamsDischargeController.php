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
