<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $platformPermissions = [
        'view_subscription_module',
        'manage_subscription_module',
        'view_company',
        'create_company',
        'edit_company',
        'approve_company',
        'block_company',
        'unblock_company',
        'delete_company',
        'reset_company_password',
    ];

    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('scope', 20)->default('company')->after('name');
        });

        DB::table('permissions')->update(['scope' => 'company']);

        DB::table('permissions')
            ->whereIn('name', $this->platformPermissions)
            ->update(['scope' => 'platform']);
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('scope');
        });
    }
};
