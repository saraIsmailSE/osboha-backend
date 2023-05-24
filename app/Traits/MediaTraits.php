<?php

namespace App\Traits;

use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Image;

trait MediaTraits
{
    function createMedia($media, $type_id, $type, $folderPath = null)
    {
        try {
            $fullPath = 'assets/images' . ($folderPath ? '/' . $folderPath : '');
            $imageName = rand(100000, 999999) . time() . '.';
            if (is_string($media)) {
                //base64 image
                $image = $media;
                $imageParts = explode(";base64,", $image);
                $imageTypeAux = explode("image/", $imageParts[0]);
                $imageType = $imageTypeAux[1];
                $imageBase64 = base64_decode($imageParts[1]);
                $imageName = $imageName . $imageType;
                file_put_contents(public_path($fullPath . '/' . $imageName), $imageBase64);
            } else {
                //image file
                $imageName = $imageName . $media->extension();
                $media->move(public_path($fullPath), $imageName);
            }

            // link media with comment
            $media = new Media();
            $media->media = $folderPath ? $folderPath . '/' . $imageName : $imageName;
            $media->type = 'image';
            $media->user_id = Auth::id();
            if ($type == 'comment') {
                $media->comment_id = $type_id;
            } elseif ($type == 'post') {
                $media->post_id = $type_id;
            } elseif ($type == 'infographicSeries') {
                $media->infographic_series_id = $type_id;
            } elseif ($type == 'infographic') {
                $media->infographic_id = $type_id;
            } elseif ($type == 'book') {
                $media->book_id = $type_id;
            } elseif ($type == 'group') {
                $media->group_id = $type_id;
            } elseif ($type == 'reaction') {
                $media->reaction_id = $type_id;
                $media->type = $type;
            } else {
                return 'Type Not Found';
            }
            $media->save();
            // dd($imageName);
            return $media;
        } catch (\Error $e) {
            report($e);
            return false;
        }
    }
    function updateMedia($media, $media_id, $folderPath = null)
    {
        //get current media
        $currentMedia = Media::find($media_id);
        //delete current media
        File::delete(public_path('assets/images/' . $currentMedia->media));

        $fullPath = 'assets/images' . ($folderPath ? '/' . $folderPath : '');
        // upload new media
        $imageName = time() . '.' . $media->extension();
        $media->move(public_path($fullPath), $imageName);

        // update current media
        $currentMedia->media = $folderPath ? $folderPath . '/' . $imageName : $imageName;
        $currentMedia->save();
    }


    function deleteMedia($media_id)
    {
        $currentMedia = Media::find($media_id);
        //delete current media        
        File::delete(public_path('assets/images/' . $currentMedia->media));
        $currentMedia->delete();
    }

    function createProfileMedia($media, $folderName)
    {
        $imageName = uniqid('osboha_') . '.' . $media->extension();
        $media->move(public_path('assets/images/profiles/' . $folderName), $imageName);
        // return media name
        return $imageName;
    }

    function resizeImage($width, $hight, $imagePath, $pathToSave, $imageName)
    {
        try {
            $img = Image::make($imagePath)->resize($width, $hight);
            $imageName = $width . 'x' . $hight . '_' . $imageName;
            $img->save($pathToSave . '/' . $imageName);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    function deleteTeProfileMedia($id)
    {
        $user = User::find($id);
        //delete current media    
        File::delete('asset/images/temMedia/' . $user->picture);
        $user->picture = null;
        $user->save();
    }

    function getRandomMediaFileName()
    {
        //get file name from assets/images/
        $files = File::files(public_path('assets/images'));
        $fileNames = [];
        foreach ($files as $file) {
            $fileNames[] = basename($file);
        }
        //get random file name
        $randomFileName = $fileNames[array_rand($fileNames)];
        return $randomFileName;
    }
}