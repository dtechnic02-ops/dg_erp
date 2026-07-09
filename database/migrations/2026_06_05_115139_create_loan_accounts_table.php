<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

public function up(): void
{

Schema::create(

'loan_accounts',

function(
Blueprint $table
){

$table->id();

$table->foreignId(
'company_id'
);

$table->string(
'loan_no'
);

$table->string(
'loan_name'
);

$table->enum(

'loan_type',

[

'taken',

'given'

]

);

$table->foreignId(
'party_account_id'
);

$table->foreignId(
'account_id'
);

$table->decimal(
'principal_amount',
18,
2
)->default(0);

$table->decimal(
'interest_rate',
18,
2
)->default(0);

$table->decimal(
'remaining_principal',
18,
2
)->default(0);

$table->date(
'start_date'
);

$table->date(
'end_date'
)->nullable();

$table->date(
'next_payment_date'
)->nullable();

$table->string(
'attachment'
)->nullable();

$table->text(
'note'
)->nullable();

$table->foreignId(
'created_by'
);

$table->tinyInteger(
'status'
)->default(1);

$table->timestamps();

}

);

}


public function down(): void
{

Schema::dropIfExists(

'loan_accounts'

);

}

};