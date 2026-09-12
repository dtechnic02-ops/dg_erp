<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->string('fiscal_payment_mode')->nullable();
            $table->timestamp('fiscal_payment_mode_captured_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->dropColumn(['fiscal_payment_mode', 'fiscal_payment_mode_captured_at']);
        });
    }
};
