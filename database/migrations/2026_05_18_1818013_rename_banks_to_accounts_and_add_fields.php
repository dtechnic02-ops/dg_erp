<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    // RENAME TABLE FIRST

    Schema::rename(
        'banks',
        'accounts'
    );

    // THEN ADD NEW COLUMNS

    Schema::table(
        'accounts',
        function (Blueprint $table) {

            $table->string(
                'currency'
            )
            ->default('AED')
            ->after('account_no');

            $table->string(
                'iban'
            )
            ->nullable()
            ->after('account_no');

            $table->decimal(
                'current_balance',
                15,
                2
            )
            ->default(0)
            ->after('opening_balance');

            $table->text(
                'note'
            )
            ->nullable()
            ->after('image_path');

        }
    );
}

    public function down(): void
    {
        Schema::table(
            'accounts',
            function (Blueprint $table) {

                $table->dropColumn([
                    'account_type',
                    'currency',
                    'iban',
                    'current_balance',
                    'note',
                ]);

            }
        );

        
    }
};