<?php

namespace Tests\Feature;

use Database\Seeders\RoleSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class HostingBootstrapSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('account_status')->default('active');
            $table->timestamps();
        });

        $this->seed(RoleSeeder::class);
    }

    public function test_secure_bootstrap_admin_is_created_once_without_password_rotation(): void
    {
        config()->set('dg-erp.bootstrap_admin', [
            'name' => 'Platform Administrator',
            'email' => 'admin@example.test',
            'password' => 'a-unique-password-123',
        ]);

        $this->seed(SuperAdminSeeder::class);

        $admin = \App\Models\User::query()->where('email', 'admin@example.test')->sole();
        $this->assertSame(1, (int) $admin->role_id);
        $this->assertNull($admin->company_id);
        $this->assertTrue(Hash::check('a-unique-password-123', $admin->password));

        config()->set('dg-erp.bootstrap_admin.password', 'another-secure-password');
        $this->seed(SuperAdminSeeder::class);

        $this->assertSame(1, \App\Models\User::query()->where('email', 'admin@example.test')->count());
        $this->assertTrue(Hash::check('a-unique-password-123', $admin->fresh()->password));
    }

    public function test_invalid_bootstrap_credentials_are_rejected(): void
    {
        config()->set('dg-erp.bootstrap_admin', [
            'name' => 'Platform Administrator',
            'email' => 'not-an-email',
            'password' => 'short',
        ]);

        $this->expectException(RuntimeException::class);
        $this->seed(SuperAdminSeeder::class);
    }
}
