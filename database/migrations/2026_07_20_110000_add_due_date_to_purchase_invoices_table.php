<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->date('due_date')
                ->nullable()
                ->after('purchase_date');
        });

        DB::statement(
            'UPDATE purchase_invoices SET due_date = purchase_date WHERE due_date IS NULL'
        );
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }
};
