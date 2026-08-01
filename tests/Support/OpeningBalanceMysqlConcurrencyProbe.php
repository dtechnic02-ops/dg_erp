<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

function connection(string $database): PDO
{
    if (! str_starts_with($database, 'dg_erp_ob_verify_')) {
        throw new RuntimeException('Concurrency probe refuses to use a non-verification database.');
    }
    Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $_ENV['DB_HOST'], $_ENV['DB_PORT'] ?? 3306, $database);
    return new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

if (($argv[1] ?? null) === '--worker') {
    [, , $database, $barrier, $reference, $requestKey] = $argv;
    while (! is_file($barrier)) {
        usleep(1000);
    }
    try {
        $pdo = connection($database);
        $statement = $pdo->prepare('INSERT INTO opening_balances
            (company_id, financial_year_id, business_date, type, reference_number, status, active_key, request_key, created_by, is_locked, created_at, updated_at)
            VALUES (1, 1, :business_date, :type, :reference_number, :status, :active_key, :request_key, 1, 0, NOW(), NOW())');
        $statement->execute(['business_date' => '2026-01-01', 'type' => 'initial', 'reference_number' => $reference,
            'status' => 'draft', 'active_key' => 'active', 'request_key' => $requestKey]);
        echo 'success';
    } catch (PDOException $exception) {
        echo $exception->getCode() === '23000' ? 'duplicate' : 'error:' . $exception->getCode();
    }
    exit;
}

$database = $argv[1] ?? '';
$pdo = connection($database);
$pdo->exec('DELETE FROM opening_balances');
$barrier = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dg_erp_ob_' . bin2hex(random_bytes(8)) . '.barrier';
$workers = [];
foreach ([['CONCURRENT-A', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'], ['CONCURRENT-B', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb']] as [$reference, $key]) {
    $command = [PHP_BINARY, __FILE__, '--worker', $database, $barrier, $reference, $key];
    $pipes = [];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (! is_resource($process)) {
        throw new RuntimeException('Unable to start MySQL concurrency worker.');
    }
    $workers[] = [$process, $pipes];
}
touch($barrier);
$results = [];
foreach ($workers as [$process, $pipes]) {
    $results[] = trim(stream_get_contents($pipes[1]));
    $error = trim(stream_get_contents($pipes[2]));
    fclose($pipes[1]); fclose($pipes[2]);
    $exit = proc_close($process);
    if ($exit !== 0 || $error !== '') {
        throw new RuntimeException('Concurrency worker failed: ' . $error);
    }
}
@unlink($barrier);
sort($results);
if ($results !== ['duplicate', 'success']) {
    fwrite(STDERR, 'Unexpected concurrency results: ' . json_encode($results) . PHP_EOL);
    exit(1);
}
echo 'PASS: one concurrent active Opening Balance succeeded and one was rejected by the database.' . PHP_EOL;
