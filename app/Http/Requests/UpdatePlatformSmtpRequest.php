<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformSmtpRequest extends FormRequest
{
    protected $dontFlash = ['password'];
    public function authorize(): bool { return (int) $this->user()?->role_id === Role::SUPER_ADMIN_ID; }
    public function rules(): array { return ['mailer' => ['required', 'in:smtp'], 'host' => ['required', 'string', 'max:255'], 'port' => ['required', 'integer', 'min:1', 'max:65535'], 'username' => ['nullable', 'string', 'max:255'], 'password' => ['nullable', 'string'], 'encryption' => ['nullable', 'in:tls,ssl,starttls'], 'from_address' => ['required', 'email', 'max:190'], 'from_name' => ['required', 'string', 'max:150'], 'is_active' => ['nullable', 'boolean']]; }
}
