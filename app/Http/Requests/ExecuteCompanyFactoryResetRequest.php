<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class ExecuteCompanyFactoryResetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user
            && (int) $user->role_id === Role::COMPANY_ADMIN_ID
            && $user->company_id !== null
            && $user->account_status === 'active'
            && Company::query()->whereKey($user->company_id)->where('status', 'active')->exists();
    }

    public function rules(): array
    {
        return [
            'challenge_id' => ['required', 'integer'],
            'current_password' => ['required', 'current_password'],
            'confirmation_phrase' => ['required', 'in:RESET MY COMPANY'],
            'otp' => ['required', 'digits:6'],
        ];
    }
}
