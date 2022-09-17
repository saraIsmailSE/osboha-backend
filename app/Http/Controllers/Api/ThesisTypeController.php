<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ThesisType;
use App\Models\Thesis;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\ThesisTypeResource;

class ThesisTypeController extends Controller
{
    use ResponseJson;
     /**
     * Read all thesis types in the system.
     * 
     * @return jsonResponseWithoutMessage ;
     */
    public function index()
    {
        $thesisTypes = ThesisType::all();
        if($thesisTypes->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(ThesisTypeResource::collection($thesisTypes), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }
    /**
     * Add new thesis type to the system(“create type” permission is required).
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function create(Request $request){

        $validator = Validator::make($request->all(), [
            'type' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    
        if(Auth::user()->can('create type')){
            ThesisType::create($request->all());
            return $this->jsonResponseWithoutMessage("Thesis-Type Created Successfully", 'data', 200);
        }
        else{
            throw new NotAuthorized;   
        }
    }
    /**
     * Find an existing thesis type in the system by its id and display it.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        $thesisType = ThesisType::find($request->id);
        if($thesisType){
            return $this->jsonResponseWithoutMessage(new ThesisTypeResource($thesisType), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }
    /**
     * Update an existing thesis type in the system by its id(“edit type” permission is required).
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'type' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('edit type')){
            $thesisType = ThesisType::find($request->id);
            if($thesisType){
                $thesisType->update($request->all());
                return $this->jsonResponseWithoutMessage("Thesis-Type Updated Successfully", 'data', 200);
            }
            else{
                throw new NotFound;   
            }
        }
        else{
            throw new NotAuthorized;   
        }
        
    }
    /**
     * Delete an existing thesis type in the system  by its id(“delete type” permission is required).
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        if(Auth::user()->can('delete type')){
            $thesisType = ThesisType::find($request->id);

            if($thesisType){
                Thesis::where('type_id',$request->id)
                    ->update(['type_id'=> 0]);
                    $thesisType->delete();
                
                return $this->jsonResponseWithoutMessage("Thesis-Type Deleted Successfully", 'data', 200);
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
