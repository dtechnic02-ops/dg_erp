<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use App\Services\PlatformRoleBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class PlatformRoleBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_platform_roles_are_idempotent_and_preserve_existing_roles(): void
    {
        DB::table('roles')->insertOrIgnore(['id' => Role::SUPER_ADMIN_ID, 'name' => 'super_admin']);
        $before = DB::table('roles')->where('id', Role::SUPER_ADMIN_ID)->first();

        $service = app(PlatformRoleBootstrapService::class);
        $service->seedRequiredRoles();
        $service->seedRequiredRoles();

        $this->assertDatabaseHas('roles', ['id' => Role::SUPER_STAFF_ID, 'name' => 'super_staff']);
        $this->assertDatabaseHas('roles', ['id' => Role::COUNTRY_ADMIN_ID, 'name' => 'country_admin']);
        $this->assertSame(1, DB::table('roles')->where('id', Role::SUPER_STAFF_ID)->count());
        $this->assertSame(1, DB::table('roles')->where('id', Role::COUNTRY_ADMIN_ID)->count());
        $this->assertEquals($before, DB::table('roles')->where('id', Role::SUPER_ADMIN_ID)->first());
    }

    public function test_reserved_role_id_collision_is_rejected_without_overwrite(): void
    {
        DB::table('roles')->where('id', Role::COUNTRY_ADMIN_ID)->delete();
        DB::table('roles')->insert(['id' => Role::COUNTRY_ADMIN_ID, 'name' => 'unrelated_role']);

        try {
            app(PlatformRoleBootstrapService::class)->seedRequiredRoles();
            $this->fail('Expected reserved platform role collision to be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('conflicts with reserved role ID', $exception->getMessage());
        }

        $this->assertDatabaseHas('roles', ['id' => Role::COUNTRY_ADMIN_ID, 'name' => 'unrelated_role']);
        $this->assertDatabaseMissing('roles', ['id' => Role::COUNTRY_ADMIN_ID, 'name' => 'country_admin']);
    }

    public function test_reserved_role_name_collision_is_rejected_without_renumbering(): void
    {
        DB::table('roles')->where('id', Role::COUNTRY_ADMIN_ID)->delete();
        DB::table('roles')->insert(['id' => 99, 'name' => 'country_admin']);

        try {
            app(PlatformRoleBootstrapService::class)->seedRequiredRoles();
            $this->fail('Expected reserved platform role name collision to be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('conflicts with reserved role ID', $exception->getMessage());
        }

        $this->assertDatabaseHas('roles', ['id' => 99, 'name' => 'country_admin']);
        $this->assertDatabaseMissing('roles', ['id' => Role::COUNTRY_ADMIN_ID]);
    }

    public function test_super_admin_can_create_country_admin_and_super_staff_through_real_post_route(): void
    {
        DB::table('roles')->insertOrIgnore(['id' => Role::SUPER_ADMIN_ID, 'name' => 'super_admin']);
        app(PlatformRoleBootstrapService::class)->seedRequiredRoles();
        $admin = $this->user('Global Admin', Role::SUPER_ADMIN_ID);
        $country = Country::create(['name' => 'Nepal', 'iso_code' => 'NP', 'is_active' => true]);

        foreach ([Role::COUNTRY_ADMIN_ID => 'Country Admin', Role::SUPER_STAFF_ID => 'Super Staff'] as $roleId => $name) {
            $email = str($name)->slug().uniqid().'@example.test';
            $this->actingAs($admin)->post(route('admin.super-staff.store'), [
                'name' => $name,
                'email' => $email,
                'password' => 'password1',
                'password_confirmation' => 'password1',
                'role_id' => $roleId,
                'country_id' => $country->id,
            ])->assertRedirect(route('admin.super-staff.index'))->assertSessionHasNoErrors();

            $this->assertDatabaseHas('users', [
                'email' => $email,
                'role_id' => $roleId,
                'country_id' => $country->id,
                'company_id' => null,
            ]);
        }
    }

    public function test_tampered_role_id_remains_rejected(): void
    {
        DB::table('roles')->insertOrIgnore(['id' => Role::SUPER_ADMIN_ID, 'name' => 'super_admin']);
        $admin = $this->user('Global Admin', Role::SUPER_ADMIN_ID);
        $country = Country::create(['name' => 'Nepal', 'iso_code' => 'NP', 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.super-staff.store'), [
            'name' => 'Tampered Staff',
            'email' => 'tampered@example.test',
            'password' => 'password1',
            'password_confirmation' => 'password1',
            'role_id' => 999,
            'country_id' => $country->id,
        ])->assertSessionHasErrors('role_id');

        $this->assertDatabaseMissing('users', ['email' => 'tampered@example.test']);
    }

    private function user(string $name, int $roleId): User
    {
        return User::create([
            'name' => $name,
            'email' => str($name)->slug().uniqid().'@example.test',
            'password' => Hash::make('password'),
            'role_id' => $roleId,
            'company_id' => null,
            'account_status' => 'active',
        ]);
    }
}
