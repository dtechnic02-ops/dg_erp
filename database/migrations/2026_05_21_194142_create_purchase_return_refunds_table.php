<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up(): void
    {
        Schema::create(
            'purchase_return_refunds',
            function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger(
                'company_id'
            );

            $table->unsignedBigInteger(
                'purchase_return_id'
            );

            $table->unsignedBigInteger(
                'account_id'
            );

            $table->decimal(
                'amount',
                18,
                2
            )->default(0);

            $table->date(
                'refund_date'
            );

            $table->text(
                'note'
            )->nullable();

            $table->unsignedBigInteger(
                'created_by'
            )->nullable();

            $table->tinyInteger(
                'status'
            )->default(1);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::dropIfExists(
            'purchase_return_refunds'
        );
    }
};