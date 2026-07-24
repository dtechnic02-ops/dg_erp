<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_opportunities')) {
            DB::table('crm_opportunities as o')
                ->join('crm_leads as l', function ($join) {
                    $join->on('o.crm_lead_id', '=', 'l.id')
                        ->on('o.company_id', '=', 'l.company_id');
                })
                ->whereNull('o.customer_id')
                ->whereNotNull('l.customer_id')
                ->update(['o.customer_id' => DB::raw('l.customer_id')]);

            Schema::table('crm_opportunities', function (Blueprint $table) {
                if ($this->foreignKeyExists('crm_opportunities', 'crm_opportunities_customer_id_foreign')) {
                    $table->dropForeign('crm_opportunities_customer_id_foreign');
                }

                if (Schema::hasColumn('crm_opportunities', 'customer_id')) {
                    $table->unsignedBigInteger('customer_id')->nullable(false)->change();
                }

                if (! $this->indexExists('crm_opportunities', 'crm_opportunities_company_customer_index')) {
                    $table->index(['company_id', 'customer_id'], 'crm_opportunities_company_customer_index');
                }

                $table->foreign('customer_id', 'crm_opportunities_customer_id_foreign')
                    ->references('id')
                    ->on('customers')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('crm_contacts')) {
            DB::table('crm_contacts as c')
                ->join('crm_leads as l', function ($join) {
                    $join->on('c.crm_lead_id', '=', 'l.id')
                        ->on('c.company_id', '=', 'l.company_id');
                })
                ->whereNull('c.customer_id')
                ->whereNotNull('l.customer_id')
                ->update(['c.customer_id' => DB::raw('l.customer_id')]);

            Schema::table('crm_contacts', function (Blueprint $table) {
                if (Schema::hasColumn('crm_contacts', 'address')) {
                    $table->dropColumn('address');
                }

                if (! Schema::hasColumn('crm_contacts', 'department')) {
                    $table->string('department', 100)->nullable()->after('designation');
                }

                if (! Schema::hasColumn('crm_contacts', 'phone')) {
                    $table->string('phone', 30)->nullable()->after('mobile');
                }

                if ($this->foreignKeyExists('crm_contacts', 'crm_contacts_customer_id_foreign')) {
                    $table->dropForeign('crm_contacts_customer_id_foreign');
                }

                if (Schema::hasColumn('crm_contacts', 'customer_id')) {
                    $table->unsignedBigInteger('customer_id')->nullable(false)->change();
                }

                $table->foreign('customer_id', 'crm_contacts_customer_id_foreign')
                    ->references('id')
                    ->on('customers')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('crm_opportunities')) {
            Schema::table('crm_opportunities', function (Blueprint $table) {
                if ($this->foreignKeyExists('crm_opportunities', 'crm_opportunities_customer_id_foreign')) {
                    $table->dropForeign('crm_opportunities_customer_id_foreign');
                }

                if ($this->indexExists('crm_opportunities', 'crm_opportunities_company_customer_index')) {
                    $table->dropIndex('crm_opportunities_company_customer_index');
                }

                if (Schema::hasColumn('crm_opportunities', 'customer_id')) {
                    $table->unsignedBigInteger('customer_id')->nullable()->change();
                }

                $table->foreign('customer_id', 'crm_opportunities_customer_id_foreign')
                    ->references('id')
                    ->on('customers')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('crm_contacts')) {
            Schema::table('crm_contacts', function (Blueprint $table) {
                if ($this->foreignKeyExists('crm_contacts', 'crm_contacts_customer_id_foreign')) {
                    $table->dropForeign('crm_contacts_customer_id_foreign');
                }

                if (Schema::hasColumn('crm_contacts', 'department')) {
                    $table->dropColumn('department');
                }

                if (Schema::hasColumn('crm_contacts', 'phone')) {
                    $table->dropColumn('phone');
                }

                if (! Schema::hasColumn('crm_contacts', 'address')) {
                    $table->text('address')->nullable()->after('email');
                }

                if (Schema::hasColumn('crm_contacts', 'customer_id')) {
                    $table->unsignedBigInteger('customer_id')->nullable()->change();
                }

                $table->foreign('customer_id', 'crm_contacts_customer_id_foreign')
                    ->references('id')
                    ->on('customers')
                    ->nullOnDelete();
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $indexName]
        );

        return ! empty($result);
    }

    private function foreignKeyExists(string $table, string $foreignKeyName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT 1 FROM information_schema.table_constraints WHERE table_schema = ? AND table_name = ? AND constraint_name = ? AND constraint_type = ? LIMIT 1',
            [$database, $table, $foreignKeyName, 'FOREIGN KEY']
        );

        return ! empty($result);
    }
};
