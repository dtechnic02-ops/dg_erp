<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbms_transmissions', function (Blueprint $table): void {
            $table->string('environment', 20)->default('legacy')->after('endpoint_type');
            $table->string('transport_kind', 20)->default('legacy')->after('environment');
        });

        Schema::table('cbms_transmissions', function (Blueprint $table): void {
            $table->dropUnique('cbms_transmissions_document_endpoint_unique');
            $table->unique(
                ['company_id', 'transmittable_type', 'transmittable_id', 'endpoint_type', 'environment', 'transport_kind'],
                'cbms_transmissions_document_provenance_unique'
            );
        });

        Schema::table('cbms_transmission_attempts', function (Blueprint $table): void {
            $table->string('environment', 20)->default('legacy')->after('attempt_number');
            $table->string('transport_kind', 20)->default('legacy')->after('environment');
        });
    }

    public function down(): void
    {
        $legacyIdentityCollisionExists = DB::table('cbms_transmissions')
            ->select('company_id', 'transmittable_type', 'transmittable_id', 'endpoint_type')
            ->groupBy('company_id', 'transmittable_type', 'transmittable_id', 'endpoint_type')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();

        if ($legacyIdentityCollisionExists) {
            throw new \LogicException(
                'Cannot safely roll back CBMS transmission provenance because multiple provenance-specific transmission records would collapse into the legacy document endpoint identity.'
            );
        }

        Schema::table('cbms_transmission_attempts', function (Blueprint $table): void {
            $table->dropColumn(['environment', 'transport_kind']);
        });

        Schema::table('cbms_transmissions', function (Blueprint $table): void {
            $table->dropUnique('cbms_transmissions_document_provenance_unique');
            $table->dropColumn(['environment', 'transport_kind']);
            $table->unique(
                ['company_id', 'transmittable_type', 'transmittable_id', 'endpoint_type'],
                'cbms_transmissions_document_endpoint_unique'
            );
        });
    }
};
