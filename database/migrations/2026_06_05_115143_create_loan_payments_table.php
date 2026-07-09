<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{

Schema::create(

'loan_payments',

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
'account_id'
);

$table->date(
'payment_date'
);

$table->decimal(
'principal_amount',
18,
2
)->default(0);

$table->decimal(
'interest_amount',
18,
2
)->default(0);

$table->decimal(
'fine_amount',
18,
2
)->default(0);

$table->decimal(
'saving_amount',
18,
2
)->default(0);

$table->decimal(
'total_amount',
18,
2
)->default(0);

$table->decimal(
'remaining_principal',
18,
2
)->default(0);

$table->string(
'reference_no'
)->nullable();

$table->string(
'attachment'
)->nullable();

$table->text(
'note'
)->nullable();

$table->tinyInteger(
'status'
)->default(1);

$table->foreignId(
'created_by'
);

$table->timestamps();

});

}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};
