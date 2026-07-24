<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['crm_follow_ups', 'crm_meetings', 'crm_tasks'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'financial_year_id')) {
                    $table->unsignedBigInteger('financial_year_id')->nullable()->after('company_id');
                    $table->index(['company_id', 'financial_year_id'], $tableName . '_company_fy_index');
                }

                if (!Schema::hasColumn($tableName, 'archived_by')) {
                    $table->unsignedBigInteger('archived_by')->nullable()->after('updated_by');
                    $table->timestamp('archived_at')->nullable()->after('archived_by');
                    $table->text('archive_reason')->nullable()->after('archived_at');
                    $table->unsignedBigInteger('cancelled_by')->nullable()->after('archive_reason');
                    $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
                    $table->text('cancel_reason')->nullable()->after('cancelled_at');
                }
            });
        }

        Schema::table('crm_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_notes', 'status')) {
                $table->string('status', 50)->default('active')->after('note');
            }
            if (!Schema::hasColumn('crm_notes', 'archived_by')) {
                $table->unsignedBigInteger('archived_by')->nullable()->after('updated_by');
                $table->timestamp('archived_at')->nullable()->after('archived_by');
                $table->text('archive_reason')->nullable()->after('archived_at');
            }
        });

        Schema::table('crm_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_attachments', 'mime_type')) {
                $table->string('mime_type', 100)->nullable()->after('original_name');
                $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            }
            if (!Schema::hasColumn('crm_attachments', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('remarks');
                $table->unsignedBigInteger('archived_by')->nullable()->after('is_archived');
                $table->timestamp('archived_at')->nullable()->after('archived_by');
                $table->text('archive_reason')->nullable()->after('archived_at');
            }
        });

        Schema::table('crm_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_leads', 'archive_reason')) {
                $table->text('archive_reason')->nullable()->after('archived_at');
            }
        });

        Schema::table('crm_opportunities', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_opportunities', 'archive_reason')) {
                $table->text('archive_reason')->nullable()->after('archived_at');
            }
        });
    }

    public function down(): void
    {
        foreach (['crm_follow_ups', 'crm_meetings', 'crm_tasks'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex($tableName . '_company_fy_index');
                $table->dropColumn([
                    'financial_year_id',
                    'archived_by',
                    'archived_at',
                    'archive_reason',
                    'cancelled_by',
                    'cancelled_at',
                    'cancel_reason',
                ]);
            });
        }

        Schema::table('crm_notes', function (Blueprint $table) {
            $table->dropColumn(['status', 'archived_by', 'archived_at', 'archive_reason']);
        });

        Schema::table('crm_attachments', function (Blueprint $table) {
            $table->dropColumn(['mime_type', 'file_size', 'is_archived', 'archived_by', 'archived_at', 'archive_reason']);
        });

        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropColumn('archive_reason');
        });

        Schema::table('crm_opportunities', function (Blueprint $table) {
            $table->dropColumn('archive_reason');
        });
    }
};
