<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {

           $table->foreignId('brand_id')
    ->nullable()
    ->after('category_id')
    ->constrained('brands')
    ->nullOnDelete();

$table->date('manufacture_date')
    ->nullable()
    ->after('brand_id');

$table->date('expiry_date')
    ->nullable()
    ->after('manufacture_date');

$table->string('batch_no')
    ->nullable()
    ->after('expiry_date');

$table->boolean('allow_online')
    ->default(false)
    ->after('batch_no');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {

            $table->dropConstrainedForeignId('brand_id');

            $table->dropColumn([
                'manufacture_date',
                'expiry_date',
                'allow_online',
            ]);

        });
    }
};