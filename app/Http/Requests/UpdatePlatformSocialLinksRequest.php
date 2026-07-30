<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformSocialLinksRequest extends FormRequest
{
    public function authorize(): bool { return (int) $this->user()?->role_id === Role::SUPER_ADMIN_ID; }
    public function rules(): array { return ['links' => ['nullable', 'array'], 'links.*.provider' => ['nullable', 'string', 'max:60', 'distinct'], 'links.*.url' => ['nullable', 'url', 'max:500'], 'links.*.is_active' => ['nullable', 'boolean'], 'links.*.display_order' => ['nullable', 'integer', 'min:0'], 'links.*.remove' => ['nullable', 'boolean']]; }
}
