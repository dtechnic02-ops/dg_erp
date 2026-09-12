<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table): void {
            // Laravel's application clock is UTC while the existing MySQL session inherits
            // the host timezone, so retain the canonical UTC value without DB conversion.
            $table->dateTime('fiscal_issued_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->dropColumn('fiscal_issued_at');
        });
    }
};
