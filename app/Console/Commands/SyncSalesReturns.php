<?php

namespace App\Console\Commands;

use App\Models\SalesReturn;
use App\Services\SalesReturnSyncService;
use Illuminate\Console\Command;

class SyncSalesReturns extends Command
{
    protected $signature = 'dg:sync-sales-returns';

    protected $description = 'DG ERP — Sync Sales Returns';

    public function handle(): int
    {
        $processed = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        $this->info('DG ERP — Sync Sales Returns');
        $this->newLine();

        SalesReturn::query()
            ->orderBy('id')
            ->chunkById(100, function ($returns) use (&$processed, &$updated, &$skipped, &$failed) {
                foreach ($returns as $return) {
                    $processed++;

                    $beforeRefunded = round((float) $return->adjust_amount, 2);
                    $beforeRemaining = round((float) $return->refund_amount, 2);

                    try {
                        SalesReturnSyncService::sync($return);

                        $return->refresh();

                        $afterRefunded = round((float) $return->adjust_amount, 2);
                        $afterRemaining = round((float) $return->refund_amount, 2);

                        if (
                            $beforeRefunded === $afterRefunded
                            && $beforeRemaining === $afterRemaining
                        ) {
                            $skipped++;
                        } else {
                            $updated++;
                        }
                    } catch (\Exception $e) {
                        $failed++;

                        $this->error(
                            "Failed [ID {$return->id} / {$return->return_no}]: {$e->getMessage()}"
                        );
                    }
                }
            });

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $processed],
                ['Updated', $updated],
                ['Skipped', $skipped],
                ['Failed', $failed],
            ]
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
