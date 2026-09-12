<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceForeignKey('sales_invoices', 'financial_year_id', 'sales_invoices_financial_year_id_foreign', function (Blueprint $table): void {
            $table->foreign('financial_year_id', 'sales_invoices_financial_year_id_foreign')
                ->references('id')->on('financial_years')->restrictOnDelete()->restrictOnUpdate();
        });
        $this->replaceForeignKey('sales_returns', 'financial_year_id', 'sales_returns_financial_year_id_foreign', function (Blueprint $table): void {
            $table->foreign('financial_year_id', 'sales_returns_financial_year_id_foreign')
                ->references('id')->on('financial_years')->restrictOnDelete()->restrictOnUpdate();
        });
        $this->replaceForeignKey('financial_years', 'company_id', 'financial_years_company_id_foreign', function (Blueprint $table): void {
            $table->foreign('company_id', 'financial_years_company_id_foreign')
                ->references('id')->on('companies')->restrictOnDelete()->restrictOnUpdate();
        });
        $this->replaceForeignKey('fiscal_document_audit_events', 'company_id', 'fiscal_document_audit_events_company_id_foreign', function (Blueprint $table): void {
            $table->foreign('company_id', 'fiscal_document_audit_events_company_id_foreign')
                ->references('id')->on('companies')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        $this->replaceForeignKey('sales_invoices', 'financial_year_id', 'sales_invoices_financial_year_id_foreign', function (Blueprint $table): void {
            $table->foreign('financial_year_id', 'sales_invoices_financial_year_id_foreign')
                ->references('id')->on('financial_years')->nullOnDelete()->restrictOnUpdate();
        });
        $this->replaceForeignKey('sales_returns', 'financial_year_id', 'sales_returns_financial_year_id_foreign', function (Blueprint $table): void {
            $table->foreign('financial_year_id', 'sales_returns_financial_year_id_foreign')
                ->references('id')->on('financial_years')->cascadeOnDelete()->restrictOnUpdate();
        });
        $this->replaceForeignKey('financial_years', 'company_id', 'financial_years_company_id_foreign', function (Blueprint $table): void {
            $table->foreign('company_id', 'financial_years_company_id_foreign')
                ->references('id')->on('companies')->cascadeOnDelete()->restrictOnUpdate();
        });
        $this->replaceForeignKey('fiscal_document_audit_events', 'company_id', 'fiscal_document_audit_events_company_id_foreign', function (Blueprint $table): void {
            $table->foreign('company_id', 'fiscal_document_audit_events_company_id_foreign')
                ->references('id')->on('companies')->cascadeOnDelete()->restrictOnUpdate();
        });
    }

    private function replaceForeignKey(string $tableName, string $column, string $foreignKey, callable $definition): void
    {
        Schema::table($tableName, fn (Blueprint $table) => $table->dropForeign(
            DB::getDriverName() === 'sqlite' ? [$column] : $foreignKey
        ));
        Schema::table($tableName, $definition);
    }
};
