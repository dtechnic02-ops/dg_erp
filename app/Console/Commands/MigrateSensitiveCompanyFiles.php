<?php

namespace App\Console\Commands;

use App\Services\FileUploadService;
use App\Services\ProtectedCompanyFileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateSensitiveCompanyFiles extends Command
{
    protected $signature = 'files:migrate-sensitive {--execute : Move verified files; otherwise report only}';
    protected $description = 'Move company-owned sensitive files from public locations to private storage';

    public function handle(): int
    {
        $moved = $missing = $review = 0;

        foreach (ProtectedCompanyFileService::RESOURCES as $type => $definition) {
            foreach ($definition['fields'] as $field) {
                $definition['model']::query()->whereNotNull($field)->orderBy('id')->chunkById(100, function ($records) use ($type, $field, &$moved, &$missing, &$review): void {
                    foreach ($records as $record) {
                        $path = str_replace('\\', '/', trim((string) $record->getAttribute($field)));
                        if ($type === 'delivery-note') {
                            $path = 'companies/'.(int) $record->company_id.'/'.$path;
                        } elseif (in_array($type, ['delivery-attachment', 'delivery-signature'], true)) {
                            $path = 'companies/'.(int) $record->company_id.'/deliveries/'.(int) $record->delivery_note_id.'/'.$path;
                        }
                        $expected = 'companies/'.(int) $record->company_id.'/';
                        if (!str_starts_with($path, $expected) || str_contains($path, '../')) {
                            $review++;
                            $this->warn("Manual review required: {$record->getTable()}#{$record->id}.{$field}");
                            continue;
                        }

                        $target = FileUploadService::privatePath($path);
                        if (Storage::disk('local')->exists($target)) {
                            continue;
                        }

                        $sources = [public_path($path), Storage::disk('public')->path($path)];
                        $source = collect($sources)->first(fn (string $candidate) => is_file($candidate));
                        if (!$source) {
                            $missing++;
                            $this->warn("Source missing: {$record->getTable()}#{$record->id}.{$field}");
                            continue;
                        }

                        if (!$this->option('execute')) {
                            $moved++;
                            continue;
                        }

                        $contents = file_get_contents($source);
                        if ($contents === false || !Storage::disk('local')->put($target, $contents)
                            || hash_file('sha256', $source) !== hash_file('sha256', Storage::disk('local')->path($target))) {
                            Storage::disk('local')->delete($target);
                            $review++;
                            $this->warn("Copy verification failed: {$record->getTable()}#{$record->id}.{$field}");
                            continue;
                        }

                        unlink($source);
                        $moved++;
                    }
                });
            }
        }

        if (Schema::hasTable('subscription_payments')) {
            DB::table('subscription_payments')->whereNotNull('proof_path')->orderBy('id')->chunkById(100, function ($records) use (&$moved, &$missing, &$review): void {
                foreach ($records as $record) {
                    $original = str_replace('\\', '/', trim((string) $record->proof_path));
                    $path = str_starts_with($original, 'companies/'.(int) $record->company_id.'/')
                        ? $original
                        : 'companies/'.(int) $record->company_id.'/subscription-payments/'.basename($original);
                    $source = collect([public_path($original), Storage::disk('public')->path($original)])
                        ->first(fn (string $candidate) => is_file($candidate));
                    if (!$source) { $missing++; continue; }
                    $target = FileUploadService::privatePath($path);
                    if (!$this->option('execute')) { $moved++; continue; }
                    if (Storage::disk('local')->exists($target)) { continue; }
                    $contents = file_get_contents($source);
                    if ($contents === false || !Storage::disk('local')->put($target, $contents)) { $review++; continue; }
                    DB::table('subscription_payments')->where('id', $record->id)->update(['proof_path' => $path]);
                    unlink($source);
                    $moved++;
                }
            });
        }

        $mode = $this->option('execute') ? 'Migration' : 'Dry run';
        $this->info("{$mode} complete: {$moved} eligible/moved, {$missing} missing, {$review} manual review.");
        return $review > 0 ? self::FAILURE : self::SUCCESS;
    }
}
