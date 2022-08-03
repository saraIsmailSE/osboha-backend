<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\LeaderRequest;
use App\Models\Group;
use App\Models\UserGroup;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use Spatie\Permission\PermissionRegistrar;
use App\Http\Resources\LeaderRequestResource;


class LeaderRequestController extends Controller
{
    use ResponseJson;

    public function index()
    {
        $leader_requests = LeaderRequest::where('user_id', Auth::id())->get();
        if($leader_requests){
            return $this->jsonResponseWithoutMessage(LeaderRequestResource::collection($leader_requests), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'members_num' => 'required',
            'gender' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
            $request['leader_id'] = Auth::id();
             $group = Group::with('user')->where('creator_id',Auth::id())->where('type_id',1)->get();
            $request['current_team_count'] =$group[0]->userAmbassador->count();
            if(Auth::user()->can('create leaderRequest')){
            LeaderRequest::create($request->all());
            return $this->jsonResponseWithoutMessage("LeaderRequest Craeted Successfully", 'data', 200);
           }
           else{
            throw new NotAuthorized; 
        }
    }
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leader_request_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $leader_request = LeaderRequest::find($request->leader_request_id);
        if($leader_request){
            return $this->jsonResponseWithoutMessage(new LeaderRequestResource($leader_request), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leader_request_id' => 'required',
            'members_num' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $leader_request = LeaderRequest::find($request->leader_request_id);
        if($leader_request){
            if(Auth::id() == $leader_request->leader_id){
                $leader_request->update($request->all());
                return $this->jsonResponseWithoutMessage("leader Request Updated Successfully", 'data', 200);
            }
            else{
                throw new NotAuthorized;   
            }
        }
        else{
            throw new NotFound;  
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leader_request_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        $leader_request = LeaderRequest::find($request->leader_request_id);
        if($leader_request){
            if(Auth::user()->can('delete leader_request') || Auth::id() == $leader_request->leader_id){
                $leader_request->delete();
                return $this->jsonResponseWithoutMessage("Leader Request Deleted Successfully", 'data', 200);
            }
            else{
                throw new NotAuthorized;
            }
        }
        else{
            throw new NotFound;   
        }
    }
}
