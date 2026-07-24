<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('financial_year_id')->nullable()->after('company_id');
            $table->index(['company_id', 'financial_year_id'], 'delivery_notes_company_fy_index');
        });

        DB::table('delivery_notes')
            ->where('status', 'processing')
            ->update(['status' => 'ready']);

        $companyIds = DB::table('delivery_notes')
            ->select('company_id')
            ->distinct()
            ->pluck('company_id');

        foreach ($companyIds as $companyId) {
            $activeFyId = DB::table('financial_years')
                ->where('company_id', $companyId)
                ->where('is_active', 1)
                ->value('id');

            if ($activeFyId) {
                DB::table('delivery_notes')
                    ->where('company_id', $companyId)
                    ->whereNull('financial_year_id')
                    ->update(['financial_year_id' => $activeFyId]);
            }
        }

        DB::statement(
            "ALTER TABLE delivery_notes MODIFY COLUMN status ENUM('draft','ready','delivered','partial','rejected','cancelled') NOT NULL DEFAULT 'draft'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE delivery_notes MODIFY COLUMN status ENUM('draft','ready','processing','delivered','partial','rejected','cancelled') NOT NULL DEFAULT 'draft'"
        );

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropIndex('delivery_notes_company_fy_index');
            $table->dropColumn('financial_year_id');
        });
    }
};
