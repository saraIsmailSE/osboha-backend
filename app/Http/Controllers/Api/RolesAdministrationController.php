<?php

namespace App\Http\Controllers\Api;

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
use App\Models\Group;
use App\Models\GroupType;
use App\Models\User;
use App\Models\UserGroup;
use App\Notifications\MailDowngradeRole;
use App\Notifications\MailUpgradeRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RolesAdministrationController extends Controller
{
    use ResponseJson;


    /**
     * assign role and assign head user to a user .
     * 
     * @return jsonResponseWithoutMessage;
     */

    public function assignRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user' => 'required|email',
            'head_user' => 'required|email',
            'role_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //check role exists
        $role = Role::find($request->role_id);
        if (!$role) {
            return $this->jsonResponseWithoutMessage("هذه الرتبة غير موجودة", 'data', 200);
        }

        //check user exists
        $user = User::where('email', $request->user)->first();
        if ($user) {
            //check head_user exists
            $head_user = User::where('email', $request->head_user)->first();
            if ($head_user) {
                $head_user_last_role = $head_user->roles->first();
                //check if head user role is greater that user role
                if ($head_user_last_role->id < $role->id) {
                    $user_current_role = $user->roles->first();
                    $arabicRole = config('constants.ARABIC_ROLES')[$role->name];
                    $userRoles = null;

                    //check if supervisor is a leader first
                    if (($user_current_role->name === 'ambassador' && $role->name === 'supervisor') ||
                        ($user_current_role->name === 'advisor' && $role->name === 'supervisor') ||
                        ($user_current_role->name === 'consultant' && $role->name === 'supervisor')
                    ) {
                        return $this->jsonResponseWithoutMessage("لا يمكنك ترقية العضو لمراقب مباشرة, يجب أن يكون قائد أولاً", 'data', 200);
                    }

                    //check if user has the role
                    if ($user->hasRole($role->name) && $user_current_role->id >= $role->id) {
                        return $this->jsonResponseWithoutMessage("المستخدم موجود مسبقاً ك" . $arabicRole, 'data', 200);
                    }

                    //if last role less than the new role => assign ew role
                    if ($user_current_role->id > $role->id) {

                        //remove last role if not ambassador or leader and new role is supervisor                                    
                        if ($user_current_role->name !== 'ambassador' && !($user_current_role->name === 'leader' && $role->name === 'supervisor')) {
                            $user->removeRole($user_current_role->name);
                        }

                        //else remove other roles
                    } else {
                        //remove all roles except the ambassador                        
                        $userRoles = $user->roles()->where('name', '!=', 'ambassador')->pluck('name');
                        foreach ($userRoles as $userRole) {
                            $user->removeRole($userRole);
                        }

                        $userRoles = collect($userRoles)->map(function ($role) {
                            return config('constants.ARABIC_ROLES')[$role];
                        });
                    }

                    // assign new role
                    $user->assignRole($role->name);

                    // Link with head user
                    $user->parent_id = $head_user->id;
                    $user->save();

                    $msg = "";
                    $successMessage = "";
                    if (!$userRoles) {
                        $msg = "تمت ترقيتك ل " . $arabicRole . " - المسؤول عنك:  " . $head_user->name;
                        $successMessage = "تمت ترقية العضو ل " . $arabicRole . " - المسؤول عنه:  " . $head_user->name;
                        $user->notify(new MailUpgradeRole($arabicRole));
                    } else {
                        $msg = count($userRoles) > 1
                            ?
                            "تم سحب الأدوار التالية منك: " . implode(',', $userRoles->all()) . " أنت الآن " . $arabicRole
                            :
                            "تم سحب دور ال" . $userRoles[0] . " منك, أنت الآن " . $arabicRole;
                        $successMessage = count($userRoles) > 1
                            ?
                            "تم سحب الأدوار التالية من العضو: " . implode(',', $userRoles->all()) . " , إنه الآن " . $arabicRole
                            :
                            "تم سحب دور ال" . $userRoles[0] . " من العضو, إنه الآن " . $arabicRole;
                        $user->notify(new MailDowngradeRole($userRoles->all(), $arabicRole));
                    }
                    // notify user
                    (new NotificationController)->sendNotification($user->id, $msg, ROLES);
                    return $this->jsonResponseWithoutMessage($successMessage, 'data', 202);
                } else {
                    return $this->jsonResponseWithoutMessage("يجب أن تكون رتبة المسؤول أعلى من الرتبة المراد الترقية لها", 'data', 200);
                }
            } else {
                return $this->jsonResponseWithoutMessage("المسؤول غير موجود", 'data', 200);
            }
        } else {
            return $this->jsonResponseWithoutMessage("المستخدم غير موجود", 'data', 200);
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
                        
                        DB::commit();
                        return $this->jsonResponseWithoutMessage("تم التبديل", 'data', 200);
                    } catch (\Exception $e) {
                        //Log::channel('auditMarks')->info($e);
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
}