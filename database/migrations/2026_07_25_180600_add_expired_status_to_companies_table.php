<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE companies MODIFY status ENUM('active','blocked','expired') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE companies MODIFY status ENUM('active','blocked') NOT NULL DEFAULT 'active'");
    }
};
