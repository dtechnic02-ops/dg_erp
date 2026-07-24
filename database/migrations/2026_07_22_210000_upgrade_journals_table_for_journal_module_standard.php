<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (!Schema::hasColumn('journals', 'reference_no')) {
                $table->string('reference_no')->nullable()->after('journal_date');
            }

            if (!Schema::hasColumn('journals', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }

            if (!Schema::hasColumn('journals', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('updated_by');
            }

            if (!Schema::hasColumn('journals', 'cancelled_date')) {
                $table->date('cancelled_date')->nullable()->after('cancelled_by');
            }

            if (!Schema::hasColumn('journals', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable()->after('cancelled_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $columns = ['reference_no', 'updated_by', 'cancelled_by', 'cancelled_date', 'cancel_reason'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('journals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
