<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_email');
            $table->string('initiated_by_email');
            $table->string('token_hash', 64)->unique();
            $table->string('otp_session_hash', 64)->nullable()->unique();
            $table->string('pending_password_hash')->nullable();
            $table->string('otp_hash')->nullable();
            $table->unsignedTinyInteger('otp_attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('link_opened_at')->nullable();
            $table->timestamp('token_consumed_at')->nullable();
            $table->timestamp('otp_sent_at')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamp('otp_verified_at')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'used_at', 'invalidated_at'], 'password_reset_active_user_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_requests');
    }
};
