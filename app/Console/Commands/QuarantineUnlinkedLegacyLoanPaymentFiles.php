<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuarantineUnlinkedLegacyLoanPaymentFiles extends Command
{
    protected $signature = 'files:quarantine-unlinked-legacy {--execute : Quarantine verified unlinked files; otherwise report only}';
    protected $description = 'Quarantine unlinked legacy files from known sensitive public company directories';

    public function handle(): int
    {
        $candidates = [
            ...array_map(fn (string $path) => ['path' => $path, 'kind' => 'loan-payment'], glob(public_path('companies/*/loan-payments/*')) ?: []),
            ...array_map(fn (string $path) => ['path' => $path, 'kind' => 'return'], glob(Storage::disk('public')->path('companies/*/returns/*')) ?: []),
        ];
        $unlinked = [];

        foreach ($candidates as $candidate) {
            $source = $candidate['path'];
            if (!is_file($source) || is_link($source)) {
                continue;
            }

            $publicPrefix = rtrim(str_replace('\\', '/', public_path()), '/').'/';
            $normalizedSource = str_replace('\\', '/', $source);
            $relative = str_starts_with($normalizedSource, $publicPrefix)
                ? substr($normalizedSource, strlen($publicPrefix))
                : 'storage/app/public/'.substr($normalizedSource, strlen(str_replace('\\', '/', storage_path('app/public/'))));
            $filename = basename($relative);
            if ($this->hasAuthoritativeLink($candidate['kind'], $relative, $filename)) {
                continue;
            }

            $stat = stat($source);
            if ($stat === false) {
                $this->error('Unable to read candidate metadata.');
                return self::FAILURE;
            }

            $unlinked[] = [
                'source' => $source,
                'relative_path' => $relative,
                'size' => (int) $stat['size'],
                'created_at_utc' => gmdate(DATE_ATOM, (int) $stat['ctime']),
                'modified_at_utc' => gmdate(DATE_ATOM, (int) $stat['mtime']),
                'sha256' => hash_file('sha256', $source),
            ];
        }

        $this->info('Unlinked legacy sensitive files: '.count($unlinked));
        if (!$this->option('execute') || $unlinked === []) {
            return self::SUCCESS;
        }

        $manifestEntries = [];
        foreach ($unlinked as $file) {
            $quarantineName = (string) Str::uuid().'.bin';
            $target = 'protected/quarantine/unlinked-legacy/'.$quarantineName;
            abort_if(Storage::disk('local')->exists($target), 500, 'Quarantine target collision.');

            $bytes = file_get_contents($file['source']);
            if ($bytes === false || !Storage::disk('local')->put($target, $bytes)) {
                $this->error('Unable to write a quarantine candidate.');
                return self::FAILURE;
            }

            if (hash_file('sha256', Storage::disk('local')->path($target)) !== $file['sha256']) {
                Storage::disk('local')->delete($target);
                $this->error('Quarantine integrity verification failed.');
                return self::FAILURE;
            }

            $manifestEntries[] = [
                'classification' => 'UNLINKED LEGACY FILE — MANUAL REVIEW',
                'quarantine_file' => $quarantineName,
                'original_relative_path' => $file['relative_path'],
                'size' => $file['size'],
                'created_at_utc' => $file['created_at_utc'],
                'modified_at_utc' => $file['modified_at_utc'],
                'sha256' => $file['sha256'],
                'quarantined_at_utc' => now('UTC')->toIso8601String(),
                'company_ownership' => null,
                'business_record' => null,
            ];
        }

        $manifest = 'protected/quarantine/unlinked-legacy/manifest-'.now('UTC')->format('Ymd-His').'-'.Str::uuid().'.json';
        if (!Storage::disk('local')->put($manifest, json_encode($manifestEntries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR))) {
            $this->error('Unable to write the quarantine manifest; public originals were retained.');
            return self::FAILURE;
        }

        foreach ($unlinked as $file) {
            unlink($file['source']);
        }

        $this->info('Quarantined and checksum-verified: '.count($manifestEntries));
        return self::SUCCESS;
    }

    private function hasAuthoritativeLink(string $kind, string $relative, string $filename): bool
    {
        $locations = match ($kind) {
            'return' => [['sales_returns', 'damage_photo'], ['purchase_returns', 'damage_photo']],
            default => [['loan_payments', 'attachment'], ['loan_saving_ledgers', 'attachment'], ['loan_accounts', 'attachment']],
        };
        foreach ($locations as [$table, $column]) {
            if (DB::table($table)->where($column, $relative)->orWhere($column, 'like', '%/'.$filename)->exists()) {
                return true;
            }
        }

        return false;
    }
}
