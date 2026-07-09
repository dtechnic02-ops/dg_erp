<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

public function up(): void
{

Schema::create(

'employee_accounts',

function(
Blueprint $table
){

$table->id();

$table->foreignId(
'company_id'
);

$table->string(
'employee_code'
)

->unique();

$table->string(
'first_name'
);

$table->string(
'middle_name'
)

->nullable();

$table->string(
'last_name'
)

->nullable();

$table->string(
'phone'
)

->nullable();

$table->string(
'email'
)

->nullable();

$table->text(
'address'
)

->nullable();

$table->string(
'gender'
)

->nullable();

$table->date(
'dob'
)

->nullable();

$table->date(
'joining_date'
);

$table->string(
'designation'
)

->nullable();

$table->string(
'department'
)

->nullable();

$table->string(
'post'
)

->nullable();

$table->enum(

'employment_type',

[

'permanent',

'contract',

'temporary',

'intern'

]

)

->default(
'permanent'
);

$table->decimal(

'basic_salary',

18,

2

)

->default(0);

$table->enum(

'salary_type',

[

'monthly',

'daily'

]

)

->default(
'monthly'
);

$table->decimal(

'opening_due_salary',

18,

2

)

->default(0);

$table->string(
'bank_name'
)

->nullable();

$table->string(
'bank_account_no'
)

->nullable();

$table->string(
'account_holder_name'
)

->nullable();

$table->string(
'cit_no'
)

->nullable();

$table->string(
'pan_no'
)

->nullable();

$table->string(
'emergency_contact'
)

->nullable();

$table->string(
'emergency_phone'
)

->nullable();

$table->string(
'photo'
)

->nullable();

$table->string(
'cv_attachment'
)

->nullable();

$table->string(
'id_document'
)

->nullable();

$table->string(
'contract_document'
)

->nullable();

$table->text(
'note'
)

->nullable();

$table->foreignId(
'created_by'
);

$table->tinyInteger(
'status'
)

->default(1);

$table->timestamps();

}

);

}

public function down(): void
{

Schema::dropIfExists(

'employee_accounts'

);

}

};