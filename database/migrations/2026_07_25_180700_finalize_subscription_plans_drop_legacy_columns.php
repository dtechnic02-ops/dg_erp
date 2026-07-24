<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_plans')) {
            return;
        }

        Schema::table('subscription_plans', function (Blueprint $table) {
            foreach (['duration_days', 'type', 'price', 'customer_limit'] as $column) {
                if (Schema::hasColumn('subscription_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_plans', 'duration_days')) {
                $table->integer('duration_days')->default(30)->after('staff_limit');
            }
            if (! Schema::hasColumn('subscription_plans', 'type')) {
                $table->enum('type', ['trial', 'monthly', 'yearly'])->default('monthly')->after('duration_days');
            }
            if (! Schema::hasColumn('subscription_plans', 'price')) {
                $table->decimal('price', 10, 2)->default(0)->after('type');
            }
            if (! Schema::hasColumn('subscription_plans', 'customer_limit')) {
                $table->integer('customer_limit')->default(0)->after('price');
            }
        });
    }
};
