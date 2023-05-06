<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
//use App\Http\Controllers\Api\NotificationController;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\Sign_up;
use App\Models\UserProfile;
use App\Models\ProfileSetting;
use App\Models\LeaderRequest;
use App\Models\Group;
use App\Models\UserGroup;
use App\Events\NewUserStats;
use App\Http\Controllers\Api\NotificationController;
use App\Models\Thesis;
use App\Models\Timeline;
use App\Models\TimelineType;
use App\Models\UserBook;
use App\Models\Week;
use App\Notifications\MailDowngradeRole;
use App\Notifications\MailUpgradeRole;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ResponseJson;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $authUser = Auth::user();

            $success['token'] = $authUser->createToken('sanctumAuth')->plainTextToken;
            $success['user'] = $authUser->load('userProfile', 'roles:id,name', 'roles.permissions:id,name');

            return $this->jsonResponse($success, 'data', 200, 'تم تسجيل الدخول بنجاح');
        } else {

            return $this->jsonResponse('UnAuthorized', 'data', 404, 'البريد الالكتروني او كلمة المرور غير صحيحة');
        }
    }


    public function signUp(Request $request)
    {
        $ambassador = Validator::make($request->all(), [
            'name'             => 'required',
            'gender'           => 'required',
            'email'            => 'required|email|unique:users,email',
            'password'         => 'required',
        ]);
        if ($ambassador->fails()) {
            return $this->jsonResponseWithoutMessage($ambassador->errors(), 'data', 500);
        }
        try {

            $user = new User($request->all());
            $user->password = bcrypt($request->input('password'));
            $user->assignRole('ambassador');
            $user->save();

            //create new timeline - type = profile
            $profileTimeline = TimelineType::where('type', 'profile')->first();
            $timeline = new Timeline();
            $timeline->type_id = $profileTimeline->id;
            $timeline->save();

            //create user profile, with profile settings
            UserProfile::create([
                'user_id' => $user->id,
                'timeline_id' => $timeline->id
            ]);
            ProfileSetting::create([
                'user_id' => $user->id,
            ]);

            event(new Registered($user));

            $success['token'] = $user->createToken('sanctumAuth')->plainTextToken;
            $success['user'] = $user->load('userProfile', 'roles:id,name', 'roles.permissions:id,name');
            return $this->jsonResponseWithoutMessage($success, 'data', 200);
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return $this->sendError('User already exist');
            } else {
                return $this->sendError($e);
            }
        }
    }

    //LATER
    public function register(Request $request)
    {
        $ambassador = Validator::make($request->all(), [
            // 'name_ar'          => 'required',
            // 'name_en'          => 'required',
            'name'             => 'required',
            'gender'           => 'required',
            'leader_gender'    => 'required',
            'phone'            => 'required|numeric',
            'email'            => 'required|email|unique:users,email',
            'password'         => 'required',
            'user_type'        => 'required',
        ]);
        if ($ambassador->fails()) {
            return $this->jsonResponseWithoutMessage($ambassador->errors(), 'data', 500);
        }
        $ambassador = $request->all();
        $ambassador['password'] = bcrypt($ambassador['password']);

        $leader_gender = $ambassador['leader_gender'];
        $ambassador_gender = $ambassador['gender'];
        if ($ambassador_gender == 'any') {
            $ambassador_condition = array($ambassador_gender);
        } else {
            $ambassador_condition = array($ambassador_gender, 'any');
        }

        if ($leader_gender == "any") {
            $leader_condition = array('male', 'female');
        } else {
            $leader_condition = array($leader_gender);
        }
        DB::transaction(function () use ($ambassador, $ambassador_condition, $leader_condition) {
            $exit = false;
            while (!$exit) {

                // Check for High Priority Requests
                $result = Sign_up::selectHighPriority($leader_condition, $ambassador_condition);
                if ($result->count() == 0) {
                    // Check for SpecialCare
                    $result = Sign_up::selectSpecialCare($leader_condition, $ambassador_condition);
                    if ($result->count() == 0) {
                        //Check New Teams
                        $result = Sign_up::selectTeam($leader_condition, $ambassador_condition);
                        if ($result->count() == 0) {
                            //Check Teams With Less Than 12 Members
                            $result = Sign_up::selectTeam_between($leader_condition, $ambassador_condition, "1", "12");

                            if ($result->count() == 0) {
                                //Check Teams With Less More 12 Members
                                $result = Sign_up::selectTeam($leader_condition, $ambassador_condition, ">", "12");
                                if ($result->count() == 0) {
                                    $ambassadorWithoutLeader = User::create($ambassador);
                                    event(new NewUserStats());
                                    if ($ambassadorWithoutLeader) {
                                        $ambassadorWithoutLeader->assignRole($ambassador['user_type']);
                                        UserProfile::create([
                                            'user_id' => $ambassadorWithoutLeader->id,
                                        ]);
                                        ProfileSetting::create([
                                            'user_id' => $ambassadorWithoutLeader->id,
                                        ]);
                                    }
                                    $exit = true;
                                    echo $this->jsonResponseWithoutMessage("Register Successfully --Without Leader", 'data', 200);
                                } else {
                                    $exit =  $this->insert_ambassador($ambassador, $result);
                                    if ($exit == true) {
                                        echo $this->jsonResponseWithoutMessage("Register Successfully -- Teams With More Than 12 Members", 'data', 200);
                                    } else {
                                        continue;
                                    }
                                }
                            } //end if Teams With Less Than 12 Members
                            else {
                                $exit =  $this->insert_ambassador($ambassador, $result);
                                if ($exit == true) {
                                    echo $this->jsonResponseWithoutMessage("Register Successfully -- Teams With Less Than 12 Members", 'data', 200);
                                } else {
                                    continue;
                                }
                            } //end else Teams With Less Than 12 Members
                        } //end if Check New Teams
                        else {
                            $exit =  $this->insert_ambassador($ambassador, $result);
                            if ($exit == true) {
                                echo $this->jsonResponseWithoutMessage("Register Successfully -- New Teams", 'data', 200);
                            } else {
                                continue;
                            }
                        } //end if Check New Teams
                    } //end if Check for SpecialCare
                    else {
                        $exit =  $this->insert_ambassador($ambassador, $result);
                        if ($exit == true) {
                            echo $this->jsonResponseWithoutMessage("Register Successfully -- SpecialCare", 'data', 200);
                        } else {
                            continue;
                        }
                    } //end else Check for SpecialCare
                } //end if Check for High Priority Requests
                else {
                    $exit =  $this->insert_ambassador($ambassador, $result);


                    if ($exit == true) {
                        echo $this->jsonResponseWithoutMessage("Register Successfully -- High Priority", 'data', 200);
                    } else {
                        continue;
                    }
                } //end else Check for High Priority Requests

            } //while     
        });
    }
    public function insert_ambassador($ambassador, $results)
    {
        foreach ($results as $result) {
            $ambassador['request_id'] = $result->id;
            $countRequests = Sign_up::countRequests($result->id);
            if ($result->members_num > $countRequests) {
                $user = User::create($ambassador);
                event(new NewUserStats());
                if ($user) {
                    $user->assignRole($ambassador['user_type']);
                    //create User Profile
                    UserProfile::create([
                        'user_id' => $user->id,
                    ]);
                    //create Profile Setting
                    ProfileSetting::create([
                        'user_id' => $user->id,
                    ]);
                    $leader_request = LeaderRequest::find($result->id);
                    $group = Group::where('creator_id', $leader_request->leader_id)->first();
                    //create User Group
                    UserGroup::create([
                        'user_id'  => $user->id,
                        'group_id'  => $group->id,
                        'user_type' => $ambassador['user_type'],
                    ]);
                }

                $countRequest = $countRequests + 1;
                if ($result->members_num <= $countRequest) {
                    Sign_up::updateRequest($result->id);
                    $msg = "You request is done";
                    // (new NotificationController)->sendNotification($result->leader_id , $msg);
                }
                $msg = "You have new user to your team";
                //(new NotificationController)->sendNotification($result->leader_id , $msg);
                return true;
            } else {
                Sign_up::updateRequest($result->id);
                $msg = "You request is done";
                // (new NotificationController)->sendNotification($result->leader_id , $msg);           
                return false;
            }
        }
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        return $this->jsonResponseWithoutMessage('You are Logged Out Successfully', 'data', 200);
    }

    public function refresh(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        $token = $user->createToken('sanctumAuth')->plainTextToken;
        return $this->jsonResponseWithoutMessage($token, 'data', 200);
    }

    protected function sendResetLinkResponse(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT)
            return $this->jsonResponse(__($status), 'data', 200, 'Send Successfully!');
        else
            return $this->jsonResponseWithoutMessage(['email' => __($status)], 'data', 200);
    }

    protected function sendResetResponse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->createToken('random key')->accessToken;

                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET)
            return $this->sendResponse(__($status), 'Updated Successfully!');
        else
            return $this->sendError('ERROR', ['email' => __($status)]);
    }

    /**
     *get auth user session data for frontend.
     * 
     * @return jsonResponse;
     */

    public function sessionData()
    {
        // last book whereHas 'status'='in progress' 
        $book_in_progress = UserBook::where('status', 'in progress')
            ->where('user_id', Auth::id())
            ->latest()
            ->pluck('book_id')->first();

        if ($book_in_progress) {
            $last_thesis = Thesis::where('user_id', Auth::id())
                ->where('book_id', $book_in_progress)
                ->with('book')
                ->latest()->first();
            $response['book_in_progress'] = $last_thesis->book;
            $response['progress'] = ($last_thesis->end_page / $last_thesis->book->end_page) * 100;
        } else {
            $response['book_in_progress'] = null;
            $response['progress'] = null;
        }

        //reading group [where auth is ambassador]
        $response['reading_team'] = UserGroup::where('user_id', Auth::id())
            ->where('user_type', 'ambassador')
            ->whereNull('termination_reason')
            ->with('group')
            ->first();

        //main timer
        $response['timer'] = Week::latest()->first();


        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    public function getRoles($id)
    {
        /*
        **** Need to discuss ****
        $role = Role::find($id);
        $roles = Role::where('level', '>', $role->level)->get();
        return $this->jsonResponse($roles, 'data', 200, 'Roles');
        */
        $authRoles = Auth::user()->load('roles:id,name');
        $authLastrole = $authRoles->roles->first();

        $roles = Role::where('id', '>=', $authLastrole->id)->orderBy('id', 'desc')->get();
        return $this->jsonResponseWithoutMessage($roles, 'data', 200);
    }


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
}