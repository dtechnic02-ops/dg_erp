<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformBrandingRequest extends FormRequest
{
    public function authorize(): bool { return (int) $this->user()?->role_id === Role::SUPER_ADMIN_ID; }
    public function rules(): array { return ['logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], 'favicon' => ['nullable', 'mimes:png,ico', 'max:512'], 'signature' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'], 'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]; }
}
