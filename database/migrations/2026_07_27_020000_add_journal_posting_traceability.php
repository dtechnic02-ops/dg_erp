<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->unsignedBigInteger('posted_by')->nullable()->after('updated_by');
            $table->timestamp('posted_at')->nullable()->after('posted_by');
            $table->unsignedBigInteger('reversed_by')->nullable()->after('cancelled_by');
            $table->timestamp('reversed_at')->nullable()->after('reversed_by');
            $table->unsignedBigInteger('reversal_of_journal_id')->nullable()->after('reversed_at');
            $table->index('reversal_of_journal_id');
        });

        foreach (['account_transactions', 'customer_transactions', 'supplier_transactions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('journal_item_id')->nullable()->after('reference_id');
                $table->unsignedBigInteger('reversed_transaction_id')->nullable()->after('journal_item_id');
                $table->index('journal_item_id');
                $table->index('reversed_transaction_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['account_transactions', 'customer_transactions', 'supplier_transactions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['journal_item_id']);
                $table->dropIndex(['reversed_transaction_id']);
                $table->dropColumn(['journal_item_id', 'reversed_transaction_id']);
            });
        }

        Schema::table('journals', function (Blueprint $table) {
            $table->dropIndex(['reversal_of_journal_id']);
            $table->dropColumn(['posted_by', 'posted_at', 'reversed_by', 'reversed_at', 'reversal_of_journal_id']);
        });
    }
};
