<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Http\Resources\InfographicResource;
use App\Models\Infographic;
use App\Models\Media;
use App\Traits\MediaTraits;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InfographicController extends Controller
{
    use ResponseJson;
    use MediaTraits;

    public function index()
    {
        #######ASMAA#######

        $infographic = Infographic::all();

        if ($infographic->isNotEmpty()) {
            //found infographic response
            return $this->jsonResponseWithoutMessage(InfographicResource::collection($infographic), 'data', 200);
        } else {
            //not found articles response
            throw new NotFound;
        }
    }

    public function create(Request $request)
    {
        #######ASMAA#######

        //validate requested data
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'designer_id' => 'required',
            'section_id' => 'required',
            'image' => 'required|image|mimes:png,jpg,jpeg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            //return validator errors
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //authorized user
        if (Auth::user()->can('create infographic')) {
            //create new article      
            $infographic = Infographic::create($request->all());

            //create media for infographic 
            $this->createMedia($request->file('image'), $infographic->id, 'infographic');

            //success response after creating the article
            return $this->jsonResponse(new InfographicResource($infographic), 'data', 200, 'Infographic Created Successfully');
        } else {
            //unauthorized user response
            throw new NotAuthorized;
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
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //find needed infographic
        $infographic = Infographic::find($request->infographic_id);

        if ($infographic) {
            //return found infographic
            return $this->jsonResponseWithoutMessage(new InfographicResource($infographic), 'data', 200);
        } else {
            //infographic not found response
            throw new NotFound;
        }
    }

    public function update(Request $request)
    {
        #######ASMAA#######

        //validate requested data
        $validator = Validator::make($request->all(), [
            'title'      => 'required',
            'designer_id'    => 'required',
            'section_id'    => 'required',
            'infographic_id' => 'required',
            'image' => 'required|image|mimes:png,jpg,jpeg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            //return validator errors
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //authorized user
        if (Auth::user()->can('edit infographic')) {
            //find needed infographic
            $infographic = Infographic::find($request->infographic_id);

            if ($infographic) {
                //update found infographic
                $infographic->update($request->all());

                //retrieve infographic media 
                $infographicMedia = Media::where('infographic_id', $infographic->id)->first();

                //update media
                $this->updateMedia($request->file('image'), $infographicMedia->id);

                //success response after update
                return $this->jsonResponse(new InfographicResource($infographic), 'data', 200, 'Infographic Updated Successfully');
            } else {
                //infographic not found response
                throw new NotFound;
            }
        } else {
            //unauthorized user response
            throw new NotAuthorized;
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
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //authorized user
        if (Auth::user()->can('delete infographic')) {

            //find needed infographic 
            $infographic = Infographic::find($request->infographic_id);

            if ($infographic) {
                //delete found infographic
                $infographic->delete();

                //retrieve infographic media 
                $infographicMedia = Media::where('infographic_id', $infographic->id)->first();

                //delete media
                $this->deleteMedia($infographicMedia->id);
            } else {
                //infographic not found response
                throw new NotFound;
            }

            //success response after delete
            return $this->jsonResponse(new InfographicResource($infographic), 'data', 200, 'Infographic Deleted Successfully');
        } else {
            //unauthorized user response
            throw new NotAuthorized;
        }
    }

    public function InfographicBySection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'section_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $infographics = Infographic::where('section_id', $request->section_id)->get();
        if ($infographics->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(InfographicResource::collection($infographics), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
}