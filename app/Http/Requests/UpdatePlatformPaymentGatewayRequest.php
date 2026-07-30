<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformPaymentGatewayRequest extends FormRequest
{
    protected $dontFlash = ['secret_key', 'webhook_secret'];
    public function authorize(): bool { return (int) $this->user()?->role_id === Role::SUPER_ADMIN_ID; }
    public function rules(): array { return ['gateway' => ['required', 'in:esewa,khalti,fonepay,stripe,paypal,razorpay'], 'display_name' => ['required', 'string', 'max:100'], 'environment' => ['required', 'in:sandbox,live'], 'public_key' => ['nullable', 'string', 'max:2000'], 'secret_key' => ['nullable', 'string', 'max:4000'], 'merchant_id' => ['nullable', 'string', 'max:255'], 'webhook_secret' => ['nullable', 'string', 'max:4000'], 'is_active' => ['nullable', 'boolean']]; }
}
