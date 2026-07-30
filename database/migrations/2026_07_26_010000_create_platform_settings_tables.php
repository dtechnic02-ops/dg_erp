<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('platform_name', 150);
            $table->string('legal_company_name', 200)->nullable();
            $table->string('owner_name', 150);
            $table->string('primary_email', 190);
            $table->string('primary_mobile', 30);
            $table->string('alternate_mobile', 30)->nullable();
            $table->string('support_email', 190)->nullable();
            $table->string('support_mobile', 30)->nullable();
            $table->string('whatsapp_number', 30)->nullable();
            $table->string('website_url')->nullable();
            $table->string('country', 100)->nullable();
            $table->string('state_province', 100)->nullable();
            $table->string('district_city', 100)->nullable();
            $table->string('municipality', 100)->nullable();
            $table->string('ward_number', 30)->nullable();
            $table->string('postal_code', 30)->nullable();
            $table->text('full_address')->nullable();
            $table->string('tax_number', 100)->nullable();
            $table->string('vat_number', 100)->nullable();
            $table->string('company_registration_number', 100)->nullable();
            $table->string('business_license_number', 100)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->string('timezone', 100)->default('Asia/Kathmandu');
            $table->string('currency_code', 3)->default('NPR');
            $table->string('language_code', 10)->default('en');
            $table->string('date_format', 30)->default('Y-m-d');
            $table->string('time_format', 30)->default('H:i');
            $table->unsignedInteger('default_trial_days')->default(0);
            $table->unsignedInteger('default_staff_limit')->nullable();
            $table->unsignedInteger('default_customer_limit')->nullable();
            $table->unsignedInteger('default_product_limit')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('platform_social_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_setting_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 60);
            $table->string('url', 500);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['platform_setting_id', 'provider'], 'platform_social_setting_provider_unique');
        });

        Schema::create('platform_smtp_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_setting_id')->constrained()->cascadeOnDelete();
            $table->string('mailer', 50)->default('smtp');
            $table->string('host');
            $table->unsignedSmallInteger('port');
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('encryption', 20)->nullable();
            $table->string('from_address', 190);
            $table->string('from_name', 150);
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
            $table->unique('platform_setting_id');
        });

        Schema::create('platform_payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_setting_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 50);
            $table->string('display_name', 100);
            $table->string('environment', 20)->default('sandbox');
            $table->text('public_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->string('merchant_id')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->json('additional_config')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->unique(['platform_setting_id', 'gateway'], 'platform_gateway_setting_gateway_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_payment_gateways');
        Schema::dropIfExists('platform_smtp_settings');
        Schema::dropIfExists('platform_social_links');
        Schema::dropIfExists('platform_settings');
    }
};
