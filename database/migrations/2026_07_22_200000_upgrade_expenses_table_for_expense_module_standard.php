<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }

            if (!Schema::hasColumn('expenses', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('updated_by');
            }

            if (!Schema::hasColumn('expenses', 'cancelled_date')) {
                $table->date('cancelled_date')->nullable()->after('cancelled_by');
            }

            if (!Schema::hasColumn('expenses', 'cancel_reason')) {
                $table->string('cancel_reason', 500)->nullable()->after('cancelled_date');
            }
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('expense_categories', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('status');
            }
        });

        try {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropUnique(['expense_no']);
            });
        } catch (\Throwable $e) {
            // Unique index may not exist in all environments.
        }
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'updated_by')) {
                $table->dropColumn('updated_by');
            }

            if (Schema::hasColumn('expenses', 'cancelled_by')) {
                $table->dropColumn('cancelled_by');
            }

            if (Schema::hasColumn('expenses', 'cancelled_date')) {
                $table->dropColumn('cancelled_date');
            }

            if (Schema::hasColumn('expenses', 'cancel_reason')) {
                $table->dropColumn('cancel_reason');
            }
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            if (Schema::hasColumn('expense_categories', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
};
