<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_return_refunds', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_return_refunds', 'refund_amount')) {
                $table->decimal('refund_amount', 18, 2)
                    ->default(0)
                    ->after('refund_date');
            }
        });

        DB::table('purchase_return_refunds')
            ->where(function ($query) {
                $query->where('cash_amount', '<=', 0)
                    ->where('adjust_amount', '<=', 0)
                    ->where('amount', '>', 0);
            })
            ->update([
                'cash_amount' => DB::raw('amount'),
            ]);

        DB::table('purchase_return_refunds')->update([
            'refund_amount' => DB::raw('COALESCE(NULLIF(refund_amount, 0), amount, cash_amount + adjust_amount, 0)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('purchase_return_refunds', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_return_refunds', 'refund_amount')) {
                $table->dropColumn('refund_amount');
            }
        });
    }
};
