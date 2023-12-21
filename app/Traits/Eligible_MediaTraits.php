<?php

namespace App\Traits;

use App\Models\Photos;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;


trait MediaTraits
{

    function createThesisMedia($media, $id)
    {
        $path = Storage::putFile('image', $media);

        try {
            $media = Photos::create(['path' => $path, "thesis_id" => $id]);
        } catch (\Illuminate\Database\QueryException $e) {
            echo ($e);
        }


        return $media;
    }

    function createUserPhoto($media, $user)
    {

        $randomString = Str::random(10);
        $path = Storage::put("/var/www/html/backend/storage/app/image", $media);
        $user->picture = $path;
        $user->save();
        return $user;
    }

    function updateMedia($media, $media_id)
    {
        //get current media
        $currentMedia = Photos::find($media_id);
        //delete current media
        File::delete(public_path('assets/images/' . $currentMedia->media));

        // upload new media
        $imageName = time() . '.' . $media->extension();
        $media->move(public_path('assets/images'), $imageName);

        // update current media
        $currentMedia->media = $imageName;
        $currentMedia->save();
    }


    function deleteMedia($media_id)
    {
        $currentMedia = Photos::find($media_id);
        //delete current media
        File::delete(public_path('assets/images/' . $currentMedia->path));
        $currentMedia->delete();
    }


    function deleteUserPicture($path)
    {
        Storage::delete($path);
    }



    function updateThesisMedia($media, $oldPath)
    {


        $path = Storage::putFile('image', $media);
        Storage::delete($oldPath);


        return $path;
    }


    function deleteThesisMedia($path)
    {



        Storage::delete($path);
    }

    function createMedia($media)
    {
        try {
            $imageName = uniqid('osboha_') . '.' . $media->extension();
            $media->move(public_path('asset/images/temMedia'), $imageName);
            // return media name
            return $imageName;
        } catch (\Error $e) {
            Log::error($e);
        }
    }
    function deleteTempMedia($id)
    {
        $user = User::find($id);
        //delete current media    
        File::delete('asset/images/temMedia/' . $user->picture);
        $user->picture = null;
        $user->save();
    }
}
