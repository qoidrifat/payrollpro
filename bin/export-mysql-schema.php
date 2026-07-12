<?php

/**
 * Export SQLite schema + seed data as MySQL-compatible SQL.
 *
 * Usage: php bin/export-mysql-schema.php > migrate.sql
 *
 * This script reads the current SQLite database (after all migrations
 * have been applied) and generates a MySQL-compatible SQL dump that can
 * be imported via phpMyAdmin on InfinityFree.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dbPath = config('database.connections.sqlite.database');
if (! $dbPath || ! file_exists($dbPath)) {
    fwrite(STDERR, "SQLite database not found at: " . ($dbPath ?? 'null') . "\n");
    exit(1);
}

// ── SQLite type → MySQL type mapping ─────────────────────────────────
$typeMap = [
    'INTEGER'     => 'INT',
    'INT'         => 'INT',
    'BIGINT'      => 'BIGINT',
    'TINYINT'     => 'TINYINT',
    'SMALLINT'    => 'SMALLINT',
    'MEDIUMINT'   => 'MEDIUMINT',
    'REAL'        => 'DOUBLE',
    'FLOAT'       => 'FLOAT',
    'DOUBLE'      => 'DOUBLE',
    'NUMERIC'     => 'DECIMAL',
    'DECIMAL'     => 'DECIMAL',
    'TEXT'        => 'TEXT',
    'VARCHAR'     => 'VARCHAR(255)',
    'CHAR'        => 'CHAR',
    'CLOB'        => 'TEXT',
    'BLOB'        => 'BLOB',
    'DATE'        => 'DATE',
    'DATETIME'    => 'DATETIME',
    'TIMESTAMP'   => 'TIMESTAMP',
    'TIME'        => 'TIME',
    'BOOLEAN'     => 'TINYINT(1)',
];

$enumMap = []; // Will hold detected ENUMs

// ── Fallback ENUM definitions from Laravel migrations ────────────────
// SQLite does NOT store CHECK constraints for Laravel enum() columns,
// so the regex-based detection below will find nothing. This map provides
// the ENUM values as defined in the migration files.
// Format: [table] => [column => [val1, val2, ...]]
$enumFallbackMap = [
    'employees' => [
        'gender'             => ['male', 'female'],
        'employment_status'  => ['permanent', 'contract', 'probation', 'intern'],
    ],
    'attendances' => [
        'status' => ['present', 'absent', 'late', 'half_day', 'sick', 'leave'],
        'type'   => ['wfo', 'wfh', 'remote'],
    ],
    'salary_components' => [
        'type' => ['allowance', 'deduction', 'bonus', 'overtime'],
    ],
    'payrolls' => [
        // status was an ENUM in the original migration but was later widened
        // to VARCHAR(20) in 2026_06_05_000001 to add 'processing'.
        // Keep it as VARCHAR to match the current schema.
    ],
    'payroll_items' => [],
    'leave_requests' => [],
    'overtime_rules' => [],
    'overtime_requests' => [],
];

// Apply fallback ENUMs (will be overridden by CHECK-detected ENUMs if any)

// ── Step 1: Collect all tables ───────────────────────────────────────
$pdo = new PDO("sqlite:{$dbPath}");
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

// ── Step 2: Gather schema info per table ─────────────────────────────
$tableSchema = [];
$fkMap = [];
$indexMap = [];

foreach ($tables as $table) {
    // Column info
    $cols = $pdo->query("PRAGMA table_info({$table})")->fetchAll(PDO::FETCH_ASSOC);
    $columns = [];
    $hasAutoIncrement = false;
    foreach ($cols as $col) {
        $name = $col['name'];
        $sqliteType = strtoupper($col['type']);
        $notNull = $col['notnull'] ? 'NOT NULL' : '';
        $default = $col['dflt_value'];
        $pk = $col['pk'];

        // Detect ENUM constraints
        if (preg_match('/^VARCHAR\((\d+)\)\s*$/i', $col['type'])) {
            // Check if there's a CHECK constraint for this column with IN list
            // We'll handle this later from sqlite_master
        }

        $columns[$name] = [
            'name'      => $name,
            'type'      => $sqliteType,
            'type_raw'  => $col['type'],
            'notnull'   => $col['notnull'],
            'default'   => $default,
            'pk'        => $pk,
        ];
        if ($pk == 1) {
            $hasAutoIncrement = true;
        }
    }
    $tableSchema[$table] = $columns;

    // Foreign keys
    $fks = $pdo->query("PRAGMA foreign_key_list({$table})")->fetchAll(PDO::FETCH_ASSOC);
    $fkMap[$table] = $fks;

    // Indexes
    $indexes = $pdo->query("PRAGMA index_list({$table})")->fetchAll(PDO::FETCH_ASSOC);
    $indexMap[$table] = [];
    foreach ($indexes as $idx) {
        if ($idx['origin'] === 'pk') continue; // Skip PK indexes
        $idxName = $idx['name'];
        $idxCols = $pdo->query("PRAGMA index_info({$idxName})")->fetchAll(PDO::FETCH_ASSOC);
        $columns = array_column($idxCols, 'name');
        $indexMap[$table][] = [
            'name'      => $idxName,
            'columns'   => $columns,
            'unique'    => $idx['unique'] == 1,
        ];
    }
}

// ── Step 3: Detect ENUM columns from sqlite_master ───────────────────
// SQLite ENUMs are stored as CHECK constraints like:
//   CHECK(gender IN ('male','female'))
//   CHECK(`status` IN ('present','absent'))
// The regex must handle both quoted (`) and unquoted column names.
foreach ($tables as $table) {
    $createSql = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='{$table}'")->fetchColumn();
    if ($createSql) {
        // Match CHECK(... IN (...)) — column can be unquoted `word` or `backtick_quoted`
        preg_match_all('/CHECK\s*\(\s*(?:`([^`]+)`|(\w+))\s+IN\s*\(([^)]+)\)/i', $createSql, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            // Column name is either group 1 (backtick) or group 2 (unquoted)
            $colName = $m[1] !== '' ? $m[1] : $m[2];
            $inClause = $m[3];
            $values = explode(',', $inClause);
            $values = array_map(function($v) {
                return trim($v, " \t\n\r\0\x0B'\"");
            }, $values);
            if (isset($tableSchema[$table][$colName])) {
                $enumMap[$table][$colName] = $values;
            }
        }
    }
}

// ── Apply fallback ENUMs from migration definitions ────────────────
// SQLite does not persist Laravel's enum() CHECK constraints, so the
// regex detection above finds nothing. Overlay the known ENUM definitions
// from the migration files (detected via regex takes priority).
foreach ($enumFallbackMap as $table => $columns) {
    foreach ($columns as $colName => $values) {
        if (!isset($enumMap[$table][$colName]) || empty($enumMap[$table][$colName])) {
            $enumMap[$table][$colName] = $values;
        }
    }
}

// ── Utility: map SQLite type to MySQL ────────────────────────────────
function mysqlType(array $col, array $enums): string {
    $name = $col['name'];
    $typeRaw = $col['type_raw'];

    // Check if this column has an ENUM constraint
    if (!empty($enums)) {
        $vals = array_map(function($v) { return "'{$v}'"; }, $enums);
        return "ENUM(" . implode(',', $vals) . ")";
    }

    // Check for DECIMAL(x,y) pattern in raw type
    if (preg_match('/^DECIMAL\s*\((\d+)\s*,\s*(\d+)\)$/i', $typeRaw, $m)) {
        return "DECIMAL({$m[1]},{$m[2]})";
    }

    // Check for VARCHAR(n) in raw type
    if (preg_match('/^VARCHAR\s*\((\d+)\)$/i', $typeRaw, $m)) {
        return "VARCHAR({$m[1]})";
    }

    // Check for INT(n)
    if (preg_match('/^INT\s*\((\d+)\)$/i', $typeRaw, $m)) {
        return "INT({$m[1]})";
    }

    // Check for BIGINT(n)
    if (preg_match('/^BIGINT\s*\((\d+)\)$/i', $typeRaw, $m)) {
        return "BIGINT({$m[1]})";
    }

    $upper = strtoupper($typeRaw);

    global $typeMap;

    // Handle types with precision like VARCHAR(255)
    if (strpos($upper, '(') !== false) {
        $baseType = strtoupper(substr($upper, 0, strpos($upper, '(')));
        if (isset($typeMap[$baseType])) {
            $base = $typeMap[$baseType];
            // If it's already VARCHAR or similar, keep the original specification
            if (preg_match('/^(VARCHAR|CHAR|DECIMAL|NUMERIC)\s*\(/i', $typeRaw)) {
                return $typeRaw;
            }
            return $base;
        }
    }

    if (isset($typeMap[$upper])) {
        return $typeMap[$upper];
    }

    // Fallback: check the base type
    $base = preg_replace('/\(.*\)/', '', $upper);
    if (isset($typeMap[$base])) {
        return $typeMap[$base];
    }

    return $typeRaw; // Pass through as-is
}

// ── Format default value for MySQL ───────────────────────────────────
function mysqlDefault($default, string $mysqlType): string {
    if ($default === null) return '';

    // Remove surrounding quotes from SQLite
    $default = trim($default);
    
    // Handle NULL default
    if (strtoupper($default) === 'NULL') {
        return 'DEFAULT NULL';
    }

    // Handle CURRENT_TIMESTAMP
    if (strtoupper($default) === 'CURRENT_TIMESTAMP' || strtoupper($default) === "'CURRENT_TIMESTAMP'") {
        return 'DEFAULT CURRENT_TIMESTAMP';
    }

    // Handle string literals
    if (preg_match('/^["\'](.+)["\']$/', $default, $m)) {
        return "DEFAULT '{$m[1]}'";
    }

    // Handle numeric values
    if (is_numeric($default)) {
        return "DEFAULT {$default}";
    }

    // Handle boolean-ish values
    if ($default === '0' || $default === '1') {
        return "DEFAULT {$default}";
    }

    return "DEFAULT '{$default}'";
}

// ── Output header ────────────────────────────────────────────────────
echo "-- ================================================================\n";
echo "-- migrate.sql — MySQL-compatible schema for Project KP\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
echo "-- Target: InfinityFree MySQL (phpMyAdmin)\n";
echo "-- ================================================================\n\n";

echo "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
echo "SET AUTOCOMMIT = 0;\n";
echo "START TRANSACTION;\n";
echo "SET time_zone = '+07:00';\n\n";

// ── Output CREATE TABLE statements ───────────────────────────────────
$autoIncrementTables = [];

// Pulse tables need special handling for MySQL virtual columns
$pulseVirtualColumns = [
    'pulse_values'     => 'key_hash',
    'pulse_entries'    => 'key_hash',
    'pulse_aggregates' => 'key_hash',
];

foreach ($tables as $table) {
    $cols = $tableSchema[$table];
    $enumsForTable = $enumMap[$table] ?? [];
    $fks = $fkMap[$table];
    $idxs = $indexMap[$table];

    echo "-- --------------------------------------------------------\n";
    echo "-- Table structure for `{$table}`\n";
    echo "-- --------------------------------------------------------\n\n";

    echo "DROP TABLE IF EXISTS `{$table}`;\n\n";

    echo "CREATE TABLE `{$table}` (\n";

    $lines = [];
    $hasAutoIncrement = false;
    $pkColumns = [];

    foreach ($cols as $col) {
        // Pulse tables: use MySQL virtual column for key_hash
        if (isset($pulseVirtualColumns[$table]) && $col['name'] === 'key_hash') {
            // In MySQL, key_hash is CHAR(16) BINARY VIRTUAL AS (UNHEX(MD5(key)))
            // Keep it as VARCHAR for imported data to work, but add comment
            $parts = ["  `{$col['name']}` CHAR(16) CHARACTER SET binary NOT NULL"];
            $lines[] = implode(' ', $parts);
            // Add the actual virtual column as a comment/instruction
            continue;
        }

        $mysqlType = mysqlType($col, $enumsForTable[$col['name']] ?? []);

        $parts = ["  `{$col['name']}` {$mysqlType}"];

        // Handle AUTO_INCREMENT
        if ($col['pk'] == 1 && $col['name'] === 'id' && (strpos($mysqlType, 'INT') !== false || strpos($mysqlType, 'BIGINT') !== false)) {
            $parts[0] = "  `{$col['name']}` {$mysqlType} NOT NULL AUTO_INCREMENT";
            $hasAutoIncrement = true;
            // Don't add to pkColumns list for composite PK tables
            continue;
        }

        if ($col['pk'] > 0) {
            $pkColumns[] = $col['name'];
        }

        // NOT NULL
        if ($col['notnull']) {
            $parts[] = 'NOT NULL';
        } else {
            $parts[] = 'NULL';
        }

        // DEFAULT
        $defaultVal = mysqlDefault($col['default'], $mysqlType);
        if ($defaultVal !== '') {
            $parts[] = $defaultVal;
        }

        $lines[] = implode(' ', $parts);
    }

    // Handle PK for tables where id is auto_increment but there's also other PKs
    // or for non-standard PK columns
    if (!$hasAutoIncrement && !empty($pkColumns)) {
        $lines[] = "  PRIMARY KEY (`" . implode('`, `', $pkColumns) . "`)";
    } else if ($hasAutoIncrement) {
        $lines[] = "  PRIMARY KEY (`id`)";
    } elseif (in_array($table, ['cache', 'cache_locks'])) {
        // These tables have 'key' as primary
        $lines[] = "  PRIMARY KEY (`key`)";
    }

    // Add UNIQUE indexes
    foreach ($idxs as $idx) {
        if ($idx['unique']) {
            $lines[] = "  UNIQUE KEY `{$idx['name']}` (`" . implode('`, `', $idx['columns']) . "`)";
        }
    }

    echo implode(",\n", $lines);
    echo "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";
}

// ── Output FOREIGN KEY constraints (AFTER all tables exist) ─────────
// IMPORTANT: Tables must exist before adding FK constraints. MySQL
// requires referenced tables to be created first, but alphabetical
// order doesn't guarantee this. Solution: create all tables without
// FKs, then add all FKs via ALTER TABLE.
echo "-- --------------------------------------------------------\n";
echo "-- Foreign Key Constraints (added after all tables exist)\n";
echo "-- --------------------------------------------------------\n\n";

foreach ($tables as $table) {
    $fks = $fkMap[$table];
    foreach ($fks as $fk) {
        $from = $fk['from'];
        $toTable = $fk['table'];
        $toCol = $fk['to'];
        $onUpdate = $fk['on_update'] ?: 'NO ACTION';
        $onDelete = $fk['on_delete'] ?: 'NO ACTION';
        $constraintName = "fk_{$table}_{$from}";
        // Truncate constraint name to 64 chars (MySQL limit)
        if (strlen($constraintName) > 64) {
            $constraintName = substr($constraintName, 0, 64);
        }
        echo "ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraintName}`";
        echo " FOREIGN KEY (`{$from}`) REFERENCES `{$toTable}` (`{$toCol}`)";
        echo " ON DELETE {$onDelete} ON UPDATE {$onUpdate};\n";
    }
}
echo "\n";

// ── Output INDEXES (non-unique, non-PK) ──────────────────────────────
echo "-- --------------------------------------------------------\n";
echo "-- Indexes for tables\n";
echo "-- --------------------------------------------------------\n\n";

foreach ($tables as $table) {
    $idxs = $indexMap[$table];
    $cols = $tableSchema[$table];

    foreach ($idxs as $idx) {
        if ($idx['unique']) continue; // Already added in CREATE TABLE

        // Skip if it's a PK-related index
        if (count($idx['columns']) === 1 && $idx['columns'][0] === 'id') continue;

        echo "CREATE INDEX `{$idx['name']}` ON `{$table}` (`" . implode('`, `', $idx['columns']) . "`);\n";
    }
}
echo "\n";

// ── Export migrations table data ─────────────────────────────────────
echo "-- --------------------------------------------------------\n";
echo "-- Migrations table data (so Laravel knows which migrations ran)\n";
echo "-- --------------------------------------------------------\n\n";

$migrationsData = $pdo->query("SELECT migration, batch FROM migrations ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
if (!empty($migrationsData)) {
    echo "INSERT INTO `migrations` (`migration`, `batch`) VALUES\n";
    $migRows = [];
    foreach ($migrationsData as $i => $m) {
        $comma = ($i < count($migrationsData) - 1) ? ',' : ';';
        $safeMigration = str_replace("'", "''", $m['migration']);
        $migRows[] = "  ('{$safeMigration}', {$m['batch']}){$comma}";
    }
    echo implode("\n", $migRows) . "\n\n";
} else {
    echo "-- No migration records found to export.\n\n";
}

// ── Seed data: roles and permissions ─────────────────────────────────
echo "-- --------------------------------------------------------\n";
echo "-- Seed data: Roles and Permissions\n";
echo "-- --------------------------------------------------------\n\n";

// Permissions
$permissions = [
    'manage-employees',
    'manage-attendance',
    'manage-leaves',
    'view-attendance',
    'manage-payroll',
    'view-payroll',
    'manage-settings',
    'view-reports',
    'view-dashboard',
];

echo "INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES\n";
$permLines = [];
foreach ($permissions as $i => $perm) {
    $comma = ($i < count($permissions) - 1) ? ',' : ';';
    $permLines[] = "  ('{$perm}', 'web', NOW(), NOW()){$comma}";
}
echo implode("\n", $permLines) . "\n\n";

// Roles
$roles = ['Admin', 'HR', 'Employee'];
echo "INSERT INTO `roles` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES\n";
$roleLines = [];
foreach ($roles as $i => $role) {
    $comma = ($i < count($roles) - 1) ? ',' : ';';
    $roleLines[] = "  ('{$role}', 'web', NOW(), NOW()){$comma}";
}
echo implode("\n", $roleLines) . "\n\n";

// Role-permission assignments (using variable assignments for ID references)
echo "-- Assign permissions to roles\n";
echo "-- Assumes: Admin=1, HR=2, Employee=3\n";
echo "-- Admin gets all permissions (1-9)\n";
echo "INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES\n";
$adminPerms = range(1, 9);
$hrPerms = [1, 2, 3, 4, 5, 6, 8, 9]; // all except manage-settings (7)
$empPerms = [4, 6, 9]; // view-attendance, view-payroll, view-dashboard
$allAssignments = [];
foreach ($adminPerms as $p) { $allAssignments[] = "({$p}, 1)"; }
foreach ($hrPerms as $p) { $allAssignments[] = "({$p}, 2)"; }
foreach ($empPerms as $p) { $allAssignments[] = "({$p}, 3)"; }
echo "  " . implode(",\n  ", $allAssignments) . ";\n\n";

// ── Seed data: Company ───────────────────────────────────────────────
echo "-- --------------------------------------------------------\n";
echo "-- Seed data: Company\n";
echo "-- --------------------------------------------------------\n\n";
echo "INSERT INTO `companies` (`name`, `slug`, `is_active`, `subscription_plan`, `created_at`, `updated_at`)\n";
echo "VALUES ('Project KP', 'project-kp', 1, 'internal', NOW(), NOW());\n\n";

// ── Seed data: BPJS Config ────────────────────────────────────────────
echo "-- --------------------------------------------------------\n";
echo "-- Seed data: BPJS Config\n";
echo "-- --------------------------------------------------------\n\n";

$bpjsConfigs = [
    ['BPJS Kesehatan - Company', 'kesehatan', 'company', 4.00, 12000000, '4% dari gaji bulanan, maksimal Rp 12.000.000'],
    ['BPJS Kesehatan - Employee', 'kesehatan', 'employee', 1.00, 12000000, '1% dari gaji bulanan, maksimal Rp 12.000.000'],
    ['BPJS TK JHT - Company', 'tk_jht', 'company', 3.70, 'NULL', '3.7% dari gaji bulanan'],
    ['BPJS TK JHT - Employee', 'tk_jht', 'employee', 2.00, 'NULL', '2% dari gaji bulanan'],
    ['BPJS TK JP - Company', 'tk_jp', 'company', 2.00, 10547400, '2% dari gaji bulanan, maksimal Rp 10.547.400 (PPU 2026)'],
    ['BPJS TK JP - Employee', 'tk_jp', 'employee', 1.00, 10547400, '1% dari gaji bulanan, maksimal Rp 10.547.400 (PPU 2026)'],
    ['BPJS TK JKK', 'tk_jkk', 'company', 0.24, 'NULL', '0.24% dari gaji bulanan (company only, risiko rendah)'],
    ['BPJS TK JKM', 'tk_jkm', 'company', 0.30, 'NULL', '0.3% dari gaji bulanan (company only)'],
];

echo "INSERT INTO `bpjs_configs` (`name`, `type`, `payer`, `rate_percentage`, `salary_cap`, `description`, `applicable_year`, `is_active`, `created_at`, `updated_at`) VALUES\n";
$bpjsRows = [];
foreach ([2025, 2026] as $year) {
    foreach ($bpjsConfigs as $i => $cfg) {
        $comma = ($year === 2026 && $i === count($bpjsConfigs) - 1) ? ';' : ',';
        $salaryCap = $cfg[4];
        if ($salaryCap !== 'NULL') {
            $salaryCap = number_format($salaryCap, 2, '.', '');
        }
        $bpjsRows[] = "  ('{$cfg[0]}', '{$cfg[1]}', '{$cfg[2]}', {$cfg[3]}, {$salaryCap}, '{$cfg[5]}', {$year}, 1, NOW(), NOW()){$comma}";
    }
}
echo implode("\n", $bpjsRows) . "\n\n";

// ── Seed data: PPh21 Config ──────────────────────────────────────────
echo "-- --------------------------------------------------------\n";
echo "-- Seed data: PPh21 Config\n";
echo "-- --------------------------------------------------------\n\n";

$pph21Brackets = [
    [0, 60000000, 5.0],
    [60000000, 250000000, 15.0],
    [250000000, 500000000, 25.0],
    [500000000, 5000000000, 30.0],
    [5000000000, 'NULL', 35.0],
];

echo "INSERT INTO `pph21_configs` (`income_bracket_start`, `income_bracket_end`, `rate_percentage`, `applicable_year`, `is_active`, `created_at`, `updated_at`) VALUES\n";
$pph21Rows = [];
foreach ([2025, 2026] as $year) {
    foreach ($pph21Brackets as $i => $b) {
        $comma = ($year === 2026 && $i === count($pph21Brackets) - 1) ? ';' : ',';
        $end = $b[1];
        $pph21Rows[] = "  ({$b[0]}, {$end}, {$b[2]}, {$year}, 1, NOW(), NOW()){$comma}";
    }
}
echo implode("\n", $pph21Rows) . "\n\n";

// ── Seed data: PTKP Config ───────────────────────────────────────────
echo "-- --------------------------------------------------------\n";
echo "-- Seed data: PTKP Config\n";
echo "-- --------------------------------------------------------\n\n";

$ptkpConfigs = [
    ['TK/0', 'Tidak Kawin, 0 tanggungan', 54000000],
    ['TK/1', 'Tidak Kawin, 1 tanggungan', 58500000],
    ['TK/2', 'Tidak Kawin, 2 tanggungan', 63000000],
    ['TK/3', 'Tidak Kawin, 3 tanggungan', 67500000],
    ['K/0', 'Kawin, 0 tanggungan', 58500000],
    ['K/1', 'Kawin, 1 tanggungan', 63000000],
    ['K/2', 'Kawin, 2 tanggungan', 67500000],
    ['K/3', 'Kawin, 3 tanggungan', 72000000],
];

echo "INSERT INTO `ptkp_configs` (`category`, `description`, `annual_amount`, `applicable_year`, `is_active`, `created_at`, `updated_at`) VALUES\n";
$ptkpRows = [];
foreach ([2025, 2026] as $year) {
    foreach ($ptkpConfigs as $i => $c) {
        $comma = ($year === 2026 && $i === count($ptkpConfigs) - 1) ? ';' : ',';
        $ptkpRows[] = "  ('{$c[0]}', '{$c[1]}', {$c[2]}, {$year}, 1, NOW(), NOW()){$comma}";
    }
}
echo implode("\n", $ptkpRows) . "\n\n";

echo "-- --------------------------------------------------------\n";
echo "-- Pulse: jika Pulse error untuk key_hash, jalankan ALTER manual:\n";
echo "-- ALTER TABLE pulse_values MODIFY key_hash CHAR(16) CHARACTER SET binary AS (UNHEX(MD5(`key`))) VIRTUAL;\n";
echo "-- ALTER TABLE pulse_entries MODIFY key_hash CHAR(16) CHARACTER SET binary AS (UNHEX(MD5(`key`))) VIRTUAL;\n";
echo "-- ALTER TABLE pulse_aggregates MODIFY key_hash CHAR(16) CHARACTER SET binary AS (UNHEX(MD5(`key`))) VIRTUAL;\n";
echo "-- --------------------------------------------------------\n\n";

echo "COMMIT;\n";
echo "-- ================================================================\n";
echo "-- End of migrate.sql\n";
echo "-- ================================================================\n";
