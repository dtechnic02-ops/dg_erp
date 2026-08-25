<?php

namespace App\Services;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserPasswordResetService
{
    public const LINK_TTL_MINUTES = 30;
    public const OTP_TTL_MINUTES = 10;
    public const MAX_OTP_ATTEMPTS = 5;

    public function initiate(User $user, User $initiator): array
    {
        return DB::transaction(function () use ($user, $initiator): array {
            PasswordResetRequest::query()
                ->where('user_id', $user->id)
                ->whereNull('used_at')
                ->whereNull('invalidated_at')
                ->lockForUpdate()
                ->update([
                    'invalidated_at' => now(),
                    'pending_password_hash' => null,
                    'otp_hash' => null,
                    'otp_session_hash' => null,
                ]);

            $token = Str::random(64);
            $resetRequest = PasswordResetRequest::create([
                'user_id' => $user->id,
                'initiated_by' => $initiator->id,
                'user_email' => $user->email,
                'initiated_by_email' => $initiator->email,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addMinutes(self::LINK_TTL_MINUTES),
            ]);

            return [$resetRequest, $token];
        });
    }

    public function openLink(string $token): ?PasswordResetRequest
    {
        $request = $this->activeLinkQuery($token)->first();

        if (! $request) {
            return null;
        }

        if ($request->link_opened_at === null) {
            $request->forceFill(['link_opened_at' => now()])->save();
        }

        return $request;
    }

    public function beginOtp(string $token, string $password): ?array
    {
        return DB::transaction(function () use ($token, $password): ?array {
            $request = $this->activeLinkQuery($token)->lockForUpdate()->first();

            if (! $request || ! $request->user_id || ! User::query()->whereKey($request->user_id)->where('account_status', 'active')->exists()) {
                return null;
            }

            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $challenge = Str::random(64);

            $request->forceFill([
                'token_consumed_at' => now(),
                'pending_password_hash' => Hash::make($password),
                'otp_hash' => Hash::make($otp),
                'otp_session_hash' => hash('sha256', $challenge),
                'otp_attempts' => 0,
                'otp_sent_at' => now(),
                'otp_expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            ])->save();

            return [$request, $challenge, $otp];
        });
    }

    public function openOtpChallenge(string $challenge): ?PasswordResetRequest
    {
        return $this->activeOtpQuery($challenge)->first();
    }

    public function verifyOtp(string $challenge, string $otp): string
    {
        return DB::transaction(function () use ($challenge, $otp): string {
            $request = PasswordResetRequest::query()
                ->where('otp_session_hash', hash('sha256', $challenge))
                ->lockForUpdate()
                ->first();

            if (! $request || $request->used_at || $request->invalidated_at || ! $request->pending_password_hash) {
                return 'invalid';
            }

            if (! $request->otp_expires_at || now()->greaterThanOrEqualTo($request->otp_expires_at)) {
                $this->invalidateRecord($request);

                return 'expired';
            }

            if ($request->otp_attempts >= self::MAX_OTP_ATTEMPTS) {
                $this->invalidateRecord($request);

                return 'locked';
            }

            if (! $request->otp_hash || ! Hash::check($otp, $request->otp_hash)) {
                $request->increment('otp_attempts');

                if ($request->fresh()->otp_attempts >= self::MAX_OTP_ATTEMPTS) {
                    $this->invalidateRecord($request->fresh());

                    return 'locked';
                }

                return 'incorrect';
            }

            $user = User::query()->lockForUpdate()->find($request->user_id);

            if (! $user || $user->account_status !== 'active') {
                $this->invalidateRecord($request);

                return 'invalid';
            }

            $user->forceFill([
                'password' => $request->pending_password_hash,
                'remember_token' => Str::random(60),
                'logout_at' => now(),
            ])->save();

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            $request->forceFill([
                'otp_verified_at' => now(),
                'password_changed_at' => now(),
                'used_at' => now(),
                'pending_password_hash' => null,
                'otp_hash' => null,
                'otp_session_hash' => null,
            ])->save();

            return 'completed';
        });
    }

    public function invalidate(PasswordResetRequest $request): void
    {
        DB::transaction(fn () => $this->invalidateRecord(
            PasswordResetRequest::query()->lockForUpdate()->findOrFail($request->id)
        ));
    }

    private function activeLinkQuery(string $token)
    {
        return PasswordResetRequest::query()
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('token_consumed_at')
            ->whereNull('used_at')
            ->whereNull('invalidated_at')
            ->where('expires_at', '>', now());
    }

    private function activeOtpQuery(string $challenge)
    {
        return PasswordResetRequest::query()
            ->where('otp_session_hash', hash('sha256', $challenge))
            ->whereNull('used_at')
            ->whereNull('invalidated_at')
            ->where('otp_expires_at', '>', now());
    }

    private function invalidateRecord(PasswordResetRequest $request): void
    {
        $request->forceFill([
            'invalidated_at' => now(),
            'pending_password_hash' => null,
            'otp_hash' => null,
            'otp_session_hash' => null,
        ])->save();
    }
}
