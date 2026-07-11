<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$options = getopt('', [
    'execute',
    'dry-run',
    'batch-size::',
    'table::',
]);

$execute = array_key_exists('execute', $options);
$dryRun = array_key_exists('dry-run', $options) || ! $execute;
$onlyTable = $options['table'] ?? null;
$batchSize = max(1, (int) ($options['batch-size'] ?? 200));

$supabaseUrl = rtrim((string) ($_ENV['SUPABASE_URL'] ?? $_SERVER['SUPABASE_URL'] ?? ''), '/');
$supabaseKey = (string) (
    $_ENV['SUPABASE_SERVICE_ROLE_KEY']
    ?? $_SERVER['SUPABASE_SERVICE_ROLE_KEY']
    ?? $_ENV['SUPABASE_PUBLISHABLE_KEY']
    ?? $_SERVER['SUPABASE_PUBLISHABLE_KEY']
    ?? $_ENV['SUPABASE_ANON_KEY']
    ?? $_SERVER['SUPABASE_ANON_KEY']
    ?? ''
);
$importToken = (string) ($_ENV['SUPABASE_IMPORT_TOKEN'] ?? $_SERVER['SUPABASE_IMPORT_TOKEN'] ?? '');
$useRpcImport = $importToken !== '';

if ($supabaseUrl === '' || $supabaseKey === '') {
    fwrite(STDERR, "SUPABASE_URL and one Supabase API key env var are required.\n");
    exit(1);
}

$dbName = (string) ($_ENV['DB_DATABASE'] ?? $_SERVER['DB_DATABASE'] ?? '');
$dbHost = (string) ($_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? '127.0.0.1');
$dbPort = (string) ($_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? '3306');
$dbUser = (string) ($_ENV['DB_USERNAME'] ?? $_SERVER['DB_USERNAME'] ?? 'root');
$dbPass = (string) ($_ENV['DB_PASSWORD'] ?? $_SERVER['DB_PASSWORD'] ?? '');
$dbSocket = (string) ($_ENV['DB_SOCKET'] ?? $_SERVER['DB_SOCKET'] ?? '');
$charset = (string) ($_ENV['DB_CHARSET'] ?? $_SERVER['DB_CHARSET'] ?? 'utf8mb4');

if ($dbName === '') {
    fwrite(STDERR, "DB_DATABASE is required.\n");
    exit(1);
}

$dsn = $dbSocket !== ''
    ? "mysql:unix_socket={$dbSocket};dbname={$dbName};charset={$charset}"
    : "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$charset}";

