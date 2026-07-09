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
       Schema::table('purchase_items', function (Blueprint $table) {

    $table->unsignedBigInteger('financial_year_id')
        ->after('company_id');

    $table->tinyInteger('status')
        ->default(1)
        ->after('created_by');

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            //
        });
    }
};
