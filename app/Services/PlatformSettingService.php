<?php

namespace App\Services;

use App\Models\PlatformPaymentGateway;
use App\Models\PlatformSetting;
use App\Models\PlatformSmtpSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PlatformSettingService
{
    private const CACHE_KEY = 'platform_settings.safe_public';

    public function settings(): PlatformSetting
    {
        return PlatformSetting::query()->firstOrCreate([], [
            'platform_name' => 'DG ERP', 'owner_name' => 'DG ERP', 'primary_email' => 'support@example.com', 'primary_mobile' => '',
            'timezone' => 'Asia/Kathmandu', 'currency_code' => 'NPR', 'language_code' => 'en', 'date_format' => 'Y-m-d', 'time_format' => 'H:i', 'default_trial_days' => 0, 'created_by' => auth()->id(),
        ]);
    }

    public function safePublicSettings(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => $this->settings()->only(['platform_name', 'logo_path', 'favicon_path', 'support_email', 'support_mobile', 'website_url', 'timezone', 'currency_code', 'language_code', 'date_format', 'time_format']));
    }

    public function updateGeneral(array $data, int $userId): PlatformSetting
    {
        $setting = $this->settings();
        DB::transaction(function () use ($setting, $data, $userId) { $setting->fill($data); $setting->updated_by = $userId; $setting->save(); });
        $this->clearCache();
        return $setting->refresh();
    }

    public function updateBranding(array $files, int $userId): PlatformSetting
    {
        $setting = $this->settings();
        $fields = ['logo' => 'logo_path', 'favicon' => 'favicon_path', 'signature' => 'signature_path', 'profile_photo' => 'profile_photo_path'];
        $newPaths = []; $oldPaths = [];
        try {
            foreach ($fields as $input => $column) {
                if (($files[$input] ?? null) instanceof UploadedFile) { $newPaths[$column] = $files[$input]->store('platform-settings', 'public'); $oldPaths[] = $setting->{$column}; }
            }
            DB::transaction(function () use ($setting, $newPaths, $userId) { $setting->fill($newPaths); $setting->updated_by = $userId; $setting->save(); });
        } catch (\Throwable $exception) {
            foreach ($newPaths as $path) { Storage::disk('public')->delete($path); }
            throw $exception;
        }
        foreach (array_filter($oldPaths) as $path) { Storage::disk('public')->delete($path); }
        $this->clearCache();
        return $setting->refresh();
    }

    public function updateSocialLinks(array $links): void
    {
        $setting = $this->settings();
        DB::transaction(function () use ($setting, $links) {
            $setting->socialLinks()->delete();
            foreach ($links as $link) {
                if (empty($link['provider']) || empty($link['url']) || !empty($link['remove'])) { continue; }
                $setting->socialLinks()->create(['provider' => strtolower(trim($link['provider'])), 'url' => $link['url'], 'is_active' => (bool) ($link['is_active'] ?? false), 'display_order' => (int) ($link['display_order'] ?? 0)]);
            }
        });
        $this->clearCache();
    }

    public function updateSmtp(array $data): PlatformSmtpSetting
    {
        $setting = $this->settings(); $password = $data['password'] ?? null; unset($data['password']);
        if ($password !== null && $password !== '') { $data['password'] = $password; }
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        return $setting->smtpSetting()->updateOrCreate([], $data);
    }

    public function updateGateway(array $data): PlatformPaymentGateway
    {
        $setting = $this->settings(); $gateway = $data['gateway'];
        foreach (['secret_key', 'webhook_secret'] as $secret) { if (($data[$secret] ?? null) === '') { unset($data[$secret]); } }
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        return $setting->paymentGateways()->updateOrCreate(['gateway' => $gateway], $data);
    }

    public function clearCache(): void { Cache::forget(self::CACHE_KEY); }
}
