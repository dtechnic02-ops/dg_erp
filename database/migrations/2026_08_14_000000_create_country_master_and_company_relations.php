<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->char('iso_code', 2)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('countries')->insert([
            'name' => 'Nepal',
            'iso_code' => 'NP',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('companies', fn (Blueprint $table) => $table->unsignedBigInteger('country_id')->nullable()->after('country'));
        Schema::table('company_registrations', fn (Blueprint $table) => $table->unsignedBigInteger('country_id')->nullable()->after('country'));

        $nepalId = (int) DB::table('countries')->where('iso_code', 'NP')->value('id');
        $this->mapNepal('companies', $nepalId);
        $this->mapNepal('company_registrations', $nepalId);
        $this->reportUnmapped('companies');
        $this->reportUnmapped('company_registrations');

        if (DB::table('companies')->whereNotNull('country_id')->whereNotIn('country_id', DB::table('countries')->select('id'))->exists()
            || DB::table('company_registrations')->whereNotNull('country_id')->whereNotIn('country_id', DB::table('countries')->select('id'))->exists()) {
            throw new RuntimeException('Country relation preflight failed because an invalid country_id exists.');
        }

        Schema::table('companies', fn (Blueprint $table) => $table->foreign('country_id')->references('id')->on('countries')->restrictOnDelete());
        Schema::table('company_registrations', fn (Blueprint $table) => $table->foreign('country_id')->references('id')->on('countries')->restrictOnDelete());
    }

    public function down(): void
    {
        Schema::table('company_registrations', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropColumn('country_id');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropColumn('country_id');
        });
        Schema::dropIfExists('countries');
    }

    private function mapNepal(string $table, int $nepalId): void
    {
        DB::table($table)
            ->whereNull('country_id')
            ->whereIn(DB::raw('UPPER(TRIM(country))'), ['NEPAL', 'NP'])
            ->update(['country_id' => $nepalId]);
    }

    private function reportUnmapped(string $table): void
    {
        $values = DB::table($table)
            ->whereNull('country_id')
            ->whereNotNull('country')
            ->whereRaw("TRIM(country) <> ''")
            ->distinct()
            ->orderBy('country')
            ->pluck('country')
            ->all();

        if ($values !== []) {
            Log::warning('Unmapped legacy country values require manual resolution.', [
                'table' => $table,
                'values' => $values,
            ]);
        }
    }
};
