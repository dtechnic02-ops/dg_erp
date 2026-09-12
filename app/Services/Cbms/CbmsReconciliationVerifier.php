<?php

namespace App\Services\Cbms;

use App\Models\CbmsTransmission;

interface CbmsReconciliationVerifier
{
    public function verify(CbmsTransmission $transmission): CbmsReconciliationResult;
}
