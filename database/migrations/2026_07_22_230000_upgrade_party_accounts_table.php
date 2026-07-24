<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_accounts', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('note');
            $table->foreignId('updated_by')->nullable()->after('created_by');
            $table->foreignId('deleted_by')->nullable()->after('updated_by');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('party_accounts', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['due_date', 'updated_by', 'deleted_by']);
        });
    }
};
