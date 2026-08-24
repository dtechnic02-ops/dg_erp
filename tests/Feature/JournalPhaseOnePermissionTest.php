<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class JournalPhaseOnePermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach(['journal_audit_events','journal_items','journal_number_sequences','journals','accounting_period_locks','employee_accounts','party_accounts','customers','suppliers','accounts','chart_accounts','financial_years','company_subscriptions','user_permissions','permission_role','permissions','users','roles','companies'] as $t)Schema::dropIfExists($t);
        Schema::create('companies',fn(Blueprint $t)=>[$t->id(),$t->string('company_name'),$t->string('status')->default('active'),$t->timestamps()]);
        Schema::create('roles',fn(Blueprint $t)=>[$t->id(),$t->string('name'),$t->timestamps()]);
        Schema::create('users',fn(Blueprint $t)=>[$t->id(),$t->string('name'),$t->string('email'),$t->string('password'),$t->unsignedBigInteger('company_id'),$t->unsignedBigInteger('role_id'),$t->string('account_status')->default('active'),$t->rememberToken(),$t->timestamps()]);
        Schema::create('permissions',fn(Blueprint $t)=>[$t->id(),$t->string('name'),$t->string('scope')->default('company'),$t->timestamps()]);Schema::create('permission_role',fn(Blueprint $t)=>[$t->id(),$t->unsignedBigInteger('permission_id'),$t->unsignedBigInteger('role_id'),$t->timestamps()]);Schema::create('user_permissions',fn(Blueprint $t)=>[$t->id(),$t->unsignedBigInteger('user_id'),$t->unsignedBigInteger('permission_id'),$t->boolean('is_allowed'),$t->timestamps()]);
        Schema::create('company_subscriptions',fn(Blueprint $t)=>[$t->id(),$t->unsignedBigInteger('company_id'),$t->string('status'),$t->boolean('is_all_modules_enabled')->default(true),$t->json('hidden_modules')->nullable(),$t->timestamps()]);
        Schema::create('financial_years',fn(Blueprint $t)=>[$t->id(),$t->unsignedBigInteger('company_id'),$t->string('name'),$t->date('start_date'),$t->date('end_date'),$t->boolean('is_active'),$t->boolean('is_closed')->default(false),$t->boolean('is_locked')->default(false),$t->timestamps()]);
        Schema::create('chart_accounts',fn(Blueprint $t)=>[$t->id(),$t->unsignedBigInteger('company_id'),$t->string('code'),$t->string('name'),$t->string('account_class')->default('asset'),$t->string('normal_balance')->default('debit'),$t->unsignedTinyInteger('level'),$t->boolean('allow_manual_entry'),$t->boolean('is_control')->default(false),$t->string('status'),$t->boolean('is_locked')->default(false),$t->timestamps(),$t->softDeletes()]);
        Schema::create('accounts',fn(Blueprint $t)=>[$t->id(),$t->unsignedBigInteger('company_id'),$t->string('account_name'),$t->string('sub_ledger_type')->nullable(),$t->decimal('current_balance',20,4)->default(0),$t->integer('status'),$t->timestamps()]);
        foreach(['customers','suppliers'] as $x)Schema::create($x,fn(Blueprint $t)=>[$t->id(),$t->unsignedBigInteger('company_id'),$t->string('name'),$t->string('status'),$t->timestamps()]);Schema::create('employee_accounts',fn(Blueprint $t)=>[$t->id(),$t->unsignedBigInteger('company_id'),$t->string('first_name'),$t->string('middle_name')->nullable(),$t->string('last_name')->nullable(),$t->integer('status'),$t->timestamps()]);Schema::create('party_accounts',fn(Blueprint $t)=>[$t->id(),$t->unsignedBigInteger('company_id'),$t->string('name'),$t->integer('status'),$t->timestamps()]);
        Schema::create('accounting_period_locks',fn(Blueprint $t)=>[$t->id(),$t->unsignedBigInteger('company_id'),$t->unsignedBigInteger('financial_year_id'),$t->date('date_from'),$t->date('date_to'),$t->boolean('is_locked'),$t->timestamps()]);
        Schema::create('journals',function(Blueprint $t){$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('financial_year_id');$t->string('journal_no');$t->date('journal_date');$t->string('journal_type');$t->string('reference_no')->nullable();$t->text('description');$t->text('remarks')->nullable();$t->text('note')->nullable();$t->uuid('request_key')->nullable();$t->decimal('total_amount',20,4);$t->unsignedBigInteger('created_by');$t->unsignedBigInteger('updated_by')->nullable();$t->boolean('is_locked')->default(false);$t->string('status');$t->timestamps();$t->unique(['company_id','financial_year_id','journal_no']);$t->unique(['company_id','request_key']);});
        Schema::create('journal_items',function(Blueprint $t){$t->id();$t->unsignedBigInteger('company_id');$t->unsignedBigInteger('journal_id');$t->unsignedBigInteger('chart_account_id');$t->unsignedBigInteger('account_id')->nullable();$t->string('type');$t->decimal('amount',20,4);$t->decimal('debit',20,4);$t->decimal('credit',20,4);$t->text('description')->nullable();$t->string('reference')->nullable();$t->unsignedInteger('line_number');$t->string('sub_ledger_type')->nullable();$t->unsignedBigInteger('sub_ledger_id')->nullable();$t->text('note')->nullable();$t->integer('status');$t->timestamps();});
        Schema::create('journal_number_sequences',fn(Blueprint $t)=>[$t->unsignedBigInteger('company_id'),$t->unsignedBigInteger('financial_year_id'),$t->unsignedBigInteger('next_number'),$t->timestamps(),$t->primary(['company_id','financial_year_id'])]);Schema::create('journal_audit_events',fn(Blueprint $t)=>[$t->id(),$t->unsignedBigInteger('company_id'),$t->unsignedBigInteger('financial_year_id'),$t->unsignedBigInteger('journal_id'),$t->string('event'),$t->string('previous_status')->nullable(),$t->string('new_status')->nullable(),$t->unsignedBigInteger('actor_id'),$t->timestamp('event_at'),$t->text('reason')->nullable(),$t->json('metadata')->nullable()]);
        DB::table('companies')->insert([['id'=>1,'company_name'=>'One'],['id'=>2,'company_name'=>'Two']]);DB::table('roles')->insert([['id'=>2,'name'=>'Admin'],['id'=>3,'name'=>'Staff']]);foreach([[1,'admin@x',2,1],[2,'staff@x',3,1]] as [$id,$email,$role,$company])DB::table('users')->insert(['id'=>$id,'name'=>$email,'email'=>$email,'password'=>Hash::make('x'),'company_id'=>$company,'role_id'=>$role]);foreach(['module_journal','journal.view','journal.create','journal.edit-draft','journal.audit-view','journal.print','view_journal','create_journal','edit_journal','print_journal'] as $permission)DB::table('permissions')->insert(['name'=>$permission,'scope'=>'company']);DB::table('company_subscriptions')->insert(['company_id'=>1,'status'=>'active']);DB::table('financial_years')->insert(['id'=>1,'company_id'=>1,'name'=>'FY26','start_date'=>'2026-01-01','end_date'=>'2026-12-31','is_active'=>1]);DB::table('chart_accounts')->insert([['id'=>1,'company_id'=>1,'code'=>'1000','name'=>'Cash','level'=>3,'allow_manual_entry'=>1,'status'=>'active'],['id'=>2,'company_id'=>1,'code'=>'3000','name'=>'Capital','level'=>3,'allow_manual_entry'=>1,'status'=>'active']]);
        $this->withoutMiddleware([EnsureCompanyUser::class,CheckSubscription::class,UpdateLastSeen::class]);
    }

    public function test_all_nine_routes_deny_unpermitted_user_and_allow_admin(): void
    {
        $journal=$this->draft(1);$staff=User::find(2);$admin=User::find(1);$payload=$this->payload();
        $routes=[['get','company.journal.index',[],[]],['get','company.journal.create',[],[]],['post','company.journal.store',[],$payload],['get','company.journal.show',[$journal],[]],['get','company.journal.edit',[$journal],[]],['put','company.journal.update',[$journal],$payload],['get','company.journal.audit',[$journal],[]],['get','company.journal.print',[],[]],['get','company.journal.print-voucher',[$journal],[]]];
        foreach($routes as [$method,$name,$params,$data])$this->actingAs($staff)->{$method}(route($name,$params),$data)->assertForbidden();
        foreach($routes as [$method,$name,$params,$data])$this->assertNotSame(403,$this->actingAs($admin)->{$method}(route($name,$params),$data)->getStatusCode(),$name);
        $foreign=$this->draft(2);foreach(['company.journal.show','company.journal.edit','company.journal.audit','company.journal.print-voucher'] as $name)$this->actingAs($admin)->get(route($name,$foreign))->assertNotFound();
    }

    private function draft(int $company): Journal{return Journal::create(['company_id'=>$company,'financial_year_id'=>1,'journal_no'=>'J-'.$company.'-'.Str::uuid(),'journal_date'=>'2026-06-01','journal_type'=>'general','description'=>'D','total_amount'=>'1.0000','created_by'=>1,'status'=>'draft']);}
    private function payload():array{return ['financial_year_id'=>1,'journal_date'=>'2026-06-01','journal_type'=>'general','description'=>'D','request_key'=>(string)Str::uuid(),'lines'=>[['chart_account_id'=>1,'debit'=>'1.0000','credit'=>'0.0000'],['chart_account_id'=>2,'debit'=>'0.0000','credit'=>'1.0000']]];}
}
