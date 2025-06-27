<?php

namespace App\Traits;

use App\Models\Media;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
// use Image;
use Intervention\Image\Facades\Image as Image;
use Intervention\Image\Facades\Image as ResizeImage;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Constraint\FileExists;
use Symfony\Component\Finder\Finder;

trait MediaTraits
{
    use FileTrait;

    protected $location = 'assets/images/';
    protected $tmpLocation = 'assets/images/tmp/';


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


            if (is_string($media)
                /**&& $this->isValidBase64Image($media)*/
            ) {
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
            // DB::commit(); // stopped by Asmaa | the transaction should be committed at the top level controller

            return $mediaRecord;
        } catch (\Exception $e) {
            // DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => 'Failed to save media'], 500);
        }
    }

    /**
     * Create temporary media file and save it to the database.
     * Saved to a temp path that needs to be moved to the actual path later.
     * use afterCommit to move the media to the actual path to avoid issues with transactions.
     *
     * @param mixed $media The media file or base64 string.
     * @param int $type_id The ID of the type to attach the media to.
     * @param string $type The type of the media (e.g., 'comment', 'post').
     * @param string|null $folderPath Optional folder path to save the media.
     * @return Media The created Media model instance.
     */
    function createTmpMedia($media, $type_id, $type, $folderPath = null)
    {
        $fullPath = $this->tmpLocation . $folderPath;
        $directory = public_path($fullPath);

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0777, true, true);
        }

        $fileName = $this->generateFileName($media);

        $videoFile = null;
        $fileType = null;

        if (is_string($media)
            /**&& $this->isValidBase64Image($media)*/
        ) {
            $this->processBase64Image($media, $fullPath, $fileName);
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
                abort(400, 'Unsupported file type');
            }

            if ($fileType === 'video') {
                $videoFile = $media;
                $videoFile->move($directory, $fileName);
            } else {
                $this->processUploadedImage($media, $fullPath, $fileName);
            }
        } else {
            abort(400, 'Invalid media format');
        }
        // Save to database
        $mediaRecord = new Media();
        $mediaRecord->media = $folderPath ? $folderPath . '/' . $fileName : $fileName;
        $mediaRecord->type = $fileType;
        $mediaRecord->user_id = Auth::id();

        if (!$this->attachMediaToType($mediaRecord, $type, $type_id)) {
            abort(400, 'Invalid media relation type');
        }

        $mediaRecord->save();

        return $mediaRecord;
    }

    function updateMedia($media, $media_id, $folderPath = null)
    {
        //get current media
        $currentMedia = Media::find($media_id);
        //delete current media
        $this->deleteMediaFile($currentMedia->media);

        $fullPath = 'assets/images' . ($folderPath ? '/' . $folderPath : '');
        // upload new media
        $imageName = time() . '.' . $media->extension();
        $media->move(public_path($fullPath), $imageName);

        // update current media
        $currentMedia->media = $folderPath ? $folderPath . '/' . $imageName : $imageName;
        $currentMedia->save();

        $currentMedia = $currentMedia->fresh();

        return $currentMedia;
    }


    function deleteMedia($media_id)
    {
        $currentMedia = Media::find($media_id);
        $this->deleteMediaFile($currentMedia->media);
        $currentMedia->delete();
    }

    function deleteMediaByMedia(Media $media)
    {
        $path = $media->media;
        //delete current media
        $this->deleteMediaFile($path);
        $media->delete();

        return $path;
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
        $this->deleteMediaFile('temMedia/' . $user->picture);
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
                if ($this->deleteMediaFile($file)) {
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
        $status = 0; // Default status
        try {
            $filePath = public_path('assets/images/' . $file);

            if ($this->deleteMediaFile($file)) {
                unset($file); // Free up the variable's memory
                $status = 1; // File deleted successfully
            } else {
                $status = -1; // File does not exist
            }
        } catch (\Exception $e) {
            Log::error("Failed to delete file: {$filePath}. Error: " . $e->getMessage());
        }

        return $status;
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
        if (!File::exists($path)) {
            return;
        }

        $directories = File::directories($path);
        foreach ($directories as $dir) {
            $this->cleanupEmptyDirectories($dir);

            if (File::isDirectory($dir) && File::isEmptyDirectory($dir)) {
                File::deleteDirectory($dir);
            }
        }
    }

    function deleteUntrackedImagesFromThesesByWeek_old($cutoffTimestamp)
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

    function deleteUntrackedImagesFromThesesByWeek($startTime, $endTime)
    {

        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        // $start = Carbon::parse('2024-09-30');
        // $end = Carbon::parse('2024-04-01');


        // dd($startTime, $endTime, $start, $end);
        echo $start->toDateTimeString() . ' ' . $end->toDateTimeString();
        echo '<br>';
        echo $start->toDateString() . ' ' . $end->toDateString();
        $basePath = public_path('assets/images/theses');
        $batchSize = 100; // Number of records to process at a time
        $allDeleted = 0;

        $finder = new Finder();
        $finder->files()
            ->in($basePath)
            ->date('< ' . $start->toDateString())
            ->date('> ' . $end->toDateString());

        $allFiles = iterator_to_array($finder);
        $fileChunks = array_chunk($allFiles, $batchSize);


        Log::channel('media')->info("START Deleting untracked media files from $startTime till $endTime, $batchSize files per chunk");
        Log::channel('media')->info('================================================================================================================');

        // dd(count($allFiles), count($fileChunks), $fileChunks);

        foreach ($fileChunks as $index => $chunk) {
            Log::channel('media')->info("START processing chunk " . ($index + 1) . " of " . count($fileChunks));

            $deleted = 0;

            $filePaths = array_map(function ($file) {
                return str_replace(public_path('assets/images/'), '', $file->getPathname());
            }, $chunk);

            // dd($filePaths);
            $existingFiles = DB::table('media')
                ->whereIn('media', $filePaths)
                ->pluck('media')
                ->toArray();

            // dd($existingFiles);

            Log::channel('media')->info("Found " . count($existingFiles) . " existing files out of " . count($filePaths) . " in the database.");
            Log::channel('media')->info("Deleting " . (count($filePaths) - count($existingFiles)) . " untracked files.");

            foreach ($chunk as $file) {
                $fullPath = $file->getPathname();
                $relativePath = str_replace(public_path('assets/images/'), '', $fullPath);

                // dd($relativePath, in_array($relativePath, $existingFiles));
                if (!in_array($relativePath, $existingFiles)) {
                    File::delete($fullPath);
                    Log::info("Deleted old untracked image: {$fullPath}");
                    $deleted++;
                }
            }

            $allDeleted += $deleted;

            Log::channel('media')->info("Deleted " . $deleted . " untracked files in chunk " . ($index + 1));
            Log::channel('media')->info("FINISHED deleting untracked media files in chunk " . ($index + 1) . " of " . count($fileChunks));
            Log::channel('media')->info("Total deleted files so far: " . $allDeleted);
            Log::channel('media')->info('================================================================================================================');
        }

        Log::channel('media')->info("FINISHED deleting untracked media files from $startTime till $endTime");
        Log::channel('media')->info("Total deleted files: " . $allDeleted);
    }

    private function generateFileName($media)
    {
        return Str::random(12) . '_' . time() . '.' . ($media instanceof \Illuminate\Http\UploadedFile ? $media->extension() : '');
    }

    private function processBase64Image($media, $fullPath, $fileName)
    {
        $imageParts = explode(";base64,", $media);
        if (count($imageParts) != 2) {
            abort(400, "Invalid Base64 Image Format");
        }

        $imageTypeAux = explode("image/", $imageParts[0]);
        if (!isset($imageTypeAux[1])) {
            abort(400, "Invalid base64 format: missing image type.");
        }

        $imageType = $imageTypeAux[1];
        $imageBase64 = base64_decode($imageParts[1]);

        if (!$imageBase64 || !@getimagesizefromstring($imageBase64)) {
            abort(400, "Invalid Base64 Image content");
            // throw new \Exception("Invalid Base64 Image content");
        }

        $fileName .= $imageType;
        // $directory = storage_path('app/' . $fullPath);
        $directory = public_path($fullPath);

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0777, true);
        }

        $imagePath = $directory . '/' . $fileName;

        try {
            Image::make($imageBase64)->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save($imagePath, 90);
        } catch (\Exception $e) {
            abort(400, "Base64 Image processing failed: " . $e->getMessage());
        }

        return $imagePath;
    }

    private function processUploadedImage($media, $fullPath, $fileName)
    {
        try {
            $image = Image::make($media->getRealPath());

            if ($image->width() > 1000) {
                $image->resize(1000, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }

            $imagePath = public_path($fullPath . '/' . $fileName);
            $image->save($imagePath, 90);
        } catch (\Exception $e) {
            abort(400, "Image processing failed: " . $e->getMessage());
        }

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

    public function getMediaContent($filePath)
    {
        $fullPath = public_path($this->location . $filePath);
        return FileTrait::fileContents($fullPath);
    }

    public function deleteMediaFile($filePath)
    {
        $fullPath = public_path($this->location . $filePath);
        return $this->deleteFile($fullPath);
    }

    public function moveTmpMediaToActual(string $logChannel = 'Comments')
    {
        $from = public_path($this->tmpLocation);
        $to = public_path($this->location);

        // dd($from, $to);
        // try {
        // FileTrait::copyDirectory($from, $to);
        // Log::channel($logChannel)->info("Moving media from temporary to actual location: {$from} to {$to}");
        FileTrait::moveDirectory($from, $to);

        // Log::channel($logChannel)->info("Cleaning up empty directories in: {$to}");
        $this->cleanupEmptyDirectories($to);
        // } catch (\Exception $e) {
        // Log::channel($logChannel)->error("Failed to move media from temporary to actual location: {$from} to {$to}. Error: " . $e->getMessage());

        // }
    }

    public function deleteTmpMedia(string $logChannel = 'Comments')
    {
        $tmpPath = public_path($this->tmpLocation);

        try {
            FileTrait::deleteDirectory($tmpPath);
            // $this->
            // deleteDirectory($tmpPath);
            Log::channel($logChannel)->info("Temporary media directory deleted: {$tmpPath}");
        } catch (\Exception $e) {
            Log::channel($logChannel)->error("Failed to delete temporary media directory: {$tmpPath}. Error: " . $e->getMessage());
        }
    }
}
