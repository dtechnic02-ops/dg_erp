<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class VerifyAdminUserPasswordResetOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) $this->user()?->role_id === Role::SUPER_ADMIN_ID;
    }

    public function rules(): array
    {
        return ['otp' => ['required', 'digits:6']];
    }
}
