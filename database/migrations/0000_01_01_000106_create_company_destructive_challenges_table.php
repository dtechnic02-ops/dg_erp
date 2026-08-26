<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_destructive_challenges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('purpose', 50);
            $table->string('otp_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(
                ['company_id', 'requested_by', 'purpose', 'used_at', 'invalidated_at'],
                'company_destructive_challenges_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_destructive_challenges');
    }
};
