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
        'expenses',
        function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger(
                'company_id'
            );

            $table->string(
                'expense_no'
            )->unique();

            $table->unsignedBigInteger(
                'expense_category_id'
            );

            $table->unsignedBigInteger(
                'account_id'
            );

            $table->date(
                'expense_date'
            );

            $table->decimal(
                'amount',
                18,
                2
            );

            $table->string(
                'reference_no'
            )->nullable();

            $table->text(
                'note'
            )->nullable();

            $table->string(
                'attachment'
            )->nullable();

            $table->unsignedBigInteger(
                'created_by'
            );

            $table->tinyInteger(
                'status'
            )->default(1);

            $table->timestamps();

        }
    );
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
