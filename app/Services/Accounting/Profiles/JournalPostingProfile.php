<?php

namespace App\Services\Accounting\Profiles;

use InvalidArgumentException;

class JournalPostingProfile
{
    public function build(array $data): array
    {
        $lines = $data['lines'] ?? null;

        if (! is_array($lines) || count($lines) < 2) {
            throw new InvalidArgumentException('A Journal accounting posting requires at least two lines.');
        }

        return [
            'company_id' => $data['company_id'],
            'financial_year_id' => $data['financial_year_id'],
            'entry_date' => $data['entry_date'],
            'reference_number' => $data['reference_number'],
            'source_module' => $data['source_module'],
            'source_type' => $data['source_type'],
            'source_id' => $data['source_id'],
            'source_event' => $data['source_event'],
            'source_key' => $data['source_key'],
            'description' => $data['description'],
            'posted_by' => $data['posted_by'],
            'lines' => array_map(fn (array $line): array => [
                'chart_account_id' => $line['chart_account_id'],
                'operational_account_id' => $line['account_id'],
                'description' => $line['description'],
                'debit' => $line['debit'],
                'credit' => $line['credit'],
                'subledger_type' => $line['subledger_type'],
                'subledger_id' => $line['subledger_id'],
            ], $lines),
        ];
    }
}
