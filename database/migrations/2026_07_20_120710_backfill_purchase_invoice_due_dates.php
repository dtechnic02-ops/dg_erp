<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasColumn('purchase_invoices', 'due_date')
            || !Schema::hasColumn('suppliers', 'credit_days')
        ) {
            return;
        }

        DB::statement(
            'UPDATE purchase_invoices pi
             INNER JOIN suppliers s ON pi.supplier_id = s.id
             SET pi.due_date = DATE_ADD(pi.purchase_date, INTERVAL COALESCE(s.credit_days, 0) DAY)
             WHERE pi.due_date IS NULL OR pi.due_date = pi.purchase_date'
        );
    }

    public function down(): void
    {
        // Data backfill only; no schema rollback.
    }
};
