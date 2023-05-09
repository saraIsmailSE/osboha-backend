<?php

namespace App\Http\Controllers\Api;

use App\Events\NotificationsEvent;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserGroupResource;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use App\Models\Group;
use App\Models\Mark;
use App\Models\UserBook;
use App\Models\Week;
use App\Notifications\MailAmbassadorDistribution;
use App\Notifications\MailMemberAdd;
use App\Traits\PathTrait;
use Illuminate\Validation\Rule;




class UserGroupController extends Controller
{
    use ResponseJson, PathTrait;
    /**
     * Read all user groups in the system.
     *
     * @return jsonResponseWithoutMessage
     */
    public function index()
    {
        #####Asmaa####
        $userGroups = UserGroup::all();

        if ($userGroups->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(UserGroupResource::collection($userGroups), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Find an existing user group in the system by its id and display it.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function show(Request $request)
    {
        #####Asmaa####
        $validator = Validator::make($request->all(), ['user_group_id' => 'required']);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userGroup = UserGroup::find($request->user_group_id);

        if ($userGroup) {
            return $this->jsonResponseWithoutMessage(new UserGroupResource($userGroup), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Get all users in specific group.
     * 
     * @param  $group_id
     * @return jsonResponseWithoutMessage;
     */
    public function usersByGroupID($group_id)
    {
        $users = Group::with('users')->where('id', $group_id)->first();
        if ($users) {
            return $this->jsonResponseWithoutMessage($users, 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    /**
     * Assign role to specific user with add him/her to group.
     * after that,this user will receive a new notification about his/her new role and group(“assgin role” permission is required).
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */



    public function create(Request $request)
    {
        // Validate the input
        $validatedData = $request->validate([
            'email' => 'required|email',
            'group_id' => 'required',
            'user_type' => 'required',
        ]);


        $user = User::where('email', $validatedData['email']);
        if (!$user) {
            return $this->jsonResponseWithoutMessage('email not found', 'data', 404);
        } else if (!is_null($user->parent_id)) {
            $user->parent_id = Auth::id();
        } else if (!$user->hasRole($validatedData['uesr_type'])) {
            return $this->jsonResponseWithoutMessage('User does not have the required role', 'data', 401);
        }



        $user->save();
        $userGroup = UserGroup::create(['user_id' => $user->id, 'group_id' =>  $validatedData['group_id'], $validatedData['uesr_type']]);

        $userGroup->save();


        return response()->json([
            'status' => 'success',
            'message' => 'User added successfully',
            'data' => $user,
        ]);
    }



    /**
     * Add user to group with specific role 
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */

    public function addMember(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'group_id' => 'required',
                'email' => 'required|email',
                'role_id' => 'required',
            ]
        );

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //check role exists
        $role = Role::find($request->role_id);
        if (!$role) {
            return $this->jsonResponseWithoutMessage("هذه الرتبة غير موجودة", 'data', 200);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $group = Group::find($request->group_id);
            if ($group) {
                $arabicRole = config('constants.ARABIC_ROLES')[$role->name];

                $checkMember = UserGroup::where('user_id', $user->id)->where('group_id', $group->id)->where('user_type', $role->name)->first();
                //by asmaa
                if ($checkMember) {
                    return $this->jsonResponseWithoutMessage('ال' . $arabicRole .  ' موجود في المجموعة', 'data', 202);
                }

                if ($user->hasRole($role->name)) {
                    //check if the role is leader and above then check if this role found in the group
                    if ($role->name !== 'ambassador') {
                        //check if role is leader and if the leader is a leader on other group
                        if ($role->name === 'leader') {
                            $leaderInGroups = UserGroup::where('user_id', $user->id)->where('user_type', 'leader')->where('group_id', '!=', $group->id)->first();
                            if ($leaderInGroups) {
                                return $this->jsonResponseWithoutMessage("لا يمكنك إضافة هذا العضو كقائد, لأنه موجود كقائد في فريق آخر ", 'data', 200);
                            }
                        }

                        $roleInGroup = UserGroup::where('group_id', $group->id)->where('user_type', $role->name)->first();
                        if ($roleInGroup) {
                            return $this->jsonResponseWithoutMessage("لا يمكنك إضافة هذا العضو ك" . $arabicRole . ", يوجد " . $arabicRole . " في المجموعة", 'data', 200);
                        }
                    }
                    if ($group->type->type == 'followup') {
                        if ($role->name == 'ambassador') {
                            if ($group->groupLeader->isEmpty())
                                return $this->jsonResponseWithoutMessage("لا يوجد قائد للمجموعة, لا يمكنك إضافة أعضاء", 'data', 200);
                            else {
                                $user->parent_id = $group->groupLeader[0]->id;
                                $user->save();
                                $user->notify(new MailAmbassadorDistribution($request->group_id));
                            }
                        }
                    } else { //later
                        //check if the group has a one in charge

                        //check if the one in charge is parent to the added user

                        //check if the group accepts this role
                    }

                    //if the added user is leader and has a role of supervisor, add them both
                    if ($user->hasRole('supervisor') && $role->name === 'leader' && $group->type->type === 'followup') {
                        $rolesToAdd = [
                            [
                                'user_id' => $user->id,
                                'group_id' => $group->id,
                                'user_type' => 'leader',
                                'created_at' => now(),
                                'updated_at' => now()

                            ],
                        ];

                        //check if the user is added a supervisor before
                        $foundAsSupervisor = UserGroup::where('user_id', $user->id)->where('group_id', $group->id)->where('user_type', 'supervisor')->first();
                        if (!$foundAsSupervisor) {
                            array_push($rolesToAdd,  [
                                'user_id' => $user->id,
                                'group_id' => $group->id,
                                'user_type' => 'supervisor',
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                        UserGroup::insert($rolesToAdd);
                        //notify the user with the supervisor addition
                        if (!$foundAsSupervisor) {
                            $arabicRole = $arabicRole . ' ومراقب';
                        }
                    } else {
                        //else create or update the record

                        //check if the added member is a supervisor who is a leader in the same group, then create a new record
                        if ($role->name === 'supervisor' && ($group->groupLeader->isNotEmpty() && $group->groupLeader[0]->id === $user->id) && $group->groupSupervisor->isEmpty()) {
                            UserGroup::Create(
                                [
                                    'user_id' => $user->id,
                                    'group_id' => $group->id,
                                    'user_type' => $role->name
                                ]
                            );
                        } else {
                            UserGroup::updateOrCreate(
                                [
                                    'user_id' => $user->id,
                                    'group_id' => $group->id
                                ],
                                ['user_type' => $role->name]
                            );
                        }
                    }

                    if ($role->name !== 'ambassador') {
                        $user->notify(new MailMemberAdd($arabicRole, $group));
                    }

                    $msg = "تمت إضافتك ك " . $arabicRole . " في المجموعة:  " . $group->name;
                    (new NotificationController)->sendNotification($user->id, $msg, ROLES, $this->getGroupPath($group->id));
                    //event(new NotificationsEvent($msg,$user));

                    $current_week_id = Week::latest()->pluck('id')->first();
                    Mark::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'week_id' => $current_week_id
                        ],
                        [
                            'user_id' => $user->id,
                            'week_id' => $current_week_id
                        ],
                    );


                    $successMessage = 'تمت إضافة العضو ك' . $arabicRole . " للمجموعة";
                    return $this->jsonResponseWithoutMessage($successMessage, 'data', 202);
                } else {
                    return $this->jsonResponseWithoutMessage("قم بترقية العضو ل" . $arabicRole . " أولاً", 'data', 200);
                }
            } else {
                return $this->jsonResponseWithoutMessage("المجموعة غير موجودة", 'data', 200);
            }
        } else {
            return $this->jsonResponseWithoutMessage("المستخدم غير موجود", 'data', 200);
        }
    }

    public function assign_role(Request $request)
    {
        #####Asmaa####

        $validator = Validator::make(
            $request->all(),
            [
                'group_id' => 'required',
                'user_id' => [
                    'required',
                    Rule::unique('user_groups')->where(fn ($query) => $query->where('group_id', $request->group_id))
                ],
                'user_type' => 'required',
            ]
        );

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->can('assign role')) {
            $user = User::find($request->user_id);
            $role = Role::where('name', $request->user_type)->first();
            $group = Group::where('id', $request->group_id)->first();

            if ($user && $role && $group) {
                $user->assignRole($role);

                $msg = "Now, you are " . $role->name . " in " . $group->name . " group";
                (new NotificationController)->sendNotification($request->user_id, $msg, ROLES, $this->getGroupPath($group->id));

                $userGroup = UserGroup::create($request->all());

                return $this->jsonResponse(new UserGroupResource($userGroup), 'data', 200, 'User Group Created Successfully');
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }
    /**
     * remove role to specific user with create group to him/her.
     * after that,this user will receive a new notification about termination reason(update role” permission is required).
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update_role(Request $request)
    {
        #####Asmaa####
        $validator = Validator::make(
            $request->all(),
            [
                'group_id' => 'required',
                'user_type' => 'required',
                'user_group_id' => 'required',
                'termination_reason' => 'required',
                'user_id' => [
                    'required',
                    Rule::unique('user_groups')->where(fn ($query) => $query->where('group_id', $request->group_id))->ignore(request('user_id'), 'user_id')
                ],
            ]
        );

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userGroup = UserGroup::find($request->user_group_id);

        if ($userGroup) {
            if (Auth::user()->can('update role')) {

                $user = User::find($request->user_id);
                $role = Role::where('name', $request->user_type)->first();
                $group = Group::where('id', $request->group_id)->first();

                if ($user && $role && $group) {
                    $user->removeRole($role);

                    $msg = "You are not a " . $role->name . " in " . $group->name . " group anymore, because you " . $request->termination_reason;
                    (new NotificationController)->sendNotification($request->user_id, $msg, ROLES, $this->getGroupPath($group->id));

                    $userGroup->update($request->all());

                    return $this->jsonResponse(new UserGroupResource($userGroup), 'data', 200, 'User Group Updated Successfully');
                } else {
                    throw new NotFound;
                }
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }
    /**
     * Read all user groups by its id in the system.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function list_user_group(Request $request)
    {
        #####Asmaa####

        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => 'required',
            ]
        );

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userGroups = UserGroup::where('user_id', $request->user_id)->get();

        if ($userGroups) {
            return $this->jsonResponseWithoutMessage(UserGroupResource::collection($userGroups), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
}