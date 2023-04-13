<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
//use App\Http\Controllers\Api\NotificationController;
use App\Models\User;
use App\Models\Sign_up;
use App\Models\UserProfile;
use App\Models\ProfileSetting;
use App\Models\LeaderRequest;
use App\Models\Group;
use App\Models\UserGroup;
use App\Events\NewUserStats;
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
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $authUser = Auth::user();

            $success['token'] = $authUser->createToken('sanctumAuth')->plainTextToken;
            $success['user'] = $authUser->load('userProfile', 'roles:name', 'permissions:name');

            return $this->jsonResponse($success, 'data', 200, 'Login Successfully');
        } else {

            return $this->jsonResponse('UnAuthorized', 'data', 404, 'Email Or Password is Wrong');
        }
    }

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

    protected function sendResetLinkResponse(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT)
            return $this->sendResponse(__($status), 'Send Successfully!');
        else
            return $this->sendError('ERROR', ['email' => __($status)]);
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
}
