<?php

namespace App\Services;

final class JournalLegacyClassificationService
{
    public function classify(array $evidence): array
    {
        if (!($evidence['valid_company'] ?? false)
            || !($evidence['valid_financial_year'] ?? false)
            || !($evidence['valid_accounts'] ?? false)
            || ($evidence['cross_company'] ?? false)
            || !($evidence['balanced'] ?? false)) {
            return ['classification' => 'unsafe', 'reason' => 'Legacy Journal has an orphan, cross-company identity, invalid Account, or is unbalanced.'];
        }

        if (($evidence['conflicting_accounting'] ?? false) || (int) ($evidence['mapping_count'] ?? 0) > 1) {
            return ['classification' => 'ambiguous', 'reason' => 'Multiple valid mappings or conflicting official accounting evidence require manual reconciliation.'];
        }

        if (($evidence['has_chart_identity'] ?? false) && !($evidence['conflicting_accounting'] ?? false)) {
            return ['classification' => 'compatible', 'reason' => 'Balanced Journal has valid company, Financial Year, and Chart Account identity without conflicting accounting evidence.'];
        }

        if ((int) ($evidence['mapping_count'] ?? 0) === 1) {
            return ['classification' => 'mappable', 'reason' => 'Balanced Journal has exactly one company-matched Chart Account mapping.'];
        }

        return ['classification' => 'ambiguous', 'reason' => 'No unique Chart Account mapping can be proven; no mapping was invented.'];
    }
}
