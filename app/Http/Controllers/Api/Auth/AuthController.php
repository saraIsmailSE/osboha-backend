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
use App\Models\Mark;
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
use Illuminate\Support\Facades\Log;


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
            DB::beginTransaction();

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

            DB::commit();
            return $this->jsonResponseWithoutMessage($success, 'data', 200);
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return $this->jsonResponseWithoutMessage('User already exist', 'data', 202);
            } else {
                Log::channel('newUser')->info($e);
                DB::rollBack();
                return $this->jsonResponseWithoutMessage('حدث خطأ، يرجى المحاولة فيما بعد', 'data', 202);
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
            return $this->jsonResponseWithoutMessage("تفقد بريدك الالكتروني", 'data', 200);
        else
            return $this->jsonResponseWithoutMessage("  حدث خطأ", 'data', 200);
    }

    protected function sendResetResponse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                /**
                 * @todo: slow query - asmaa         
                 */
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->createToken('random key')->accessToken;

                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET)
            return $this->jsonResponseWithoutMessage($status, 'Updated Successfully!', 200);
        else
            return $this->jsonResponseWithoutMessage($status, 'data', 500);
    }

    /**
     *get auth user session data for frontend.
     * 
     * @return jsonResponse;
     */

    public function sessionData()
    {
        // latest books whereHas 'status'='in progress' 
        $book_in_progress = UserBook::where('status', 'in progress')
            ->where('user_id', Auth::id())
            ->latest()
            ->limit(3)
            ->get();

        if ($book_in_progress) {
            foreach ($book_in_progress as $key => $book) {
                $last_thesis = Thesis::where('user_id', Auth::id())
                    ->where('book_id', $book->book_id)
                    ->with('book')
                    ->latest()->first();
                if ($last_thesis) {
                    $response['book_in_progress'][$key] = $last_thesis->book;
                    $response['progress'][$key] = ($last_thesis->end_page / $last_thesis->book->end_page) * 100;
                }
            }
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


    /**
     *return excluded member [FOR NOW].
     * 
     * @return jsonResponse;
     * @todo: slow query - asmaa
     */

    public function returnToTeam()
    {
        //update is_excluded to 0
        $user = User::Find(Auth::id());
        $user->is_excluded = 0;

        $userGroup = UserGroup::where('user_id', $user->id)->where('user_type', 'ambassador')->first();
        $leader = UserGroup::where('group_id', $userGroup->user_id)->where('user_type', 'leader')->first();
        $user->parent_id = $leader->user_id;
        $user->save();
        /**
         * @todo: slow query - asmaa         
         */
        // update termination_reason to null
        $userGroup->termination_reason = null;
        $userGroup->save();


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

        $notification = new NotificationController();
        $msg = 'قام السفير ' . $user->name . ' بالعودة إلى الفريق';
        $notification->sendNotification($user->parent_id, $msg, EXCLUDED_USER);

        return $this->jsonResponseWithoutMessage('تم التعديل بنجاح', 'data', 200);
    }
    /**
     *reset auth email.
     * 
     * @return jsonResponse;
     */

    public function resetEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
        ]);

        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        try {
            DB::beginTransaction();

            $user = User::find(Auth::id());
            $user->email = $request->email;
            $user->email_verified_at = null;
            $user->save();
            //sendEmailVerificationNotification
            $user->sendEmailVerificationNotification();
            DB::commit();

            return $this->jsonResponseWithoutMessage('Reset Successfully!', 'data', 200);
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return $this->jsonResponseWithoutMessage('User already exist', 'data', 201);
            } else {
                Log::channel('newUser')->info($e);
                DB::rollBack();
                return $this->jsonResponseWithoutMessage('حدث خطأ، يرجى المحاولة فيما بعد', 'data', 201);
            }
        }
    }
}
