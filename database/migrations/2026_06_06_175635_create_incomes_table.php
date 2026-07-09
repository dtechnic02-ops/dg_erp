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
        Schema::create('incomes', function (Blueprint $table) {

$table->id();

$table->unsignedBigInteger(
'company_id'
);

$table->string(
'income_no'
);

$table->string(
'title'
);

$table->unsignedBigInteger(
'account_id'
);

$table->decimal(
'amount',
18,
2
)->default(0);

$table->date(
'income_date'
);

$table->string(
'category'
)->nullable();

$table->string(
'attachment'
)->nullable();

$table->longText(
'note'
)->nullable();

$table->unsignedBigInteger(
'created_by'
);

$table->tinyInteger(
'status'
)->default(1);

$table->timestamps();

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
