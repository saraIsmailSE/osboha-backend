<?php

namespace App\Traits;

use Illuminate\Support\Facades\File;

trait FileTrait
{
    public static function deleteFile(string $filePath): bool
    {
        if (File::exists($filePath)) {
            return File::delete($filePath);
        }
        return false;
    }

    public static function fileContents(string $filePath): string
    {
        if (File::exists($filePath)) {
            return File::get($filePath);
        }
        return '';
    }

    public static function createDirectory(string $directoryPath): bool
    {
        if (!File::exists($directoryPath)) {
            // return File::makeDirectory($directoryPath, 0755, true);
            return File::makeDirectory($directoryPath, 0777, true);
        }
        return true;
    }

    public static function moveDirectory(string $source, string $destination): bool
    {
        if (!File::exists($source)) {
            return false;
        }

        // dd("source", $source, "destination", $destination);
        FileTrait::createDirectory($destination);

        // $files = File::allFiles($source);
        $directories = File::directories($source);

        // dd("files", $files, "directories", $directories);
        try {
            // foreach ($files as $file) {
            //     $relativePath = str_replace($source, '', $file->getPathname());
            //     $targetDir = $destination . $relativePath;
            //     $this->createDirectory($targetDir);
            //     // dd("source", $source, "file", $file->getPathname(), "relativePath", $relativePath, "targetDir", $targetDir, "file name", $file->getFilename());
            //     File::move($file, $targetDir . '/' . $file->getFilename());
            // }

            foreach ($directories as $dir) {
                $relativePath = str_replace($source, '', $dir);
                $targetDir = $destination . $relativePath;
                FileTrait::createDirectory($targetDir);
                File::copyDirectory($dir, $targetDir);
                File::deleteDirectory($dir);
            }

            return true;
        } catch (\Throwable $e) {
            abort(500, "Error moving files: " . $e->getMessage());
            return false;
        }
        // return File::moveDirectory($source, $destination);
    }

    public static function copyDirectory(string $source, string $destination): bool
    {
        if (!File::exists($source)) {
            return false;
        }
        FileTrait::createDirectory($destination);
        return File::copyDirectory($source, $destination);
    }

    public static function deleteDirectory(string $directoryPath): bool
    {
        if (File::exists($directoryPath)) {
            return File::deleteDirectory($directoryPath);
        }
        return false;
    }
}
