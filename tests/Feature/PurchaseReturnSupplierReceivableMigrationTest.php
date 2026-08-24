<?php

namespace Tests\Feature;

use Tests\TestCase;

class PurchaseReturnSupplierReceivableMigrationTest extends TestCase
{
    public function test_historical_supplier_receivable_patch_is_absorbed_into_the_final_baseline(): void
    {
        $snapshot = json_decode(
            file_get_contents(database_path('schema/pre-consolidation-structure.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $purchaseReturnColumns = array_column($snapshot['tables']['purchase_returns']['columns'], 'COLUMN_NAME');
        $refundColumns = array_column($snapshot['tables']['purchase_return_refunds']['columns'], 'COLUMN_NAME');

        $this->assertContains('request_key', $purchaseReturnColumns);
        $this->assertContains('idempotency_key', $refundColumns);
        $this->assertTrue($this->hasUniqueIndex(
            $snapshot['tables']['purchase_returns']['indexes'],
            ['company_id', 'request_key'],
        ));
        $this->assertTrue($this->hasUniqueIndex(
            $snapshot['tables']['purchase_return_refunds']['indexes'],
            ['company_id', 'idempotency_key'],
        ));

        $this->assertSame(
            [],
            glob(database_path('migrations/*provision_supplier_return_receivable_account.php')) ?: [],
            'The obsolete data-patch migration must not return to the final baseline.',
        );
    }

    private function hasUniqueIndex(array $indexes, array $expectedColumns): bool
    {
        $groups = [];
        foreach ($indexes as $index) {
            if ((int) $index['NON_UNIQUE'] === 0 && $index['INDEX_NAME'] !== 'PRIMARY') {
                $groups[$index['INDEX_NAME']][] = $index['COLUMN_NAME'];
            }
        }

        return in_array($expectedColumns, array_values($groups), true);
    }
}
