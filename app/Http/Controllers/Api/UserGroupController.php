<?php

namespace App\Http\Controllers\Api;

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

class UserGroupController extends Controller
{
    use ResponseJson;

    public function index()
    {
        #####Asmaa####
        $userGroups = UserGroup::all();

        if($userGroups->isNotEmpty())
        {
            return $this->jsonResponseWithoutMessage(UserGroupResource::collection($userGroups), 'data', 200);
        }else
        {
            throw new NotFound;
        }
    }

    public function show(Request $request)
    {
        #####Asmaa####
        $validator = Validator::make($request->all(), ['user_group_id' => 'required']);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userGroup = UserGroup::find($request->user_group_id);

        if($userGroup){
            return $this->jsonResponseWithoutMessage(new UserGroupResource($userGroup), 'data', 200);
        }else{
            throw new NotFound;
        }
    }

    public function assign_role(Request $request){
        #####Asmaa####

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
            
            $user->assignRole($role);

            $userGroup = UserGroup::create($request->all());

            return $this->jsonResponse(new UserGroupResource($userGroup), 'data', 200, 'User Group Created Successfully');
        }else{
            throw new NotAuthorized;
        }
    }

    public function update_role(Request $request){
        #####Asmaa####
        $validator = Validator::make($request->all(), 
        [
            'user_id' => 'required',
            'group_id' => 'required',
            'user_type' => 'required', 
            'user_group_id' => 'required',
            'termination_reason' => 'required'       
        ]); 
        
        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userGroup = UserGroup::find($request->user_group_id);

        if($userGroup){
            if(Auth::user()->can('update role')){

                $user = User::find($request->user_id);
                $role = Role::where('name' ,$request->user_type)->first();
                
                $user->removeRole($role);
    
                $userGroup->update($request->all());
    
                return $this->jsonResponse(new UserGroupResource($userGroup), 'data', 200, 'User Group Updated Successfully');
            }else{
                throw new NotAuthorized;
            }
        }else{
            throw new NotFound;
        }        
    }
    public function list_user_group(Request $request){
        #####Asmaa####
        
        $validator = Validator::make($request->all(), 
        [
            'user_id' => 'required',  
        ]); 
        
        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userGroups = UserGroup::where('user_id', $request->user_id)->first();

        if($userGroups){
            return $this->jsonResponseWithoutMessage(UserGroupResource::collection($userGroups), 'data', 200);
        }else{
            throw new NotFound;
        }    
    }
}
