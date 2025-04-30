<?php

namespace App\Traits;

use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
// use Image;
use Intervention\Image\Facades\Image as Image;
use Intervention\Image\Facades\Image as ResizeImage;
use Illuminate\Support\Facades\DB;

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

            $fileName = $this->generateFileName($media);

            $imageFile = null;
            $videoFile = null;
            $fileType = null;


            if (is_string($media) && $this->isValidBase64Image($media)) {
                $imageFile = $this->processBase64Image($media, $fullPath, $fileName);
                $fileType = 'image';
            } elseif ($media instanceof \Illuminate\Http\UploadedFile) {
                $allowedMimeTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'image/heic',
                    'image/heif',
                    'video/mp4',
                    'video/quicktime',
                    'video/x-msvideo'
                ];

                $fileType = explode('/', $media->getClientMimeType())[0];
                if (!in_array($media->getClientMimeType(), $allowedMimeTypes)) {
                    return response()->json(['error' => 'Unsupported file type'], 400);
                }

                if ($fileType === 'video') {
                    $videoFile = $media;
                    $videoFile->move(public_path($fullPath), $fileName);
                } else {
                    $this->processUploadedImage($media, $fullPath, $fileName);
                }
            } else {
                return response()->json(['error' => 'Invalid media format'], 400);
            }
            // Save to database
            $mediaRecord = new Media();
            $mediaRecord->media = $folderPath ? $folderPath . '/' . $fileName : $fileName;
            $mediaRecord->type = $fileType;
            $mediaRecord->user_id = Auth::id();

            if (!$this->attachMediaToType($mediaRecord, $type, $type_id)) {
                return response()->json(['error' => 'Invalid media type'], 400);
            }

            $mediaRecord->save();
            DB::commit();

            return $mediaRecord;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => 'Failed to save media'], 500);
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
            $filePath = public_path('assets/images/' . $file);
            try {
                if (File::exists($filePath)) {
                    File::delete($filePath);
                    $filesDeleted[] = $file;
                }
            } catch (\Exception $e) {
                Log::error("Failed to delete file: {$filePath}. Error: " . $e->getMessage());
            }
            unset($file); // Free up the variable's memory
        }
        return $filesDeleted;
    }
    function deleteMedia_v2($file)
    {
        try {
            $filePath = public_path('assets/images/' . $file);
            if (File::exists($filePath)) {
                File::delete($filePath);
                unset($file); // Free up the variable's memory
                return 'deleted';
            }
        } catch (\Exception $e) {
            Log::error("Failed to delete file: {$filePath}. Error: " . $e->getMessage());
        }
    }

    function deleteDirectory($file)
    {
        $filePath = public_path('assets/images/' . ltrim($file, '/'));
        try {
            Log::info("Checking path: {$filePath} - is dir: " . (File::isDirectory($filePath) ? 'yes' : 'no'));

            if (!File::exists($filePath)) {
                Log::warning("Path does not exist: {$filePath}");
                return 'not found';
            }

            if (File::isDirectory($filePath)) {
                File::deleteDirectory($filePath);
                Log::info("Directory deleted: {$filePath}");
                return 'directory deleted';
            } else {
                File::delete($filePath);
                Log::info("File deleted: {$filePath}");
                return 'file deleted';
            }
        } catch (\Exception $e) {
            Log::error("Failed to delete: {$filePath}. Error: " . $e->getMessage());
            return 'error';
        }
    }

    function cleanupEmptyDirectories($path)
    {
        $directories = File::directories($path);
        Log::channel('media')->info("Scanning: {$path}");
        Log::channel('media')->info("Directories found: " . count(File::directories($path)));

        foreach ($directories as $dir) {
            $this->cleanupEmptyDirectories($dir);

            if (File::isDirectory($dir) && File::isEmptyDirectory($dir)) {
                File::deleteDirectory($dir);
                Log::info("Empty directory deleted: {$dir}");
            }
        }
    }

    function deleteUntrackedImagesFromThesesByWeek($cutoffTimestamp)
    {
        $basePath = public_path('assets/images/theses');
        $userFolders = File::directories($basePath);

        foreach ($userFolders as $userFolder) {
            $bookFolders = File::directories($userFolder);

            foreach ($bookFolders as $bookFolder) {
                $images = File::files($bookFolder);

                foreach ($images as $image) {
                    $fullPath = $image->getPathname();
                    $relativePath = str_replace(public_path('assets/images/') . '/', '', $fullPath);
                    $createdAt = $image->getMTime();

                    if ($createdAt < $cutoffTimestamp && !Media::where('media', $relativePath)->exists()) {
                        File::delete($fullPath);
                        Log::info("Deleted old untracked image: {$fullPath}");
                    }
                }
                if (File::isEmptyDirectory($bookFolder)) {
                    File::deleteDirectory($bookFolder);
                    Log::info("Deleted empty book folder: {$bookFolder}");
                }
            }
        }
    }


    private function generateFileName($media)
    {
        return Str::random(12) . '_' . time() . '.' . ($media instanceof \Illuminate\Http\UploadedFile ? $media->extension() : '');
    }

    private function processBase64Image($media, $fullPath, $fileName)
    {
        $imageParts = explode(";base64,", $media);
        $imageTypeAux = explode("image/", $imageParts[0]);
        $imageType = $imageTypeAux[1];

        $imageBase64 = base64_decode($imageParts[1]);

        if (!$imageBase64 || !@getimagesizefromstring($imageBase64)) {
            throw new \Exception("Invalid Base64 Image");
        }

        $fileName .= $imageType;
        $imagePath = storage_path('app/' . $fullPath . '/' . $fileName);

        Image::make($imageBase64)->resize(500, null, function ($constraint) {
            $constraint->aspectRatio();
        })->save($imagePath, 90);

        return $imagePath;
    }

    private function processUploadedImage($media, $fullPath, $fileName)
    {
        $image = Image::make($media->getRealPath());

        if ($image->width() > 1000) {
            $image->resize(1000, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        }

        $imagePath = public_path($fullPath . '/' . $fileName);
        $image->save($imagePath, 90);

        return $imagePath;
    }

    private function attachMediaToType($media, $type, $type_id)
    {
        switch ($type) {
            case 'comment':
                $media->comment_id = $type_id;
                break;
            case 'post':
                $media->post_id = $type_id;
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
            case 'question':
                $media->question_id = $type_id;
                $media->type = $type;
                break;
            case 'answer':
                $media->answer_id = $type_id;
                $media->type = $type;
                break;
            case 'book_report':
                $media->book_report_id = $type_id;
                $media->type = $type;
                break;
            default:
                return false;
        }
        return true;
    }

    public function deleteOfficialDoc($userID)
    {
        $pathToRemove = '/assets/images/Official_Document/' . 'osboha_official_document_' . $userID;

        //get all files with same name no matter what extension is
        $filesToRemove = glob(public_path($pathToRemove . '.*'));

        foreach ($filesToRemove as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
