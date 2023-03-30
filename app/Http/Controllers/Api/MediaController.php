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

    /**
     * Read all media in the system.
     * 
     * @return jsonResponseWithoutMessage;
     */
    public function index()
    {
        $media = Media::all();
        return $media;
        if ($media) {
            return $this->jsonResponseWithoutMessage($media, 'data', 200);
        }
    }
    /**
     *Add a new media to the system.
     * 
     * @param  Request  $request
     * @return jsonResponse;
     */
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


    /**
     * Find and show an existing media in the system by its id.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'media_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $media = Media::find($request->media_id);
        if ($media) {
            return $this->jsonResponseWithoutMessage($media, 'data', 200);
        }
    }

    /**
     * Update an existing media’s.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
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

        if ($media) //asmaa
        {
            $this->updateMedia($request->file('media'), $request->media_id); //asmaa

            return $this->jsonResponseWithoutMessage("Media Updated Successfully", 'data', 200);
        } else {
            throw new NotFound; //asmaa 
        }
    }

    /**
     * Delete an existing media’s in the system using its id.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
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

        if ($media) //asmaa
        {
            $this->deleteMedia($request->media_id); //asmaa

            return $this->jsonResponseWithoutMessage("Media Deleted Successfully", 'data', 200);
        } else {
            throw new NotFound; //asmaa
        }
    }

    public function get_image($folder)
    {

        if (isset($_GET['fileName'])) {
            $path = public_path() . '/asset/images/' . $folder . '/' . $_GET['fileName'];
            return response()->download($path, $_GET['fileName']);
        } else {
            return $this->sendError('file nout found');
        }
    }
}
