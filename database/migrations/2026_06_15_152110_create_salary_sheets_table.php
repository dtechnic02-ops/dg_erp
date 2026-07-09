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
        Schema::create('salary_sheets', function (Blueprint $table) {

    $table->id();

    $table->unsignedBigInteger('company_id');

    $table->unsignedBigInteger('financial_year_id');

    $table->unsignedBigInteger('employee_id');

    $table->string('salary_month',20);

    $table->decimal('basic_salary',15,2)
          ->default(0);

    $table->integer('working_days')
          ->default(30);

    $table->integer('present_days')
          ->default(30);

    $table->integer('absent_days')
          ->default(0);

    $table->decimal('allowance',15,2)
          ->default(0);

    $table->decimal('bonus',15,2)
          ->default(0);

    $table->decimal('overtime_amount',15,2)
          ->default(0);

    $table->decimal('deduction',15,2)
          ->default(0);

    $table->decimal('net_salary',15,2)
          ->default(0);

    $table->enum(
        'status',
        [
            'unpaid',
            'partial',
            'paid',
            'cancelled'
        ]
    )->default('unpaid');

    $table->text('note')
          ->nullable();

    $table->unsignedBigInteger('created_by')
          ->nullable();

    $table->timestamps();

    $table->unique(
    [
        'company_id',
        'financial_year_id',
        'employee_id',
        'salary_month'
    ],
    'salary_unique'
);

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_sheets');
    }
};
