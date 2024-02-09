<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Media;
use App\Models\Week;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use Illuminate\Support\Facades\File;
use App\Rules\base64OrImage;
use App\Rules\base64OrImageMaxSize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Termwind\Components\Dd;

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
     *upload media to the system. [for testing]
     *
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => [
                'required',
                new base64OrImage(),
                new base64OrImageMaxSize(1 * 1024 * 1024),
            ],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $folder_path = 'testMedia';
        $this->uploadTest($request->image, $folder_path);

        return $this->jsonResponseWithoutMessage("Media added Successfully", 'data', 200);
    }


    /**
     * Find and show an existing media in the system by its id.
     *
     * @param  media id
     * @return media;
     */
    public function show($id)
    {
        $media = Media::find($id);
        if ($media) {
            try {
                $path = public_path() . '/assets/images/' . $media->media;
                return response()->download($path, rand(100000, 999999) . time());
            } catch (\Exception $e) {
                throw new NotFound;
            }
        } else {
            return $this->jsonResponseWithoutMessage("NOT FOUND", 'data', 404);
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

    /**
     * Remove media files of the old records from the public folder.
     * Keep the records of the current week and the last week.
     * @return JsonResponse;
     */
    public function removeOldMedia()
    {
        Log::channel('media')->info('START');
        //get last week
        $lastWeek = Week::orderBy('id', 'desc')
            ->skip(1)
            ->take(1)
            ->first();

        $created_at = $lastWeek->created_at;

        //get all media records of the last week that has comment_id not null

        $media = DB::table('media')
            ->where('created_at', '<', $created_at)
            ->where('type', 'image')
            ->whereNotNull('comment_id')
            ->pluck('media');

        //delete media files
        try {
            $deletedFiles = $this->deleteMediaFiles($media);

            //log the deleted files
            Log::channel('media')->info('Deleted media files', [
                'deletedFiles' => $deletedFiles,
                'message' => count($deletedFiles) > 0 ? '(' . count($deletedFiles) . ') Media files deleted successfully' : 'No media files deleted'
            ]);
        } catch (\Throwable $th) {
            Log::channel('media')->error('Error while deleting media files', [
                'error' => $th->getMessage() . ' in ' . $th->getFile() . ' at line ' . $th->getLine(),
            ]);
        }
    }
}
