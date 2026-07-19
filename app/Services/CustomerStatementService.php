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
        $customer = Customer::findOrFail($customerId);

        $openingBalance = round((float) $customer->opening_balance, 2);
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $runningBalance = $openingBalance;

        $transactions = CustomerTransaction::where('company_id', $customer->company_id)
            ->where('customer_id', $customerId)
            ->where('status', 1)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        foreach ($transactions as $transaction) {
            if (self::isOpeningBalanceEntry($transaction)) {
                $transaction->update([
                    'balance' => $openingBalance,
                ]);

                continue;
            }

            $debit = round((float) $transaction->debit, 2);
            $credit = round((float) $transaction->credit, 2);

            $totalDebit = round($totalDebit + $debit, 2);
            $totalCredit = round($totalCredit + $credit, 2);
            $runningBalance = round($runningBalance + $debit - $credit, 2);

            $transaction->update([
                'balance' => $runningBalance,
            ]);
        }

        $closingBalance = round(
            $openingBalance + $totalDebit - $totalCredit,
            2
        );

        if (abs($closingBalance - $runningBalance) > 0.01) {
            throw new \RuntimeException(
                'Closing balance mismatch for customer ID ' . $customerId . '.'
            );
        }

        $customer->update([
            'current_balance' => $closingBalance,
        ]);

        return [
            'opening_balance' => $openingBalance,
            'total_debit'     => $totalDebit,
            'total_credit'    => $totalCredit,
            'closing_balance' => $closingBalance,
        ];
    }

    protected static function isOpeningBalanceEntry(
        CustomerTransaction $transaction
    ): bool {
        return $transaction->reference_type === 'opening_balance';
    }
}
