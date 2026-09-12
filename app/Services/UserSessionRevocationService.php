<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserSessionRevocationService
{
    public function revoke(User $user): void
    {
        $user->forceFill(['remember_token' => null])->save();

        if (config('session.driver') === 'database' && Schema::hasTable((string) config('session.table', 'sessions'))) {
            DB::table((string) config('session.table', 'sessions'))->where('user_id', $user->getKey())->delete();
        }
    }
}
