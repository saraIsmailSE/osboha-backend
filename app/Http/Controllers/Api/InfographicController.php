<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Http\Resources\InfographicResource;
use App\Models\Infographic;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InfographicController extends Controller
{
    use ResponseJson;

    public function index()
    {
         #######ASMAA#######

         $infographic = Infographic::all();

         if($infographic){
             //found infographic response
             return $this->jsonResponseWithoutMessage(InfographicResource::collection($infographic), 'data', 200);
         }else{
             //not found articles response
             throw new NotFound();
         }
    }

    public function create(Request $request)
    {
        #######ASMAA#######

        //validate requested data
        $validator = Validator::make($request->all(), [
            'title' => 'required', 
            'designer_id' => 'required',
            'section' => 'required',            
        ]);

        if($validator->fails()){
            //return validator errors
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //authorized user
        if(Auth::user()->can('create infographic')){      
            //create new article      
            Infographic::create(new InfographicResource($request->all())); 

            //success response after creating the article
            return $this->jsonResponseWithoutMessage('infographic created successfully', 'data', 200);
        }else{
            //unauthorized user response
            throw new NotAuthorized();           
        }
    }

    public function show(Request $request)
    {
        #######ASMAA#######

        //validate infographic id 
        $validator = Validator::make($request->all(), [
            'infographic_id' => 'required'
        ]);

        //validator errors response
        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //find needed infographic
        $infographic = Infographic::find($request->infographic_id);

        if($infographic){
            //return found infographic
            return $this->jsonResponseWithoutMessage(new InfographicResource($infographic), 'data', 200);
        }else{
            //infographic not found response
            throw new NotFound();           
        }
    }

    public function update(Request $request)
    {
        #######ASMAA#######

         //validate requested data
         $validator = Validator::make($request->all(), [
            'title'      => 'required', 
            'designer_id'    => 'required',            
            'section'    => 'required',
            'infographic_id' => 'required',            
        ]);

        if($validator->fails()){
            //return validator errors
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //authorized user
        if(Auth::user()->can('edit infographic')){
            //find needed infographic
            $infographic = Infographic::find($request->infographic_id);

            //update found infographic
            $infographic->update($request->all()); 

            //success response after update
            return $this->jsonResponseWithoutMessage('Infographic updated successfully', 'data', 200);
        }else{
            //unauthorized user response
            throw new NotAuthorized();            
        }
    }

    public function delete(Request $request)
    {
        #######ASMAA#######

        //validate infographic id 
        $validator = Validator::make($request->all(), [
            'infographic_id' => 'required'
        ]);

        //validator errors response
        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //authorized user
        if(Auth::user()->can('delete infographic')){

            //find needed infographic 
            $infographic = Infographic::find($request->infographic_id);

            //delete found infographic
            $infographic->delete();

            //success response after delete
            return $this->jsonResponseWithoutMessage('Infographic deleted successfully', 'data', 200);
        }else{
            //unauthorized user response
            throw new NotAuthorized();            
        }
    }
}
