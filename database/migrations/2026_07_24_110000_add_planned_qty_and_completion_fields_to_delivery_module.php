<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_note_items', function (Blueprint $table) {
            $table->decimal('planned_qty', 15, 2)->default(0)->after('invoice_qty');
        });

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->string('pdf_path')->nullable()->after('cancel_reason');
            $table->timestamp('completed_at')->nullable()->after('cancelled_at');
            $table->unsignedBigInteger('completed_by')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_note_items', function (Blueprint $table) {
            $table->dropColumn('planned_qty');
        });

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropColumn(['pdf_path', 'completed_at', 'completed_by']);
        });
    }
};
