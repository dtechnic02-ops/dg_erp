<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
public function up(): void
{
    Schema::table('sales_return_items', function (Blueprint $table) {

        $table->unsignedBigInteger('sales_item_id')
              ->after('sales_return_id');

        $table->index([
            'sales_return_id',
            'sales_item_id'
        ]);

    });
}

public function down(): void
{
    Schema::table('sales_return_items', function (Blueprint $table) {

        $table->dropIndex([
            'sales_return_id',
            'sales_item_id'
        ]);

        $table->dropColumn('sales_item_id');

    });
}
};
