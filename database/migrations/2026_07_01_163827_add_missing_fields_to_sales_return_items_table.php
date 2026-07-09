<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table(
        'sales_return_items',
        function (Blueprint $table) {

            $table->unsignedBigInteger(
                'financial_year_id'
            )->after(
                'company_id'
            );

            $table->unsignedBigInteger(
                'created_by'
            )->nullable()->after(
                'total_price'
            );

            $table->boolean(
                'status'
            )->default(1)->after(
                'created_by'
            );

            $table->index(
                'financial_year_id'
            );

        }
    );
}
};
