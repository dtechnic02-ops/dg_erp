<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_leads', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('lead_no');
                $table->index(['company_id', 'customer_id'], 'crm_leads_company_customer_index');
            }
        });

        if (Schema::hasColumn('crm_leads', 'converted_customer_id')) {
            DB::table('crm_leads')
                ->whereNotNull('converted_customer_id')
                ->whereNull('customer_id')
                ->update(['customer_id' => DB::raw('converted_customer_id')]);
        }

        Schema::table('crm_leads', function (Blueprint $table) {
            $columnsToDrop = array_filter([
                Schema::hasColumn('crm_leads', 'customer_name') ? 'customer_name' : null,
                Schema::hasColumn('crm_leads', 'contact_person') ? 'contact_person' : null,
                Schema::hasColumn('crm_leads', 'mobile') ? 'mobile' : null,
                Schema::hasColumn('crm_leads', 'email') ? 'email' : null,
                Schema::hasColumn('crm_leads', 'address') ? 'address' : null,
                Schema::hasColumn('crm_leads', 'country') ? 'country' : null,
                Schema::hasColumn('crm_leads', 'converted_customer_id') ? 'converted_customer_id' : null,
            ]);

            if ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            }
        });

        Schema::table('crm_leads', function (Blueprint $table) {
            if (Schema::hasColumn('crm_leads', 'customer_id')) {
                try {
                    $table->foreign('customer_id', 'crm_leads_customer_id_foreign')
                        ->references('id')
                        ->on('customers')
                        ->nullOnDelete();
                } catch (\Throwable $e) {
                    // FK may already exist from a prior migration attempt.
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            try {
                $table->dropForeign('crm_leads_customer_id_foreign');
            } catch (\Throwable $e) {
                //
            }

            $table->dropIndex('crm_leads_company_customer_index');

            $table->string('customer_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('country', 100)->nullable();
            $table->unsignedBigInteger('converted_customer_id')->nullable();

            $table->dropColumn('customer_id');
        });
    }
};
