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
use Illuminate\Support\Carbon;



class LeaderRequestController extends Controller
{
    use ResponseJson;

    /**
     * Read all leader requests in the system.
     * 
     * @return jsonResponseWithoutMessage;
     */
    public function index()
    {
        $leader_requests = LeaderRequest::all();
        if($leader_requests){
            return $this->jsonResponseWithoutMessage(LeaderRequestResource::collection($leader_requests), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }

     /**
     * Add a new leader request to the system (“create RequestAmbassador” permission is required)
     * 
     * @param  Request  $request
     * @return jsonResponse;
     */
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
        if(Auth::user()->can('create RequestAmbassador')){
            $leader_requests = LeaderRequest::where('leader_id',Auth::id())->where('is_done',0)->orderBy('created_at', 'desc')->first();
             if(!$leader_requests){
                $group = Group::with('user')->where('creator_id',Auth::id())->where('type_id',1)->first();
                 $request['current_team_count'] = $group->userAmbassador->count();
                 //check the number of membmers that can leader request ,the number of membmers up to a maximum of 30 members
                 if($request['current_team_count'] >= 30){
                    return $this->jsonResponseWithoutMessage("Sorry!you can not make requset,your group is completed", 'data', 200);
                }
                else{
                    $members_num_leader_can_request = 30 - $request['current_team_count'];
                    if( $request['members_num'] > $members_num_leader_can_request ){
                            $request['members_num'] = $members_num_leader_can_request;
                            LeaderRequest::create($request->all());
                            return $this->jsonResponseWithoutMessage("you can't request this count of members, you can add:".$members_num_leader_can_request." members,so we update your request to :".$members_num_leader_can_request." members", 'data', 200);
                        }
                    else{
                            LeaderRequest::create($request->all());
                            return $this->jsonResponseWithoutMessage("LeaderRequest Craeted Successfully", 'data', 200);
                    }   
                }
             }
             //if leader has request and his request not done
             else{
                return $this->jsonResponseWithoutMessage("You already have request", 'data', 200);
             }  
        }
        else{
            throw new NotAuthorized;
        }        
    }

     /**
     * Find and show an existing leader request in the system by its id.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
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

     /**
     * Update an existing leader request details( “edit RequestAmbassador” permission is required OR the logged in user_id has to match the user_id in the request).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update(Request $request)
    {
        $leader_request = LeaderRequest::find($request->leader_request_id);
        if($leader_request){
            if(Auth::id() == $leader_request->leader_id || Auth::user()->can('edit RequestAmbassador')){
                //chech if request is not done
                if($leader_request['is_done'] == 0){
                    //get the current team count by count all membmer in group of this leader
                    $group = Group::with('user')->where('creator_id',Auth::id())->where('type_id',1)->first();
                    $request['current_team_count'] = $group->userAmbassador->count();
                    $members_num_leader_can_request = 30 - $request['current_team_count'];//check the number of membmers that can leader request ,the number of membmers up to a maximum of 30 members
                    if( $request['members_num'] > $members_num_leader_can_request ){
                        return $this->jsonResponseWithoutMessage("you can't request that number of members ,the previous request was:".$request['members_num']." members and maximum number that can be requested is:".$members_num_leader_can_request." members", 'data', 200);
                    }
                    else{
                        $leader_request->update($request->all());
                        return $this->jsonResponseWithoutMessage("leader Request Updated Successfully", 'data', 200);
                    }
                }
                else{
                    return $this->jsonResponseWithoutMessage("This Request is done so you can't update", 'data', 200);
                }

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
