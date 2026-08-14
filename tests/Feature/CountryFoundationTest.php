<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CompanyApprovalController;
use App\Models\Company;
use App\Models\CompanyRegistration;
use App\Models\Country;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CountryFoundationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['company_subscriptions','subscription_plans','user_permissions','permission_role','permissions','users','roles','countries','company_registrations','companies'] as $table) Schema::dropIfExists($table);
        Schema::create('companies', function(Blueprint $t){$t->id();$t->string('company_name');$t->string('mobile')->unique();$t->string('email')->unique();foreach(['telephone','fax_no','website','address','address_line_2','country','language','pan_number','vat_number','logo_path','signature_path'] as $c)$t->string($c)->nullable();$t->string('status')->default('active');$t->timestamps();});
        Schema::create('company_registrations', function(Blueprint $t){$t->id();$t->string('company_name');$t->string('full_name');$t->string('email')->unique();$t->string('username')->unique();$t->string('password');$t->string('mobile_no')->nullable();$t->string('country')->nullable();$t->integer('selected_user_limit')->default(5);$t->string('status')->default('pending');$t->timestamps();});
        DB::table('companies')->insert([['id'=>1,'company_name'=>'Nepal Legacy','mobile'=>'100','email'=>'one@example.test','country'=>'NEPAL'],['id'=>2,'company_name'=>'Unmapped Legacy','mobile'=>'200','email'=>'two@example.test','country'=>'Atlantis']]);
        DB::table('company_registrations')->insert([['id'=>1,'company_name'=>'NP Registration','full_name'=>'One','email'=>'reg@example.test','username'=>'reg','password'=>'x','mobile_no'=>'300','country'=>'NP','status'=>'pending'],['id'=>2,'company_name'=>'Unknown Registration','full_name'=>'Two','email'=>'unknown@example.test','username'=>'unknown','password'=>'x','mobile_no'=>'400','country'=>'Unknown Land','status'=>'pending']]);
        Log::spy();
        (require database_path('migrations/2026_08_14_000000_create_country_master_and_company_relations.php'))->up();

        Schema::create('roles', fn(Blueprint $t)=>[$t->id(),$t->string('name'),$t->timestamps()]);
        Schema::create('users', function(Blueprint $t){$t->id();$t->string('name');$t->string('email')->unique();$t->string('password');$t->unsignedBigInteger('role_id')->nullable();$t->unsignedBigInteger('company_id')->nullable();$t->string('account_status')->default('active');$t->timestamp('last_seen')->nullable();$t->rememberToken();$t->timestamps();});
        Schema::create('permissions', fn(Blueprint $t)=>[$t->id(),$t->string('name'),$t->string('scope'),$t->timestamps()]);
        Schema::create('permission_role', fn(Blueprint $t)=>[$t->id(),$t->unsignedBigInteger('permission_id'),$t->unsignedBigInteger('role_id'),$t->timestamps()]);
        Schema::create('user_permissions', fn(Blueprint $t)=>[$t->id(),$t->unsignedBigInteger('user_id'),$t->unsignedBigInteger('permission_id'),$t->boolean('is_allowed'),$t->timestamps()]);
        Schema::create('subscription_plans', function(Blueprint $t){$t->id();$t->string('code');$t->string('name');$t->text('description')->nullable();$t->unsignedInteger('staff_limit')->default(1);$t->json('hidden_modules')->nullable();$t->boolean('is_active')->default(true);$t->unsignedInteger('sort_order')->default(0);$t->unsignedBigInteger('created_by')->nullable();$t->unsignedBigInteger('updated_by')->nullable();$t->timestamp('cancelled_at')->nullable();$t->unsignedBigInteger('cancelled_by')->nullable();$t->text('cancel_reason')->nullable();$t->timestamps();});
        DB::table('roles')->insert([['id'=>1,'name'=>'Super Admin'],['id'=>2,'name'=>'Company Admin']]);
        DB::table('users')->insert([['id'=>1,'name'=>'Owner One','email'=>'owner1@example.test','password'=>Hash::make('x'),'role_id'=>2,'company_id'=>1],['id'=>2,'name'=>'Owner Two','email'=>'owner2@example.test','password'=>Hash::make('x'),'role_id'=>2,'company_id'=>2],['id'=>99,'name'=>'Platform Admin','email'=>'admin@example.test','password'=>Hash::make('x'),'role_id'=>1,'company_id'=>null]]);
        foreach([['module_company_profile','company'],['view_company_profile','company'],['edit_company_profile','company']] as [$name,$scope])DB::table('permissions')->insert(['name'=>$name,'scope'=>$scope]);
        DB::table('subscription_plans')->insert(['id'=>1,'code'=>'trial','name'=>'Trial','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
    }

    public function test_country_master_is_unique_and_nepal_is_canonical_once(): void
    {
        $this->assertDatabaseHas('countries',['name'=>'Nepal','iso_code'=>'NP','is_active'=>1]);
        $this->assertSame(1,Country::where('iso_code','NP')->count());
        Country::create(['name'=>'India','iso_code'=>'IN','is_active'=>1]);
        try{Country::create(['name'=>'Duplicate India','iso_code'=>'in','is_active'=>1]);$this->fail('Duplicate ISO accepted.');}catch(QueryException){$this->assertSame(1,Country::where('iso_code','IN')->count());}
        try{$nepal=Country::where('iso_code','NP')->sole();$nepal->update(['is_active'=>0]);$this->fail('Canonical Nepal was deactivated.');}catch(\Illuminate\Validation\ValidationException){$this->assertTrue(Country::where('iso_code','NP')->sole()->is_active);}
    }

    public function test_country_master_reuses_platform_settings_permission(): void
    {
        $this->actingAs(User::findOrFail(99));
        $this->post(route('admin.countries.store'),['name'=>'India','iso_code'=>'in','is_active'=>1])->assertRedirect();
        $india=Country::where('iso_code','IN')->sole();
        $this->put(route('admin.countries.update',$india),['name'=>'Republic of India','iso_code'=>'IN','is_active'=>1])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Republic of India',$india->fresh()->name);
        $this->actingAs(User::findOrFail(1))->post(route('admin.countries.store'),['name'=>'Bhutan','iso_code'=>'BT','is_active'=>1])->assertForbidden();
    }

    public function test_legacy_nepal_maps_and_unmapped_values_are_reported_and_unchanged(): void
    {
        $nepal=Country::where('iso_code','NP')->sole();
        $this->assertSame($nepal->id,Company::find(1)->country_id);
        $this->assertSame($nepal->id,CompanyRegistration::find(1)->country_id);
        $this->assertNull(Company::find(2)->country_id);$this->assertSame('Atlantis',Company::find(2)->country);
        $this->assertNull(CompanyRegistration::find(2)->country_id);$this->assertSame('Unknown Land',CompanyRegistration::find(2)->country);
        Log::shouldHaveReceived('warning')->withArgs(fn($message,$context)=>str_contains($message,'Unmapped legacy country')&&in_array($context['table'],['companies','company_registrations'],true))->twice();
    }

    public function test_registration_dropdown_saves_only_active_country_id(): void
    {
        $nepal=Country::where('iso_code','NP')->sole();
        $this->get(route('company.register'))->assertOk()->assertSee('name="country_id"',false)->assertDontSee('name="country"',false);
        $payload=['company_name'=>'New Co','full_name'=>'New Owner','email'=>'new@example.test','mobile_no'=>'500','username'=>'new-owner','password'=>'secret1','country_id'=>$nepal->id];
        $this->post(route('company.register.post'),$payload)->assertRedirect(route('login'));
        $this->assertDatabaseHas('company_registrations',['email'=>'new@example.test','country_id'=>$nepal->id]);
        $inactive=Country::create(['name'=>'Inactive','iso_code'=>'ZZ','is_active'=>0]);$payload['email']='bad@example.test';$payload['username']='bad-owner';$payload['mobile_no']='501';$payload['country_id']=$inactive->id;
        $this->post(route('company.register.post'),$payload)->assertSessionHasErrors('country_id');
    }

    public function test_profile_dropdown_updates_only_authenticated_company_and_rejects_inactive_country(): void
    {
        $nepal=Country::where('iso_code','NP')->sole();$this->withoutMiddleware();$this->actingAs(User::findOrFail(1));
        $template=file_get_contents(resource_path('views/company/profile.blade.php'));$this->assertStringContainsString('name="country_id"',$template);$this->assertStringNotContainsString('name="country"',$template);
        $this->post(route('company.profile.update'),['company_name'=>'Updated One','email'=>'one@example.test','mobile'=>'100','country_id'=>$nepal->id])->assertRedirect();
        $this->assertSame($nepal->id,Company::find(1)->country_id);$this->assertNull(Company::find(2)->country_id);
        $inactive=Country::create(['name'=>'Inactive','iso_code'=>'ZZ','is_active'=>0]);
        $this->post(route('company.profile.update'),['company_name'=>'Rejected','email'=>'one@example.test','mobile'=>'100','country_id'=>$inactive->id])->assertSessionHasErrors('country_id');
        $this->assertSame('Updated One',Company::find(1)->company_name);
    }

    public function test_approval_transfers_active_country_id_and_rejects_inactive_country(): void
    {
        $nepal=Country::where('iso_code','NP')->sole();
        $registration=CompanyRegistration::create(['company_name'=>'Approved Co','full_name'=>'Approved Owner','email'=>'approved@example.test','username'=>'approved','password'=>Hash::make('secret'),'mobile_no'=>'600','country_id'=>$nepal->id,'status'=>'pending']);
        $this->actingAs(User::findOrFail(99));File::shouldReceive('exists')->once()->andReturnFalse();File::shouldReceive('makeDirectory')->once();
        $subscription=$this->mock(SubscriptionService::class);$subscription->shouldReceive('startRegisterTrial')->once();
        app(CompanyApprovalController::class)->approve($registration->id);
        $this->assertDatabaseHas('companies',['email'=>'approved@example.test','country_id'=>$nepal->id]);
        $inactive=Country::create(['name'=>'Inactive','iso_code'=>'ZZ','is_active'=>0]);$bad=CompanyRegistration::create(['company_name'=>'Bad Co','full_name'=>'Bad Owner','email'=>'bad-approval@example.test','username'=>'bad-approval','password'=>'x','mobile_no'=>'601','country_id'=>$inactive->id,'status'=>'pending']);
        app(CompanyApprovalController::class)->approve($bad->id);$this->assertDatabaseMissing('companies',['email'=>'bad-approval@example.test']);
    }
}
