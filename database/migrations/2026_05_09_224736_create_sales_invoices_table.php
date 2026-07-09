<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {

            $table->id();

            $table->foreignId('created_by');

            $table->foreignId('company_id');

            $table->foreignId('customer_id');

            $table->string('invoice_no');

            $table->date('sale_date');

            $table->decimal('subtotal', 15, 2)->default(0);

            $table->decimal('discount', 15, 2)->default(0);

            $table->decimal('total_vat', 15, 2)->default(0);

            $table->decimal('grand_total', 15, 2)->default(0);

            $table->decimal('paid_amount', 15, 2)->default(0);

            $table->decimal('due_amount', 15, 2)->default(0);

            $table->enum('payment_status', [
                'paid',
                'partial',
                'unpaid'
            ])->default('unpaid');

            $table->text('note')->nullable();

            $table->tinyInteger('status')->default(1);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};