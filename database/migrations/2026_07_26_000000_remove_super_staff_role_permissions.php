<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permission_role')
            ->where('role_id', Role::SUPER_STAFF_ID)
            ->delete();
    }

    public function down(): void
    {
        // Removed legacy pivot rows cannot be reconstructed safely without an audit snapshot.
    }
};
