<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;

class ActivityController extends Controller
{
    use ResponseJson;

    public function index()
    {
        #######ASMAA#######
        //get and display all the activities
        $activity = Activity::all();
        if($activity){
            // found articles response
            return $this->jsonResponseWithoutMessage(ActivityResource::collection($activity), 'data',200);
        }
        else{
            //not found articles response
            throw new NotFound;

            //return $this->jsonResponseWithoutMessage('No Rcords Found', 'data',204);
        }
    }

    public function create(Request $request)
    {
        #######ASMAA#######

        //create new activity and store it in the database
      
        //validate requested data
        $validator = Validator::make($request->all(), [
            'name'    => 'required',
            'version' => 'required',            
        ]);

        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        } 
        
        //authorized user
        if(Auth::user()->can('create activity')){
            //create new activity
            $activity = Activity::create($request->all());

            //success response after creating new activity
            return $this->jsonResponse(new ActivityResource($activity), 'data', 200, 'Activity Created Successfully');
        }
        else{
            //unauthorized user
            throw new NotAuthorized;

            //return $this->jsonResponseWithoutMessage('Unauthorized', 'data',401);
        }
    }

    public function show(Request $request)
    {
        #######ASMAA#######

        //validate activity id
        $validator = Validator::make($request->all(), [
            'activity_id' => 'required',
        ]);

        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        //find needed activity
        $activity = Activity::find($request->activity_id);
        if($activity){
            //found activity response (display its data)
            return $this->jsonResponseWithoutMessage(new ActivityResource($activity), 'data',200);
        }
        else{
            //not found activity response
            throw new NotFound;

            //return $this->jsonResponseWithoutMessage('Activity Not Found', 'data',204);
        }
    }

    public function update(Request $request)
    {
        #######ASMAA#######

         //validate requested data
         $validator = Validator::make($request->all(), [
            'name'        => 'required',
            'version'     => 'required',
            'activity_id' => 'required',
        ]);

        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }   
        
        //authorized user
        if(Auth::user()->can('edit activity')){
            //find needed activity
            $activity = Activity::find($request->activity_id);

            //updated found activity
            $activity->update($request->all());

            //success response after update
            return $this->jsonResponse(new ActivityResource($activity), 'data', 200, "Activity Updated Successfully");
        }
        else{
            //unauthorized user response
            throw new NotAuthorized;

            //return $this->jsonResponseWithoutMessage('Unauthorized', 'data',401);
        }
    }

    public function delete(Request $request)
    {
        #######ASMAA#######
        
         //validate activity id 
        $validator = Validator::make($request->all(), [
            'activity_id' => 'required',
        ]);

        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        if(Auth::user()->can('delete activity')){
            //find needed activity
            $activity = Activity::find($request->activity_id);

            //deleted found activity
            $activity->delete();

             //success response after delete
            return $this->jsonResponse(new ActivityResource($activity), 'data', 200, "Activity Deleted Successfully");
        }
        else{
            //unauthorized user response
            throw new NotAuthorized;

            //return $this->jsonResponseWithoutMessage('Unauthorized', 'data',401);
        }
    }
}
