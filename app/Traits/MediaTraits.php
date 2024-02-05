<?php

namespace App\Traits;

use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Image;
use Intervention\Image\Facades\Image as ResizeImage;

trait MediaTraits
{
    function createMedia($media, $type_id, $type, $folderPath = null)
    {
        try {
            $fullPath = 'assets/images' . ($folderPath ? '/' . $folderPath : '');

            //check folder exist
            if (!File::exists(public_path($fullPath))) {
                File::makeDirectory(public_path($fullPath), 0777, true, true);
            }

            $imageName = rand(100000, 999999) . time() . '.';
            $imageFile = null;
            if (is_string($media)) {
                //base64 image
                $image = $media;
                $imageParts = explode(";base64,", $image);
                $imageTypeAux = explode("image/", $imageParts[0]);
                $imageType = $imageTypeAux[1];
                $imageBase64 = base64_decode($imageParts[1]);
                $imageName = $imageName . $imageType;

                $imageFile = $imageBase64;
                //resize image
                ResizeImage::make($imageBase64)->resize(500, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->save(public_path($fullPath . '/' . $imageName));

                // file_put_contents(public_path($fullPath . '/' . $imageName), $imageBase64);
            } else {
                //image file
                $imageName = $imageName . $media->extension();
                $imageFile = $media;

                // $media->move(public_path($fullPath), $imageName);
            }


            // // resize the image to a width of 500 and constrain aspect ratio (auto height)
            ResizeImage::make($imageFile)->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save(public_path($fullPath . '/' . $imageName));

            // link media with comment
            $media = new Media();
            $media->media = $folderPath ? $folderPath . '/' . $imageName : $imageName;
            $media->type = 'image';
            $media->user_id = Auth::id();

            switch ($type) {
                case 'comment':
                    $media->comment_id = $type_id;
                    break;
                case 'post':
                    $media->post_id = $type_id;
                    break;
                case 'infographicSeries':
                    $media->infographic_series_id = $type_id;
                    break;
                case 'infographic':
                    $media->infographic_id = $type_id;
                    break;
                case 'book':
                    $media->book_id = $type_id;
                    break;
                case 'group':
                    $media->group_id = $type_id;
                    break;
                case 'reaction':
                    $media->reaction_id = $type_id;
                    $media->type = $type;
                    break;
                case 'message':
                    $media->message_id = $type_id;
                    $media->type = $type;
                    break;
                case 'user_exception':
                    $media->user_exception_id = $type_id;
                    $media->type = $type;
                    break;
                case 'question':
                    $media->question_id = $type_id;
                    $media->type = $type;
                    break;
                case 'answer':
                    $media->answer_id = $type_id;
                    $media->type = $type;
                    break;
                default:
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

    function createOfficialDocument($imagePath, $imageName)
    {
        $pathToSave = 'assets/images/Official_Document/';
        //check folder exist
        if (!File::exists(public_path($pathToSave))) {
            File::makeDirectory(public_path($pathToSave), 0777, true, true);
        }
        Image::make($imagePath)->resize(500, null, function ($constraint) {
            $constraint->aspectRatio();
        })->save(public_path($pathToSave . '/' . $imageName));
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


    function uploadTest($media, $folderPath = null)
    {
        try {
            $fullPath = 'assets/images' . ($folderPath ? '/' . $folderPath : '');

            //check folder exist
            if (!File::exists(public_path($fullPath))) {
                File::makeDirectory(public_path($fullPath), 0777, true, true);
            }

            $imageName = rand(100000, 999999) . time() . '.';
            $imageFile = null;
            if (is_string($media)) {
                //base64 image
                $image = $media;
                $imageParts = explode(";base64,", $image);
                $imageTypeAux = explode("image/", $imageParts[0]);
                $imageType = $imageTypeAux[1];
                $imageBase64 = base64_decode($imageParts[1]);
                $imageName = $imageName . $imageType;

                $imageFile = $imageBase64;
                //resize image
                ResizeImage::make($imageBase64)->resize(500, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->save(public_path($fullPath . '/' . $imageName));

                // file_put_contents(public_path($fullPath . '/' . $imageName), $imageBase64);
            } else {
                //image file
                $imageName = $imageName . $media->extension();
                $imageFile = $media;

                // $media->move(public_path($fullPath), $imageName);
            }
            ResizeImage::make($imageFile)->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save(public_path($fullPath . '/' . $imageName));
            return true;
        } catch (\Error $e) {
            report($e);
            return false;
        }
    }

    function deleteMediaFiles($files)
    {
        $filesDeleted = [];
        foreach ($files as $file) {
            //check if file exist
            if (File::exists(public_path('assets/images/' . $file))) {
                //delete file
                File::delete(public_path('assets/images/' . $file));
                $filesDeleted[] = $file;
            }
        }

        return $filesDeleted;
    }
}
