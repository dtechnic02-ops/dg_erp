<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {

            $table->foreignId('vat_id')
                ->nullable()
                ->after('unit_id')
                ->constrained('vats')
                ->nullOnDelete();

        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {

            $table->dropForeign(['vat_id']);
            $table->dropColumn('vat_id');

        });
    }
};