<?php

namespace App\Http\Controllers\Api ;

use App\Http\Controllers\Controller;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use App\Http\Resources\UserGroupResource;
use App\Models\UserGroup;
use App\Models\Group;
use App\Models\User;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;

class CPanelController extends Controller
{
    use ResponseJson;


    //get user by email

    public function getUserByEmail(Request $request){
        //dd( $request->email);
    $validator = Validator::make($request->all(), 
    [
        'email' => 'required',
    ]);

    if($validator->fails()){
        return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
    }

     $email =$request->input('email'); 
      $user = User::where('email', $email)->first();
      if($user){
        return $this->jsonResponseWithoutMessage($user,'data', 200);
    }else{
        throw new NotFound;
}
    }


    // display all roles and permissions
    public function index()
    { 
    $users = User::with('roles.permissions')->get();
    if ($users->isNotEmpty()) {
        // found roles and permissions response
        return $this->jsonResponseWithoutMessage($users, 'data', 200);
    } else {
        //not found roles and response
        throw new NotFound;
    }
}
    
    



    public function assign_role(Request $request){

        $validator = Validator::make($request->all(), 
        [
            'user_id' => 'required',
            'group_id' => 'required',
            'user_type' => 'required',        
        ]); 
        
        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if(Auth::user()->can('assgin role')){
            $user = User::find($request->user_id);
            $role = Role::where('name' ,$request->user_type)->first();
            $group = Group::where('id' ,$request->group_id)->first();
            
            $user->assignRole($role);

            $msg = "Now, you are " . $role->name ." in ". $group->name ." group" ;
            (new NotificationController)->sendNotification($request->user_id , $msg);
 
            $userGroup = UserGroup::create($request->all());

            return $this->jsonResponse(new UserGroupResource($userGroup), 'data', 200, 'User Group Created Successfully');
        }else{
            throw new NotAuthorized;
        }
    }
}