<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('income_categories', 'chart_account_id')) {
            Schema::table('income_categories', function (Blueprint $table): void {
                $table->foreignId('chart_account_id')
                    ->after('company_id')
                    ->constrained('chart_accounts')
                    ->restrictOnDelete();
            });

            return;
        }

        if (! $this->hasChartAccountForeignKey()) {
            Schema::table('income_categories', function (Blueprint $table): void {
                $table->foreign('chart_account_id')
                    ->references('id')
                    ->on('chart_accounts')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('income_categories', 'chart_account_id')) {
            return;
        }

        Schema::table('income_categories', function (Blueprint $table): void {
            if ($this->hasChartAccountForeignKey()) {
                $table->dropForeign(['chart_account_id']);
            }

            $table->dropColumn('chart_account_id');
        });
    }

    private function hasChartAccountForeignKey(): bool
    {
        foreach (Schema::getForeignKeys('income_categories') as $foreignKey) {
            if (
                $foreignKey['columns'] === ['chart_account_id']
                && $foreignKey['foreign_table'] === 'chart_accounts'
                && $foreignKey['foreign_columns'] === ['id']
            ) {
                return true;
            }
        }

        return false;
    }
};
