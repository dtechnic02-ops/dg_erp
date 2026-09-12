<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureAuditorReadOnly;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\Expense;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\ProtectedCompanyFileService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CriticalFileSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['expenses', 'permission_role', 'user_permissions', 'permissions', 'users', 'roles', 'companies'] as $table) Schema::dropIfExists($table);
        Schema::create('companies', fn (Blueprint $t) => [$t->id(), $t->string('company_name'), $t->string('status')->default('active'), $t->timestamps()]);
        Schema::create('roles', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->timestamps()]);
        Schema::create('users', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('email'), $t->string('password'), $t->unsignedBigInteger('company_id'), $t->unsignedBigInteger('role_id'), $t->string('account_status')->default('active'), $t->rememberToken(), $t->timestamps()]);
        Schema::create('permissions', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('scope')->default('company'), $t->timestamps()]);
        Schema::create('user_permissions', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('user_id'), $t->unsignedBigInteger('permission_id'), $t->boolean('is_allowed'), $t->timestamps()]);
        Schema::create('permission_role', fn (Blueprint $t) => [$t->unsignedBigInteger('permission_id'), $t->unsignedBigInteger('role_id')]);
        Schema::create('expenses', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('company_id'), $t->string('attachment')->nullable(), $t->timestamps()]);
        DB::table('companies')->insert([['id' => 1, 'company_name' => 'One'], ['id' => 2, 'company_name' => 'Two']]);
        DB::table('roles')->insert([['id' => 2, 'name' => 'company_admin'], ['id' => 3, 'name' => 'staff'], ['id' => 6, 'name' => 'auditor']]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'One', 'email' => 'one@example.test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 2],
            ['id' => 2, 'name' => 'Two', 'email' => 'two@example.test', 'password' => Hash::make('x'), 'company_id' => 2, 'role_id' => 2],
            ['id' => 3, 'name' => 'Staff', 'email' => 'staff@example.test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 3],
            ['id' => 4, 'name' => 'Auditor', 'email' => 'auditor@example.test', 'password' => Hash::make('x'), 'company_id' => 1, 'role_id' => 6],
        ]);
        DB::table('permissions')->insert([
            ['name' => 'module_expense', 'scope' => 'company'],
            ['name' => 'view_expense', 'scope' => 'company'],
        ]);
        foreach (DB::table('permissions')->pluck('id') as $permissionId) {
            DB::table('permission_role')->insert(['permission_id' => $permissionId, 'role_id' => 6]);
        }
        $this->withoutMiddleware([EnsureCompanyUser::class, EnsureAuditorReadOnly::class, UpdateLastSeen::class, CheckSubscription::class]);
        Storage::fake('local');
    }

    public function test_dangerous_client_filename_cannot_control_private_physical_name(): void
    {
        $temporary = tempnam(sys_get_temp_dir(), 'dg-secure-upload-').'.png';
        file_put_contents($temporary, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true));
        $file = new UploadedFile($temporary, 'invoice.php', 'image/png', null, true);
        $path = FileUploadService::uploadPrivateFile($file, 'companies/1/expenses');

        $this->assertMatchesRegularExpression('#^companies/1/expenses/[0-9a-f-]{36}\.png$#', $path);
        Storage::disk('local')->assertExists(FileUploadService::privatePath($path));
        $this->assertFileDoesNotExist(public_path($path));
        $this->assertStringNotContainsString('invoice.php', $path);
    }

    public function test_ordinary_catalog_media_remains_public_with_a_server_controlled_name(): void
    {
        $temporary = tempnam(sys_get_temp_dir(), 'dg-public-media-').'.png';
        file_put_contents($temporary, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true));
        $file = new UploadedFile($temporary, 'catalog.php', 'image/png', null, true);
        $path = FileUploadService::uploadFile($file, 'companies/999/security-test-media');

        try {
            $this->assertFileExists(public_path($path));
            $this->assertMatchesRegularExpression('#^companies/999/security-test-media/[0-9a-f-]{36}\.png$#', $path);
            $this->assertStringNotContainsString('catalog.php', $path);
            Storage::disk('local')->assertMissing(FileUploadService::privatePath($path));
        } finally {
            FileUploadService::deleteFile($path);
        }
    }

    public function test_protected_delivery_enforces_authentication_and_company_scope(): void
    {
        $path = 'companies/1/expenses/evidence.pdf';
        Storage::disk('local')->put(FileUploadService::privatePath($path), '%PDF synthetic');
        $expense = Expense::query()->create(['company_id' => 1, 'attachment' => $path]);
        $url = route('company.protected-files.show', ['expense', $expense->id, 'attachment']);

        $this->get($url)->assertRedirect();
        $this->actingAs(User::findOrFail(2))->get($url)->assertNotFound();
        $this->actingAs(User::findOrFail(3))->get($url)->assertForbidden();
        $this->actingAs(User::findOrFail(4))->get($url)->assertOk();
        $this->actingAs(User::findOrFail(1))->get($url)->assertOk();
    }

    public function test_path_and_field_manipulation_are_rejected(): void
    {
        $expense = Expense::query()->create(['company_id' => 1, 'attachment' => '../secrets.env']);
        $this->actingAs(User::findOrFail(1))
            ->get(route('company.protected-files.show', ['expense', $expense->id, 'attachment']))
            ->assertNotFound();
        $this->actingAs(User::findOrFail(1))
            ->get(route('company.protected-files.show', ['expense', $expense->id, 'password']))
            ->assertNotFound();

        $files = app(ProtectedCompanyFileService::class);
        $this->assertTrue($files->isUnsafePath('../x'));
        $this->assertTrue($files->isUnsafePath('C:/x'));
        $this->assertTrue($files->isUnsafePath('/etc/passwd'));
    }

    public function test_public_webroot_contains_no_diagnostic_or_unapproved_php_files(): void
    {
        foreach (['info.php', 'test.php', 'info.txt'] as $name) $this->assertFileDoesNotExist(public_path($name));

        $phpFiles = collect(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(public_path(), \FilesystemIterator::SKIP_DOTS)))
            ->filter(fn (\SplFileInfo $file) => $file->isFile() && strtolower($file->getExtension()) === 'php')
            ->map(fn (\SplFileInfo $file) => str_replace('\\', '/', $file->getPathname()))
            ->values();
        $this->assertSame([str_replace('\\', '/', public_path('index.php'))], $phpFiles->all());
        foreach ($phpFiles as $file) {
            $this->assertStringNotContainsString('phpinfo(', strtolower((string) file_get_contents($file)));
        }
    }
}
