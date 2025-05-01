<?php

namespace App\Http\Controllers\Api;

use App\Enums\SystemRole;
use App\Http\Controllers\Controller;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Helpers\RoleHelper;
use App\Models\Group;
use App\Models\GroupType;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserParent;
use App\Notifications\MailDowngradeRole;
use App\Notifications\MailUpgradeRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RolesAdministrationController extends Controller
{
    use ResponseJson;

    public function getSecondaryRoles($type)
    {
        $rolesToRetrieve = [];
        switch ($type) {

            case 'specialCare':
                $rolesToRetrieve = [
                    'special_care_coordinator',
                    'special_care_supervisor',
                    'special_care_leader',
                    'ambassador',
                ];
                break;
            case 'marathon':
                $rolesToRetrieve = [
                    'marathon_coordinator',
                    'marathon_verification_supervisor',
                    'marathon_supervisor',
                    'marathon_ambassador',
                ];
                break;
            case 'ramadan':
                $rolesToRetrieve = [
                    'ramadan_coordinator',
                    'ramadan_hadith_corrector',
                    'ramadan_fiqh_corrector',
                    'ramadan_tafseer_corrector',
                    'ramadan_vedio_corrector',
                ];
                break;
            case 'eligible':
                $rolesToRetrieve = [
                    'eligible_admin',
                    'reviewer',
                    'auditor',
                    'super_auditer',
                    'super_reviewer',
                    'user_accept',
                ];
                break;
            case 'WithdrawnsTeam':
                $rolesToRetrieve = [
                    'coordinator_of_withdrawns_team',
                    'member_of_withdrawns_team',
                ];
                break;
            case 'booksTeam':
                $rolesToRetrieve = [
                    'book_quality_team_coordinator',
                    'book_quality_supervisor',
                    'book_quality_team',
                ];
                break;
            case 'osbohaSupport':
                $rolesToRetrieve = [
                    'osboha_support_coordinator',
                    'osboha_support_member',
                ];
                break;
        }

        $roles = Role::whereIn('name', $rolesToRetrieve)->orderBy('id', 'desc')->get();
        return $this->jsonResponseWithoutMessage($roles, 'data', 200);
    }


    public function assignNonBasicRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user' => 'required|email',
            'role_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors()->first(), 'data', 500);
        }

        //check role exists
        $role = Role::find($request->role_id);
        if (!$role) {
            return $this->jsonResponseWithoutMessage("هذه الرتبة غير موجودة", 'data', 200);
        }

        //check user exists
        $user = User::where('email', $request->user)->first();
        if ($user) {

            $arabicRole = SystemRole::translate($role->name);
            if ($user->hasRole($role->name)) {
                return $this->jsonResponseWithoutMessage("المستخدم موجود مسبقاً ك" . $arabicRole, 'data', 200);
            } else {
                //check if user has a leader role
                if (in_array(
                    $role->name,
                    [
                        SystemRole::SPECIAL_CARE_LEADER->value,
                        SystemRole::SPECIAL_CARE_SUPERVISOR->value
                    ]
                ) && !$user->hasRole(SystemRole::LEADER->value)) {
                    return $this->jsonResponseWithoutMessage("يجب أن يكون العضو قائداً أولاًً ", 'data', 200);
                }

                $user->assignRole($role->name);
                $msg = "قام " . Auth::user()->fullName . " بـ ترقيتك لـ : " . $arabicRole;
                (new NotificationController)->sendNotification($user->id, $msg, ROLES);

                $logInfo = ' قام ' . Auth::user()->fullName . " بترقية العضو " . $user->fullName . ' لـ ' . $role->name;
                Log::channel('community_edits')->info($logInfo);

                return $this->jsonResponseWithoutMessage("تمت ترقية العضو ل " . $arabicRole, 'data', 200);
            }
        } else {
            return $this->jsonResponseWithoutMessage("المستخدم غير موجود", 'data', 200);
        }
    }

    /**
     * assign role and assign head user to a user .
     *
     * @return jsonResponseWithoutMessage;
     */

    public function assignRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user' => 'required|email|different:head_user',
            'head_user' =>
            [
                // 'required',
                'required_unless:role_id,' . Role::where('name', SystemRole::SUPPORT_LEADER->value)->first()->id,
                'different:user',
                'email',
                'nullable'

            ],
            'role_id' => 'required',
        ]);


        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors()->first(), 'data', 500);
        }


        //check role exists
        $role = Role::find($request->role_id);
        if (!$role) {
            return $this->jsonResponseWithoutMessage("هذه الرتبة غير موجودة", 'data', 200);
        }

        //check user exists
        $user = User::where('email', $request->user)->first();
        if ($user) {

            $authUser = Auth::user();
            // / !(SystemRole::canUserManageAnotherUser($authUser, $user))

            if (!(SystemRole::canUserManageRole($authUser, $role->name))) {
                return $this->jsonResponseWithoutMessage("ليس لديك صلاحية لترقية العضو ل " . SystemRole::translate($role->name), 'data', 200);
            }
            //check if role is support leader, just assign role
            if ($role->name === SystemRole::SUPPORT_LEADER->value) {
                return $this->handleSupportLeaderRole($user, $role);
            }

            //check head_user exists
            $head_user = User::where('email', $request->head_user)->first();
            if ($head_user) {
                $arabicRole = SystemRole::translate($role->name);

                //check if user has the role
                if ($user->hasRole($role->name)) {
                    return $this->jsonResponseWithoutMessage("المستخدم موجود مسبقاً ك" . $arabicRole, 'data', 200);
                }

                //check if head roles has a role greater that the greatest role of the user
                if (SystemRole::canUserManageAnotherUser($head_user, $user)) {

                    //check if head user role is greater that user role
                    if (SystemRole::canUserManageRole($head_user, $role->name)) {

                        //check if supervisor is a leader first
                        if (
                            !$user->hasRole(SystemRole::LEADER->value) &&
                            $role->name === SystemRole::SUPERVISOR->value
                        ) {
                            return $this->jsonResponseWithoutMessage("لا يمكنك ترقية العضو لمراقب مباشرة, يجب أن يكون قائد أولاً", 'data', 200);
                        }

                        $userRoles = [];
                        if ($role->name === SystemRole::SUPERVISOR->value) {
                            //remove all roles except the ambassador and book_quality_team and leader
                            $userRoles = $user->roles()->whereNotIn('name', [SystemRole::AMBASSADOR->value, SystemRole::BOOK_QUALITY_TEAM->value, SystemRole::LEADER->value])->pluck('name');
                        } else {
                            $userRoles = $user->roles()->whereNotIn('name', [SystemRole::AMBASSADOR->value, SystemRole::BOOK_QUALITY_TEAM->value])->pluck('name');
                        }

                        foreach ($userRoles as $userRole) {
                            $user->removeRole($userRole);
                        }

                        if (count($userRoles) > 0) {
                            $userRoles = $userRoles->map(function ($role) {
                                return SystemRole::translate($role);
                            });
                        }

                        // assign new role
                        $user->assignRole($role->name);

                        // Link with head user
                        $user->parent_id = $head_user->id;
                        $user->save();

                        //Update User Parent
                        UserParent::where("user_id", $user->id)->update(["is_active" => 0]);
                        UserParent::create([
                            'user_id' => $user->id,
                            'parent_id' =>  $head_user->id,
                            'is_active' => 1,
                        ]);


                        $msg = "";
                        $successMessage = "";
                        if (count($userRoles) === 0) {
                            $msg = "تمت ترقيتك ل " . $arabicRole . " - المسؤول عنك:  " . $head_user->fullName;
                            $successMessage = "تمت ترقية العضو ل " . $arabicRole . " - المسؤول عنه:  " . $head_user->fullName;

                            //TODO: remove if statement
                            // if (!config('app.debug')) {
                            $user->notify(new MailUpgradeRole($arabicRole));
                            // }
                        } else {
                            $msg = count($userRoles) > 1
                                ?
                                "تم سحب الأدوار التالية منك: " . implode(',', $userRoles->all()) . ", أنت الآن " . $arabicRole
                                :
                                "تم سحب دور ال" . $userRoles[0] . " منك, أنت الآن " . $arabicRole;
                            $successMessage = count($userRoles) > 1
                                ?
                                "تم سحب الأدوار التالية من العضو: " . implode(',', $userRoles->all()) . ", إنه الآن " . $arabicRole
                                :
                                "تم سحب دور ال" . $userRoles[0] . " من العضو, إنه الآن " . $arabicRole;

                            // if (!config('app.debug')) {
                            $user->notify(new MailDowngradeRole($userRoles->all(), $arabicRole));
                            // }
                        }
                        // notify user
                        (new NotificationController)->sendNotification($user->id, $msg, ROLES);

                        $logInfo = ' قام ' . Auth::user()->fullName . " بترقية العضو " . $user->fullName . ' لـ ' . $arabicRole . " - المسؤول عنه:  " . $head_user->fullName;
                        Log::channel('community_edits')->info($logInfo);

                        return $this->jsonResponseWithoutMessage($successMessage, 'data', 200);
                    } else {
                        return $this->jsonResponseWithoutMessage("يجب أن تكون رتبة المسؤول أعلى من الرتبة المراد الترقية لها", 'data', 200);
                    }
                } else {
                    return $this->jsonResponseWithoutMessage("يجب أن تكون رتبة المسؤول اعلى من رتبة المستخدم", 'data', 200);
                }
            } else {
                return $this->jsonResponseWithoutMessage("المسؤول غير موجود", 'data', 200);
            }
        } else {
            return $this->jsonResponseWithoutMessage("المستخدم غير موجود", 'data', 200);
        }
    }

    function handleSupportLeaderRole(User $user, Role $role)
    {
        $arabicRole = SystemRole::translate($role->name);

        if (Auth::user()->hasAnyRole(SystemRole::basicHighRoles())) {

            if ($user->hasAnyRole(SystemRole::basicHighRoles())) {
                return $this->jsonResponseWithoutMessage("لا يمكنك ترقية المستخدم ل " . $arabicRole . " لأنه يملك صلاحيات قيادية", 'data', 200);
            }

            if ($user->hasRole($role->name)) {
                return $this->jsonResponseWithoutMessage("المستخدم موجود مسبقاً ك" . $arabicRole, 'data', 200);
            }

            $user->assignRole($role->name);

            $logInfo = ' قام ' . Auth::user()->fullName . " بترقية العضو " . $user->fullName . ' لـ ' . $arabicRole;
            Log::channel('community_edits')->info($logInfo);

            return $this->jsonResponseWithoutMessage("تمت ترقية العضو ل " . $arabicRole, 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage("ليس لديك صلاحية لترقية العضو ل " . $arabicRole, 'data', 200);
        }
    }
    /**
     * Change advising team for a supervisor
     *
     * @return jsonResponseWithoutMessage;
     */

    public function ChangeAdvisingTeam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supervisor' => 'required|email',
            'new_advisor' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //get supervisor info
        $supervisor = User::where('email', $request->supervisor)->first();
        if ($supervisor) {
            //check if supervisor has supervisor role
            if ($supervisor->hasRole('supervisor')) {
                // get new advisor info
                $new_advisor = User::where('email', $request->new_advisor)->first();
                //check if new_advisor has advisor role

                if ($new_advisor->hasRole('advisor')) {
                    DB::beginTransaction();
                    try {

                        // link supervisor with his new advisor
                        $supervisor->parent_id = $new_advisor->id;
                        $supervisor->save();

                        //Update User Parent
                        UserParent::where("user_id", $supervisor->id)->update(["is_active" => 0]);
                        UserParent::create([
                            'user_id' => $supervisor->id,
                            'parent_id' =>  $new_advisor->id,
                            'is_active' => 1,
                        ]);

                        /* make supervisor ambassador in new advisor advising group
                        * 1- get advising group
                        * 2- update group_id where supervisor is ambassador
                        */

                        // 1- get advising group
                        $advising_group = UserGroup::where('user_id', $new_advisor->id)->where('user_type', 'advisor')->whereNull('termination_reason')->whereHas('group.type', function ($q) {
                            $q->where('type', 'advising');
                        })->first();

                        if ($advising_group) {
                            // 2- update group_id where supervisor is ambassador
                            $user_group = UserGroup::updateOrCreate(
                                [
                                    'user_id' => $supervisor->id,
                                    'user_type' => 'ambassador',
                                ],
                                [
                                    'user_id' => $supervisor->id,
                                    'group_id' => $advising_group->group_id,
                                    'user_type' => 'ambassador',
                                ]
                            );
                            $userGroupCacheKey = 'user_group_' .  $supervisor->id;
                            Cache::forget($userGroupCacheKey);
                        } else {
                            return $this->jsonResponseWithoutMessage("الموجه ليس موجهاً في أي مجموعة", 'data', 200);
                        }

                        /* add new advisor to supervisor`s groups and remove the old advisor
                        * 1- get supervisor`s groups
                        * 2- update user_id where user_type is advisor
                        */

                        //1- get supervisor`s groups
                        $groupsID = UserGroup::where('user_id', $supervisor->id)->where('user_type', 'supervisor')->pluck('group_id');

                        // 2- update user_id where user_type is advisor
                        foreach ($groupsID as $key => $id) {
                            UserGroup::updateOrCreate(
                                [
                                    'group_id' => $id,
                                    'user_type' => 'advisor',
                                ],
                                [
                                    'user_id' => $new_advisor->id,
                                    'group_id' => $id,
                                    'user_type' => 'advisor',
                                ]
                            );
                        }

                        // add supervisor as ambassador to the new advising team
                        UserGroup::where('user_type', 'ambassador')->where('user_id', $supervisor->id)->whereNull('termination_reason')->update(['termination_reason'  => 'advisor_change']);
                        UserGroup::Create(
                            [
                                'user_id' =>  $supervisor->id,
                                'group_id' => $advising_group->group_id,
                                'user_type' => 'ambassador'
                            ]
                        );

                        DB::commit();

                        $logInfo = ' قام ' . Auth::user()->fullName . " بنقل " . $supervisor->fullName . ' لـ ' .  $new_advisor->fullName;
                        Log::channel('community_edits')->info($logInfo);

                        return $this->jsonResponseWithoutMessage("تم التبديل", 'data', 200);
                    } catch (\Exception $e) {
                        Log::channel('RolesAdministration')->info($e);
                        DB::rollBack();

                        return $e->getMessage();
                    }
                } else {
                    return $this->jsonResponseWithoutMessage("لا يمكنك نقل العضو لموجه أخر , يجب أن يكون الموجه موجهاً أولاً", 'data', 200);
                }
            } else {
                return $this->jsonResponseWithoutMessage("لا يمكنك نقل العضو لموجه أخر , يجب أن يكون مراقباً أولاً", 'data', 200);
            }
        } else {
            return $this->jsonResponseWithoutMessage("المراقب غير موجود", 'data', 200);
        }
    }

    /**
     * Swap Leaders Between 2 Supervisors
     * @param Request contains currentSupervisor email, newSupervisor email
     * @return jsonResponseWithoutMessage;
     */

    public function supervisorsSwap(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currentSupervisor' => 'required|email',
            'newSupervisor' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->hasanyrole('admin|advisor|consultant')) {
            //get supervisors info
            $currentSupervisor = User::where('email', $request->currentSupervisor)->first();
            $newSupervisor = User::where('email', $request->newSupervisor)->first();

            if ($currentSupervisor && $newSupervisor) {
                //check if supervisor has supervisor role
                if ($currentSupervisor->hasRole('supervisor') && $newSupervisor->hasRole('supervisor')) {

                    //supervising group and its leaders [leaders here are ambassadors] for  supervisor 1
                    try {
                        DB::beginTransaction();


                        ########## check Advisors ##########

                        //if 2 diffrent advisors
                        if ($currentSupervisor->parent_id  != $newSupervisor->parent_id) {
                            $supervisors_1_Parent = $currentSupervisor->parent_id;
                            $supervisors_1_Parent_advising_group = Group::whereHas('type', function ($q) {
                                $q->where('type', 'advising');
                            })
                                ->whereHas('users', function ($q) use ($supervisors_1_Parent) {
                                    $q->where('user_id', $supervisors_1_Parent);
                                })
                                ->first();

                            $supervisors_2_Parent = $newSupervisor->parent_id;
                            $supervisors_2_Parent_advising_group = Group::whereHas('type', function ($q) {
                                $q->where('type', 'advising');
                            })
                                ->whereHas('users', function ($q) use ($supervisors_2_Parent) {
                                    $q->where('user_id', $supervisors_2_Parent);
                                })
                                ->first();


                            //change parent id for each supervisor and add advisors to groups

                            $currentSupervisor->parent_id =  $supervisors_2_Parent;
                            $currentSupervisor->save();

                            //Update User Parent
                            UserParent::where("user_id", $currentSupervisor->id)->update(["is_active" => 0]);
                            UserParent::create([
                                'user_id' => $currentSupervisor->id,
                                'parent_id' =>  $supervisors_2_Parent,
                                'is_active' => 1,
                            ]);

                            $currentSupervisorGroups = UserGroup::where('user_type', 'supervisor')->where('user_id', $currentSupervisor->id)->pluck('group_id');


                            //add advisor to groups
                            UserGroup::where('user_type', 'advisor')->whereIn('group_id', $currentSupervisorGroups)->update(['user_id'  => $supervisors_2_Parent]);
                            //add supervisor as ambassador to advising group
                            UserGroup::where('user_type', 'ambassador')->where('user_id', $currentSupervisor->id)->update(['group_id'  => $supervisors_2_Parent_advising_group->id]);

                            $newSupervisor->parent_id =  $supervisors_1_Parent;
                            $newSupervisor->save();

                            //Update User Parent
                            UserParent::where("user_id", $newSupervisor->id)->update(["is_active" => 0]);
                            UserParent::create([
                                'user_id' => $currentSupervisor->id,
                                'parent_id' =>  $supervisors_1_Parent,
                                'is_active' => 1,
                            ]);

                            $newSupervisorGroups = UserGroup::where('user_type', 'supervisor')->where('user_id', $newSupervisor->id)->pluck('group_id');
                            UserGroup::where('user_type', 'advisor')->whereIn('group_id', $newSupervisorGroups)->update(['user_id'  => $supervisors_1_Parent]);
                            UserGroup::where('user_type', 'ambassador')->where('user_id', $newSupervisor->id)->update(['group_id'  => $supervisors_1_Parent_advising_group->id]);
                        }

                        $supervising1Group = Group::whereHas('type', function ($q) {
                            $q->where('type', 'supervising');
                        })
                            ->whereHas('users', function ($q) use ($currentSupervisor) {
                                $q->where('user_id', $currentSupervisor->id);
                            })
                            ->with('userAmbassador')
                            ->first();

                        $supervising2Group = Group::whereHas('type', function ($q) {
                            $q->where('type', 'supervising');
                        })
                            ->whereHas('users', function ($q) use ($newSupervisor) {
                                $q->where('user_id', $newSupervisor->id);
                            })
                            ->with('userAmbassador')
                            ->first();

                        if ($supervising1Group) {
                            foreach ($supervising1Group->userAmbassador as $leader) {
                                // for each leader in group 1
                                //update supervisor
                                $leader->update(['parent_id'  => $newSupervisor->id]);
                                //Update User Parent
                                UserParent::where("user_id", $leader->id)->update(["is_active" => 0]);
                                UserParent::create([
                                    'user_id' => $leader->id,
                                    'parent_id' =>  $newSupervisor->id,
                                    'is_active' => 1,
                                ]);
                                $follow_up_group_id = UserGroup::where('user_type', 'leader')->where('user_id', $leader->id)->pluck('group_id')->first();
                                UserGroup::where('user_type', 'supervisor')->where('group_id', $follow_up_group_id)->update(['user_id'  => $newSupervisor->id]);
                            }
                        } else {
                            return $this->jsonResponseWithoutMessage("المراقب الأول ليس مراقباً في مجموعة الرقابة ", 'data', 200);
                        }

                        if ($supervising2Group) {
                            foreach ($supervising2Group->userAmbassador as $leader) {
                                // for each leader in group 1
                                //update supervisor
                                $leader->update(['parent_id'  => $currentSupervisor->id]);
                                //Update User Parent
                                UserParent::where("user_id", $leader->id)->update(["is_active" => 0]);
                                UserParent::create([
                                    'user_id' => $leader->id,
                                    'parent_id' =>  $currentSupervisor->id,
                                    'is_active' => 1,
                                ]);

                                //update supervisor in following up leader group
                                $follow_up_group_id = UserGroup::where('user_type', 'leader')->where('user_id', $leader->id)->pluck('group_id')->first();
                                UserGroup::where('user_type', 'supervisor')->where('group_id', $follow_up_group_id)->update(['user_id'  => $currentSupervisor->id]);
                            }
                        } else {
                            return $this->jsonResponseWithoutMessage("المراقب الثاني ليس مراقباً في مجموعة الرقابة ", 'data', 200);
                        }


                        ########## Change Supervising Team ##########

                        //terminate current recored then create one
                        UserGroup::where('user_type', 'supervisor')->where('user_id', $currentSupervisor->id)->where('group_id', $supervising1Group->id)->whereNull('termination_reason')->update(['termination_reason'  => 'supervisor_change']);
                        UserGroup::Create(
                            [
                                'user_id' =>  $currentSupervisor->id,
                                'group_id' => $supervising2Group->id,
                                'user_type' => 'supervisor'
                            ]
                        );
                        UserGroup::where('user_type', 'supervisor')->where('user_id', $newSupervisor->id)->where('group_id', $supervising2Group->id)->whereNull('termination_reason')->update(['termination_reason'  => 'supervisor_change']);
                        UserGroup::Create(
                            [
                                'user_id' =>  $newSupervisor->id,
                                'group_id' => $supervising1Group->id,
                                'user_type' => 'supervisor'
                            ]
                        );

                        DB::commit();

                        $logInfo = ' قام ' . Auth::user()->fullName . " بالتبديل " . $currentSupervisor->fullName . ' و ' .  $newSupervisor->fullName;
                        Log::channel('community_edits')->info($logInfo);

                        return $this->jsonResponseWithoutMessage("تم التبديل", 'data', 200);
                    } catch (\Exception $exception) {
                        Log::channel('RolesAdministration')->info($exception);
                        DB::rollBack();
                    }
                } else {
                    return $this->jsonResponseWithoutMessage("يجب أن يكون العضو مراقب أولاً", 'data', 200);
                }
            } else {
                return $this->jsonResponseWithoutMessage("المراقب غير موجود", 'data', 200);
            }
        } else {
            throw new NotAuthorized;
        }
    }



    /**
     * set new supervisor and let the current Ambassador only
     * @param Request contains currentSupervisor email, newSupervisor email
     * @return jsonResponseWithoutMessage;
     */

    public function newSupervisor_currentToAmbassador(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currentSupervisor' => 'required|email',
            'newSupervisor' => 'required|email',
            'newLeader'  => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }


        if (Auth::user()->hasanyrole('admin|advisor|consultant')) {

            $currentSupervisor = User::where('email', $request->currentSupervisor)->first();
            if ($currentSupervisor) {
                //get new supervisor info
                $newSupervisor = User::where('email', $request->newSupervisor)->first();
                if ($newSupervisor) {
                    //check if new supervisor has supervisor role
                    if ($newSupervisor->hasRole('supervisor')) {
                        //get new leader info
                        $newLeader = User::where('email', $request->newLeader)->first();

                        if ($newLeader) {
                            //check if new leader has leader role
                            if ($newLeader->hasRole('leader')) {

                                //get current supervisor followup group [where he is a leader]
                                $currentSupervisor_leadingGroup = UserGroup::where('user_id', $currentSupervisor->id)
                                    ->where('user_type', 'leader')
                                    ->whereNull('termination_reason')
                                    ->first();
                                if ($currentSupervisor_leadingGroup) {

                                    //get current supervisor followup group [where he is an ambassador [ADVISING GROUP] ]
                                    $currentSupervisor_followupGroup = UserGroup::where('user_id', $currentSupervisor->id)
                                        ->where('user_type', 'ambassador')
                                        ->whereNull('termination_reason')
                                        ->first();
                                    DB::beginTransaction();
                                    try {

                                        //* اضافة المراقب الجديد إلى مجموعة التوجيه كـ سفير

                                        UserGroup::updateOrCreate(
                                            [
                                                'user_id' => $newSupervisor->id,
                                                'user_type' => "ambassador"
                                            ],
                                            [
                                                'group_id' => $currentSupervisor_followupGroup->group_id
                                            ]
                                        );
                                        $userGroupCacheKey = 'user_group_' .  $newSupervisor->id;
                                        Cache::forget($userGroupCacheKey);

                                        //* نقل قادة المراقب الحالي إلى المراقب الجديد
                                        $supervisorLeaders = User::where("parent_id", $currentSupervisor->id)->whereHas('roles', function ($q) {
                                            $q->where('name', '=', 'leader');
                                        })->get();
                                        foreach ($supervisorLeaders as $leader) {
                                            $leader->parent_id = $newSupervisor->id;
                                            $leader->save();
                                            //Update User Parent
                                            UserParent::where("user_id", $leader->id)->update(["is_active" => 0]);
                                            UserParent::create([
                                                'user_id' => $leader->id,
                                                'parent_id' =>  $newSupervisor->id,
                                                'is_active' => 1,
                                            ]);
                                        }

                                        //* نقل سفراء المراقب الحالي إلى القائد الجديد
                                        $supervisorAmbassadors = User::where("parent_id", $currentSupervisor->id)->whereHas('roles', function ($q) {
                                            $q->whereNotIn('name', ['leader', 'supervisor', 'admin', 'advisor', 'consultant']);
                                        })->get();
                                        foreach ($supervisorAmbassadors as $ambassador) {
                                            $ambassador->parent_id = $newLeader->id;
                                            $ambassador->save();
                                            //Update User Parent
                                            UserParent::where("user_id", $ambassador->id)->update(["is_active" => 0]);
                                            UserParent::create([
                                                'user_id' => $ambassador->id,
                                                'parent_id' =>  $newLeader->id,
                                                'is_active' => 1,
                                            ]);
                                        }

                                        //* المسؤول عن المراقب الحالي يصبح القائد الجديد
                                        $currentSupervisor->parent_id = $newLeader->id;
                                        $currentSupervisor->save();

                                        //Update User Parent
                                        UserParent::where("user_id", $currentSupervisor->id)->update(["is_active" => 0]);
                                        UserParent::create([
                                            'user_id' => $currentSupervisor->id,
                                            'parent_id' =>  $newLeader->id,
                                            'is_active' => 1,
                                        ]);


                                        //* سحب رتبة الرقابة والقيادة من المراقب الحالي
                                        $currentSupervisor->removeRole("supervisor");
                                        $currentSupervisor->removeRole("leader");

                                        //* اضافة القائد الجديد إلى مجموعة المتابعة كـ قائد
                                        UserGroup::where('user_type', 'leader')->where('user_id',  $newLeader->id)->whereNull('termination_reason')->update(['termination_reason'  => 'supervisor_change']);
                                        UserGroup::create(
                                            [
                                                'user_id' => $newLeader->id,
                                                'user_type' => "leader",
                                                'group_id' => $currentSupervisor_leadingGroup->group_id
                                            ]
                                        );

                                        //* اضافة القائد الجديد إلى مجموعة الرقابة كـ سفير

                                        //get current supervisor Supervising group [where he is a supervisor]


                                        $currentSupervisor_supervisingGroup = UserGroup::whereHas('group.type', function ($q) {
                                            $q->where('type', '=', 'supervising');
                                        })
                                            ->where('user_groups.user_id', $currentSupervisor->id)
                                            ->where('user_groups.user_type', 'supervisor')
                                            ->whereNull('user_groups.termination_reason')
                                            ->first();

                                        UserGroup::where('user_type', 'ambassador')->where('user_id',  $newLeader->id)->whereNull('termination_reason')->update(['termination_reason'  => 'supervisor_change']);
                                        UserGroup::create(
                                            [
                                                'user_id' => $newLeader->id,
                                                'user_type' => "ambassador",
                                                'group_id' => $currentSupervisor_supervisingGroup->group_id
                                            ]
                                        );

                                        //* اضافة المراقب الجديد إلى مجموعة الرقابة كـ مراقب
                                        //* اضافة المراقب الجديد إلى مجموعات القادة كـ مراقب [بالاضافة إلى مجموعة المتابعة الخاصة بالمراقب الحالي]
                                        UserGroup::where('user_id', $currentSupervisor->id)
                                            ->where('user_type', "supervisor")
                                            ->update(['user_id'  => $newSupervisor->id]);

                                        DB::commit();
                                        $logInfo = ' قام ' . Auth::user()->fullName . " بالتبديل " . $currentSupervisor->fullName . ' و ' .  $newSupervisor->fullName . ' وأصبح ' . $currentSupervisor->name . ' سفيرا ';
                                        Log::channel('community_edits')->info($logInfo);

                                        return $this->jsonResponseWithoutMessage("تم التبديل", 'data', 200);
                                    } catch (\Exception $e) {
                                        Log::channel('RolesAdministration')->info($e);
                                        DB::rollBack();

                                        return $e->getMessage();
                                    }
                                } else {
                                    return $this->jsonResponseWithoutMessage("يجب أن يكون المراقب الحالي قائدًا لفريق متابعة", 'data', 200);
                                }
                            } else {
                                return $this->jsonResponseWithoutMessage("يجب أن يكون القائد الجديد قائدًا أولًا", 'data', 200);
                            }
                        } else {
                            return $this->jsonResponseWithoutMessage("القائد الجديد غير موجود", 'data', 200);
                        }
                    } else {
                        return $this->jsonResponseWithoutMessage("يجب أن يكون المراقب الجديد مراقباً أولاً", 'data', 200);
                    }
                } else {
                    return $this->jsonResponseWithoutMessage("المراقب الجديد غير موجود", 'data', 200);
                }
            } else {
                return $this->jsonResponseWithoutMessage("المراقب الحالي غير موجود", 'data', 200);
            }
        } else {
            throw new NotAuthorized;
        }
    }


    /**
     * set new supervisor and let the current Leader only
     * @param Request contains currentSupervisor email, newSupervisor email
     * @return jsonResponseWithoutMessage;
     */

    public function newSupervisor_currentToLeader(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currentSupervisor' => 'required|email',
            'newSupervisor' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->hasanyrole('admin|advisor|consultant')) {

            $currentSupervisor = User::where('email', $request->currentSupervisor)->first();
            if ($currentSupervisor) {
                //get new supervisor info
                $newSupervisor = User::where('email', $request->newSupervisor)->first();
                if ($newSupervisor) {

                    $supervisingGroupTypeID = GroupType::where('type', 'supervising')->pluck('id')->first();

                    //check if new supervisor has supervisor role
                    if ($newSupervisor->hasRole('supervisor')) {

                        //get new supervisor followup group [where he is a leader]
                        $newSupervisor_leadingGroup = UserGroup::where('user_id', $newSupervisor->id)
                            ->where('user_type', 'leader')
                            ->whereNull('termination_reason')
                            ->first();
                        if ($newSupervisor_leadingGroup) {

                            //get current supervisor followup group [where he is an ambassador [ADVISING GROUP] ]
                            $currentSupervisor_followupGroup = UserGroup::where('user_id', $currentSupervisor->id)
                                ->where('user_type', 'ambassador')
                                ->whereNull('termination_reason')
                                ->first();
                            DB::beginTransaction();
                            try {

                                //* اضافة المراقب الجديد إلى مجموعة التوجيه كـ سفير
                                UserGroup::where('user_type', 'ambassador')->where('user_id',  $newSupervisor->id)->whereNull('termination_reason')->update(['termination_reason'  => 'supervisor_upgrade']);
                                UserGroup::create(
                                    [
                                        'user_id' => $newSupervisor->id,
                                        'user_type' => "ambassador",
                                        'group_id' => $currentSupervisor_followupGroup->group_id
                                    ]
                                );


                                //* نقل قادة المراقب الحالي إلى المراقب الجديد
                                $supervisorLeaders = User::where("parent_id", $currentSupervisor->id)->whereHas('roles', function ($q) {
                                    $q->where('name', '=', 'leader');
                                })->get();

                                foreach ($supervisorLeaders as $leader) {
                                    $leader->parent_id = $newSupervisor->id;
                                    $leader->save();

                                    //Update User Parent
                                    UserParent::where("user_id", $leader->id)->update(["is_active" => 0]);
                                    UserParent::create([
                                        'user_id' => $leader->id,
                                        'parent_id' =>  $newSupervisor->id,
                                        'is_active' => 1,
                                    ]);
                                }

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

                                //* سحب رتبة الرقابة من المراقب الحالي
                                $currentSupervisor->removeRole("supervisor");

                                //* اضافة المراقب الجديد إلى مجموعة الرقابة كـ مراقب
                                //* اضافة المراقب الجديد إلى مجموعات القادة كـ مراقب [بالاضافة إلى مجموعة المتابعة الخاصة بالمراقب الحالي]
                                UserGroup::where('user_id', $currentSupervisor->id)
                                    ->where('user_type', "supervisor")
                                    ->update(['user_id'  => $newSupervisor->id]);


                                //* اضافة المراقب الحالي كـ سفير في مجموعة الرقابة الخاصة بالمراقب الجديد
                                $newSupervisor_SupervisingGroupID = UserGroup::where('user_id', $newSupervisor->id)
                                    ->whereHas('group', function ($q)  use ($supervisingGroupTypeID) {
                                        $q->where('type_id', '=', $supervisingGroupTypeID);
                                    })->where('user_type', 'supervisor')->pluck('group_id')->first();

                                UserGroup::where('user_type', 'ambassador')->where('user_id',  $currentSupervisor->id)->whereNull('termination_reason')->update(['termination_reason'  => 'advisor_change']);
                                UserGroup::create(
                                    [
                                        'user_id' => $currentSupervisor->id,
                                        'user_type' => "ambassador",
                                        'group_id' => $newSupervisor_SupervisingGroupID
                                    ]
                                );


                                DB::commit();
                                $logInfo = ' قام ' . Auth::user()->fullName . " بالتبديل " . $currentSupervisor->fullName . ' و ' .  $newSupervisor->fullName . ' وأصبح ' . $currentSupervisor->name . ' قائدا ';
                                Log::channel('community_edits')->info($logInfo);

                                return $this->jsonResponseWithoutMessage("تم التبديل", 'data', 200);
                            } catch (\Exception $e) {
                                Log::channel('RolesAdministration')->info($e);
                                DB::rollBack();

                                return $e->getMessage();
                            }
                        } else {
                            return $this->jsonResponseWithoutMessage("يجب أن يكون المراقب الجديد قائدًا لفريق متابعة", 'data', 200);
                        }
                    } else {
                        return $this->jsonResponseWithoutMessage("يجب أن يكون المراقب الجديد مراقباً أولاً", 'data', 200);
                    }
                } else {
                    return $this->jsonResponseWithoutMessage("المراقب الجديد غير موجود", 'data', 200);
                }
            } else {
                return $this->jsonResponseWithoutMessage("المراقب الحالي غير موجود", 'data', 200);
            }
        } else {
            throw new NotAuthorized;
        }
    }



    /**
     *  set new Leader and let the current ambassador only
     * @param Request contains newLeader email, currentLeader email
     * @return jsonResponseWithoutMessage;
     */

    public function newLeader_currentToAmbassador(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'newLeader' => 'required|email',
            'currentLeader' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->hasanyrole('admin|advisor|consultant')) {
            $currentLeader = User::where('email', $request->currentLeader)->first();

            if ($currentLeader) {
                //get new leader info
                $newLeader = User::where('email', $request->newLeader)->first();

                if ($newLeader) {
                    //check if new leader has leader role
                    if ($newLeader->hasRole('leader')) {

                        //get leader group id
                        $leaderGroupId = UserGroup::where('user_id', $currentLeader->id)
                            ->where('user_type', 'leader')
                            ->pluck('group_id')->first();

                        if ($leaderGroupId) {
                            // نقل سفراء القائد الحالي إلى القائد الجديد
                            DB::beginTransaction();
                            try {

                                //get ambassadors of currentLeader
                                $ambassadorsID = UserGroup::where('group_id', $leaderGroupId)
                                    ->where('user_type', 'ambassador')
                                    ->pluck('user_id');

                                //update parent id for ambassadors
                                $ambassadors = User::whereIn('id', $ambassadorsID)->get();
                                foreach ($ambassadors as $ambassador) {
                                    $ambassador->parent_id = $newLeader->id;
                                    $ambassador->save();
                                    //Update User Parent
                                    UserParent::where("user_id", $ambassador->id)->update(["is_active" => 0]);
                                    UserParent::create([
                                        'user_id' => $ambassador->id,
                                        'parent_id' =>  $newLeader->id,
                                        'is_active' => 1,
                                    ]);
                                }

                                //  إضافة القائد الجديد لغروب الرقابة كسفير
                                $supervisingGroupId = UserGroup::where('user_id', $currentLeader->id)
                                    ->where('user_type', 'ambassador')
                                    ->pluck('group_id')->first();

                                UserGroup::where('user_type', 'ambassador')->where('user_id',  $newLeader->id)->whereNull('termination_reason')->update(['termination_reason'  => 'leader_upgrade']);
                                UserGroup::create(
                                    [
                                        'user_id' => $newLeader->id,
                                        'user_type' => "ambassador",
                                        'group_id' => $supervisingGroupId
                                    ]
                                );

                                // المسؤول عن القائد الجديد هو المراقب
                                $supervisorID = User::where('id', $currentLeader->id)->pluck('parent_id')->first();
                                User::where('id', $newLeader->id)->update(['parent_id'  => $supervisorID]);

                                //Update User Parent
                                UserParent::where("user_id", $newLeader->id)->update(["is_active" => 0]);
                                UserParent::create([
                                    'user_id' => $newLeader->id,
                                    'parent_id' =>  $supervisorID,
                                    'is_active' => 1,
                                ]);

                                // اضافة القائد الجديد إلى مجموعة المتابعة كقائد
                                UserGroup::where('user_type', 'leader')->where('group_id', $leaderGroupId)->update(['user_id'  => $newLeader->id]);

                                //سحب رتبة القيادة من القائد الحالي
                                $currentLeader->removeRole('leader');

                                //المسؤول عن القائد الحالي هو القائد الجديد
                                User::where('id', $currentLeader->id)->update(['parent_id'  => $newLeader->id]);
                                //Update User Parent
                                UserParent::where("user_id", $currentLeader->id)->update(["is_active" => 0]);
                                UserParent::create([
                                    'user_id' => $currentLeader->id,
                                    'parent_id' =>  $newLeader->id,
                                    'is_active' => 1,
                                ]);

                                // اضافة القائد الحالي إلى مجموعة المتابعة الخاصة به كـ سفير
                                UserGroup::where('user_type', 'ambassador')->where('user_id', $currentLeader->id)->update(['group_id'  => $leaderGroupId]);

                                DB::commit();
                                $logInfo = ' قام ' . Auth::user()->fullName . " بالتبديل " . $currentLeader->fullName . ' و ' .  $newLeader->fullName . ' وأصبح ' . $currentLeader->name . ' سفيرا ';
                                Log::channel('community_edits')->info($logInfo);

                                return $this->jsonResponseWithoutMessage(" تم تبديل القائد وجعل القائد القديم سفير في نفس المجموعة", 'data', 200);
                            } catch (\Exception $e) {
                                Log::channel('RolesAdministration')->info($e);
                                DB::rollBack();

                                return $e->getMessage();
                            }
                        } else {
                            return $this->jsonResponseWithoutMessage("يجب أن يكون القائد الحالي قائد لفريق متابعة", 'data', 200);
                        }
                    } else {
                        return $this->jsonResponseWithoutMessage("يجب أن يكون القائد الجديد  له دور قائداَ أولاً", 'data', 200);
                    }
                } else {
                    return $this->jsonResponseWithoutMessage("القائد  الجديد غير موجود", 'data', 200);
                }
            } else {
                return $this->jsonResponseWithoutMessage("القائد  الحالي غير موجود", 'data', 200);
            }
        } else {
            throw new NotAuthorized;
        }
    }
    /**
     * transfer ambassador to new leader
     * @param Request contains ambassador_email, leader_email
     * @return jsonResponseWithoutMessage;
     */

    public function transferAmbassador(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ambassadors' => 'required|array',
            'ambassadors.*.ambassador_email' => 'required|email',
            'leader_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->hasanyrole('admin|advisor|consultant')) {
            //get new leader info
            $newLeader = User::where('email', $request->leader_email)->with('roles')->first();

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
                                UserParent::create([
                                    'user_id' => $ambassador->id,
                                    'parent_id' =>  $newLeader->id,
                                    'is_active' => 1,
                                ]);
                                //move ambassador to new followup group
                                UserGroup::where('user_type', 'ambassador')->where('user_id',  $ambassador->id)->whereNull('termination_reason')->update(['termination_reason'  => 'transfer_ambassador']);
                                UserGroup::create(
                                    [
                                        'user_id' => $ambassador->id,
                                        'user_type' => "ambassador",
                                        'group_id' => $newLeaderGroupId,
                                        'termination_reason' => Null,
                                    ]
                                );

                                $logInfo = ' قام ' . Auth::user()->fullName . " بنقل  " . $ambassador->fullName . ' إلى القائد ' .  $newLeader->fullName;
                                Log::channel('community_edits')->info($logInfo);
                                $response['message'] = 'تم النقل';
                            } else {
                                array_push($response['not_exists'], $ambassadorToMove);
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

    /**
     * move a Leader to newSupervisor
     * @param Request contains leader email [current], newSupervisor email [new]
     * @return jsonResponseWithoutMessage;
     */

    public function transferLeader(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leaders' => 'required',
            'leaders.*.leader_email' => 'required|email',
            'newSupervisor' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->hasanyrole('admin|advisor|consultant')) {
            $response = [];
            $response['need_promotion'] = [];
            $response['not_exists'] = [];

            //get new supervisor info
            $newSupervisor = User::where('email', $request->newSupervisor)->first();
            if ($newSupervisor) {
                //check if new supervisor has supervisor role
                if ($newSupervisor->hasRole('supervisor')) {

                    //get supervising group
                    $newSupervisingGroupId = Group::whereHas('type', function ($q) {
                        $q->where('type', 'supervising');
                    })
                        ->whereHas('users', function ($q) use ($newSupervisor) {
                            $q->where('user_id', $newSupervisor->id);
                        })
                        ->pluck('id')
                        ->first();

                    if ($newSupervisingGroupId) {
                        ##### START WORKING WITH LEADERS #####
                        foreach ($request->leaders as $index => $leaderToMove) {
                            $leader = User::where('email', $leaderToMove['leader_email'])->first();
                            if ($leader) {
                                //check if new leader has leader role
                                if ($leader->hasRole('leader')) {

                                    $currentAdvisorId = User::where('id', $leader->parent_id)->pluck('parent_id')->first();

                                    // جعل القائد سفير في غروب الرقابة الجديد
                                    UserGroup::where('user_type', 'ambassador')->where('user_id',  $leader->id)->whereNull('termination_reason')->update(['termination_reason'  => 'transfer_leader']);
                                    UserGroup::create(
                                        [
                                            'user_id' => $leader->id,
                                            'user_type' => "ambassador",
                                            'group_id' => $newSupervisingGroupId
                                        ]
                                    );

                                    //إضافة المراقب الجديد لفريق المتابعة الخاص بالقائد
                                    // find leader group id
                                    $leaderGroupId = UserGroup::where('user_type', 'leader')->where('user_id', $leader->id)->pluck('group_id')->first();
                                    //add new supervisor to leader group
                                    UserGroup::where('user_type', 'supervisor')->where('group_id', $leaderGroupId)->update(['user_id'  => $newSupervisor->id]);

                                    //جعل المراقب الجديد مسؤل عن القائد
                                    $leader->update(['parent_id'  => $newSupervisor->id]);
                                    //Update User Parent
                                    UserParent::where("user_id", $leader->id)->update(["is_active" => 0]);
                                    UserParent::create([
                                        'user_id' => $leader->id,
                                        'parent_id' =>  $newSupervisor->id,
                                        'is_active' => 1,
                                    ]);

                                    // تغيير الموجه المسؤول عن القائد إذا كان المراقب الجديد في فريق توجيه آخر
                                    if ($newSupervisor->parent_id != $currentAdvisorId) {
                                        UserGroup::where('user_type', 'advisor')->where('group_id', $leaderGroupId)->update(['user_id'  => $newSupervisor->parent_id]);
                                    }
                                    $logInfo = ' قام ' . Auth::user()->fullName . " بنقل القائد " . $leader->fullName . ' إلى المراقب ' .  $newSupervisor->fullName;
                                    Log::channel('community_edits')->info($logInfo);

                                    $response['message'] = 'تم النقل';
                                } else {
                                    array_push($response['need_promotion'], $leaderToMove['leader_email']);
                                }
                            } else {
                                array_push($response['not_exists'], $leaderToMove['leader_email']);
                            }
                        }
                        return $this->jsonResponseWithoutMessage($response, 'data', 200);
                    } else {
                        $response['message'] = 'الفريق الرقابي الجديد غير موجود ';

                        return $this->jsonResponseWithoutMessage($response, 'data', 200);
                    }
                } else {
                    $response['message'] = 'يجب أن يكون المراقب الجديد مراقباً أولاً';

                    return $this->jsonResponseWithoutMessage($response, 'data', 200);
                }
            } else {
                $response['message'] = 'المراقب الجديد غير موجود';

                return $this->jsonResponseWithoutMessage($response, 'data', 200);
            }
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * Remove role from user by its supervisor
     * Only for secondary roles separated from
     * main roles like (consultant, advisor,
     * supervisor, leader, ambassador)
     *
     * @param Request contains email, role_id
     * @return JsonResponse
     */
    function removeSecondaryRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'role_id' => 'required|exists:roles,id'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors()->first(), 'data', Response::HTTP_BAD_REQUEST);
        }

        $userRoleName = Role::where('id', $request->role_id)->pluck('name')->first();

        $user = User::where('email', $request->email)
            ->first();

        if (!$user) {
            return $this->jsonResponseWithoutMessage(
                "المستخدم غير موجود",
                'data',
                Response::HTTP_NOT_FOUND
            );
        }

        //check if the user has the role
        if (!$user->hasRole($userRoleName)) {
            return $this->jsonResponseWithoutMessage(
                "المستخدم ليس لديه هذا الدور",
                'data',
                Response::HTTP_NOT_FOUND
            );
        }

        $mainRoles = ['admin', 'consultant', 'advisor', 'supervisor', 'leader', 'ambassador'];

        //check if role contains `coordinator` string or role is advisor
        $authUser = Auth::user();
        $isSuperRole = $authUser->hasRole(['advisor', 'admin', 'book_quality_team_coordinator', 'special_care_coordinator', 'ramadan_coordinator', 'marathon_coordinator', 'eligible_admin']);
        $secondaryRoles = $authUser->getRoleNames()->filter(function ($item) use ($mainRoles) {
            return  !in_array($item, $mainRoles);
        });


        $isAuthorized = $isSuperRole || $secondaryRoles->count() > 0;


        //check if the coordinator is authorized to remove the role
        if (!$isSuperRole && $isAuthorized) {
            //check if one of the roles exists in the ROLES_HIERARCHY
            $isAuthorized = $secondaryRoles->contains(function ($value, $key) use ($userRoleName) {
                if (!array_key_exists($value, config('constants.ROLES_HIERARCHY'))) {
                    return false;
                }
                return in_array($userRoleName, config('constants.ROLES_HIERARCHY')[$value]);
            });
        }

        if (!$isAuthorized) {
            return $this->jsonResponseWithoutMessage(
                "غير مصرح لك بالقيام بهذه العملية",
                'data',
                Response::HTTP_UNAUTHORIZED
            );
        }

        //remove the role
        $user->removeRole($userRoleName);
        return $this->jsonResponseWithoutMessage(
            "تم إزالة الدور '" . config('constants.ARABIC_ROLES')[$userRoleName] . "' من '" . $user->name . "' بنجاح",
            'data',
            Response::HTTP_OK
        );
    }

    /**
     * Get secondary roles that are under the responsibility of the user
     * @return JsonResponse
     */
    public function getSecondaryRolesByRole()
    {
        $authUser = Auth::user();
        $isSuperRole = $authUser->hasRole(['advisor', 'admin']);
        $mainRoles = ['admin', 'consultant', 'advisor', 'supervisor', 'leader', 'ambassador'];
        $secondaryRoles = $authUser->getRoleNames()->filter(function ($item) use ($mainRoles) {
            return  !in_array($item, $mainRoles);
        });

        $rolesToRetrieve = [];
        //if super role, return all roles
        if ($isSuperRole) {
            foreach (config('constants.ROLES_HIERARCHY') as $key => $value) {
                $rolesToRetrieve = array_merge($rolesToRetrieve, [$key], $value);
            }
        } else if ($secondaryRoles->count() > 0) {
            //get roles responsible for
            $authorizedRoles = array_intersect($secondaryRoles->toArray(), array_keys(config('constants.ROLES_HIERARCHY')));
            foreach ($authorizedRoles as $role) {
                if (array_key_exists($role, config('constants.ROLES_HIERARCHY'))) {
                    $rolesToRetrieve = array_merge($rolesToRetrieve, config('constants.ROLES_HIERARCHY')[$role]);
                }
            }
        } else {
            return $this->jsonResponseWithoutMessage(
                "غير مصرح لك بالقيام بهذه العملية",
                'data',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $roles = Role::whereIn(
            'name',
            $rolesToRetrieve
        )
            ->orderBy('id', 'desc')
            ->get();

        return $this->jsonResponseWithoutMessage($roles, 'data', Response::HTTP_OK);
    }

    public function switchAdministrators(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_administrator_email' => 'required|email',
            'role' => 'required',
            'group_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors()->first(), 'data', Response::HTTP_BAD_REQUEST);
        }
        if (!Auth::user()->hasAnyRole(['admin', 'consultant'])) {
            throw new NotAuthorized;
        }

        $user = User::where('email', $request->new_administrator_email)
            ->first();

        if (!$user) {
            return $this->jsonResponseWithoutMessage(
                "المستخدم غير موجود",
                'data',
                Response::HTTP_NOT_FOUND
            );
        }

        //check if the user has the role
        if (!$user->hasRole($request->role)) {
            return $this->jsonResponseWithoutMessage(
                "المستخدم ليس لديه هذا الدور",
                'data',
                Response::HTTP_NOT_FOUND
            );
        }

        $group = Group::find($request->group_id);
        $arabicRole = config('constants.ARABIC_ROLES')[$request->role];
        UserGroup::where('user_type', $request->role)->where('group_id', $request->group_id)->whereNull('termination_reason')->update(['termination_reason'  => 'switch_administrators']);
        UserGroup::Create(
            [
                'user_id' =>  $user->id,
                'group_id' => $request->group_id,
                'user_type' => $request->role
            ]
        );
        $logInfo = ' قام ' . Auth::user()->fullName . " بتبديل  " . $arabicRole . ' في فريق ' .  $group->name . ' وأصبح ' . $user->fullName;
        Log::channel('community_edits')->info($logInfo);

        $msg = "تمت تعيينك برتبة " . $arabicRole . " لفريق:  " . $group->name;
        (new NotificationController)->sendNotification($user->id, $msg, ROLES);

        return $this->jsonResponseWithoutMessage(
            "تم اضافة '" . $user->fullName .  "  بدور  " . $arabicRole . "' بنجاح",
            'data',
            Response::HTTP_OK
        );
    }
}
