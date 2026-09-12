<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CompanyCbmsApiConfiguration extends Model {
 protected $fillable=['company_id','environment','client_identifier','encrypted_credential','configured_by','updated_by'];
 protected $hidden=['encrypted_credential'];
 protected function casts(): array { return ['encrypted_credential'=>'encrypted','last_verified_at'=>'datetime']; }
 public function company(){ return $this->belongsTo(Company::class); }
 public function configuredBy(){ return $this->belongsTo(User::class,'configured_by'); }
 public function updatedBy(){ return $this->belongsTo(User::class,'updated_by'); }
}
