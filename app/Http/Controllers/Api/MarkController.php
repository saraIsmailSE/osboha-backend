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

class MarkController extends Controller
{
    use ResponseJson;
    
    public function index()
    {
        $marks = Mark::all();
        if($marks){
            return $this->jsonResponseWithoutMessage($marks, 'data',200);
        }
        else{
           // throw new NotFound;
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

        $mark = Mark::find($request->mark_id);
        if($mark){
            return $this->jsonResponseWithoutMessage($mark, 'data',200);
        }
        else{
           // throw new NotFound;
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
            //throw new NotAuthorized;   
        }
    }
}
