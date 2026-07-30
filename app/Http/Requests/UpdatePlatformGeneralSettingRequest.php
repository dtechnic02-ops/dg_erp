<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformGeneralSettingRequest extends FormRequest
{
    public function authorize(): bool { return (int) $this->user()?->role_id === Role::SUPER_ADMIN_ID; }
    public function rules(): array
    {
        return [
            'platform_name' => ['required', 'string', 'max:150'], 'legal_company_name' => ['nullable', 'string', 'max:200'],
            'owner_name' => ['required', 'string', 'max:150'], 'primary_email' => ['required', 'email', 'max:190'],
            'primary_mobile' => ['required', 'string', 'max:30'], 'alternate_mobile' => ['nullable', 'string', 'max:30'],
            'support_email' => ['nullable', 'email', 'max:190'], 'support_mobile' => ['nullable', 'string', 'max:30'], 'whatsapp_number' => ['nullable', 'string', 'max:30'], 'website_url' => ['nullable', 'url', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'], 'state_province' => ['nullable', 'string', 'max:100'], 'district_city' => ['nullable', 'string', 'max:100'], 'municipality' => ['nullable', 'string', 'max:100'], 'ward_number' => ['nullable', 'string', 'max:30'], 'postal_code' => ['nullable', 'string', 'max:30'], 'full_address' => ['nullable', 'string'],
            'tax_number' => ['nullable', 'string', 'max:100'], 'vat_number' => ['nullable', 'string', 'max:100'], 'company_registration_number' => ['nullable', 'string', 'max:100'], 'business_license_number' => ['nullable', 'string', 'max:100'],
            'timezone' => ['required', 'timezone'], 'currency_code' => ['required', 'string', 'size:3'], 'language_code' => ['required', 'string', 'max:10'], 'date_format' => ['required', 'string', 'max:30'], 'time_format' => ['required', 'string', 'max:30'],
            'default_trial_days' => ['required', 'integer', 'min:0'], 'default_staff_limit' => ['nullable', 'integer', 'min:0'], 'default_customer_limit' => ['nullable', 'integer', 'min:0'], 'default_product_limit' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
