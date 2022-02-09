<?php

namespace App\Http\Controllers;

use App\Models\RejectedMark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RejectedMarkController extends Controller
{
    public function index()
    {
        $rejected_marks = RejectedMark::all();
        if($rejected_marks){
            return $this->jsonResponseWithoutMessage($rejected_marks, 'data',200);
        }
        else{
           // throw new NotFound;
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rejecter_note' => 'required', 
            'is_acceptable' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    
        if(Auth::user()->can('reject mark' | 'accept mark')){
            RejectedMark::create($request->all());
            return $this->jsonResponseWithoutMessage("Rejected Mark Craeted Successfully", 'data', 200);
        }
        else{
            //throw new NotAuthorized;   
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

        $rejected_mark = RejectedMark::find($request->rejected_mark_id);
        if($rejected_mark){
            return $this->jsonResponseWithoutMessage($rejected_mark, 'data',200);
        }
        else{
           // throw new NotFound;
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rejecter_note' => 'required', 
            'is_acceptable' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('reject mark' | 'accept mark')){
            $rejected_mark = RejectedMark::find($request->mark_id);
            $rejected_mark->update($request->all());
            return $this->jsonResponseWithoutMessage("Rejected Mark Updated Successfully", 'data', 200);
        }
        else{
            //throw new NotAuthorized;   
        }
    }
}
