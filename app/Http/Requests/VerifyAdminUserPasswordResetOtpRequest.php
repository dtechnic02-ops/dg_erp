<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class VerifyAdminUserPasswordResetOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(\App\Services\PlatformAuthorizationService::class)->can($this->user(), 'platform_users_manage');
    }

    public function rules(): array
    {
        return ['otp' => ['required', 'digits:6']];
    }
}
