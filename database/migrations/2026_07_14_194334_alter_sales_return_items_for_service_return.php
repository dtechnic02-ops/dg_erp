<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_return_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')
                ->nullable()
                ->change();

            $table->unsignedBigInteger('service_id')
                ->nullable()
                ->after('product_id');

            $table->foreign('service_id')
                ->references('id')
                ->on('services')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('sales_return_items', function (Blueprint $table) {
            $table->dropForeign(['service_id']);

            $table->dropColumn('service_id');

            $table->unsignedBigInteger('product_id')
                ->nullable(false)
                ->change();
        });
    }
};
