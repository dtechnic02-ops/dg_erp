<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addForeignKeys('crm_configurations', [
            ['company_id', 'companies', 'id', 'cascade'],
            ['created_by', 'users', 'id'],
            ['updated_by', 'users', 'id'],
        ]);

        $this->addForeignKeys('crm_leads', [
            ['company_id', 'companies', 'id', 'cascade'],
            ['financial_year_id', 'financial_years', 'id'],
            ['assigned_employee_id', 'employee_accounts', 'id', 'restrict'],
            ['customer_id', 'customers', 'id'],
            ['created_by', 'users', 'id'],
            ['updated_by', 'users', 'id'],
            ['closed_by', 'users', 'id'],
            ['archived_by', 'users', 'id'],
            ['cancelled_by', 'users', 'id'],
            ['converted_by', 'users', 'id'],
        ]);

        $this->addForeignKeys('crm_opportunities', [
            ['company_id', 'companies', 'id', 'cascade'],
            ['financial_year_id', 'financial_years', 'id'],
            ['crm_lead_id', 'crm_leads', 'id'],
            ['customer_id', 'customers', 'id'],
            ['assigned_employee_id', 'employee_accounts', 'id', 'restrict'],
            ['created_by', 'users', 'id'],
            ['updated_by', 'users', 'id'],
            ['closed_by', 'users', 'id'],
            ['archived_by', 'users', 'id'],
            ['cancelled_by', 'users', 'id'],
        ]);

        $this->addForeignKeys('crm_contacts', [
            ['company_id', 'companies', 'id', 'cascade'],
            ['financial_year_id', 'financial_years', 'id'],
            ['crm_lead_id', 'crm_leads', 'id'],
            ['customer_id', 'customers', 'id'],
            ['assigned_employee_id', 'employee_accounts', 'id', 'restrict'],
            ['created_by', 'users', 'id'],
            ['updated_by', 'users', 'id'],
            ['closed_by', 'users', 'id'],
            ['archived_by', 'users', 'id'],
            ['cancelled_by', 'users', 'id'],
        ]);

        foreach (['crm_follow_ups', 'crm_meetings', 'crm_tasks'] as $table) {
            $this->addForeignKeys($table, [
                ['company_id', 'companies', 'id', 'cascade'],
                ['financial_year_id', 'financial_years', 'id'],
                ['crm_lead_id', 'crm_leads', 'id'],
                ['crm_opportunity_id', 'crm_opportunities', 'id'],
                ['assigned_employee_id', 'employee_accounts', 'id', 'restrict'],
                ['created_by', 'users', 'id'],
                ['updated_by', 'users', 'id'],
                ['archived_by', 'users', 'id'],
                ['cancelled_by', 'users', 'id'],
            ]);
        }

        $this->addForeignKeys('crm_notes', [
            ['company_id', 'companies', 'id', 'cascade'],
            ['created_by', 'users', 'id'],
            ['updated_by', 'users', 'id'],
            ['archived_by', 'users', 'id'],
        ]);

        $this->addForeignKeys('crm_attachments', [
            ['company_id', 'companies', 'id', 'cascade'],
            ['created_by', 'users', 'id'],
            ['archived_by', 'users', 'id'],
        ]);

        $this->addForeignKeys('crm_status_histories', [
            ['company_id', 'companies', 'id', 'cascade'],
            ['changed_by', 'users', 'id'],
        ]);
    }

    public function down(): void
    {
        // Foreign keys are removed automatically when CRM tables are rolled back.
    }

    private function addForeignKeys(string $table, array $definitions): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($table, $definitions) {
            foreach ($definitions as $definition) {
                [$column, $referencedTable, $referencedColumn] = $definition;
                $onDelete = $definition[3] ?? 'null';

                if (!Schema::hasColumn($table, $column)) {
                    continue;
                }

                $indexName = substr($table . '_' . $column . '_foreign', 0, 64);

                try {
                    $foreign = $blueprint->foreign($column, $indexName)
                        ->references($referencedColumn)
                        ->on($referencedTable);

                    match ($onDelete) {
                        'cascade' => $foreign->cascadeOnDelete(),
                        'restrict' => $foreign->restrictOnDelete(),
                        default => $foreign->nullOnDelete(),
                    };
                } catch (\Throwable $e) {
                    // Skip if FK already exists or data prevents creation.
                }
            }
        });
    }
};
