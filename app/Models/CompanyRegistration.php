<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyRegistration extends Model
{
    protected $table = 'company_registrations';

    protected $fillable = [
        'company_name',
        'full_name',
        'email',
        'username',
        'password',
        'mobile_no',
        'country',
        'country_id',
        'registered_by_user_id',
        'selected_user_limit',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
    ];
    public function countryMaster()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function registeredBy() { return $this->belongsTo(User::class, 'registered_by_user_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function rejector() { return $this->belongsTo(User::class, 'rejected_by'); }

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'rejected_at' => 'datetime'];
    }
}
