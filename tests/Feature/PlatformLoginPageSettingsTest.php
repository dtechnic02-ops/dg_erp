<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlatformLoginPageSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            Role::SUPER_ADMIN_ID => 'Super Admin',
            Role::COMPANY_ADMIN_ID => 'Company Admin',
            Role::COMPANY_STAFF_ID => 'Company Staff',
            Role::SUPER_STAFF_ID => 'Super Staff',
        ] as $id => $name) {
            Role::query()->create(['id' => $id, 'name' => $name]);
        }
    }

    public function test_super_admin_can_publish_login_information_and_safe_media(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role_id' => Role::SUPER_ADMIN_ID, 'company_id' => null]);
        $setting = $this->platformSetting($admin);

        $this->actingAs($admin)->put(route('admin.platform-settings.login-page.update'), [
            'heading' => 'Business without boundaries',
            'description' => 'A secure ERP platform.',
            'address_line_2' => 'Floor 2',
            'address_line_3' => 'Kathmandu, Nepal',
            'is_published' => '1',
            'hero_image' => UploadedFile::fake()->image('hero.jpg', 1200, 675),
            'gallery' => [[
                'image' => UploadedFile::fake()->image('gallery.png', 800, 600),
                'alt_text' => 'Business dashboard',
                'display_order' => '2',
                'is_active' => '1',
            ]],
        ])->assertRedirect()->assertSessionHas('success');

        $login = $setting->refresh()->loginSetting;
        $this->assertTrue($login->is_published);
        $this->assertStringStartsWith('platform/login/hero/', $login->hero_image_path);
        $this->assertStringStartsWith('platform/login/gallery/', $login->gallery_images[0]['path']);
        Storage::disk('public')->assertExists($login->hero_image_path);
        Storage::disk('public')->assertExists($login->gallery_images[0]['path']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Business without boundaries')
            ->assertSee('Business dashboard')
            ->assertSee(route('login.post'), false)
            ->assertDontSee(route('company.register'), false);
    }

    public function test_replacing_media_deletes_only_the_previous_login_media_after_success(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role_id' => Role::SUPER_ADMIN_ID, 'company_id' => null]);
        $setting = $this->platformSetting($admin);
        Storage::disk('public')->put('platform/login/hero/old.jpg', 'old');
        Storage::disk('public')->put('platform-settings/logo.png', 'shared');
        $setting->loginSetting()->create([
            'hero_image_path' => 'platform/login/hero/old.jpg',
            'gallery_images' => [],
            'is_published' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.platform-settings.login-page.update'), [
            'is_published' => '1',
            'hero_image' => UploadedFile::fake()->image('replacement.webp'),
        ])->assertRedirect();

        Storage::disk('public')->assertMissing('platform/login/hero/old.jpg');
        Storage::disk('public')->assertExists($setting->refresh()->loginSetting->hero_image_path);
        Storage::disk('public')->assertExists('platform-settings/logo.png');
    }

    public function test_company_user_cannot_manage_platform_login_settings(): void
    {
        $companyAdmin = User::factory()->create(['role_id' => Role::COMPANY_ADMIN_ID, 'company_id' => 999]);

        $this->actingAs($companyAdmin)
            ->put(route('admin.platform-settings.login-page.update'), ['heading' => 'Forbidden'])
            ->assertForbidden();
    }

    public function test_unpublished_login_information_is_not_rendered(): void
    {
        $admin = User::factory()->create(['role_id' => Role::SUPER_ADMIN_ID, 'company_id' => null]);
        $setting = $this->platformSetting($admin);
        $setting->loginSetting()->create([
            'heading' => 'Hidden heading',
            'description' => 'Hidden description',
            'gallery_images' => [],
            'is_published' => false,
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Hidden heading')
            ->assertDontSee('Hidden description')
            ->assertSee($setting->platform_name);
    }

    private function platformSetting(User $admin): PlatformSetting
    {
        return PlatformSetting::query()->create([
            'platform_name' => 'DG ERP',
            'owner_name' => 'DG ERP Owner',
            'primary_email' => 'owner@example.test',
            'primary_mobile' => '+9779800000000',
            'whatsapp_number' => '+9779800000000',
            'website_url' => 'https://example.test',
            'full_address' => 'Address line 1',
            'timezone' => 'Asia/Kathmandu',
            'currency_code' => 'NPR',
            'language_code' => 'en',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'default_trial_days' => 0,
            'created_by' => $admin->id,
        ]);
    }
}
