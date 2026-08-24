<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'iso_code' => strtoupper(trim((string) $this->input('iso_code'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'iso_code' => [
                'required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/',
                Rule::unique('countries', 'iso_code')->ignore($this->route('country')),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
