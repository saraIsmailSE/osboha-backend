<?php

namespace App\Http\Controllers\Api ;

use App\Http\Controllers\Controller;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Resources\UserResource;
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
     $email =$request->email; 
      $user = User::where('email', $email)->get();
      if($user){
        return $this->jsonResponseWithoutMessage(UserResource::collection($user), 'data', 200);
    }else{
        throw new NotFound;
}
    }


    // display all roles and permissions
    public function index() { 

    $role= Role::with('permissions')->get();
    $permissions= Permission::all();  
    if ($role->isNotEmpty()) {
        // found roles and permissions response
        return $this->jsonResponseWithoutMessage(compact('role', 'permissions'), 'data', 200);
      
    }
            else{
        //not found roles and response
        throw new NotFound;
    }
    
    /*$permissions= Permission::all();
    if ($permissions->isNotEmpty()) {
        // found permissions response
        return $this->jsonResponseWithoutMessage($permissions, 'data', 200);
    } else {
        //not found response
        throw new NotFound;
    }
    
   */
}

    
    



    public function assign_role(Request $request){
        //dd($request->user_type);
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
            return $this->jsonResponse(UserResource::collection($user), 'data', 200, 'User Created Successfully');
        }else{
            throw new NotAuthorized;
        }
    }
}