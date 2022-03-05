<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RejectedMark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Http\Resources\RejectedMarkResource;
use App\Models\Week;
use Carbon\Carbon;

class RejectedMarkController extends Controller
{
    use ResponseJson;

    public function index()
    {
        if(Auth::user()->can('audit mark')){
            $current_week = Week::latest()->first();
            $rejected_marks = RejectedMark::where('week_id', $current_week->id)->get();
        
            if($rejected_marks){
                return $this->jsonResponseWithoutMessage(RejectedMarkResource::collection($rejected_marks), 'data',200);
            }
            else{
               throw new NotFound;
            }
        } else {
            throw new NotAuthorized;   
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rejecter_note' => 'required', 
            'user_id' => 'required',
            'thesis_id' => 'required', 
            'week_id' => 'required', 
            'rejecter_id' => Auth::id()
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    
        if(Auth::user()->can('reject mark')){
            RejectedMark::create($request->all());
            return $this->jsonResponseWithoutMessage("Rejected Mark Craeted Successfully", 'data', 200);
        }
        else{
            throw new NotAuthorized;   
        }
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rejected_mark_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    
 
        if(Auth::user()->can('audit mark')){
            $rejected_mark = RejectedMark::find($request->rejected_mark_id);
            if($rejected_mark){
                return $this->jsonResponseWithoutMessage(new RejectedMarkResource($rejected_mark), 'data',200);
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
            'rejecter_note' => 'required', 
            'is_acceptable' => 'required',
            'rejected_mark_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('audit mark')){
            $rejected_mark = RejectedMark::find($request->rejected_mark_id);
            if($rejected_mark){
                $rejected_mark->update($request->all());
                return $this->jsonResponseWithoutMessage("Rejected Mark Updated Successfully", 'data', 200);
            }
            else{
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized;   
        }
    } 

    public function list_user_mark(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'week_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
            & $request->week_id == null)
        {
            $rejected_marks = RejectedMark::where('user_id', $request->user_id)->get();
            if($rejected_marks){
                return $this->jsonResponseWithoutMessage(RejectedMarkResource::collection($rejected_marks), 'data',200);
            }
            else{
                throw new NotFound;
            }
        } 
        else if((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
                & $request->week_id != null)
        {
            $rejected_marks = RejectedMark::where('user_id', $request->user_id)
                                            ->where('week_id', $request->week_id)->get();
            if($rejected_marks){
                return $this->jsonResponseWithoutMessage(RejectedMarkResource::collection($rejected_marks), 'data',200);
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
