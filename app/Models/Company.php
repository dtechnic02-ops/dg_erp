<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'company_name',
        'mobile',
        'email',
        'status',
        'telephone',
        'fax_no',
        'website',
        'address',
        'address_line_2',
        'country',
        'language',
        'pan_number',
        'vat_number',
        'logo_path',
        'signature_path',
        'selected_user_limit',
        'expiry_date',
        'selected_customer_limit'
    ];
    public function financialYears()
{

    return $this->hasMany(
        FinancialYear::class
    );

}
}
