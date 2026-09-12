<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Models\Company;
use App\Models\CompanyCbmsApiConfiguration;
use App\Models\CompanyIrdCbmsSetting;
use App\Models\CompanyTaxSetting;
use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CbmsApiConfigurationFoundationTest extends TestCase
{
    use RefreshDatabase;

    private Country $nepal;
    private Country $uae;
    private Company $companyA;
    private Company $companyB;
    private Company $uaeCompany;
    private User $global;
    private User $adminA;
    private User $adminB;
    private User $countryAdmin;
    private User $uaeCountryAdmin;
    private User $superStaff;
    private User $companyStaff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(CheckSubscription::class);
        foreach ([1=>'super_admin',2=>'company_admin',3=>'staff',4=>'super_staff',5=>'country_admin'] as $id=>$name) {
            DB::table('roles')->insertOrIgnore(compact('id','name'));
        }
        $this->nepal=Country::create(['name'=>'Nepal','iso_code'=>'NP','is_active'=>true]);
        $this->uae=Country::create(['name'=>'UAE','iso_code'=>'AE','is_active'=>true]);
        $this->companyA=$this->company('Nepal A',$this->nepal);
        $this->companyB=$this->company('Nepal B',$this->nepal);
        $this->uaeCompany=$this->company('UAE Company',$this->uae);
        $this->global=$this->user('Global',1,null,null);
        $this->adminA=$this->user('Admin A',2,$this->companyA->id,null);
        $this->adminB=$this->user('Admin B',2,$this->companyB->id,null);
        $this->countryAdmin=$this->user('Nepal Country',5,null,$this->nepal->id);
        $this->uaeCountryAdmin=$this->user('UAE Country',5,null,$this->uae->id);
        $this->superStaff=$this->user('Super Staff',4,null,$this->nepal->id);
        $this->companyStaff=$this->user('Company Staff',3,$this->companyA->id,null);
    }

    public function test_global_super_admin_can_create_and_update_nepal_configuration_securely(): void
    {
        $secret='global-secret-123';
        $this->actingAs($this->global)->get(route('admin.company.cbms-api.edit',$this->companyA))->assertOk()->assertDontSee($secret);
        $this->put(route('admin.company.cbms-api.update',$this->companyA),['environment'=>'test','client_identifier'=>'client-a','credential'=>$secret,'company_id'=>$this->companyB->id,'configured_by'=>$this->adminB->id,'updated_by'=>$this->adminB->id])->assertSessionHasNoErrors();
        $config=CompanyCbmsApiConfiguration::where('company_id',$this->companyA->id)->sole();
        $raw=DB::table('company_cbms_api_configurations')->where('id',$config->id)->value('encrypted_credential');
        $this->assertNotSame($secret,$raw);
        $this->assertSame($secret,$config->encrypted_credential);
        $this->assertArrayNotHasKey('encrypted_credential',$config->toArray());
        $this->assertSame($this->global->id,(int)$config->configured_by);
        $this->assertSame($this->global->id,(int)$config->updated_by);
        $this->get(route('admin.company.cbms-api.edit',$this->companyA))->assertOk()->assertSee('************')->assertDontSee($secret);
    }

    public function test_company_admin_owns_configuration_blank_preserves_and_replacement_is_encrypted(): void
    {
        $this->actingAs($this->adminA)->put(route('company.settings.cbms-api.update'),['environment'=>'test','client_identifier'=>'own','credential'=>'first-secret','company_id'=>$this->companyB->id,'configured_by'=>$this->adminB->id])->assertSessionHasNoErrors();
        $config=$this->companyA->cbmsApiConfiguration()->sole();
        $this->assertSame($this->adminA->id,(int)$config->configured_by);
        $this->assertDatabaseMissing('company_cbms_api_configurations',['company_id'=>$this->companyB->id]);
        $this->put(route('company.settings.cbms-api.update'),['environment'=>'production','client_identifier'=>'updated','credential'=>''])->assertSessionHasNoErrors();
        $this->assertSame('first-secret',$config->fresh()->encrypted_credential);
        $firstRaw=DB::table('company_cbms_api_configurations')->where('id',$config->id)->value('encrypted_credential');
        $this->put(route('company.settings.cbms-api.update'),['environment'=>'production','credential'=>'replacement-secret','updated_by'=>$this->adminB->id])->assertSessionHasNoErrors();
        $config->refresh();
        $this->assertSame('replacement-secret',$config->encrypted_credential);
        $this->assertNotSame($firstRaw,DB::table('company_cbms_api_configurations')->where('id',$config->id)->value('encrypted_credential'));
        $this->assertSame($this->adminA->id,(int)$config->updated_by);
        $this->actingAs($this->adminA)->put(route('admin.company.ird-cbms.update',$this->companyA),['is_enabled'=>1])->assertForbidden();
    }

    public function test_all_disallowed_actors_and_cross_company_paths_are_denied(): void
    {
        foreach ([$this->countryAdmin,$this->uaeCountryAdmin,$this->superStaff] as $actor) {
            $this->actingAs($actor)->get(route('admin.company.cbms-api.edit',$this->companyA))->assertForbidden();
            $this->put(route('admin.company.cbms-api.update',$this->companyA),['environment'=>'test','credential'=>'forbidden'])->assertForbidden();
        }
        $this->actingAs($this->companyStaff)->get(route('company.settings.cbms-api.edit'))->assertForbidden();
        $this->put(route('company.settings.cbms-api.update'),['environment'=>'test','credential'=>'forbidden'])->assertForbidden();
        $this->actingAs($this->adminA)->get(route('admin.company.cbms-api.edit',$this->companyB))->assertForbidden();
        $this->put(route('admin.company.cbms-api.update',$this->companyB),['environment'=>'test','credential'=>'forbidden'])->assertForbidden();
        $uaeAdmin=$this->user('UAE Admin',2,$this->uaeCompany->id,null);
        $this->actingAs($uaeAdmin)->get(route('company.settings.cbms-api.edit'))->assertNotFound();
        $this->put(route('company.settings.cbms-api.update'),['environment'=>'test','credential'=>'forbidden'])->assertNotFound();
        $this->assertSame(0,CompanyCbmsApiConfiguration::count());
    }

    public function test_configuration_and_activation_remain_independent_and_country_admin_keeps_activation(): void
    {
        CompanyTaxSetting::create(['company_id'=>$this->companyA->id,'is_vat_registered'=>true,'updated_by'=>$this->global->id]);
        $this->companyA->update(['pan_number'=>'123456789','vat_number'=>'987654321']);
        $this->actingAs($this->global)->put(route('admin.company.cbms-api.update',$this->companyA),['environment'=>'test','credential'=>'secret'])->assertSessionHasNoErrors();
        $this->assertFalse(CompanyIrdCbmsSetting::where('company_id',$this->companyA->id)->where('is_enabled',true)->exists());
        $this->actingAs($this->countryAdmin)->put(route('admin.company.ird-cbms.update',$this->companyA),['is_enabled'=>1])->assertSessionHasNoErrors();
        $this->assertTrue(CompanyIrdCbmsSetting::where('company_id',$this->companyA->id)->sole()->is_enabled);
        $this->actingAs($this->global)->put(route('admin.company.cbms-api.update',$this->companyA),['environment'=>'production','credential'=>''])->assertSessionHasNoErrors();
        $this->assertTrue(CompanyIrdCbmsSetting::where('company_id',$this->companyA->id)->sole()->is_enabled);
        $this->actingAs($this->countryAdmin)->put(route('admin.company.ird-cbms.update',$this->companyA),['is_enabled'=>0])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('company_cbms_api_configurations',['company_id'=>$this->companyA->id]);
    }

    public function test_routes_require_authentication_and_validation_never_flashes_credential(): void
    {
        $this->get(route('company.settings.cbms-api.edit'))->assertRedirect(route('login'));
        $this->put(route('company.settings.cbms-api.update'),[])->assertRedirect(route('login'));
        $secret='never-flash-this-secret';
        $response=$this->actingAs($this->adminA)->from(route('company.settings.cbms-api.edit'))->put(route('company.settings.cbms-api.update'),['environment'=>'invalid','credential'=>$secret]);
        $response->assertSessionHasErrors('environment');
        $this->assertNotSame($secret,session()->getOldInput('credential'));
    }

    public function test_reset_classification_preserves_and_permanent_delete_classifies_configuration(): void
    {
        $factory=new \ReflectionClass(\App\Services\CompanyFactoryResetService::class);
        $preserved=$factory->getReflectionConstant('PRESERVED_COMPANY_TABLES')->getValue();
        $this->assertContains('company_cbms_api_configurations',$preserved);
        $this->assertContains('company_cbms_api_configurations',\App\Services\CompanyPermanentDeletionService::COMPANY_TABLES);
        $source=file_get_contents(app_path('Services/CompanyPermanentDeletionService.php'));
        $this->assertStringContainsString("'company_cbms_api_configurations'",$source);
    }

    private function company(string $name,Country $country): Company { return Company::create(['company_name'=>$name,'mobile'=>uniqid(),'email'=>str($name)->slug().uniqid().'@test.local','status'=>'active','country_id'=>$country->id]); }
    private function user(string $name,int $role,?int $company,?int $country): User { return User::create(['name'=>$name,'email'=>str($name)->slug().uniqid().'@test.local','password'=>Hash::make('password'),'role_id'=>$role,'company_id'=>$company,'country_id'=>$country,'account_status'=>'active']); }
}
