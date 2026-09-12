<?php

namespace App\Services\Cbms;

use App\Models\CbmsTransmission;

class CbmsSalesResponseParser
{
    public function parse(int|string $code): array
    {
        $code = (string) $code;
        return match ($code) {
            '200' => ['code' => $code, 'status' => CbmsTransmission::STATUS_SUBMITTED, 'category' => 'submitted'],
            '101' => ['code' => $code, 'status' => CbmsTransmission::STATUS_DUPLICATE_REQUIRES_RECONCILIATION, 'category' => 'bill_already_exists'],
            '102', '103' => ['code' => $code, 'status' => CbmsTransmission::STATUS_RETRYABLE_FAILURE, 'category' => $code === '102' ? 'saving_exception' : 'unknown_api_issue'],
            '100', '104' => ['code' => $code, 'status' => CbmsTransmission::STATUS_PERMANENT_FAILURE, 'category' => $code === '100' ? 'credential_failure' : 'model_invalid'],
            default => ['code' => $code, 'status' => CbmsTransmission::STATUS_RETRYABLE_FAILURE, 'category' => 'unrecognized_response'],
        };
    }
}
