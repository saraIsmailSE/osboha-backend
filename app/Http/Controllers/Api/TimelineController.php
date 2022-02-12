<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use App\Models\Timeline;

class TimelineController extends Controller
{
    use ResponseJson;

    public function index()
    {
        $timeline = Timeline::all();
        if($timeline){
            return $this->jsonResponseWithoutMessage($timeline, 'data',200);
        }
    }

    public function create(Request $request)
    {
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
            return $this->jsonResponseWithoutMessage($timeline, 'data',200);
        }
    }

    public function update(Request $request)
    {
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
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'timeline_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $timeline = Timeline::find($request->timeline_id);
        $timeline->delete();
        return $this->jsonResponseWithoutMessage("Timeline Is Deleted Successfully", 'data', 200);
    }
}
