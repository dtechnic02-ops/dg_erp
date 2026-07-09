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
       Schema::create('journals', function (Blueprint $table) {

$table->id();

$table->unsignedBigInteger(
'company_id'
);

$table->string(
'journal_no'
);

$table->date(
'journal_date'
);

$table->decimal(
'total_amount',
18,
2
)->default(0);

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
        Schema::dropIfExists('journals');
    }
};
