<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('created_by');
            $table->foreignId('cancelled_by')->nullable()->after('updated_by');
            $table->date('cancelled_date')->nullable()->after('cancelled_by');
            $table->string('cancel_reason', 500)->nullable()->after('cancelled_date');
        });

        Schema::table('loan_saving_ledgers', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('created_by');
        });

        Schema::table('loan_payments', function (Blueprint $table) {
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('cancelled_by')->references('id')->on('users');
        });

        Schema::table('loan_saving_ledgers', function (Blueprint $table) {
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['updated_by', 'cancelled_by', 'cancelled_date', 'cancel_reason']);
        });

        Schema::table('loan_saving_ledgers', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn('updated_by');
        });
    }
};
