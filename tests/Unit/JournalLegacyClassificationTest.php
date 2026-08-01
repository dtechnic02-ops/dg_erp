<?php

namespace Tests\Unit;

use App\Services\JournalLegacyClassificationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JournalLegacyClassificationTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_all_legacy_classifications_are_reasoned_and_preserved(array $evidence, string $expected, string $reasonFragment): void
    {
        $result=(new JournalLegacyClassificationService)->classify($evidence);
        $this->assertSame($expected,$result['classification']);
        $this->assertStringContainsString($reasonFragment,$result['reason']);
        $this->assertNotSame('',trim($result['reason']));
    }

    public static function cases(): array
    {
        $valid=['valid_company'=>true,'valid_financial_year'=>true,'valid_accounts'=>true,'cross_company'=>false,'balanced'=>true,'conflicting_accounting'=>false];
        return [
            'compatible'=>[$valid+['has_chart_identity'=>true,'mapping_count'=>1],'compatible','Chart Account identity'],
            'mappable'=>[$valid+['has_chart_identity'=>false,'mapping_count'=>1],'mappable','exactly one company-matched'],
            'ambiguous'=>[$valid+['has_chart_identity'=>false,'mapping_count'=>2],'ambiguous','Multiple valid mappings'],
            'unsafe'=>[array_replace($valid,['balanced'=>false]),'unsafe','unbalanced'],
        ];
    }
}
