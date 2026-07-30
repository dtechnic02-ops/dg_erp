<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlatformSetting extends Model
{
    protected $fillable = [
        'platform_name', 'legal_company_name', 'owner_name', 'primary_email', 'primary_mobile',
        'alternate_mobile', 'support_email', 'support_mobile', 'whatsapp_number', 'website_url',
        'country', 'state_province', 'district_city', 'municipality', 'ward_number', 'postal_code',
        'full_address', 'tax_number', 'vat_number', 'company_registration_number', 'business_license_number',
        'logo_path', 'favicon_path', 'signature_path', 'profile_photo_path', 'timezone', 'currency_code',
        'language_code', 'date_format', 'time_format', 'default_trial_days', 'default_staff_limit',
        'default_customer_limit', 'default_product_limit', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['default_trial_days' => 'integer', 'default_staff_limit' => 'integer', 'default_customer_limit' => 'integer', 'default_product_limit' => 'integer'];
    }

    public function socialLinks(): HasMany { return $this->hasMany(PlatformSocialLink::class)->orderBy('display_order'); }
    public function smtpSetting(): HasOne { return $this->hasOne(PlatformSmtpSetting::class); }
    public function paymentGateways(): HasMany { return $this->hasMany(PlatformPaymentGateway::class); }
}
