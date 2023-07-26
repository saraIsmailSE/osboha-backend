<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserInfoResource;
use App\Models\User;
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
}
