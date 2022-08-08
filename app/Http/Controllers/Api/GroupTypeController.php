<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupType;
use App\Models\Group;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\GroupTypeResource;

class GroupTypeController extends Controller
{
    use ResponseJson;

    public function index()
    {
        $groupTypes = GroupType::all();
        if($groupTypes->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(GroupTypeResource::collection($groupTypes), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }

    public function create(Request $request){

        $validator = Validator::make($request->all(), [
            'type' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    
        if(Auth::user()->can('create type')){
            GroupType::create($request->all());
            return $this->jsonResponseWithoutMessage("Group-Type Created Successfully", 'data', 200);
        }
        else{
            throw new NotAuthorized;   
        }
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        $groupType = GroupType::find($request->id);
        if($groupType){
            return $this->jsonResponseWithoutMessage(new GroupTypeResource($groupType), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'type' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('edit type')){
            $groupType = GroupType::find($request->id);
            if($groupType){
                $groupType->update($request->all());
                return $this->jsonResponseWithoutMessage("Group-Type Updated Successfully", 'data', 200);
            }
            else{
                throw new NotFound;   
            }
        }
        else{
            throw new NotAuthorized;   
        }
        
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        if(Auth::user()->can('delete type')){
            $groupType = GroupType::find($request->id);

            if($groupType){
                Group::where('type_id',$request->id)
                    ->update(['type_id'=> 0]);
                    $groupType->delete();
                
                return $this->jsonResponseWithoutMessage("Group-Type Deleted Successfully", 'data', 200);
            }
            else{
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized;
        }
    }

}
