<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

public function up(): void
{

Schema::create(

'party_accounts',

function(
Blueprint $table
){

$table->id();

$table->foreignId(
'company_id'
);

$table->string(
'account_no'
);

$table->string(
'name'
);

$table->string(
'phone'
)->nullable();

$table->text(
'address'
)->nullable();

$table->decimal(
'opening_balance',
18,
2
)->default(0);

$table->decimal(
'current_balance',
18,
2
)->default(0);

$table->enum(

'type',

[

'bank',

'person',

'customer',

'supplier',

'company',

'other'

]

);

$table->string(
'photo'
)->nullable();

$table->string(
'id_card'
)->nullable();

$table->string(
'document'
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

'party_accounts'

);

}

};