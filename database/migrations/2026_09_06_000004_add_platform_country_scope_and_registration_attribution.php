<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('country_id')->nullable()->after('company_id')->constrained('countries')->restrictOnDelete();
        });
        Schema::table('company_registrations', function (Blueprint $table): void {
            $table->foreignId('registered_by_user_id')->nullable()->after('country_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->restrictOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
        });
    }

    public function down(): void
    {
        Schema::table('company_registrations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('registered_by_user_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn('approved_at');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn('rejected_at');
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('country_id'));
    }
};
