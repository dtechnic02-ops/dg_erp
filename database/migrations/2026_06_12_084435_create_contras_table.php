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
       Schema::create('contras', function (Blueprint $table) {

    $table->id();

    $table->foreignId(
        'company_id'
    );

    $table->foreignId(
        'financial_year_id'
    );

    $table->string(
        'contra_no'
    );

    $table->date(
        'contra_date'
    );

    $table->foreignId(
        'from_account_id'
    );

    $table->foreignId(
        'to_account_id'
    );

    $table->decimal(
        'amount',
        18,
        2
    );

    $table->string(
        'transfer_type'
    )
    ->nullable();

    $table->string(
        'reference_no'
    )
    ->nullable();

    $table->text(
        'note'
    )
    ->nullable();

    $table->string(
        'attachment'
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

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contras');
    }
    
};
