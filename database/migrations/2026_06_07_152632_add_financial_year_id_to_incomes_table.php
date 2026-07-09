<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('incomes', 'financial_year_id')) {

            Schema::table('incomes', function (Blueprint $table) {

                $table->unsignedBigInteger('financial_year_id')
                      ->nullable()
                      ->after('company_id');

            });

        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('incomes', 'financial_year_id')) {

            Schema::table('incomes', function (Blueprint $table) {

                $table->dropColumn('financial_year_id');

            });

        }
    }
};