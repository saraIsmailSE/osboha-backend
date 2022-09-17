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
use App\Models\Group;
use Illuminate\Validation\Rule;




class UserGroupController extends Controller
{
    use ResponseJson;
    /**
     * Read all user groups in the system.
     *
     * @return jsonResponseWithoutMessage
     */
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
     /**
     * Find an existing user group in the system by its id and display it.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
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
    /**
     * Assign role to specific user with add him/her to group.
     * after that,this user will receive a new notification about his/her new role and group(“assgin role” permission is required).
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */

    public function assign_role(Request $request){
        #####Asmaa####

        $validator = Validator::make($request->all(), 
        [
            'group_id' => 'required',
            'user_id' => ['required',
                            Rule::unique('user_groups')->where(fn ($query) => $query->where('group_id', $request->group_id))],
            'user_type' => 'required',   
        ]); 


        
        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if(Auth::user()->can('assign role')){
            $user = User::find($request->user_id);
            $role = Role::where('name' ,$request->user_type)->first();
            $group = Group::where('id' ,$request->group_id)->first();

            if($user && $role && $group)
            {
                $user->assignRole($role);

                $msg = "Now, you are " . $role->name ." in ". $group->name ." group" ;
                (new NotificationController)->sendNotification($request->user_id , $msg);
     
                $userGroup = UserGroup::create($request->all());

                return $this->jsonResponse(new UserGroupResource($userGroup), 'data', 200, 'User Group Created Successfully');
            } else {
                throw new NotFound;
            }
        }else{
            throw new NotAuthorized;
        }
    }
    /**
     * remove role to specific user with create group to him/her.
     * after that,this user will receive a new notification about termination reason(update role” permission is required).
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update_role(Request $request){
        #####Asmaa####
        $validator = Validator::make($request->all(), 
        [
            'group_id' => 'required',
            'user_type' => 'required', 
            'user_group_id' => 'required',
            'termination_reason' => 'required',
            'user_id' => ['required',
                            Rule::unique('user_groups')->where(fn ($query) => $query->where('group_id', $request->group_id))->ignore(request('user_id'),'user_id')],    
        ]); 
        
        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userGroup = UserGroup::find($request->user_group_id);

        if($userGroup){
            if(Auth::user()->can('update role')){

                $user = User::find($request->user_id);
                $role = Role::where('name' ,$request->user_type)->first();
                $group = Group::where('id' ,$request->group_id)->first();

                if($user && $role && $group)
                {
                    $user->removeRole($role);
        
                    $msg = "You are not a " . $role->name ." in ". $group->name ." group anymore, because you " . $request->termination_reason;
                    (new NotificationController)->sendNotification($request->user_id , $msg);

                    $userGroup->update($request->all());
        
                    return $this->jsonResponse(new UserGroupResource($userGroup), 'data', 200, 'User Group Updated Successfully');
                } else {
                    throw new NotFound;
                }
            }else{
                throw new NotAuthorized;
            }
        }else{
            throw new NotFound;
        }        
    }
    /**
     * Read all user groups by its id in the system.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function list_user_group(Request $request){
        #####Asmaa####
        
        $validator = Validator::make($request->all(), 
        [
            'user_id' => 'required',  
        ]); 
        
        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userGroups = UserGroup::where('user_id', $request->user_id)->get();

        if($userGroups){
            return $this->jsonResponseWithoutMessage(UserGroupResource::collection($userGroups), 'data', 200);
        }else{
            throw new NotFound;
        }    
    }
}
