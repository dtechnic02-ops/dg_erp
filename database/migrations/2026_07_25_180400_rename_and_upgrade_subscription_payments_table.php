<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments') && ! Schema::hasTable('subscription_payments')) {
            Schema::rename('payments', 'subscription_payments');
        }

        if (! Schema::hasTable('subscription_payments')) {
            Schema::create('subscription_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
                $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->restrictOnDelete();
                $table->foreignId('billing_cycle_id')->constrained('billing_cycles')->restrictOnDelete();
                $table->enum('action_type', ['assign', 'renew', 'upgrade', 'downgrade'])->default('assign');
                $table->decimal('amount', 12, 2);
                $table->char('currency_code', 3)->default('NPR');
                $table->string('payment_method', 50);
                $table->date('payment_date');
                $table->string('reference_no', 100)->nullable();
                $table->string('proof_path')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
                $table->foreignId('company_subscription_id')->nullable()->constrained('company_subscriptions')->nullOnDelete();
                $table->foreignId('target_subscription_id')->nullable()->constrained('company_subscriptions')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->dateTime('verified_at')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('rejected_at')->nullable();
                $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('rejection_reason')->nullable();
                $table->dateTime('cancelled_at')->nullable();
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('cancel_reason')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['company_id', 'status'], 'idx_sp_company_status');
                $table->index(['status', 'payment_date'], 'idx_sp_status_payment_date');
                $table->index(['action_type', 'status'], 'idx_sp_action_status');
                $table->index('approved_at', 'idx_sp_approved_at');
                $table->index(['subscription_plan_id', 'billing_cycle_id'], 'idx_sp_plan_cycle');
            });

            return;
        }

        if (Schema::hasColumn('subscription_payments', 'plan_id') && ! Schema::hasColumn('subscription_payments', 'subscription_plan_id')) {
            $this->dropForeignKeyIfExists('subscription_payments', 'plan_id');

            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->renameColumn('plan_id', 'subscription_plan_id');
            });

            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->foreign('subscription_plan_id')->references('id')->on('subscription_plans')->restrictOnDelete();
            });
        }

        Schema::table('subscription_payments', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_payments', 'method') && ! Schema::hasColumn('subscription_payments', 'payment_method')) {
                $table->renameColumn('method', 'payment_method');
            }
            if (Schema::hasColumn('subscription_payments', 'note') && ! Schema::hasColumn('subscription_payments', 'notes')) {
                $table->renameColumn('note', 'notes');
            }
            if (Schema::hasColumn('subscription_payments', 'screenshot') && ! Schema::hasColumn('subscription_payments', 'proof_path')) {
                $table->renameColumn('screenshot', 'proof_path');
            }
            if (! Schema::hasColumn('subscription_payments', 'billing_cycle_id')) {
                $table->foreignId('billing_cycle_id')->nullable()->after('subscription_plan_id')->constrained('billing_cycles')->restrictOnDelete();
            }
            if (! Schema::hasColumn('subscription_payments', 'action_type')) {
                $table->enum('action_type', ['assign', 'renew', 'upgrade', 'downgrade'])->default('assign')->after('billing_cycle_id');
            }
            if (! Schema::hasColumn('subscription_payments', 'currency_code')) {
                $table->char('currency_code', 3)->default('NPR')->after('amount');
            }
            if (! Schema::hasColumn('subscription_payments', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('subscription_payments', 'reference_no')) {
                $table->string('reference_no', 100)->nullable()->after('payment_date');
            }
            if (! Schema::hasColumn('subscription_payments', 'company_subscription_id')) {
                $table->foreignId('company_subscription_id')->nullable()->after('status')->constrained('company_subscriptions')->nullOnDelete();
            }
            if (! Schema::hasColumn('subscription_payments', 'target_subscription_id')) {
                $table->foreignId('target_subscription_id')->nullable()->after('company_subscription_id')->constrained('company_subscriptions')->nullOnDelete();
            }
            if (! Schema::hasColumn('subscription_payments', 'verified_at')) {
                $table->dateTime('verified_at')->nullable()->after('notes');
                $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('subscription_payments', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('verified_by');
                $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('subscription_payments', 'rejected_at')) {
                $table->dateTime('rejected_at')->nullable()->after('approved_by');
                $table->foreignId('rejected_by')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('subscription_payments', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_by');
            }
            if (! Schema::hasColumn('subscription_payments', 'cancelled_at')) {
                $table->dateTime('cancelled_at')->nullable()->after('rejection_reason');
                $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('subscription_payments', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable()->after('cancelled_by');
            }
            if (! Schema::hasColumn('subscription_payments', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('subscription_payments', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        $monthlyCycleId = DB::table('billing_cycles')->where('code', 'monthly')->value('id');

        DB::table('subscription_payments')->orderBy('id')->each(function ($payment) use ($monthlyCycleId) {
            $plan = DB::table('subscription_plans')->where('id', $payment->subscription_plan_id)->first();
            $cycleId = $monthlyCycleId;

            if ($plan && property_exists($plan, 'type') && $plan->type) {
                $cycleCode = $plan->type === 'yearly' ? 'yearly' : 'monthly';
                $cycleId = DB::table('billing_cycles')->where('code', $cycleCode)->value('id') ?? $monthlyCycleId;
            }

            DB::table('subscription_payments')->where('id', $payment->id)->update([
                'billing_cycle_id' => $cycleId,
                'action_type' => 'assign',
                'payment_date' => $payment->created_at ? date('Y-m-d', strtotime($payment->created_at)) : now()->toDateString(),
                'amount' => $payment->amount,
            ]);
        });
    }

    protected function dropForeignKeyIfExists(string $table, string $column): void
    {
        $database = Schema::getConnection()->getDatabaseName();

        $constraints = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$database, $table, $column]
        );

        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint->CONSTRAINT_NAME}`");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('subscription_payments')) {
            return;
        }

        Schema::rename('subscription_payments', 'payments');
    }
};
