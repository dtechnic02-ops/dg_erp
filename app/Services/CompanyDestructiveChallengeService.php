<?php

namespace App\Services;

use App\Models\CompanyDestructiveChallenge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class CompanyDestructiveChallengeService
{
    public const TTL_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;

    public function issue(int $companyId, int $requestedBy, string $purpose): array
    {
        return DB::transaction(function () use ($companyId, $requestedBy, $purpose): array {
            $active = CompanyDestructiveChallenge::query()
                ->where('company_id', $companyId)
                ->where('requested_by', $requestedBy)
                ->where('purpose', $purpose)
                ->whereNull('used_at')
                ->whereNull('invalidated_at')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($active && $active->created_at->gt(now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))) {
                throw new RuntimeException('Please wait before requesting another verification code.');
            }

            CompanyDestructiveChallenge::query()
                ->where('company_id', $companyId)
                ->where('requested_by', $requestedBy)
                ->where('purpose', $purpose)
                ->whereNull('used_at')
                ->whereNull('invalidated_at')
                ->update(['invalidated_at' => now(), 'otp_hash' => 'invalidated']);

            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $challenge = CompanyDestructiveChallenge::create([
                'company_id' => $companyId,
                'requested_by' => $requestedBy,
                'purpose' => $purpose,
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            ]);

            return [$challenge, $otp];
        });
    }

    public function invalidate(int $challengeId, int $companyId, int $requestedBy, string $purpose): void
    {
        CompanyDestructiveChallenge::query()
            ->whereKey($challengeId)
            ->where('company_id', $companyId)
            ->where('requested_by', $requestedBy)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['invalidated_at' => now(), 'otp_hash' => 'invalidated']);
    }

    public function consume(
        int $challengeId,
        int $companyId,
        int $requestedBy,
        string $purpose,
        string $otp
    ): CompanyDestructiveChallenge {
        $result = DB::transaction(function () use ($challengeId, $companyId, $requestedBy, $purpose, $otp): CompanyDestructiveChallenge|string {
            $challenge = CompanyDestructiveChallenge::query()->lockForUpdate()->find($challengeId);

            if (! $challenge
                || $challenge->company_id !== $companyId
                || $challenge->requested_by !== $requestedBy
                || $challenge->purpose !== $purpose
                || $challenge->invalidated_at
                || $challenge->used_at) {
                return 'invalid';
            }

            if (now()->greaterThanOrEqualTo($challenge->expires_at)) {
                $challenge->forceFill(['invalidated_at' => now(), 'otp_hash' => 'expired'])->save();
                return 'expired';
            }

            if ($challenge->attempts >= self::MAX_ATTEMPTS) {
                return 'locked';
            }

            if (! Hash::check($otp, $challenge->otp_hash)) {
                $challenge->increment('attempts');
                if ($challenge->fresh()->attempts >= self::MAX_ATTEMPTS) {
                    $challenge->forceFill(['invalidated_at' => now(), 'otp_hash' => 'locked'])->save();
                }
                return $challenge->fresh()->invalidated_at ? 'locked' : 'incorrect';
            }

            $challenge->forceFill(['used_at' => now(), 'otp_hash' => 'used'])->save();
            return $challenge;
        }, 3);

        if ($result instanceof CompanyDestructiveChallenge) {
            return $result;
        }

        throw new RuntimeException(match ($result) {
            'expired' => 'The verification code has expired. Request a new code.',
            'locked' => 'Maximum verification attempts exceeded. Request a new code.',
            'incorrect' => 'The verification code is invalid.',
            default => 'The destructive verification request is invalid or has already been used.',
        });
    }
}
