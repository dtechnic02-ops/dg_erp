<?php

namespace App\Services\Cbms;

use App\Services\NepaliDateService;
use DateTimeInterface;

class CbmsDateFormatter
{
    public function __construct(private readonly NepaliDateService $dates) {}
    public function format(string|DateTimeInterface $date): string { return $this->dates->adToIrdBs($date); }
}
