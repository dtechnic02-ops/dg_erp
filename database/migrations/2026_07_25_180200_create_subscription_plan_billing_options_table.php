<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plan_billing_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->foreignId('billing_cycle_id')->constrained('billing_cycles')->restrictOnDelete();
            $table->decimal('price', 12, 2)->default(0);
            $table->char('currency_code', 3)->default('NPR');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['subscription_plan_id', 'billing_cycle_id'], 'uq_plan_billing_option');
            $table->index(['subscription_plan_id', 'is_active'], 'idx_spbo_plan_active');
            $table->index(['billing_cycle_id', 'is_active'], 'idx_spbo_cycle_active');
        });

        if (! Schema::hasColumn('subscription_plans', 'type')) {
            return;
        }

        $cycleMap = DB::table('billing_cycles')->pluck('id', 'code');

        DB::table('subscription_plans')->orderBy('id')->each(function ($plan) use ($cycleMap) {
            $cycleCode = match ($plan->type) {
                'yearly' => 'yearly',
                'monthly' => 'monthly',
                default => null,
            };

            if (! $cycleCode || ! isset($cycleMap[$cycleCode])) {
                return;
            }

            DB::table('subscription_plan_billing_options')->updateOrInsert(
                [
                    'subscription_plan_id' => $plan->id,
                    'billing_cycle_id' => $cycleMap[$cycleCode],
                ],
                [
                    'price' => $plan->price ?? 0,
                    'currency_code' => 'NPR',
                    'is_active' => (bool) ($plan->is_active ?? true),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plan_billing_options');
    }
};
