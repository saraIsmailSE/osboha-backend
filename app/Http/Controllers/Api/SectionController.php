<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Book;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\SectionResource;
use App\Models\Infographic;
use App\Models\InfographicSeries;

class SectionController extends Controller
{
    use ResponseJson;
   
    /**
     * Read all sections in the system.
     * 
     * @return jsonResponseWithoutMessage;
     */
    public function index()
    {
            $sections = Section::all();
            if($sections->isNotEmpty()){
                return $this->jsonResponseWithoutMessage(SectionResource::collection($sections), 'data',200);
            }
            else{
                throw new NotFound;
            }
    }

    /**
     *Add a new section to the system (“create section” permission is required)
     * 
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'section' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        if(Auth::user()->can('create section')){   
            Section::create($request->all());
            return $this->jsonResponseWithoutMessage("Section Created Successfully", 'data', 200);
        } else {
            throw new NotAuthorized;
        }

    }

    /**
     * Find and show an existing section in the system by its id.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'section_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        $section = Section::find($request->section_id);
        if($section){
            return $this->jsonResponseWithoutMessage(new SectionResource($section), 'data',200);
        }
        else{
           throw new NotFound;
        }
    }

    /**
     * Update an existing section’s details ( “edit section” permission is required).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'section_id' => 'required',
            'section' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if(Auth::user()->can('edit section')){
            $section = Section::find($request->section_id);
            if($section){
                    $section->update($request->all());
                    return $this->jsonResponseWithoutMessage("Section Updated Successfully", 'data', 200);
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
     * Delete an existing section’s in the system using its id (“delete section” permission is required). 
     * with update section_id in (Book,Infographic,InfographicSeries)to zero.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'section_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        if(Auth::user()->can('delete section')){
            $section = Section::find($request->section_id);
            
            if($section){

                Book::where('section_id',$request->section_id)
                    ->update(['section_id'=> 0]);

                Infographic::where('section_id',$request->section_id)
                    ->update(['section_id'=> 0]);

                InfographicSeries::where('section_id',$request->section_id)
                    ->update(['section_id'=> 0]);
        
                $section->delete();
                return $this->jsonResponseWithoutMessage("Section Deleted Successfully", 'data', 200);
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