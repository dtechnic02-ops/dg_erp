<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

public function up(): void
{

Schema::create(

'loan_saving_ledgers',

function(
Blueprint $table
){

$table->id();

$table->foreignId(
'company_id'
);

$table->foreignId(
'loan_account_id'
);

$table->foreignId(
'loan_payment_id'
)

->nullable();

$table->foreignId(
'account_id'
);

$table->enum(

'type',

[

'deposit',

'withdraw'

]

);

$table->decimal(

'amount',

18,

2

)->default(0);

$table->decimal(

'balance_after',

18,

2

)->default(0);

$table->date(
'date'
);

$table->string(
'attachment'
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

'loan_saving_ledgers'

);

}

};