$pdo = new PDO($dsn, $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$tables = loadTables($pdo, $dbName);

if ($onlyTable !== null) {
    $tables = array_values(array_filter($tables, fn (string $table): bool => $table === $onlyTable));
    if ($tables === []) {
        fwrite(STDERR, "Table {$onlyTable} was not found locally.\n");
        exit(1);
    }
}

$columnsByTable = loadColumns($pdo, $dbName, $tables);
$jsonColumnsByTable = loadJsonColumns($pdo, $dbName, $tables);
$primaryKeysByTable = loadPrimaryKeys($pdo, $dbName, $tables);
$orderedTables = orderTablesByForeignKeys($pdo, $dbName, $tables);
$localCounts = [];
$remoteCountsBefore = [];

echo ($dryRun ? 'DRY RUN' : 'EXECUTE')." Supabase data sync\n";
echo "Source MySQL database: {$dbName}\n";
echo "Target Supabase REST: {$supabaseUrl}\n";
echo 'Import mode: '.($useRpcImport ? 'temporary RPC' : 'direct REST upsert')."\n";
echo 'Tables: '.count($orderedTables)."\n\n";

foreach ($orderedTables as $table) {
    $localCounts[$table] = localCount($pdo, $table);
    $remoteCountsBefore[$table] = remoteCount($supabaseUrl, $supabaseKey, $table, $importToken);

    preflightRemoteColumns($supabaseUrl, $supabaseKey, $table, $columnsByTable[$table], $importToken);

    if ($localCounts[$table] > 0 && ($primaryKeysByTable[$table] ?? []) === []) {
        fwrite(STDERR, "Refusing to sync non-empty table {$table}: no primary key for safe upsert.\n");
        exit(1);
    }

    printf(
        "%-34s local=%5d remote_before=%5d pk=%s\n",
        $table,
        $localCounts[$table],
        $remoteCountsBefore[$table],
        implode(',', $primaryKeysByTable[$table] ?? []) ?: '-'
    );
}

if ($dryRun) {
    echo "\nNo rows were written. Re-run with --execute to upsert data.\n";
    exit(0);
}

echo "\nWriting rows...\n";

foreach ($orderedTables as $table) {
    $count = $localCounts[$table];
    if ($count === 0) {
        echo "{$table}: skipped, no local rows\n";

        continue;
    }

    $offset = 0;
    $written = 0;

    while ($offset < $count) {
        $rows = fetchRows($pdo, $table, $columnsByTable[$table], $batchSize, $offset);
        $rows = normalizeRows($rows, $jsonColumnsByTable[$table] ?? []);

        upsertRows(
            $supabaseUrl,
            $supabaseKey,
            $table,
            $rows,
            $primaryKeysByTable[$table],
            $importToken,
        );

        $written += count($rows);
        $offset += $batchSize;
    }

    echo "{$table}: upserted {$written} row(s)\n";
}

echo "\nVerifying counts...\n";
$mismatches = [];

foreach ($orderedTables as $table) {
    finalizeTable($supabaseUrl, $supabaseKey, $table, $importToken);

    $remoteAfter = remoteCount($supabaseUrl, $supabaseKey, $table, $importToken);
    $local = $localCounts[$table];
    $status = $remoteAfter === $local ? 'OK' : 'MISMATCH';

    printf("%-34s local=%5d remote_after=%5d %s\n", $table, $local, $remoteAfter, $status);

    if ($remoteAfter !== $local) {
        $mismatches[] = $table;
    }
}

if ($mismatches !== []) {
    fwrite(STDERR, "\nCount mismatch after sync: ".implode(', ', $mismatches)."\n");
    exit(1);
}

echo "\nSupabase data sync completed and verified.\n";

/**
 * @return list<string>
 */
function loadTables(PDO $pdo, string $dbName): array
{
    $stmt = $pdo->prepare(
        "select table_name
         from information_schema.tables
         where table_schema = :database
           and table_type = 'BASE TABLE'
         order by table_name"
    );
    $stmt->execute(['database' => $dbName]);

    return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * @param  list<string>  $tables
 * @return array<string, list<string>>
 */
function loadColumns(PDO $pdo, string $dbName, array $tables): array
{
    $columns = [];
    $stmt = $pdo->prepare(
        'select column_name
         from information_schema.columns
         where table_schema = :database
           and table_name = :table
         order by ordinal_position'
    );

    foreach ($tables as $table) {
        $stmt->execute(['database' => $dbName, 'table' => $table]);
        $columns[$table] = array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    return $columns;
}

/**
 * @param  list<string>  $tables
 * @return array<string, list<string>>
 */
function loadJsonColumns(PDO $pdo, string $dbName, array $tables): array
{
    $jsonColumns = [];
    $stmt = $pdo->prepare(
        "select column_name
         from information_schema.columns
         where table_schema = :database
           and table_name = :table
           and data_type = 'json'"
    );

    foreach ($tables as $table) {
        $stmt->execute(['database' => $dbName, 'table' => $table]);
        $jsonColumns[$table] = array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    return $jsonColumns;
}

/**
 * @param  list<string>  $tables
 * @return array<string, list<string>>
 */
function loadPrimaryKeys(PDO $pdo, string $dbName, array $tables): array
{
    $primaryKeys = [];
    $stmt = $pdo->prepare(
        "select k.column_name
         from information_schema.table_constraints c
         join information_schema.key_column_usage k
           on k.constraint_schema = c.constraint_schema
          and k.constraint_name = c.constraint_name
          and k.table_name = c.table_name
         where c.table_schema = :database
           and c.table_name = :table
           and c.constraint_type = 'PRIMARY KEY'
         order by k.ordinal_position"
    );

    foreach ($tables as $table) {
        $stmt->execute(['database' => $dbName, 'table' => $table]);
        $primaryKeys[$table] = array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    return $primaryKeys;
}

/**
 * @param  list<string>  $tables
 * @return list<string>
 */
function orderTablesByForeignKeys(PDO $pdo, string $dbName, array $tables): array
{
    $known = array_fill_keys($tables, true);
    $deps = array_fill_keys($tables, []);

    $stmt = $pdo->prepare(
        'select table_name as table_name, referenced_table_name as referenced_table_name
         from information_schema.key_column_usage
         where table_schema = :database
           and referenced_table_name is not null'
    );
    $stmt->execute(['database' => $dbName]);

    foreach ($stmt->fetchAll() as $row) {
        $table = (string) $row['table_name'];
        $parent = (string) $row['referenced_table_name'];

        if (isset($known[$table], $known[$parent]) && $table !== $parent) {
            $deps[$table][$parent] = true;
        }
    }

    $ordered = [];
    $temporary = [];
    $permanent = [];

    $visit = function (string $table) use (&$visit, &$deps, &$ordered, &$temporary, &$permanent): void {
        if (isset($permanent[$table])) {
            return;
        }

        if (isset($temporary[$table])) {
            $ordered[] = $table;
            $permanent[$table] = true;

            return;
        }

        $temporary[$table] = true;

        foreach (array_keys($deps[$table] ?? []) as $parent) {
            $visit($parent);
        }

        unset($temporary[$table]);
        $permanent[$table] = true;
        $ordered[] = $table;
    };

    foreach ($tables as $table) {
        $visit($table);
    }

    return array_values(array_unique($ordered));
}

function localCount(PDO $pdo, string $table): int
{
    return (int) $pdo->query('select count(*) from `'.str_replace('`', '``', $table).'`')->fetchColumn();
}

/**
 * @param  list<string>  $columns
 * @return list<array<string, mixed>>
 */
function fetchRows(PDO $pdo, string $table, array $columns, int $limit, int $offset): array
{
    $selected = implode(', ', array_map(fn (string $column): string => '`'.str_replace('`', '``', $column).'`', $columns));
    $sql = sprintf(
        'select %s from `%s` order by %s limit %d offset %d',
        $selected,
        str_replace('`', '``', $table),
        implode(', ', array_map(fn (string $column): string => '`'.str_replace('`', '``', $column).'`', $columns)),
        $limit,
        $offset,
    );

    return $pdo->query($sql)->fetchAll();
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @param  list<string>  $jsonColumns
 * @return list<array<string, mixed>>
 */
function normalizeRows(array $rows, array $jsonColumns): array
{
    foreach ($rows as &$row) {
        foreach ($row as $column => $value) {
            if (is_string($value)) {
                $row[$column] = normalizeStringValue($value);
            }
        }

        foreach ($jsonColumns as $column) {
            if (! array_key_exists($column, $row) || $row[$column] === null || $row[$column] === '') {
                continue;
            }

            $decoded = json_decode((string) $row[$column], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $row[$column] = $decoded;
            }
        }
    }

    return $rows;
}

function normalizeStringValue(string $value): string
{
    if (preg_match('//u', $value) === 1) {
        return $value;
    }

    if (strlen($value) === 16) {
        $hex = bin2hex($value);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20),
        );
    }

    return base64_encode($value);
}

/**
 * @param  list<string>  $columns
 */
function preflightRemoteColumns(
    string $supabaseUrl,
    string $supabaseKey,
    string $table,
    array $columns,
    string $importToken,
): void {
    if ($importToken !== '') {
        $remoteColumns = rpc($supabaseUrl, $supabaseKey, '__codex_table_columns', [
            'p_table_name' => $table,
            'p_token' => $importToken,
        ]);

        sort($columns);
        sort($remoteColumns);

        $missingRemote = array_values(array_diff($columns, $remoteColumns));
        if ($missingRemote !== []) {
            throw new RuntimeException("Remote table {$table} is missing column(s): ".implode(', ', $missingRemote));
        }

        return;
    }

    $select = implode(',', $columns);
    request(
        'GET',
        "{$supabaseUrl}/rest/v1/".rawurlencode($table).'?select='.rawurlencode($select).'&limit=0',
        $supabaseKey,
    );
}

function remoteCount(string $supabaseUrl, string $supabaseKey, string $table, string $importToken): int
{
    if ($importToken !== '') {
        return (int) rpc($supabaseUrl, $supabaseKey, '__codex_count_rows', [
            'p_table_name' => $table,
            'p_token' => $importToken,
        ]);
    }

    $response = request(
        'GET',
        "{$supabaseUrl}/rest/v1/".rawurlencode($table).'?select=*',
        $supabaseKey,
        null,
        [
            'Prefer: count=exact',
            'Range-Unit: items',
            'Range: 0-0',
        ],
        true,
    );

    $contentRange = $response['headers']['content-range'] ?? $response['headers']['Content-Range'] ?? null;

    if (! is_string($contentRange) || ! str_contains($contentRange, '/')) {
        throw new RuntimeException("Unable to read remote count for {$table}.");
    }

    $total = substr($contentRange, strrpos($contentRange, '/') + 1);

    return $total === '*' ? 0 : (int) $total;
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @param  list<string>  $primaryKeys
 */
function upsertRows(
    string $supabaseUrl,
    string $supabaseKey,
    string $table,
    array $rows,
    array $primaryKeys,
    string $importToken,
): void {
    if ($rows === []) {
        return;
    }

    if ($importToken !== '') {
        rpc($supabaseUrl, $supabaseKey, '__codex_import_rows', [
            'p_table_name' => $table,
            'p_rows' => $rows,
            'p_token' => $importToken,
        ]);

        return;
    }

    $url = "{$supabaseUrl}/rest/v1/".rawurlencode($table)
        .'?on_conflict='.rawurlencode(implode(',', $primaryKeys));

    request(
        'POST',
        $url,
        $supabaseKey,
        json_encode($rows, JSON_THROW_ON_ERROR),
        [
            'Content-Type: application/json',
            'Prefer: resolution=merge-duplicates,return=minimal',
        ],
    );
}

function finalizeTable(string $supabaseUrl, string $supabaseKey, string $table, string $importToken): void
{
    if ($importToken === '') {
        return;
    }

    rpc($supabaseUrl, $supabaseKey, '__codex_finalize_table', [
        'p_table_name' => $table,
        'p_token' => $importToken,
    ]);
}

/**
 * @param  array<string, mixed>  $payload
 */
function rpc(string $supabaseUrl, string $supabaseKey, string $function, array $payload): mixed
{
    $response = request(
        'POST',
        "{$supabaseUrl}/rest/v1/rpc/".rawurlencode($function),
        $supabaseKey,
        json_encode($payload, JSON_THROW_ON_ERROR),
        [
            'Content-Type: application/json',
        ],
    );

    if ($response['body'] === '') {
        return null;
    }

    return json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
}

/**
 * @param  list<string>  $extraHeaders
 * @return array{status:int,headers:array<string,string>,body:string}
 */
function request(
    string $method,
    string $url,
    string $supabaseKey,
    ?string $body = null,
    array $extraHeaders = [],
    bool $allowPartial = false,
): array {
    $headers = array_merge([
        'apikey: '.$supabaseKey,
        'Authorization: Bearer '.$supabaseKey,
        'Accept: application/json',
    ], $extraHeaders);

    $responseHeaders = [];
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADERFUNCTION => function ($ch, string $header) use (&$responseHeaders): int {
            $length = strlen($header);
            $header = trim($header);

            if ($header !== '' && str_contains($header, ':')) {
                [$name, $value] = explode(':', $header, 2);
                $responseHeaders[strtolower(trim($name))] = trim($value);
            }

            return $length;
        },
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $responseBody = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($responseBody === false || $error !== '') {
        throw new RuntimeException("HTTP request failed: {$error}");
    }

    $ok = $allowPartial
        ? ($status >= 200 && $status < 300)
        : ($status >= 200 && $status < 300);

    if (! $ok) {
        $safeUrl = preg_replace('/([?&]apikey=)[^&]+/', '$1***', $url) ?? $url;
        throw new RuntimeException("Supabase request failed ({$status}) {$method} {$safeUrl}: {$responseBody}");
    }

    return [
        'status' => $status,
        'headers' => $responseHeaders,
        'body' => (string) $responseBody,
    ];
}
