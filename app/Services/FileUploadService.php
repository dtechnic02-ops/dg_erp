<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class FileUploadService
{
    /**
     * Create folder if not exists
     */
    private const MIME_EXTENSIONS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * Upload and resize image
     */
    public static function uploadPrivateImage(
        $file,
        string $folder,
        int $width = 600
    ): string
    {
        $filename = (string) Str::uuid().'.jpg';

        $manager =
            new ImageManager(
                new Driver()
            );

      $image =
    $manager->decode(
        $file
    );


    $image->scale(
    width: $width
);

        $encoded = $image->toJpeg();
        Storage::disk('local')->put(self::privatePath($folder.'/'.$filename), (string) $encoded);

        return
            $folder.'/'.$filename;
    }

    /**
     * Upload normal file
     */
    public static function uploadPrivateFile(
        $file,
        string $folder
    ): string
    {
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new \InvalidArgumentException('The uploaded file is invalid.');
        }

        $mime = (string) $file->getMimeType();
        $extension = self::MIME_EXTENSIONS[$mime] ?? null;
        if ($extension === null) {
            throw new \InvalidArgumentException('This file type is not allowed.');
        }

        $filename = (string) Str::uuid().'.'.$extension;
        Storage::disk('local')->putFileAs(self::privatePath($folder), $file, $filename);

        return
            $folder.'/'.$filename;
    }

    public static function uploadImage($file, string $folder, int $width = 600): string
    {
        $filename = (string) Str::uuid().'.jpg';
        $directory = public_path($folder);
        if (!is_dir($directory)) mkdir($directory, 0755, true);
        $image = (new ImageManager(new Driver()))->decode($file);
        $image->scale(width: $width);
        $image->save($directory.DIRECTORY_SEPARATOR.$filename);
        return $folder.'/'.$filename;
    }

    public static function uploadFile($file, string $folder): string
    {
        if (!$file instanceof UploadedFile || !$file->isValid()) throw new \InvalidArgumentException('The uploaded file is invalid.');
        $extension = self::MIME_EXTENSIONS[(string) $file->getMimeType()] ?? null;
        if ($extension === null) throw new \InvalidArgumentException('This file type is not allowed.');
        $filename = (string) Str::uuid().'.'.$extension;
        $directory = public_path($folder);
        if (!is_dir($directory)) mkdir($directory, 0755, true);
        $file->move($directory, $filename);
        return $folder.'/'.$filename;
    }

    /**
     * Delete file
     */
    public static function deleteFile(
        ?string $path
    ): void
    {
        if ($path) {
            Storage::disk('local')->delete(self::privatePath($path));

            // Transitional cleanup for a legacy file after its owning record has
            // already been resolved by the calling company-scoped workflow.
            $legacy = public_path(ltrim($path, '/\\'));
            if (is_file($legacy)) {
                unlink($legacy);
            }
        }
    }

    /**
     * Replace normal file
     */
    public static function replaceFile(
        Request $request,
        string $field,
        ?string $oldFile,
        string $folder
    ): ?string
    {
        if (
            !$request->hasFile(
                $field
            )
        ) {
            return $oldFile;
        }

        self::deleteFile(
            $oldFile
        );

        return self::uploadFile(
            $request->file($field),
            $folder
        );
    }

    /**
     * Replace image
     */
    public static function replaceImage(
        Request $request,
        string $field,
        ?string $oldFile,
        string $folder,
        int $width = 700
    ): ?string
    {
        if (
            !$request->hasFile(
                $field
            )
        ) {
            return $oldFile;
        }

        self::deleteFile(
            $oldFile
        );

        return self::uploadImage(
            $request->file($field),
            $folder,
            $width
        );
    }

    public static function privatePath(string $path): string
    {
        return 'protected/'.ltrim(str_replace('\\', '/', $path), '/');
    }

    public static function replacePrivateFile(Request $request, string $field, ?string $oldFile, string $folder): ?string
    {
        if (!$request->hasFile($field)) return $oldFile;
        self::deleteFile($oldFile);
        return self::uploadPrivateFile($request->file($field), $folder);
    }

    public static function replacePrivateImage(Request $request, string $field, ?string $oldFile, string $folder, int $width = 700): ?string
    {
        if (!$request->hasFile($field)) return $oldFile;
        self::deleteFile($oldFile);
        return self::uploadPrivateImage($request->file($field), $folder, $width);
    }
}
