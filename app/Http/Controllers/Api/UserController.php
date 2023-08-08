<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserInfoResource;
use App\Models\User;
use App\Models\UserGroup;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use ResponseJson;


    public function searchUsers(Request $request)
    {
        $searchQuery = $request->query('search');
        $users = User::where('name', 'LIKE', '%' . $searchQuery . '%')->get();
        return $this->jsonResponseWithoutMessage(UserInfoResource::collection($users), "data", 200);
    }
    public function searchByEmail($email)
    {
        $response['user'] = User::with('parent')->where('email', $email )->first();
        if($response['user']){
            $response['roles'] = $response['user']->getRoleNames();
            $response['followup_team'] = UserGroup::with('group')->where('user_id' , $response['user']->id)->where('user_type','ambassador')->whereNull('termination_reason')->first();
            $response['groups'] = UserGroup::where('user_id' , $response['user']->id)->get();
            return $this->jsonResponseWithoutMessage($response, "data", 200);
        }
        else{
            return $this->jsonResponseWithoutMessage(null, "data", 200);
        }
    }
}
