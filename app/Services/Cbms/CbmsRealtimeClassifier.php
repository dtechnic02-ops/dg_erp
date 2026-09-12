<?php

namespace App\Services\Cbms;

use DateTimeInterface;
use Illuminate\Support\Carbon;

class CbmsRealtimeClassifier
{
    public function isRealtime(DateTimeInterface $issuedAt, DateTimeInterface $attemptedAt): bool
    {
        $elapsed = Carbon::instance($issuedAt)->diffInSeconds(Carbon::instance($attemptedAt), false);
        return $elapsed >= 0 && $elapsed <= (int) config('cbms.realtime_window_seconds', 300);
    }

    public function clientDateTime(DateTimeInterface $attemptedAt): string
    {
        return Carbon::instance($attemptedAt)->format('Y-m-d H:i:s');
    }
}
