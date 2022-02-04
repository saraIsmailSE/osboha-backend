<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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
            $success['user'] = $authUser;

            return $this->jsonResponse($success, 'data', 200, 'Login Successfully');
        } else {

            return $this->jsonResponse('UnAuthorized', 'data', 404, 'Email Or Password is Wrong');
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_ar' => 'required',
            'name_en' => 'required',
            'phone' => 'required|numeric',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'user_type' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $user->assignRole($request->user_type);
        return $this->jsonResponseWithoutMessage("Register Successfully", 'data', 200);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        return $this->jsonResponseWithoutMessage('You are Logged Out Successfully', 'data', 200);
    }
}
