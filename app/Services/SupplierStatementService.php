<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SupplierStatementService
{
    public static function recalculateAll(
        int $companyId,
        ?int $userId = null
    ): array {
        $summary = [
            'total_suppliers_processed'     => 0,
            'total_statements_recalculated' => 0,
            'total_errors'                  => 0,
            'errors'                        => [],
        ];

        Supplier::where('company_id', $companyId)
            ->orderBy('id')
            ->chunk(100, function ($suppliers) use (&$summary) {
                foreach ($suppliers as $supplier) {
                    $summary['total_suppliers_processed']++;

                    try {
                        DB::transaction(function () use ($supplier) {
                            self::recalculateSupplier((int) $supplier->id);
                        });

                        $summary['total_statements_recalculated']++;
                    } catch (Throwable $e) {
                        $summary['total_errors']++;
                        $summary['errors'][] = [
                            'supplier_id'   => (int) $supplier->id,
                            'supplier_name' => $supplier->name,
                            'message'       => $e->getMessage(),
                        ];
                    }
                }
            });

        Log::info('Supplier Statement maintenance recalculation completed', [
            'company_id'                      => $companyId,
            'user_id'                         => $userId ?? auth()->id(),
            'total_suppliers_processed'       => $summary['total_suppliers_processed'],
            'total_statements_recalculated'   => $summary['total_statements_recalculated'],
            'total_errors'                    => $summary['total_errors'],
            'errors'                          => $summary['errors'],
        ]);

        return $summary;
    }

    public static function recalculateSupplier(int $supplierId): array
    {
        SupplierTransactionService::recalculateSupplier($supplierId);

        return self::buildRecalculationSummary($supplierId);
    }

    protected static function buildRecalculationSummary(int $supplierId): array
    {
        $supplier = Supplier::findOrFail($supplierId);

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $openingBalance = 0.0;

        $transactions = SupplierTransaction::where('company_id', $supplier->company_id)
            ->where('supplier_id', $supplierId)
            ->where('status', 1)
            ->get();

        foreach ($transactions as $transaction) {
            $debit = round((float) $transaction->debit, 2);
            $credit = round((float) $transaction->credit, 2);

            if ($transaction->reference_type === 'opening_balance') {
                $openingBalance = round($openingBalance + $credit - $debit, 2);
            }

            $totalDebit = round($totalDebit + $debit, 2);
            $totalCredit = round($totalCredit + $credit, 2);
        }

        return [
            'opening_balance' => $openingBalance,
            'total_debit'     => $totalDebit,
            'total_credit'    => $totalCredit,
            'closing_balance' => round((float) $supplier->current_balance, 2),
        ];
    }
}
