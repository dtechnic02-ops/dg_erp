<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CustomerStatementService
{
    public static function recalculateAll(
        int $companyId,
        ?int $userId = null
    ): array {
        $summary = [
            'total_customers_processed'     => 0,
            'total_statements_recalculated' => 0,
            'total_errors'                  => 0,
            'errors'                        => [],
        ];

        Customer::where('company_id', $companyId)
            ->orderBy('id')
            ->chunk(100, function ($customers) use (&$summary) {
                foreach ($customers as $customer) {
                    $summary['total_customers_processed']++;

                    try {
                        DB::transaction(function () use ($customer) {
                            self::recalculateCustomer((int) $customer->id);
                        });

                        $summary['total_statements_recalculated']++;
                    } catch (Throwable $e) {
                        $summary['total_errors']++;
                        $summary['errors'][] = [
                            'customer_id'   => (int) $customer->id,
                            'customer_name' => $customer->name,
                            'message'       => $e->getMessage(),
                        ];
                    }
                }
            });

        Log::info('Customer Statement maintenance recalculation completed', [
            'company_id'                      => $companyId,
            'user_id'                         => $userId ?? auth()->id(),
            'total_customers_processed'       => $summary['total_customers_processed'],
            'total_statements_recalculated'   => $summary['total_statements_recalculated'],
            'total_errors'                    => $summary['total_errors'],
            'errors'                          => $summary['errors'],
        ]);

        return $summary;
    }

    public static function recalculateCustomer(int $customerId): array
    {
        CustomerTransactionService::recalculateCustomer($customerId);

        return self::buildRecalculationSummary($customerId);
    }

    protected static function buildRecalculationSummary(int $customerId): array
    {
        $customer = Customer::findOrFail($customerId);

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $openingBalance = 0.0;

        $transactions = CustomerTransaction::where('company_id', $customer->company_id)
            ->where('customer_id', $customerId)
            ->where('status', 1)
            ->get();

        foreach ($transactions as $transaction) {
            $debit = round((float) $transaction->debit, 2);
            $credit = round((float) $transaction->credit, 2);

            if ($transaction->reference_type === 'opening_balance') {
                $openingBalance = round($openingBalance + $debit, 2);
            }

            $totalDebit = round($totalDebit + $debit, 2);
            $totalCredit = round($totalCredit + $credit, 2);
        }

        return [
            'opening_balance' => $openingBalance,
            'total_debit'     => $totalDebit,
            'total_credit'    => $totalCredit,
            'closing_balance' => round((float) $customer->current_balance, 2),
        ];
    }
}
