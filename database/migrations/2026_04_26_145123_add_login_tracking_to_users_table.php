<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {

        // 🔐 account status
        $table->string('account_status')->default('active'); 
        // active / blocked

        // 🟢 online status
        $table->string('online_status')->default('offline'); 
        // online / offline

        $table->timestamp('login_at')->nullable();
        $table->timestamp('logout_at')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
