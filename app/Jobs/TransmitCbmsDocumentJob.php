<?php

namespace App\Jobs;

use App\Models\CbmsTransmission;
use App\Services\Cbms\CbmsTransmissionProcessor;
use App\Services\Cbms\CbmsTransmissionStateMachine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class TransmitCbmsDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public readonly int $transmissionId) {}

    public function backoff(): array
    {
        return array_map('intval', config('cbms.retry_delays_seconds', [60, 300, 900, 3600]));
    }

    public function handle(CbmsTransmissionProcessor $processor, CbmsTransmissionStateMachine $states): void
    {
        $result = $processor->process($this->transmissionId);
        if (! $result->retryable || $result->transmission->attempt_count >= (int) config('cbms.max_attempts', 5)) return;

        DB::transaction(function () use ($states): void {
            $locked = CbmsTransmission::query()->lockForUpdate()->findOrFail($this->transmissionId);
            if ($locked->status === CbmsTransmission::STATUS_RETRYABLE_FAILURE) {
                $states->move($locked, CbmsTransmission::STATUS_QUEUED);
            }
        });
        $index = max(0, min($result->transmission->attempt_count - 1, count($this->backoff()) - 1));
        $this->release($this->backoff()[$index] ?? 3600);
    }
}
