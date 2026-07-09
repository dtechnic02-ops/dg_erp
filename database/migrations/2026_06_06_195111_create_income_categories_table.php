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
   Schema::create('income_categories', function (Blueprint $table) {

$table->id();

$table->unsignedBigInteger(
'company_id'
);

$table->string(
'name'
);

$table->string(
'code'
)->nullable();

$table->longText(
'note'
)->nullable();

$table->tinyInteger(
'status'
)->default(1);

$table->unsignedBigInteger(
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
        Schema::dropIfExists('income_categories');
    }
};
