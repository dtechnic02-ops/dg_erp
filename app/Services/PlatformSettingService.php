<?php

namespace App\Services;

use App\Models\PlatformPaymentGateway;
use App\Models\PlatformSetting;
use App\Models\PlatformSmtpSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PlatformSettingService
{
    private const CACHE_KEY = 'platform_settings.safe_public';
    private const LOGIN_MEDIA_PREFIX = 'platform/login/';

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

    public function publicLoginPageData(): array
    {
        if (!Schema::hasTable('platform_settings')) {
            return $this->defaultLoginPageData();
        }

        $setting = $this->settings();
        $login = Schema::hasTable('platform_login_settings') ? $setting->loginSetting()->first() : null;
        $published = $login?->is_published === true;
        $gallery = $published
            ? collect($login->gallery_images ?? [])->where('is_active', true)->sortBy('display_order')->values()->all()
            : [];

        return [
            'platformSetting' => $setting,
            'loginSetting' => $published ? $login : null,
            'loginGallery' => $gallery,
            'loginLinks' => $published && Schema::hasTable('platform_social_links')
                ? $setting->socialLinks()->where('is_active', true)->get()
                : collect(),
        ];
    }

    public function updateLoginPage(array $data, int $userId): PlatformSetting
    {
        $setting = $this->settings()->loadMissing('loginSetting');
        $login = $setting->loginSetting;
        $oldHero = $login?->hero_image_path;
        $oldGallery = collect($login?->gallery_images ?? [])->pluck('path')->filter()->all();
        $newPaths = [];

        try {
            $heroPath = $oldHero;
            if (($data['hero_image'] ?? null) instanceof UploadedFile) {
                $heroPath = $data['hero_image']->store(self::LOGIN_MEDIA_PREFIX.'hero', 'public');
                $newPaths[] = $heroPath;
            } elseif (!empty($data['remove_hero_image'])) {
                $heroPath = null;
            }

            $gallery = [];
            foreach ($data['gallery'] ?? [] as $index => $item) {
                if (!empty($item['remove'])) {
                    continue;
                }

                $path = $this->validatedExistingLoginMediaPath($item['existing_path'] ?? null, $oldGallery);
                if (($item['image'] ?? null) instanceof UploadedFile) {
                    $path = $item['image']->store(self::LOGIN_MEDIA_PREFIX.'gallery', 'public');
                    $newPaths[] = $path;
                }
                if (!$path) {
                    continue;
                }

                $gallery[] = [
                    'path' => $path,
                    'alt_text' => trim((string) ($item['alt_text'] ?? '')),
                    'display_order' => (int) ($item['display_order'] ?? $index),
                    'is_active' => (bool) ($item['is_active'] ?? false),
                ];
            }

            usort($gallery, fn (array $left, array $right): int => $left['display_order'] <=> $right['display_order']);

            DB::transaction(function () use ($setting, $data, $userId, $heroPath, $gallery): void {
                $setting->loginSetting()->updateOrCreate([], [
                    'heading' => $data['heading'] ?? null,
                    'description' => $data['description'] ?? null,
                    'hero_image_path' => $heroPath,
                    'address_line_2' => $data['address_line_2'] ?? null,
                    'address_line_3' => $data['address_line_3'] ?? null,
                    'gallery_images' => $gallery,
                    'is_published' => (bool) ($data['is_published'] ?? false),
                    'updated_by' => $userId,
                ]);
            });
        } catch (\Throwable $exception) {
            $this->deleteLoginMedia($newPaths);
            throw $exception;
        }

        $retained = array_merge([$heroPath], array_column($gallery, 'path'));
        $this->deleteLoginMedia(array_diff(array_merge([$oldHero], $oldGallery), array_filter($retained)));
        $this->clearCache();

        return $setting->refresh()->load('loginSetting');
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

    private function validatedExistingLoginMediaPath(?string $path, array $knownPaths): ?string
    {
        if (!$path || !in_array($path, $knownPaths, true)) {
            return null;
        }

        return str_starts_with(str_replace('\\', '/', $path), self::LOGIN_MEDIA_PREFIX) ? $path : null;
    }

    private function defaultLoginPageData(): array
    {
        return [
            'platformSetting' => new PlatformSetting(['platform_name' => 'DG ERP']),
            'loginSetting' => null,
            'loginGallery' => [],
            'loginLinks' => collect(),
        ];
    }

    private function deleteLoginMedia(array $paths): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            $normalized = str_replace('\\', '/', $path);
            if (str_starts_with($normalized, self::LOGIN_MEDIA_PREFIX) && !str_contains($normalized, '../')) {
                Storage::disk('public')->delete($normalized);
            }
        }
    }
}
