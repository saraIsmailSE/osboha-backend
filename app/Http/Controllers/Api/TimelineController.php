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
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;


class TimelineController extends Controller
{
    use ResponseJson;

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

    public function create(Request $request)
    {   
        if(Auth::user()->can('create timeline')){
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'type' => 'required',
                'description' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
            } 
            
            Timeline::create($request->all());
            return $this->jsonResponseWithoutMessage("Timeline Is Created Successfully", 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }

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

    public function update(Request $request)
    {
        if(Auth::user()->can('edit timeline')){
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'type' => 'required',
                'description' => 'required',
                'timeline_id' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
            }

            $timeline = Timeline::find($request->timeline_id);
            $timeline->update($request->all());
            return $this->jsonResponseWithoutMessage("Timeline Is Updated Successfully", 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }

    public function delete(Request $request)
    {
        if(Auth::user()->can('delete timeline')){
            $validator = Validator::make($request->all(), [
                'timeline_id' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
            }
            
            $timeline = Timeline::find($request->timeline_id);
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
            throw new NotAuthorized;
        }
    }
}
