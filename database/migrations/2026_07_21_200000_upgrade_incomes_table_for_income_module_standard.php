<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            if (!Schema::hasColumn('incomes', 'income_category_id')) {
                $table->unsignedBigInteger('income_category_id')->nullable()->after('financial_year_id');
            }

            if (!Schema::hasColumn('incomes', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }

            if (!Schema::hasColumn('incomes', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('updated_by');
            }

            if (!Schema::hasColumn('incomes', 'cancelled_date')) {
                $table->date('cancelled_date')->nullable()->after('cancelled_by');
            }

            if (!Schema::hasColumn('incomes', 'cancel_reason')) {
                $table->string('cancel_reason', 500)->nullable()->after('cancelled_date');
            }
        });

        if (Schema::hasColumn('incomes', 'category')) {
            $rows = DB::table('incomes')->select('id', 'company_id', 'category')->get();

            foreach ($rows as $row) {
                if (empty($row->category)) {
                    continue;
                }

                $categoryId = DB::table('income_categories')
                    ->where('company_id', $row->company_id)
                    ->where('name', $row->category)
                    ->value('id');

                if ($categoryId) {
                    DB::table('incomes')
                        ->where('id', $row->id)
                        ->update(['income_category_id' => $categoryId]);
                }
            }

            Schema::table('incomes', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }

    public function down(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            if (!Schema::hasColumn('incomes', 'category')) {
                $table->string('category')->nullable()->after('income_date');
            }

            if (Schema::hasColumn('incomes', 'income_category_id')) {
                $table->dropColumn('income_category_id');
            }

            if (Schema::hasColumn('incomes', 'updated_by')) {
                $table->dropColumn('updated_by');
            }

            if (Schema::hasColumn('incomes', 'cancelled_by')) {
                $table->dropColumn('cancelled_by');
            }

            if (Schema::hasColumn('incomes', 'cancelled_date')) {
                $table->dropColumn('cancelled_date');
            }

            if (Schema::hasColumn('incomes', 'cancel_reason')) {
                $table->dropColumn('cancel_reason');
            }
        });
    }
};
