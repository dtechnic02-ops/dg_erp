<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Services\PlatformAuthorizationService;
use Illuminate\Foundation\Http\FormRequest;

class ExecuteCompanyPermanentDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user
            && (int) $user->role_id === Role::SUPER_ADMIN_ID
            && $user->company_id === null
            && $user->account_status === 'active'
            && app(PlatformAuthorizationService::class)->can($user, 'platform_companies_delete');
    }

    public function rules(): array
    {
        return [
            'challenge_id' => ['required', 'integer'],
            'otp' => ['required', 'digits:6'],
            'company_name_confirmation' => ['required', 'string', 'max:255'],
        ];
    }
}
