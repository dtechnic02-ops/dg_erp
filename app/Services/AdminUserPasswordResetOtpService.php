<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserPasswordResetOtpService
{
    private const TTL_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    public function issue(int $adminUserId, int $targetUserId): array
    {
        $activeKey = $this->activeKey($adminUserId, $targetUserId);

        if ($previousRequestId = Cache::get($activeKey)) {
            Cache::forget($this->requestKey($previousRequestId));
        }

        $requestId = (string) Str::uuid();
        $expiresAt = now()->addMinutes(self::TTL_MINUTES);
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->requestKey($requestId), [
            'admin_user_id' => $adminUserId,
            'target_user_id' => $targetUserId,
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => $expiresAt->toIso8601String(),
            'otp_used_at' => null,
            'verified_at' => null,
        ], $expiresAt);
        Cache::put($activeKey, $requestId, $expiresAt);

        return [$requestId, $otp];
    }

    public function verify(string $requestId, int $adminUserId, int $targetUserId, string $otp): string
    {
        $payload = Cache::get($this->requestKey($requestId));

        if (! $this->belongsToRequest($payload, $adminUserId, $targetUserId)) {
            return 'invalid';
        }

        $expiresAt = Carbon::parse($payload['expires_at']);

        if (now()->greaterThanOrEqualTo($expiresAt)) {
            $this->invalidate($requestId, $adminUserId, $targetUserId);

            return 'expired';
        }

        if ($payload['otp_used_at'] !== null) {
            return 'used';
        }

        if (! Hash::check($otp, $payload['otp_hash'])) {
            $payload['attempts']++;

            if ($payload['attempts'] >= self::MAX_ATTEMPTS) {
                $this->invalidate($requestId, $adminUserId, $targetUserId);

                return 'locked';
            }

            Cache::put($this->requestKey($requestId), $payload, $expiresAt);

            return 'incorrect';
        }

        $payload['otp_used_at'] = now()->toIso8601String();
        $payload['verified_at'] = now()->toIso8601String();
        Cache::put($this->requestKey($requestId), $payload, $expiresAt);

        return 'verified';
    }

    public function isVerified(string $requestId, int $adminUserId, int $targetUserId): bool
    {
        $payload = Cache::get($this->requestKey($requestId));

        return $this->belongsToRequest($payload, $adminUserId, $targetUserId)
            && $payload['otp_used_at'] !== null
            && $payload['verified_at'] !== null
            && now()->lessThan(Carbon::parse($payload['expires_at']));
    }

    public function invalidate(string $requestId, int $adminUserId, int $targetUserId): void
    {
        Cache::forget($this->requestKey($requestId));
        Cache::forget($this->activeKey($adminUserId, $targetUserId));
    }

    private function belongsToRequest(mixed $payload, int $adminUserId, int $targetUserId): bool
    {
        return is_array($payload)
            && (int) ($payload['admin_user_id'] ?? 0) === $adminUserId
            && (int) ($payload['target_user_id'] ?? 0) === $targetUserId;
    }

    private function requestKey(string $requestId): string
    {
        return 'admin_user_password_reset.'.$requestId;
    }

    private function activeKey(int $adminUserId, int $targetUserId): string
    {
        return 'admin_user_password_reset.active.'.$adminUserId.'.'.$targetUserId;
    }
}
