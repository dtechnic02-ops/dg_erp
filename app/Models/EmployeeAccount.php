<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAccount extends Model
{

protected $fillable = [

'company_id',

'employee_code',

'first_name',

'middle_name',

'last_name',

'phone',

'email',

'address',

'gender',

'dob',

'joining_date',

'designation',

'department',

'post',

'employment_type',

'basic_salary',

'salary_type',

'opening_due_salary',

'bank_name',

'bank_account_no',

'account_holder_name',

'cit_no',

'pan_no',

'emergency_contact',

'emergency_phone',

'photo',

'cv_attachment',

'id_document',

'contract_document',

'note',

'created_by',

'status'

];

/*
|--------------------------------------------------------------------------
| COMPANY
|--------------------------------------------------------------------------
*/

public function company()
{

return $this->belongsTo(

Company::class

);

}

/*
|--------------------------------------------------------------------------
| CREATOR
|--------------------------------------------------------------------------
*/

public function creator()
{

return $this->belongsTo(

User::class,

'created_by'

);

}

/*
|--------------------------------------------------------------------------
| FULL NAME
|--------------------------------------------------------------------------
*/

public function getFullNameAttribute()
{

return trim(

$this->first_name

.' '

.

$this->middle_name

.' '

.

$this->last_name

);

}

}