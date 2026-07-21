<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('updated_by')
                ->nullable()
                ->after('created_by');

            $table->unsignedBigInteger('deleted_by')
                ->nullable()
                ->after('updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->dropColumn([
                'updated_by',
                'deleted_by',
            ]);
        });
    }
};
