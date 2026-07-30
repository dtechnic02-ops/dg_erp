<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CompleteAdminUserPasswordResetRequest extends FormRequest
{
    protected $dontFlash = ['password', 'password_confirmation'];

    public function authorize(): bool
    {
        return (int) $this->user()?->role_id === Role::SUPER_ADMIN_ID;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ];
    }
}
