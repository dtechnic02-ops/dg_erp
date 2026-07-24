<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('company_subscription_id')->nullable()->constrained('company_subscriptions')->restrictOnDelete();
            $table->foreignId('subscription_payment_id')->nullable()->constrained('subscription_payments')->nullOnDelete();
            $table->enum('event_type', [
                'register_trial_started',
                'free_trial_assigned',
                'plan_assigned',
                'renewed',
                'upgraded',
                'downgraded',
                'activated',
                'expired',
                'cancelled',
                'payment_submitted',
                'payment_approved',
                'payment_rejected',
            ]);
            $table->enum('subscription_type_before', ['register_trial', 'free_trial', 'paid'])->nullable();
            $table->enum('subscription_type_after', ['register_trial', 'free_trial', 'paid'])->nullable();
            $table->unsignedBigInteger('subscription_plan_id_before')->nullable();
            $table->unsignedBigInteger('subscription_plan_id_after')->nullable();
            $table->unsignedBigInteger('billing_cycle_id_before')->nullable();
            $table->unsignedBigInteger('billing_cycle_id_after')->nullable();
            $table->enum('status_before', ['active', 'expired', 'cancelled'])->nullable();
            $table->enum('status_after', ['active', 'expired', 'cancelled'])->nullable();
            $table->date('start_date_before')->nullable();
            $table->date('start_date_after')->nullable();
            $table->date('expiry_date_before')->nullable();
            $table->date('expiry_date_after')->nullable();
            $table->unsignedInteger('staff_limit_before')->nullable();
            $table->unsignedInteger('staff_limit_after')->nullable();
            $table->json('hidden_modules_before')->nullable();
            $table->json('hidden_modules_after')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->dateTime('event_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'event_at'], 'idx_sh_company_event_at');
            $table->index(['company_subscription_id', 'event_at'], 'idx_sh_subscription_event_at');
            $table->index(['event_type', 'event_at'], 'idx_sh_event_type_event_at');
            $table->index('subscription_payment_id', 'idx_sh_payment_id');
            $table->index('event_at', 'idx_sh_event_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_histories');
    }
};
