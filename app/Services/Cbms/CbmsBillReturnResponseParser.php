<?php

namespace App\Services\Cbms;

use App\Models\CbmsTransmission;

class CbmsBillReturnResponseParser
{
    public function parse(int|string $code): array
    {
        $code = (string) $code;
        return match ($code) {
            '200' => ['code' => $code, 'status' => CbmsTransmission::STATUS_SUBMITTED, 'category' => 'submitted'],
            '101' => ['code' => $code, 'status' => CbmsTransmission::STATUS_DUPLICATE_REQUIRES_RECONCILIATION, 'category' => 'ambiguous_bill_return_101'],
            '102', '103' => ['code' => $code, 'status' => CbmsTransmission::STATUS_RETRYABLE_FAILURE, 'category' => $code === '102' ? 'saving_exception' : 'unknown_api_issue'],
            '100', '104', '105' => ['code' => $code, 'status' => CbmsTransmission::STATUS_PERMANENT_FAILURE, 'category' => match ($code) { '100' => 'credential_failure', '104' => 'model_invalid', default => 'referenced_bill_not_found' }],
            default => ['code' => $code, 'status' => CbmsTransmission::STATUS_RETRYABLE_FAILURE, 'category' => 'unrecognized_response'],
        };
    }
}
