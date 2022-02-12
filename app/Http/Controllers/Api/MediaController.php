<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Media;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
class MediaController extends Controller


{
    use ResponseJson;

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
            'type' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        Media::create($request->all());
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
            'type' => 'required'


        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $media = Media::find($request->media_id);
        $media->update($request->all());
        return $this->jsonResponseWithoutMessage("Media Updated Successfully", 'data', 200);
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
        $media->delete();
        return $this->jsonResponseWithoutMessage("Media Deleted Successfully", 'data', 200);
    }
}