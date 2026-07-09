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
        'selected_user_limit',
        'status',
    ];
}