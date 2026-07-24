<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        Schema::table('loan_accounts', function (Blueprint $table) {

            $table->foreignId('cancelled_by')->nullable()->after('updated_by');

            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');

        });



        if (Schema::hasColumn('loan_accounts', 'deleted_at')) {

            DB::table('loan_accounts')

                ->whereNotNull('deleted_at')

                ->update([

                    'status'       => 0,

                    'cancelled_by' => DB::raw('deleted_by'),

                    'cancelled_at' => DB::raw('deleted_at'),

                    'deleted_at'   => null,

                ]);

        }



        Schema::table('loan_accounts', function (Blueprint $table) {

            if (Schema::hasColumn('loan_accounts', 'deleted_by')) {

                $table->dropForeign(['deleted_by']);

                $table->dropColumn('deleted_by');

            }



            if (Schema::hasColumn('loan_accounts', 'deleted_at')) {

                $table->dropSoftDeletes();

            }

        });



        Schema::table('loan_accounts', function (Blueprint $table) {

            $table->foreign('cancelled_by')

                ->references('id')

                ->on('users');

        });

    }



    public function down(): void

    {

        Schema::table('loan_accounts', function (Blueprint $table) {

            $table->dropForeign(['cancelled_by']);

            $table->dropColumn(['cancelled_by', 'cancelled_at']);

            $table->foreignId('deleted_by')->nullable()->after('updated_by');

            $table->softDeletes();

        });



        Schema::table('loan_accounts', function (Blueprint $table) {

            $table->foreign('deleted_by')

                ->references('id')

                ->on('users');

        });

    }

};


