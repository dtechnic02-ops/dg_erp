<?php

namespace App\Services\Cbms;

use App\Models\CbmsTransmission;

class UnavailableCbmsReconciliationVerifier implements CbmsReconciliationVerifier
{
    public function verify(CbmsTransmission $transmission): CbmsReconciliationResult
    {
        return new CbmsReconciliationResult(false);
    }
}
