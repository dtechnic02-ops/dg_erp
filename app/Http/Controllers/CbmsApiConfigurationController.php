<?php
namespace App\Http\Controllers;
use App\Models\Company;
use App\Models\CompanyCbmsApiConfiguration;
use App\Services\CbmsApiConfigurationAuthorizationService;
use App\Services\NepalIrdCbmsModeService;
use Illuminate\Http\Request;
class CbmsApiConfigurationController extends Controller {
 public function platformEdit(Company $company,CbmsApiConfigurationAuthorizationService $auth,NepalIrdCbmsModeService $mode){ return $this->view($company,$auth,$mode,true); }
 public function tenantEdit(CbmsApiConfigurationAuthorizationService $auth,NepalIrdCbmsModeService $mode){ return $this->view(auth()->user()->company,$auth,$mode,false); }
 public function platformUpdate(Request $r,Company $company,CbmsApiConfigurationAuthorizationService $auth){ return $this->save($r,$company,$auth); }
 public function tenantUpdate(Request $r,CbmsApiConfigurationAuthorizationService $auth){ return $this->save($r,auth()->user()->company,$auth); }
 private function view(Company $company,$auth,$mode,bool $platform){ $auth->authorize(auth()->user(),$company); $configuration=CompanyCbmsApiConfiguration::where('company_id',$company->id)->first(); return view('shared.cbms-api-configuration',compact('company','configuration','platform')+['isModeActive'=>$mode->isActiveForCompany($company),'credentialsConfigured'=>(bool)($configuration?->client_identifier && $configuration?->encrypted_credential),'sellerPan'=>$company->pan_number]); }
 private function save(Request $r,Company $company,$auth){ $auth->authorize(auth()->user(),$company); $data=$r->validate(['environment'=>['required','in:test,production'],'client_identifier'=>['nullable','string','max:255'],'credential'=>['nullable','string','max:10000']]); $configuration=CompanyCbmsApiConfiguration::where('company_id',$company->id)->first(); if(!$configuration && blank($data['credential']??null)) return back()->withErrors(['credential'=>'Credential is required for initial configuration.']); $values=['environment'=>$data['environment'],'client_identifier'=>$data['client_identifier']??null,'updated_by'=>auth()->id()]; if(!$configuration){$values['company_id']=$company->id;$values['configured_by']=auth()->id();} if(filled($data['credential']??null))$values['encrypted_credential']=$data['credential']; CompanyCbmsApiConfiguration::updateOrCreate(['company_id'=>$company->id],$values); return back()->with('success','CBMS API configuration saved.'); }
}
