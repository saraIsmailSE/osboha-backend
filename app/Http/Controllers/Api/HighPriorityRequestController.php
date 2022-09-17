<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\HighPriorityRequest;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use Spatie\Permission\PermissionRegistrar;
use App\Http\Resources\HighPriorityRequestResource;
use Illuminate\Support\Carbon;

class HighPriorityRequestController extends Controller
{
    use ResponseJson;

     /**
     * Read all high priority requests in the system.
     * 
     * @return jsonResponseWithoutMessage;
     */
    public function index()
    {
        $high_priority_requests = HighPriorityRequest::all();
        if($high_priority_requests->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(HighPriorityRequestResource::collection($high_priority_requests), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }

    /**
     *Add a new high priority request to the system (“create highPriorityRequestAmbassador” permission is required)
     * 
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $leader_requests = HighPriorityRequest::where('request_id', $request->request_id)->first();        
            if(!$leader_requests){
                if(Auth::user()->can('create highPriorityRequestAmbassador')){
                    HighPriorityRequest::create($request->all());
                    return $this->jsonResponseWithoutMessage("HighPriorityRequest Craeted Successfully", 'data', 200);
                }
                else{
                throw new NotAuthorized;
                }
            }
        else{
            return $this->jsonResponseWithoutMessage("This request is one of high priority request ", 'data', 200);

        }
    }

   /**
     * Find and show an existing high priority request in the system by its id.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */ 
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $high_priority_request = HighPriorityRequest::where('request_id',$request->request_id)->first();
        if($high_priority_request){
            return $this->jsonResponseWithoutMessage(new HighPriorityRequestResource($high_priority_request), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }

}