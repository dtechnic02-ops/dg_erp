<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserFacingErrorSanitizerService;
use App\Services\UserSessionRevocationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthenticationSessionErrorSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['sessions', 'users', 'roles', 'companies'] as $table) Schema::dropIfExists($table);
        Schema::create('companies', fn (Blueprint $t) => [$t->id(), $t->string('company_name'), $t->string('status')->default('active'), $t->timestamps()]);
        Schema::create('roles', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->timestamps()]);
        Schema::create('users', function (Blueprint $t): void {
            $t->id(); $t->string('name'); $t->string('email')->unique(); $t->string('password');
            $t->unsignedBigInteger('company_id')->nullable(); $t->unsignedBigInteger('country_id')->nullable();
            $t->unsignedBigInteger('role_id'); $t->string('account_status')->default('active');
            $t->timestamp('login_at')->nullable(); $t->timestamp('logout_at')->nullable(); $t->timestamp('last_seen')->nullable();
            $t->rememberToken(); $t->timestamps();
        });
        Schema::create('sessions', function (Blueprint $t): void {
            $t->string('id')->primary(); $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable(); $t->text('user_agent')->nullable();
            $t->longText('payload'); $t->integer('last_activity')->index();
        });
        DB::table('companies')->insert(['id' => 1, 'company_name' => 'Company One']);
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'super_admin'], ['id' => 2, 'name' => 'company_admin'],
            ['id' => 3, 'name' => 'staff'], ['id' => 6, 'name' => 'auditor'],
        ]);
        foreach ([1 => [1, null], 2 => [3, 1], 3 => [2, 1], 4 => [6, 1]] as $id => [$role, $company]) {
            DB::table('users')->insert([
                'id' => $id, 'name' => 'User '.$id, 'email' => "user{$id}@example.test",
                'password' => Hash::make('ValidPassword!1'), 'company_id' => $company,
                'role_id' => $role, 'account_status' => 'active',
            ]);
        }

        Route::middleware(['web', 'auth', 'account.active'])->get('/__b2/protected', fn () => response('allowed'))->name('b2.protected');
        Route::middleware('web')->get('/__b2/unsafe-flash', fn () => redirect('/')->with('error', 'SQLSTATE[23000]: private table detail'));
        Route::middleware('web')->get('/__b2/safe-flash', fn () => redirect('/')->with('error', 'Insufficient stock available.'));
        Route::middleware('web')->get('/__b2/unsafe-json', fn () => response()->json(['message' => 'Undefined variable $secret in C:\\private\\Controller.php on line 9'], 500));
    }

    protected function tearDown(): void
    {
        foreach (['user1@example.test', 'missing@example.test', 'other@example.test'] as $email) {
            RateLimiter::clear($this->loginKey($email, '10.20.30.40'));
        }
        parent::tearDown();
    }

    public function test_active_users_remain_authenticated_while_blocked_roles_are_logged_out(): void
    {
        $this->actingAs(User::findOrFail(2))->get('/__b2/protected')->assertOk()->assertSee('allowed');

        foreach ([2, 3, 4] as $id) {
            $user = User::findOrFail($id);
            $user->update(['account_status' => 'blocked']);
            $this->withSession(['_token' => 'known-token'])->actingAs($user)
                ->get('/__b2/protected')
                ->assertRedirect(route('login'))
                ->assertSessionHas('error', 'Your account is not active. Please contact an administrator.');
            $this->assertGuest();
            $this->assertNotSame('known-token', session()->token());
        }
    }

    public function test_login_limits_failures_by_identity_and_ip_and_success_clears_attempts(): void
    {
        $credentials = ['email' => 'user1@example.test', 'password' => 'wrong'];
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])->post(route('login.post'), $credentials)
                ->assertStatus(302)->assertSessionHas('error', 'Invalid Credentials');
        }
        $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])->post(route('login.post'), $credentials)
            ->assertStatus(429)->assertSessionHas('error', 'Too many login attempts. Please try again later.');

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])->post(route('login.post'), [
            'email' => 'other@example.test', 'password' => 'wrong',
        ])->assertStatus(302)->assertSessionHas('error', 'Invalid Credentials');

        RateLimiter::clear($this->loginKey('user1@example.test', '10.20.30.40'));
        $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])->post(route('login.post'), [
            'email' => 'user1@example.test', 'password' => 'ValidPassword!1',
        ])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs(User::findOrFail(1));
        $this->assertSame(0, RateLimiter::attempts($this->loginKey('user1@example.test', '10.20.30.40')));
    }

    public function test_invalid_credentials_flash_displays_once_then_expires(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])
            ->from(route('login'))
            ->post(route('login.post'), [
                'email' => 'missing@example.test',
                'password' => 'wrong',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Invalid Credentials');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Invalid Credentials');

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Invalid Credentials');
    }

    public function test_blocking_revokes_database_sessions_and_remember_token_without_affecting_other_users(): void
    {
        config(['session.driver' => 'database', 'session.table' => 'sessions']);
        $target = User::findOrFail(2);
        $other = User::findOrFail(3);
        $target->forceFill(['remember_token' => 'target-token'])->save();
        DB::table('sessions')->insert([
            ['id' => 'target-session', 'user_id' => $target->id, 'payload' => 'target', 'last_activity' => time()],
            ['id' => 'other-session', 'user_id' => $other->id, 'payload' => 'other', 'last_activity' => time()],
        ]);

        app(UserSessionRevocationService::class)->revoke($target);

        $this->assertNull($target->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-session', 'user_id' => $other->id]);
    }

    public function test_internal_flash_and_json_details_are_suppressed_but_safe_business_messages_remain(): void
    {
        $this->get('/__b2/unsafe-flash')->assertSessionHas('error', UserFacingErrorSanitizerService::GENERIC_MESSAGE);
        $this->get('/__b2/safe-flash')->assertSessionHas('error', 'Insufficient stock available.');
        $this->getJson('/__b2/unsafe-json')->assertStatus(500)
            ->assertJson(['message' => UserFacingErrorSanitizerService::GENERIC_MESSAGE])
            ->assertDontSee('private')->assertDontSee('secret');
    }

    public function test_normal_logout_still_invalidates_authentication(): void
    {
        $this->actingAs(User::findOrFail(1))->post(route('logout'))->assertRedirect('/login');
        $this->assertGuest();
    }

    private function loginKey(string $email, string $ip): string
    {
        return 'login:'.hash('sha256', strtolower(trim($email)).'|'.$ip);
    }
}
