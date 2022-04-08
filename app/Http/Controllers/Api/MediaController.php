<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Media;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;

class MediaController extends Controller
{
    use ResponseJson;
    use MediaTraits;

    public function index()
    {
        $media = Media::all();
        return $media;
        if($media){
            return $this->jsonResponseWithoutMessage($media, 'data',200);
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'type' => 'required',
            'media' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        // Media::create($request->all()); -- stopped by asmaa

        $this->createMedia($request->file('image'), $request->type_id, $request->type); //asmaa

        return $this->jsonResponseWithoutMessage("Media added Successfully", 'data', 200);

    }


    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'media_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $media = Media::find($request->media_id);
        if($media){
            return $this->jsonResponseWithoutMessage($media, 'data',200);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'media_id' => 'required',
            'type' => 'required',
            'media' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $media = Media::find($request->media_id);

        // $media->update($request->all()); -- stopped by asmaa

        if($media) //asmaa
        {
            $this->updateMedia($request->file('media'), $request->media_id); //asmaa

            return $this->jsonResponseWithoutMessage("Media Updated Successfully", 'data', 200);
        }
        else
        {
            throw new NotFound; //asmaa 
        }        
    }
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'media_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $media = Media::find($request->media_id);

        // $media->delete(); -- stopped by asmaa

        if($media) //asmaa
        {
            $this->deleteMedia($request->media_id); //asmaa

            return $this->jsonResponseWithoutMessage("Media Deleted Successfully", 'data', 200);
        }
        else
        {
            throw new NotFound; //asmaa
        }          
    }
}