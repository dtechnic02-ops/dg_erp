<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table(
        'purchase_return_refunds',
        function ($table) {

            $table->unsignedBigInteger(
                'financial_year_id'
            )->nullable()->after('company_id');

        }
    );
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_return_refunds', function (Blueprint $table) {
            //
        });
    }
};
