<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscriptions') && ! Schema::hasTable('company_subscriptions')) {
            Schema::rename('subscriptions', 'company_subscriptions');
        }

        if (! Schema::hasTable('company_subscriptions')) {
            Schema::create('company_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
                $table->enum('subscription_type', ['register_trial', 'free_trial', 'paid'])->default('paid');
                $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->restrictOnDelete();
                $table->foreignId('billing_cycle_id')->nullable()->constrained('billing_cycles')->restrictOnDelete();
                $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
                $table->date('start_date');
                $table->date('expiry_date')->nullable();
                $table->unsignedInteger('staff_limit');
                $table->json('hidden_modules')->nullable();
                $table->boolean('is_all_modules_enabled')->default(false);
                $table->foreignId('previous_subscription_id')->nullable()->constrained('company_subscriptions')->nullOnDelete();
                $table->dateTime('activated_at')->nullable();
                $table->dateTime('expired_at')->nullable();
                $table->dateTime('cancelled_at')->nullable();
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('cancel_reason')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('approved_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['company_id', 'status'], 'idx_cs_company_status');
                $table->index(['company_id', 'expiry_date'], 'idx_cs_company_expiry');
                $table->index(['status', 'expiry_date'], 'idx_cs_status_expiry');
                $table->index(['subscription_type', 'status'], 'idx_cs_type_status');
                $table->index('subscription_plan_id', 'idx_cs_plan_id');
                $table->index('previous_subscription_id', 'idx_cs_previous');
            });

            return;
        }

        if (Schema::hasColumn('company_subscriptions', 'plan_id') && ! Schema::hasColumn('company_subscriptions', 'subscription_plan_id')) {
            $this->dropForeignKeyIfExists('company_subscriptions', 'plan_id');

            Schema::table('company_subscriptions', function (Blueprint $table) {
                $table->renameColumn('plan_id', 'subscription_plan_id');
            });

            Schema::table('company_subscriptions', function (Blueprint $table) {
                $table->foreign('subscription_plan_id')->references('id')->on('subscription_plans')->restrictOnDelete();
            });
        }

        Schema::table('company_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('company_subscriptions', 'subscription_type')) {
                $table->enum('subscription_type', ['register_trial', 'free_trial', 'paid'])->default('paid')->after('company_id');
            }
            if (! Schema::hasColumn('company_subscriptions', 'billing_cycle_id')) {
                $table->foreignId('billing_cycle_id')->nullable()->after('subscription_plan_id')->constrained('billing_cycles')->restrictOnDelete();
            }
            if (! Schema::hasColumn('company_subscriptions', 'staff_limit')) {
                $table->unsignedInteger('staff_limit')->default(1)->after('expiry_date');
            }
            if (! Schema::hasColumn('company_subscriptions', 'hidden_modules')) {
                $table->json('hidden_modules')->nullable()->after('staff_limit');
            }
            if (! Schema::hasColumn('company_subscriptions', 'is_all_modules_enabled')) {
                $table->boolean('is_all_modules_enabled')->default(false)->after('hidden_modules');
            }
            if (! Schema::hasColumn('company_subscriptions', 'previous_subscription_id')) {
                $table->foreignId('previous_subscription_id')->nullable()->after('is_all_modules_enabled')->constrained('company_subscriptions')->nullOnDelete();
            }
            if (! Schema::hasColumn('company_subscriptions', 'activated_at')) {
                $table->dateTime('activated_at')->nullable()->after('previous_subscription_id');
            }
            if (! Schema::hasColumn('company_subscriptions', 'expired_at')) {
                $table->dateTime('expired_at')->nullable()->after('activated_at');
            }
            if (! Schema::hasColumn('company_subscriptions', 'cancelled_at')) {
                $table->dateTime('cancelled_at')->nullable()->after('expired_at');
            }
            if (! Schema::hasColumn('company_subscriptions', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('company_subscriptions', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable()->after('cancelled_by');
            }
            if (! Schema::hasColumn('company_subscriptions', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('cancel_reason')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('company_subscriptions', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('company_subscriptions', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('company_subscriptions', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
        });

        DB::table('company_subscriptions')->orderBy('id')->each(function ($row) {
            $plan = $row->subscription_plan_id
                ? DB::table('subscription_plans')->where('id', $row->subscription_plan_id)->first()
                : null;

            $company = DB::table('companies')->where('id', $row->company_id)->first();

            $staffLimit = $company->selected_user_limit ?? ($plan->staff_limit ?? 1);
            $hiddenModules = $plan->hidden_modules ?? null;
            if (is_string($hiddenModules)) {
                $hiddenModules = json_decode($hiddenModules, true);
            }

            $status = in_array($row->status, ['active', 'expired', 'cancelled'], true)
                ? $row->status
                : ($row->status === 'expired' ? 'expired' : 'active');

            DB::table('company_subscriptions')->where('id', $row->id)->update([
                'subscription_type' => 'paid',
                'staff_limit' => $staffLimit,
                'hidden_modules' => $hiddenModules ? json_encode($hiddenModules) : null,
                'is_all_modules_enabled' => empty($hiddenModules),
                'status' => $status,
                'activated_at' => $row->created_at,
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
        if (! Schema::hasTable('company_subscriptions')) {
            return;
        }

        if (Schema::hasTable('subscription_payments') || Schema::hasTable('subscription_histories')) {
            return;
        }

        Schema::dropIfExists('company_subscriptions');

        if (! Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
                $table->date('start_date');
                $table->date('expiry_date');
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }
    }
};
