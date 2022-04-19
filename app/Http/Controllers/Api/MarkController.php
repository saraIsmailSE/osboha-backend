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
use App\Models\Week;
use Carbon\Carbon;


class MarkController extends Controller
{
    use ResponseJson;

    public function index()
    {
        if(Auth::user()->can('audit mark')){
            $current_week = Week::latest()->first();
            $marks = Mark::where('week_id', $current_week->id)->get();
        
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
            if($mark){
                $mark->update($request->all());
                return $this->jsonResponseWithoutMessage("Mark Updated Successfully", 'data', 200);
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
            'user_id' => 'required_without:week_id',
            'week_id' => 'required_without:user_id'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        
        if((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
            && $request->has('week_id') && $request->has('user_id'))
        {
            $marks = Mark::where('user_id', $request->user_id)
                        ->where('week_id', $request->week_id)->get();
            if($marks){
                return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data',200);
            }
            else{
                throw new NotFound;
            }
        } 
        else if((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
                && $request->has('week_id'))
        {
            $marks = Mark::where('week_id', $request->week_id)->get();
            if($marks){
                return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data',200);
            }
            else{
                throw new NotFound;
            }
        }
        else if((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
                && $request->has('user_id'))
        {
            $mark = Mark::where('user_id', $request->user_id)->latest()->first();
            if($mark){
                return $this->jsonResponseWithoutMessage(new MarkResource($mark), 'data',200);
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