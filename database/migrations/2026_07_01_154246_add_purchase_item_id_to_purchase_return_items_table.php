<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'purchase_return_items',
            function (Blueprint $table) {

                $table->unsignedBigInteger(
                    'purchase_item_id'
                )->after(
                    'purchase_return_id'
                );

                $table->index([

                    'purchase_return_id',

                    'purchase_item_id'

                ]);

            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'purchase_return_items',
            function (Blueprint $table) {

                $table->dropIndex([

                    'purchase_return_id',

                    'purchase_item_id'

                ]);

                $table->dropColumn(
                    'purchase_item_id'
                );

            }
        );
    }
};