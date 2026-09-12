<?php

namespace Tests\Fakes;

use App\Models\CbmsTransmission;
use App\Services\Cbms\CbmsReconciliationResult;
use App\Services\Cbms\CbmsReconciliationVerifier;

class FakeCbmsReconciliationVerifier implements CbmsReconciliationVerifier
{
    public function __construct(public CbmsReconciliationResult $result) {}
    public function verify(CbmsTransmission $transmission): CbmsReconciliationResult { return $this->result; }
}
