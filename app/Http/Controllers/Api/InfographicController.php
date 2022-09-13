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
     /**
     * Get all the information related to all the infographics found in the system
     * 
     * @return jsonResponseWithoutMessage
     */
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

    /**
     * Add new Infographic to the system (“create infographic” permission is required).
     * Detailed Steps:
     * 1-  Validate required data and the image format.
     * 2-  Add new infographic to the database if the permission is valid.
     * 3-  Add the image to the database using MediaTraits.
     * 4-  Return success or error message.
     * @param  Request  $request
     * @return jsonResponse;
     */
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
    /**
     * Find existing infographic in the system by its id and display it.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
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
    /**
     * Update existing infographic in the system (“edit infographic” permission is required)
     * Detailed Steps : 
     * 1-  Validate required data and the image format
     * 2-  Find the requested infographic by id
     * 3-  Update the requested infographic in the database if the permission is valid
     * 4-  Find the media related to the infographic by infographicID
     * 5-  Update the image in the database using MediaTraits
     * 6-  Return success or error message
     *
     * @param  Request  $request
     * @return jsonResponse;
     */

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
                if ($infographicMedia) {
                    $this->updateMedia($request->file('image'), $infographicMedia->id);
                }

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
    /**
     * Find existing infographic in the system by its id then delete it (“delete infographic” permission is required).
     * Detailed Steps:
     * 1- Validate required data
     * 2- Find the requested infographic by id
     * 3- Delete the requested infographic in the database if the permission is valid
     * 4- Find the media related to the infographic by infographicID
     * 5- Delete the image from the database using MediaTraits
     * 6- Return success or error message

     * @param  Request  $request
     * @return jsonResponse;
     */
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
                //retrieve infographic media 
                $infographicMedia = Media::where('infographic_id', $infographic->id)->first();

                //delete media
                if ($infographicMedia) {
                    $this->deleteMedia($infographicMedia->id);
                }

                //delete found infographic
                $infographic->delete();
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
  /**
     *  Find all the infographics in the system related to a requested section then return them
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
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