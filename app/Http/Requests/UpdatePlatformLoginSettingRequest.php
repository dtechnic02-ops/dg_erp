<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformLoginSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(\App\Services\PlatformAuthorizationService::class)
            ->can($this->user(), 'platform_settings_manage');
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'address_line_3' => ['nullable', 'string', 'max:255'],
            'is_published' => ['nullable', 'boolean'],
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_hero_image' => ['nullable', 'boolean'],
            'gallery' => ['nullable', 'array', 'max:5'],
            'gallery.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'gallery.*.existing_path' => ['nullable', 'string', 'max:500'],
            'gallery.*.alt_text' => ['nullable', 'string', 'max:160'],
            'gallery.*.display_order' => ['nullable', 'integer', 'min:0', 'max:100'],
            'gallery.*.is_active' => ['nullable', 'boolean'],
            'gallery.*.remove' => ['nullable', 'boolean'],
        ];
    }
}
