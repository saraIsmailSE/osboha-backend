<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use App\Models\Timeline;
use App\Http\Resources\timelineResource ;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;



class TimelineController extends Controller
{
    use ResponseJson;
    /**
     * Read all information about all timelines in the system (“list timelines” permission is required)
     * 
     * @return jsonResponseWithoutMessage ;
     */
    public function index()
    {
        if(Auth::user()->can('list timelines')){
            $timeline = Timeline::all();
            if($timeline){
                return $this->jsonResponseWithoutMessage(timelineResource::collection($timeline), 'data',200);
            } else {
                throw new NotFound;
            }
        } else {
           throw new NotAuthorized;   
        }
    }
    /**
     * Add new timeline to the system (“create timeline” permission is required).
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function create(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'type_id' => 'required',
            'description' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        } 

        if(Auth::user()->can('create timeline')){   
            Timeline::create($request->all());
            return $this->jsonResponseWithoutMessage("Timeline Is Created Successfully", 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }
    /**
     * Find an existing timeline in the system by its id.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'timeline_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $timeline = Timeline::find($request->timeline_id);
        if($timeline){
            return $this->jsonResponseWithoutMessage(new timelineResource($timeline), 'data',200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Update an existing timeline in the system by its id (“edit timeline” permission is required).
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'type_id' => 'required',
            'description' => 'required',
            'timeline_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if(Auth::user()->can('edit timeline')){
            $timeline = Timeline::find($request->timeline_id);
            if($timeline){
                $timeline->update($request->all());
                return $this->jsonResponseWithoutMessage("Timeline Is Updated Successfully", 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }
    /**
     * Delete an existing timeline in the system by its id (“delete timeline” permission is required).
     *
     *  @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'timeline_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        
        if(Auth::user()->can('delete timeline')){   
            $timeline = Timeline::find($request->timeline_id);
            if($timeline){
                foreach ($timeline->posts as $post) {
                     if($post->type == "article"){
                        $post->timeline_id = null;
                     } else {
                        $post->delete();
                     }
                }
                $timeline->delete();
                return $this->jsonResponseWithoutMessage("Timeline Is Deleted Successfully", 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }
}
