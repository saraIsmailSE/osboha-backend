<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mark;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Http\Resources\MarkResource;
use App\Models\User;

class MarkController extends Controller
{
    use ResponseJson;

    public function index()
    {
        $marks = Mark::all();
        if(Auth::user()->can('audit mark')){
            if($marks){
                return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data',200);
            }
            else{
               throw new NotFound;
            }
        } else {
            throw new NotAuthorized;   
        }
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mark_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        if(Auth::user()->can('audit mark')){
            $mark = Mark::find($request->mark_id);
            if($mark){
                return $this->jsonResponseWithoutMessage(new MarkResource($mark), 'data',200);
            }
            else{
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;   
        } 
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'out_of_90' => 'required', 
            'out_of_100' => 'required', 
            'total_pages' => 'required',  
            'support' => 'required', 
            'total_thesis' => 'required', 
            'total_screenshot' => 'required',
            'mark_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('edit mark')){
            $mark = Mark::find($request->mark_id);
            $mark->update($request->all());
            return $this->jsonResponseWithoutMessage("Mark Updated Successfully", 'data', 200);
        }
        else{
            throw new NotAuthorized;   
        }
    }

    public function marks_by_userid($user_id){
        if(Auth::user()->can('audit mark')){
            $marks = Mark::where('user_id', $user_id)->get();
            return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data',200);
        } else{
            throw new NotAuthorized;   
        }
    }

    public function marks_by_weekid($week_id){
        if(Auth::user()->can('audit mark')){
            $marks = Mark::where('week_id', $week_id)->get();
            return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data',200);
        } else{
            throw new NotAuthorized;   
        }
    }

    public function marks_by_userid_and_weekid($user_id, $week_id){
        if(Auth::user()->can('audit mark')){
            $marks = Mark::where('user_id', $user_id)
                        ->where('week_id', $week_id)->get();
            return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data',200);
        } else{
            throw new NotAuthorized;   
        }
    }
}
