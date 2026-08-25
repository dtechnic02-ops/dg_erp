<?php

namespace App\Http\Controllers;

use App\Services\PlatformSettingService;

class PublicLoginController extends Controller
{
    public function __invoke(PlatformSettingService $settings)
    {
        return view('login', $settings->publicLoginPageData());
    }
}
