<?php

declare(strict_types=1);

$basePath = dirname(__DIR__, 2);
require $basePath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

function mysqlConnection(string $database): PDO
{
    if (! str_starts_with($database, 'dg_erp_ob_verify_')) {
        throw new RuntimeException('Posting probe refuses to use a non-verification database.');
    }
    Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $_ENV['DB_HOST'], $_ENV['DB_PORT'] ?? 3306, $database);
    return new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

if (($argv[1] ?? null) === '--worker') {
    [, , $database, $barrier, $openingId] = $argv;
    putenv('DB_DATABASE=' . $database);
    $_ENV['DB_DATABASE'] = $database;
    $_SERVER['DB_DATABASE'] = $database;
    $app = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    while (! is_file($barrier)) usleep(1000);
    try {
        app(App\Services\OpeningBalanceService::class)->post(App\Models\OpeningBalance::findOrFail((int) $openingId), 1, 1);
        echo 'success';
    } catch (RuntimeException $exception) {
        echo 'rejected';
    } catch (Throwable $exception) {
        echo 'error:' . get_class($exception) . ':' . $exception->getMessage();
    }
    exit;
}

$database = $argv[1] ?? '';
$pdo = mysqlConnection($database);
$pdo->exec("DELETE FROM opening_balances");
$pdo->exec("DELETE FROM chart_accounts WHERE system_code IN ('OB_PROBE_ASSET','OB_PROBE_EQUITY')");
$account = $pdo->prepare('INSERT INTO chart_accounts
    (company_id, code, name, account_class, normal_balance, system_code, level, sort_order, is_system, is_control, allow_manual_entry, status, is_locked, created_at, updated_at)
    VALUES (1, :code, :name, :class, :normal, :system, 3, 0, 1, 0, 1, \'active\', 0, NOW(), NOW())');
$account->execute(['code' => 'OBP-101', 'name' => 'Probe Asset', 'class' => 'asset', 'normal' => 'debit', 'system' => 'OB_PROBE_ASSET']);
$assetId = (int) $pdo->lastInsertId();
$account->execute(['code' => 'OBP-301', 'name' => 'Probe Equity', 'class' => 'equity', 'normal' => 'credit', 'system' => 'OB_PROBE_EQUITY']);
$equityId = (int) $pdo->lastInsertId();
$pdo->exec("INSERT INTO opening_balances
    (company_id, financial_year_id, business_date, type, reference_number, status, active_key, request_key, created_by, submitted_by, approved_by, submitted_at, approved_at, is_locked, created_at, updated_at)
    VALUES (1,1,'2026-01-01','initial','OB-POST-CONCURRENT','approved','active','cccccccc-cccc-4ccc-8ccc-cccccccccccc',1,1,1,NOW(),NOW(),0,NOW(),NOW())");
$openingId = (int) $pdo->lastInsertId();
$line = $pdo->prepare('INSERT INTO opening_balance_lines
    (opening_balance_id, chart_account_id, line_number, debit, credit, base_debit, base_credit, created_at, updated_at)
    VALUES (:opening, :account, :line, :debit, :credit, :debit, :credit, NOW(), NOW())');
$line->execute(['opening' => $openingId, 'account' => $assetId, 'line' => 1, 'debit' => '10.0000', 'credit' => '0.0000']);
$line->execute(['opening' => $openingId, 'account' => $equityId, 'line' => 2, 'debit' => '0.0000', 'credit' => '10.0000']);

$barrier = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dg_erp_ob_post_' . bin2hex(random_bytes(8)) . '.barrier';
$workers = [];
for ($i = 0; $i < 2; $i++) {
    $pipes = [];
    $process = proc_open([PHP_BINARY, __FILE__, '--worker', $database, $barrier, (string) $openingId], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (! is_resource($process)) throw new RuntimeException('Unable to start posting worker.');
    $workers[] = [$process, $pipes];
}
touch($barrier);
$results = [];
foreach ($workers as [$process, $pipes]) {
    $results[] = trim(stream_get_contents($pipes[1]));
    $error = trim(stream_get_contents($pipes[2]));
    fclose($pipes[1]); fclose($pipes[2]);
    $exit = proc_close($process);
    if ($exit !== 0 || $error !== '') throw new RuntimeException('Posting worker failed: ' . $error);
}
@unlink($barrier);
sort($results);
$entryCount = (int) $pdo->query("SELECT COUNT(*) FROM accounting_entries WHERE source_module='opening_balance' AND source_id={$openingId}")->fetchColumn();
$journalCount = (int) $pdo->query("SELECT COUNT(*) FROM journals WHERE source_module='opening_balance' AND source_id={$openingId}")->fetchColumn();
$status = $pdo->query("SELECT status FROM opening_balances WHERE id={$openingId}")->fetchColumn();
if ($results !== ['rejected', 'success'] || $entryCount !== 1 || $journalCount !== 1 || $status !== 'posted') {
    fwrite(STDERR, json_encode(compact('results', 'entryCount', 'journalCount', 'status')) . PHP_EOL);
    exit(1);
}
echo 'PASS: concurrent posting produced one Journal and one Accounting Entry; the second post was rejected.' . PHP_EOL;
