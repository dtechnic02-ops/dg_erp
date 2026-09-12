<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\ProtectedCompanyFileService;

class ProtectedCompanyFileController extends Controller
{
    public function show(string $type, int $id, string $field, ProtectedCompanyFileService $files)
    {
        return $files->response($type, $id, $field, request()->boolean('download'));
    }
}
