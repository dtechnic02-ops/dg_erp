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
        'selected_user_limit',
        'status',
    ];
    public function countryMaster()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
