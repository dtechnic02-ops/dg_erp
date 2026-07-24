<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_sheets', function (Blueprint $table) {
            if (!Schema::hasColumn('salary_sheets', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }

            if (!Schema::hasColumn('salary_sheets', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('updated_by');
            }

            if (!Schema::hasColumn('salary_sheets', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            }

            if (!Schema::hasColumn('salary_sheets', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable()->after('cancelled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('salary_sheets', function (Blueprint $table) {
            $columns = ['cancel_reason', 'cancelled_at', 'cancelled_by', 'updated_by'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('salary_sheets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
