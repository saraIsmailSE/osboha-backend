<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserInfoResource;
use App\Models\User;
use App\Models\UserGroup;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use ResponseJson;


    public function searchUsers(Request $request)
    {
        $searchQuery = $request->query('search');
        $users = User::where('name', 'LIKE', '%' . $searchQuery . '%')
            ->whereNotNull('parent_id')
            ->get();
        return $this->jsonResponseWithoutMessage(UserInfoResource::collection($users), "data", 200);
    }
    public function searchByEmail($email)
    {
        $response['user'] = User::with('parent')->where('email', $email)->first();
        if ($response['user']) {
            $response['roles'] = $response['user']->getRoleNames();
            $response['in_charge_of'] = User::where('parent_id', $response['user']->id)->get();
            $response['followup_team'] = UserGroup::with('group')->where('user_id', $response['user']->id)->where('user_type', 'ambassador')->whereNull('termination_reason')->first();
            $response['groups'] = UserGroup::where('user_id', $response['user']->id)->get();
            return $this->jsonResponseWithoutMessage($response, "data", 200);
        } else {
            return $this->jsonResponseWithoutMessage(null, "data", 200);
        }
    }

    public function assignToParent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user' => 'required|email',
            'head_user' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        try {
            //check user exists
            $user = User::where('email', $request->user)->first();
            if ($user) {
                //check head_user exists
                $head_user = User::where('email', $request->head_user)->first();
                if ($head_user) {
                    $user_last_role = $user->roles->first();
                    $head_user_last_role = $head_user->roles->first();
                    //check if head user role is greater that user role
                    if ($head_user_last_role->id < $user_last_role->id) {
                        //if last role less than the new role => assign ew role
                        // Link with head user
                        $user->parent_id = $head_user->id;
                        $user->save();

                        $msg = "قام " . Auth::user()->name . " بـ تعيين : " . $head_user->name . " مسؤولًا عنك";
                        (new NotificationController)->sendNotification($user->id, $msg, ROLES);

                        $msg = "قام " . Auth::user()->name . " بـ تعيينك مسؤولاً عن : " . $user->name;
                        (new NotificationController)->sendNotification($head_user->id, $msg, ROLES);

                        return $this->jsonResponseWithoutMessage("تم التعيين", 'data', 200);
                    } else {
                        return $this->jsonResponseWithoutMessage("يجب أن تكون رتبة المسؤول أعلى من رتبة المسؤول عنه", 'data', 200);
                    }
                } else {
                    return $this->jsonResponseWithoutMessage("المسؤول غير موجود", 'data', 200);
                }
            } else {
                return $this->jsonResponseWithoutMessage("المستخدم غير موجود", 'data', 200);
            }
        } catch (\Illuminate\Database\QueryException $error) {
            return $this->jsonResponseWithoutMessage($error, 'data', 500);
        }
    }
    public function getInfo($id)
    {
        try {
            //get user info
            $user = User::findOrFail($id);
            return $this->jsonResponseWithoutMessage($user, 'data', 200);
        } catch (\Illuminate\Database\QueryException $error) {
            return $this->jsonResponseWithoutMessage($error, 'data', 500);
        }
    }
}
