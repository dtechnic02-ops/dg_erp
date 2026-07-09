<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {

            $table->foreignId('created_by')
                ->nullable()
                ->after('company_id');

        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {

            $table->dropColumn('created_by');

        });
    }
};