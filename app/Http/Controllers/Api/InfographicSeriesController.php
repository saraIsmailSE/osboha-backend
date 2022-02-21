<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Models\InfographicSeries;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InfographicSeriesController extends Controller
{
    use ResponseJson;
    
    public function index()
    {
         #######ASMAA#######
        //get and display all the series
        $series = InfographicSeries::all();
        if($series){
            // found series response
            return $this->jsonResponseWithoutMessage($series, 'data',200);
        }
        else{
            //not found series response
            throw new NotFound();
        }
    }

    public function create(Request $request)
    {
        #######ASMAA#######

        //create new series and store it in the database
      
        //validate requested data
        $validator = Validator::make($request->all(), [
            'title'    => 'required',
            'section' => 'required',            
        ]);

        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        } 
        
        //authorized user
        if(Auth::user()->can('create infographicSeries')){
            //create new series
            infographicSeries::create(new $request->all());

            //success response after creating new infographic Series
            return $this->jsonResponseWithoutMessage("infographic Series has been Created Successfully", 'data', 200);
        }
        else{
            //unauthorized user
            throw new NotAuthorized();            
        }
    }

    public function show(Request $request)
    {
       #######ASMAA#######

        //validate series id
        $validator = Validator::make($request->all(), [
            'series_id' => 'required',
        ]);

        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        //find needed series
        $series = InfographicSeries::find($request->series_id);
        if($series){
            //found series response (display its data)
            return $this->jsonResponseWithoutMessage($series, 'data',200);
        }
        else{
            //not found series response
            throw new NotFound();
        }
    }

    public function update(Request $request)
    {
        #######ASMAA#######

         //validate requested data
         $validator = Validator::make($request->all(), [
            'title'    => 'required',
            'section' => 'required',
            'series_id' => 'required', 
        ]);

        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }   
        
        //authorized user
        if(Auth::user()->can('edit infographicSeries')){
            //find needed series
            $series = InfographicSeries::find($request->series_id);

            //updated found series
            $series->update($request->all());

            //success response after update
            return $this->jsonResponseWithoutMessage("Infographic Series has been Updated Successfully", 'data', 200);
        }
        else{
            //unauthorized user response
            throw new NotAuthorized();
        }
    }

    public function delete(Request $request)
    {
        #######ASMAA#######
        
         //validate series id 
        $validator = Validator::make($request->all(), [
            'series_id' => 'required',
        ]);

        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        if(Auth::user()->can('delete infographicSeries')){
            //find needed series
            $series = InfographicSeries::find($request->series_id);

            //deleted found series
            $series->delete();

             //success response after delete
            return $this->jsonResponseWithoutMessage("infographic Series has been Deleted Successfully", 'data', 200);
        }
        else{
            //unauthorized user response
            throw new NotAuthorized();
        }
    }
}
