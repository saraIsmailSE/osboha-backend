<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Http\Resources\InfographicSeriesResource;
use App\Models\InfographicSeries;
use App\Models\Media;
use App\Traits\MediaTraits;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InfographicSeriesController extends Controller
{
    use ResponseJson;
    use MediaTraits;
     /**
     *  Get all the information related to all the Infographic Series found in the system
     * 
     * @return jsonResponseWithoutMessage
     */

    public function index()
    {
        #######ASMAA#######
        //get and display all the series
        $series = InfographicSeries::all();
        if ($series->isNotEmpty()) {
            // found series response
            return $this->jsonResponseWithoutMessage(InfographicSeriesResource::collection($series), 'data', 200);
        } else {
            //not found series response
            throw new NotFound;
        }
    }
      /**
     * Add new Infographic Series to the system with “create infographicSeries” permission
     * Detailed Steps:
     * 1- Validate required data and the image format
     * 2- Add new infographic series to the database if the permission is valid
     * 3- Add the image to the database using MediaTraits
     * 4- Return success or error message

     * @param  Request  $request
     * @return jsonResponse;
     */

    public function create(Request $request)
    {
        #######ASMAA#######

        //create new series and store it in the database

        //validate requested data
        $validator = Validator::make($request->all(), [
            'title'    => 'required',
            'section_id' => 'required',
            'image' => 'required|image|mimes:png,jpg,jpeg,gif,svg|max:2048',
        ]);

        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //authorized user
        if (Auth::user()->can('create infographicSeries')) {
            //create new series
            $infographicSeries = infographicSeries::create($request->all());

            //create media for infographic 
            $this->createMedia($request->file('image'), $infographicSeries->id, 'infographicSeries');

            //success response after creating new infographic Series
            return $this->jsonResponse(new InfographicSeriesResource($infographicSeries), 'data', 200, "infographic Series Created Successfully");
        } else {
            //unauthorized user
            throw new NotAuthorized;
        }
    }
    /**
     * Find existing infographic Series in the system by its id and display it
     * Detailed Steps:
     * 1- Validate required data and the image format.
     * 2- Find the requested infographic series by id.
     * 3- Update the requested infographic series in the database if the permission is valid.
     * 4- Find the media related to the infographic series by seriesID.
     * 5- update the image in the database using MediaTraits.
     * 6- Return success or error message.

     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */

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
        if ($series) {
            //found series response (display its data)
            return $this->jsonResponseWithoutMessage(new InfographicSeriesResource($series), 'data', 200);
        } else {
            //not found series response
            throw new NotFound;
        }
    }
    /**
     *  Update existing infographic Series in the system with “edit infographicSeries” permission
     * 
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function update(Request $request)
    {
        #######ASMAA#######

        //validate requested data
        $validator = Validator::make($request->all(), [
            'title'    => 'required',
            'section_id' => 'required',
            'series_id' => 'required',
            'image' => 'required|image|mimes:png,jpg,jpeg,gif,svg|max:2048',
        ]);

        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //authorized user
        if (Auth::user()->can('edit infographicSeries')) {
            //find needed series
            $series = InfographicSeries::find($request->series_id);

            if ($series) {
                //updated found series
                $series->update($request->all());

                //retrieve InfographicSeries media 
                $infographicSeriesMedia = Media::where('infographic_series_id', $series->id)->first();

                //update media
                if ($infographicSeriesMedia) {
                    $this->updateMedia($request->file('image'), $infographicSeriesMedia->id);
                }
            } else {
                //not found series response
                throw new NotFound;
            }
            //success response after update
            return $this->jsonResponse(new InfographicSeriesResource($series), 'data', 200, "Infographic Series Updated Successfully");
        } else {
            //unauthorized user response
            throw new NotAuthorized;
        }
    }
    /**
     * Find existing infographic Series in the system by its id then delete it with “delete infographicSeries” permission
     * 
     * @param  Request  $request
     * @return jsonResponse;
     */
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

        if (Auth::user()->can('delete infographicSeries')) {
            //find needed series
            $series = InfographicSeries::find($request->series_id);

            if ($series) {
                //retrieve InfographicSeries media 
                $infographicSeriesMedia = Media::where('infographic_series_id', $series->id)->first();

                //keep media with no series id
                if ($infographicSeriesMedia) {
                    $infographicSeriesMedia->infographic_series_id = null;
                    $infographicSeriesMedia->save();
                }

                //delete found series
                $series->delete();

                //delete media
                // $this->deleteMedia($infographicSeriesMedia->id);
            } else {
                //not found series response
                throw new NotFound;
            }
            //success response after delete
            return $this->jsonResponse(new InfographicSeriesResource($series), 'data', 200, "infographic Series Deleted Successfully");
        } else {
            //unauthorized user response
            throw new NotAuthorized;
        }
    }
 /**
     *
     *  Find all the infographic series in the system related to a requested section then return them
     *  Detailed Steps:
     *   1-  Validate required data.
     *   2-  Find the requested infographicSeries by id.
     *   3-  Delete the requested infographicSeries in the database if the permission is valid.
     *   4-  Find the media related to the infographicSeries by SeriesID.
     *   5-  Delete the image from the database using MediaTraits.
     *   6-  Return success or error message.
     *  @param  Request  $request
     *  @return jsonResponseWithoutMessage;
     */

    public function SeriesBySection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'section_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $infographicSeries = infographicSeries::where('section_id', $request->section_id)->get();
        if ($infographicSeries->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(InfographicSeriesResource::collection($infographicSeries), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
}