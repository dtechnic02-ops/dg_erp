<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('companies', function (Blueprint $table) {
        $table->integer('selected_customer_limit')->default(0)->after('selected_user_limit');
    });
}

public function down()
{
    Schema::table('companies', function (Blueprint $table) {
        $table->dropColumn('selected_customer_limit');
    });
}
};
