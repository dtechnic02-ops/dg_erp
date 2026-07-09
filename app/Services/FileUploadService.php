<?php

namespace App\Services;

use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class FileUploadService
{
    /**
     * Create folder if not exists
     */
    private static function ensureFolder(
        string $folder
    ): void
    {
        if (
            !file_exists(
                public_path($folder)
            )
        ) {
            mkdir(
                public_path($folder),
                0755,
                true
            );
        }
    }

    /**
     * Upload and resize image
     */
    public static function uploadImage(
        $file,
        string $folder,
        int $width = 600
    ): string
    {
        self::ensureFolder(
            $folder
        );

        $filename =
            time()
            .'_'
            .uniqid()
            .'.jpg';

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

        $image->save(
            public_path(
                $folder.'/'.$filename
            )
        );

        return
            $folder.'/'.$filename;
    }

    /**
     * Upload normal file
     */
    public static function uploadFile(
        $file,
        string $folder
    ): string
    {
        self::ensureFolder(
            $folder
        );

    $filename =
    time()
    .'_'
    .uniqid()
    .'.'
    .$file->getClientOriginalExtension();

        $file->move(
            public_path($folder),
            $filename
        );

        return
            $folder.'/'.$filename;
    }

    /**
     * Delete file
     */
    public static function deleteFile(
        ?string $path
    ): void
    {
        if (
            $path &&
            file_exists(
                public_path($path)
            )
        ) {
            unlink(
                public_path($path)
            );
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
}