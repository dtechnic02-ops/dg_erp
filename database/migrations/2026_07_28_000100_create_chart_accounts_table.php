<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('chart_accounts')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->enum('account_class', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->string('account_category')->nullable();
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->string('system_code')->nullable();
            $table->unsignedTinyInteger('level')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_control')->default(false);
            $table->boolean('allow_manual_entry')->default(true);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index('account_class');
            $table->index('system_code');
            $table->index('status');
            $table->index(['company_id', 'account_class']);
            $table->index(['company_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_accounts');
    }
};
