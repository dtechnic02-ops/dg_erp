<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CompanyHardResetService
{
    public static function reset(
        int $companyId
    )
    {
        DB::transaction(function () use (
            $companyId
        ) {

            /*
            |--------------------------------------------------------------------------
            | TRANSACTION TABLES
            |--------------------------------------------------------------------------
            */

            // यहाँबाट Transaction Table हरू Delete हुनेछन्


            /*
            |--------------------------------------------------------------------------
            | MASTER TABLES
            |--------------------------------------------------------------------------
            */

            // यहाँबाट Master Table हरू Delete हुनेछन्


            /*
            |--------------------------------------------------------------------------
            | STAFF DELETE
            |--------------------------------------------------------------------------
            */

            // Company Admin बाहेक Staff Delete


            /*
            |--------------------------------------------------------------------------
            | STORAGE DELETE
            |--------------------------------------------------------------------------
            */

            $folder =
                storage_path(
                    'app/public/companies/' .
                    $companyId
                );

            if (
                File::exists($folder)
            )
            {
                File::deleteDirectory(
                    $folder
                );
            }

        });
    }
}