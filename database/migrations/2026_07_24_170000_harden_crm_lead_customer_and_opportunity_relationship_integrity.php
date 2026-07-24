<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        if (Schema::hasTable('crm_leads') && Schema::hasColumn('crm_leads', 'customer_id')) {

            $this->backfillLeadCustomerIds();

            $this->assertNoNullLeadCustomerIds();

            $this->hardenLeadCustomerIdColumn();

        }



        if (Schema::hasTable('crm_opportunities') && Schema::hasColumn('crm_opportunities', 'crm_lead_id')) {

            $this->backfillOpportunityRelationshipIds();

            $this->assertNoNullOpportunityRelationshipIds();

            $this->hardenOpportunityRelationshipIdColumn();

        }

    }



    public function down(): void

    {

        if (Schema::hasTable('crm_leads') && Schema::hasColumn('crm_leads', 'customer_id')) {

            Schema::table('crm_leads', function (Blueprint $table) {

                if ($this->foreignKeyExists('crm_leads', 'crm_leads_customer_id_foreign')) {

                    $table->dropForeign('crm_leads_customer_id_foreign');

                }



                $table->unsignedBigInteger('customer_id')->nullable()->change();



                $table->foreign('customer_id', 'crm_leads_customer_id_foreign')

                    ->references('id')

                    ->on('customers')

                    ->nullOnDelete();

            });

        }



        if (Schema::hasTable('crm_opportunities') && Schema::hasColumn('crm_opportunities', 'crm_lead_id')) {

            Schema::table('crm_opportunities', function (Blueprint $table) {

                if ($this->foreignKeyExists('crm_opportunities', 'crm_opportunities_crm_lead_id_foreign')) {

                    $table->dropForeign('crm_opportunities_crm_lead_id_foreign');

                }



                $table->unsignedBigInteger('crm_lead_id')->nullable()->change();



                $table->foreign('crm_lead_id', 'crm_opportunities_crm_lead_id_foreign')

                    ->references('id')

                    ->on('crm_leads')

                    ->nullOnDelete();

            });

        }

    }



    private function backfillLeadCustomerIds(): void

    {

        if (Schema::hasColumn('crm_leads', 'converted_customer_id')) {

            DB::table('crm_leads')

                ->whereNull('customer_id')

                ->whereNotNull('converted_customer_id')

                ->update(['customer_id' => DB::raw('converted_customer_id')]);

        }



        DB::statement(<<<'SQL'

            UPDATE crm_leads AS l

            INNER JOIN (

                SELECT

                    o.crm_lead_id AS lead_id,

                    o.company_id,

                    MIN(o.customer_id) AS customer_id

                FROM crm_opportunities AS o

                WHERE o.crm_lead_id IS NOT NULL

                  AND o.customer_id IS NOT NULL

                GROUP BY o.crm_lead_id, o.company_id

                HAVING COUNT(DISTINCT o.customer_id) = 1

            ) AS src ON src.lead_id = l.id AND src.company_id = l.company_id

            SET l.customer_id = src.customer_id

            WHERE l.customer_id IS NULL

        SQL);



        DB::statement(<<<'SQL'

            UPDATE crm_leads AS l

            INNER JOIN (

                SELECT

                    c.crm_lead_id AS lead_id,

                    c.company_id,

                    MIN(c.customer_id) AS customer_id

                FROM crm_contacts AS c

                WHERE c.crm_lead_id IS NOT NULL

                  AND c.customer_id IS NOT NULL

                GROUP BY c.crm_lead_id, c.company_id

                HAVING COUNT(DISTINCT c.customer_id) = 1

            ) AS src ON src.lead_id = l.id AND src.company_id = l.company_id

            SET l.customer_id = src.customer_id

            WHERE l.customer_id IS NULL

        SQL);

    }



    private function assertNoNullLeadCustomerIds(): void

    {

        $invalid = DB::table('crm_leads')

            ->whereNull('customer_id')

            ->orderBy('id')

            ->limit(10)

            ->get(['id', 'company_id', 'lead_no']);



        if ($invalid->isEmpty()) {

            return;

        }



        $total = DB::table('crm_leads')->whereNull('customer_id')->count();

        $sample = $invalid

            ->map(fn ($row) => sprintf('id=%d company_id=%d lead_no=%s', $row->id, $row->company_id, $row->lead_no))

            ->implode('; ');



        throw new RuntimeException(

            "Cannot make crm_leads.customer_id NOT NULL: {$total} relationship record(s) still have NULL customer_id. "

            . "Assign each relationship to an existing customer, then rerun the migration. Sample: {$sample}"

        );

    }



    private function hardenLeadCustomerIdColumn(): void

    {

        Schema::table('crm_leads', function (Blueprint $table) {

            if ($this->foreignKeyExists('crm_leads', 'crm_leads_customer_id_foreign')) {

                $table->dropForeign('crm_leads_customer_id_foreign');

            }



            $table->unsignedBigInteger('customer_id')->nullable(false)->change();



            $table->foreign('customer_id', 'crm_leads_customer_id_foreign')

                ->references('id')

                ->on('customers')

                ->restrictOnDelete();

        });

    }



    private function backfillOpportunityRelationshipIds(): void

    {

        DB::statement(<<<'SQL'

            UPDATE crm_opportunities AS o

            INNER JOIN (

                SELECT

                    ranked.company_id,

                    ranked.id AS opportunity_id,

                    ranked.crm_lead_id

                FROM (

                    SELECT

                        o2.id,

                        o2.company_id,

                        l.id AS crm_lead_id,

                        ROW_NUMBER() OVER (

                            PARTITION BY o2.id

                            ORDER BY l.lead_date DESC, l.id DESC

                        ) AS row_num

                    FROM crm_opportunities AS o2

                    INNER JOIN crm_leads AS l

                        ON l.company_id = o2.company_id

                       AND l.customer_id = o2.customer_id

                    WHERE o2.crm_lead_id IS NULL

                      AND o2.customer_id IS NOT NULL

                ) AS ranked

                WHERE ranked.row_num = 1

            ) AS src ON src.opportunity_id = o.id AND src.company_id = o.company_id

            SET o.crm_lead_id = src.crm_lead_id

            WHERE o.crm_lead_id IS NULL

        SQL);

    }



    private function assertNoNullOpportunityRelationshipIds(): void

    {

        $invalid = DB::table('crm_opportunities')

            ->whereNull('crm_lead_id')

            ->orderBy('id')

            ->limit(10)

            ->get(['id', 'company_id', 'opportunity_no', 'customer_id']);



        if ($invalid->isEmpty()) {

            return;

        }



        $total = DB::table('crm_opportunities')->whereNull('crm_lead_id')->count();

        $sample = $invalid

            ->map(fn ($row) => sprintf(

                'id=%d company_id=%d opportunity_no=%s customer_id=%s',

                $row->id,

                $row->company_id,

                $row->opportunity_no,

                $row->customer_id ?? 'NULL'

            ))

            ->implode('; ');



        throw new RuntimeException(

            "Cannot make crm_opportunities.crm_lead_id NOT NULL: {$total} opportunity record(s) still have NULL crm_lead_id. "

            . "Link each opportunity to a customer relationship for the same company and customer, then rerun the migration. Sample: {$sample}"

        );

    }



    private function hardenOpportunityRelationshipIdColumn(): void

    {

        Schema::table('crm_opportunities', function (Blueprint $table) {

            if ($this->foreignKeyExists('crm_opportunities', 'crm_opportunities_crm_lead_id_foreign')) {

                $table->dropForeign('crm_opportunities_crm_lead_id_foreign');

            }



            $table->unsignedBigInteger('crm_lead_id')->nullable(false)->change();



            $table->foreign('crm_lead_id', 'crm_opportunities_crm_lead_id_foreign')

                ->references('id')

                ->on('crm_leads')

                ->restrictOnDelete();

        });

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


