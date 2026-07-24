<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plans') && ! Schema::hasTable('subscription_plans')) {
            Schema::rename('plans', 'subscription_plans');
        }

        if (! Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->unsignedInteger('staff_limit');
                $table->json('hidden_modules')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('cancelled_at')->nullable();
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('cancel_reason')->nullable();
                $table->timestamps();
                $table->index(['is_active', 'sort_order'], 'idx_subscription_plans_active_sort');
            });

            return;
        }

        Schema::table('subscription_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_plans', 'code')) {
                $table->string('code', 50)->nullable()->after('id');
            }
            if (! Schema::hasColumn('subscription_plans', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (Schema::hasColumn('subscription_plans', 'user_limit') && ! Schema::hasColumn('subscription_plans', 'staff_limit')) {
                $table->renameColumn('user_limit', 'staff_limit');
            } elseif (! Schema::hasColumn('subscription_plans', 'staff_limit')) {
                $table->unsignedInteger('staff_limit')->default(1)->after('description');
            }
            if (! Schema::hasColumn('subscription_plans', 'hidden_modules')) {
                $table->json('hidden_modules')->nullable()->after('staff_limit');
            }
            if (! Schema::hasColumn('subscription_plans', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_active');
            }
            if (! Schema::hasColumn('subscription_plans', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('sort_order')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('subscription_plans', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('subscription_plans', 'cancelled_at')) {
                $table->dateTime('cancelled_at')->nullable()->after('updated_by');
            }
            if (! Schema::hasColumn('subscription_plans', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('subscription_plans', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable()->after('cancelled_by');
            }
        });

        $plans = DB::table('subscription_plans')->whereNull('code')->orWhere('code', '')->get();
        foreach ($plans as $plan) {
            $code = Str::slug($plan->name, '_');
            if ($code === '' || $code === 'trial') {
                $code = 'legacy_plan_' . $plan->id;
            }
            $suffix = 1;
            $base = $code;
            while (DB::table('subscription_plans')->where('code', $code)->where('id', '!=', $plan->id)->exists()) {
                $code = $base . '_' . $suffix;
                $suffix++;
            }
            DB::table('subscription_plans')->where('id', $plan->id)->update(['code' => $code]);
        }

        Schema::table('subscription_plans', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_plans', 'code')) {
                $table->string('code', 50)->nullable(false)->change();
                $table->unique('code', 'uq_subscription_plans_code');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('subscription_plans')) {
            return;
        }

        if (Schema::hasTable('subscription_plan_billing_options')) {
            return;
        }

        Schema::table('subscription_plans', function (Blueprint $table) {
            foreach (['code', 'description', 'hidden_modules', 'sort_order', 'created_by', 'updated_by', 'cancelled_at', 'cancelled_by', 'cancel_reason'] as $column) {
                if (Schema::hasColumn('subscription_plans', $column)) {
                    if (in_array($column, ['created_by', 'updated_by', 'cancelled_by'], true)) {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
            if (Schema::hasColumn('subscription_plans', 'staff_limit') && ! Schema::hasColumn('subscription_plans', 'user_limit')) {
                $table->renameColumn('staff_limit', 'user_limit');
            }
        });

        Schema::rename('subscription_plans', 'plans');
    }
};
