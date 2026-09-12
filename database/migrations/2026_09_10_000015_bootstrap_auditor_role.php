<?php

use App\Services\PlatformRoleBootstrapService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('roles')->exists()) {
            app(PlatformRoleBootstrapService::class)->seedRequiredRoles();
        }
    }

    public function down(): void
    {
        // Assigned users may reference this additive role; rollback preserves it safely.
    }
};
