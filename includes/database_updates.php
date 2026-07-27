<?php

/**
 * Inspect the one-time employment module database update without changing data.
 */
function employmentDatabaseUpdateStatus(PDO $pdo): array
{
    $requiredTables = [
        'employment_jobs',
        'employment_applications',
        'employment_application_attachments',
        'employment_application_events',
    ];
    $requiredColumns = [
        'employment_application_id',
        'employee_number',
        'job_title',
        'department',
        'job_grade',
        'supervisor_name',
        'supervisor_phone',
    ];

    $tablePlaceholders = implode(',', array_fill(0, count($requiredTables), '?'));
    $tableStatement = $pdo->prepare(
        "SELECT TABLE_NAME
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ($tablePlaceholders)"
    );
    $tableStatement->execute($requiredTables);
    $existingTables = array_map('strtolower', $tableStatement->fetchAll(PDO::FETCH_COLUMN));

    $columnPlaceholders = implode(',', array_fill(0, count($requiredColumns), '?'));
    $columnStatement = $pdo->prepare(
        "SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'employees'
           AND COLUMN_NAME IN ($columnPlaceholders)"
    );
    $columnStatement->execute($requiredColumns);
    $existingColumns = array_map('strtolower', $columnStatement->fetchAll(PDO::FETCH_COLUMN));

    $roleStatement = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE code = 'employee_portal'");
    $roleStatement->execute();
    $hasEmployeeRole = (int)$roleStatement->fetchColumn() > 0;

    $missing = [];
    foreach (array_diff($requiredTables, $existingTables) as $table) {
        $missing[] = 'table:' . $table;
    }
    foreach (array_diff($requiredColumns, $existingColumns) as $column) {
        $missing[] = 'employees.' . $column;
    }
    if (!$hasEmployeeRole) {
        $missing[] = 'role:employee_portal';
    }

    $total = count($requiredTables) + count($requiredColumns) + 1;

    return [
        'complete' => $missing === [],
        'found' => $total - count($missing),
        'total' => $total,
        'missing' => $missing,
    ];
}

/**
 * Split a regular SQL migration into statements while respecting quoted values
 * and SQL comments. Stored procedures with DELIMITER directives are intentionally
 * outside the scope of the project migration files.
 */
function splitSqlUpdateStatements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $state = 'normal';

    for ($index = 0; $index < $length; $index++) {
        $character = $sql[$index];
        $next = $index + 1 < $length ? $sql[$index + 1] : '';

        if ($state === 'line_comment') {
            if ($character === "\n") {
                $state = 'normal';
                $buffer .= "\n";
            }
            continue;
        }

        if ($state === 'block_comment') {
            if ($character === '*' && $next === '/') {
                $state = 'normal';
                $index++;
            }
            continue;
        }

        if ($state === 'normal') {
            if ($character === '#' || ($character === '-' && $next === '-' && ($index + 2 >= $length || ctype_space($sql[$index + 2])))) {
                $state = 'line_comment';
                if ($character === '-') {
                    $index++;
                }
                continue;
            }
            if ($character === '/' && $next === '*') {
                $state = 'block_comment';
                $index++;
                continue;
            }
            if ($character === "'" || $character === '"' || $character === '`') {
                $state = $character;
                $buffer .= $character;
                continue;
            }
            if ($character === ';') {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $character;
            continue;
        }

        $buffer .= $character;
        if ($character === '\\' && $index + 1 < $length) {
            $buffer .= $sql[++$index];
            continue;
        }
        if ($character === $state) {
            if ($next === $state) {
                $buffer .= $next;
                $index++;
            } else {
                $state = 'normal';
            }
        }
    }

    $statement = trim($buffer);
    if ($statement !== '') {
        $statements[] = $statement;
    }

    return $statements;
}

/**
 * Apply only the bundled employment migration selected by the application.
 */
function importEmploymentDatabaseUpdate(PDO $pdo, string $migrationPath): int
{
    if (!is_file($migrationPath) || !is_readable($migrationPath)) {
        throw new RuntimeException('The employment database update file is unavailable.');
    }

    $sql = file_get_contents($migrationPath);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('The employment database update file is empty.');
    }

    $statements = splitSqlUpdateStatements($sql);
    if ($statements === []) {
        throw new RuntimeException('No executable SQL statements were found in the update file.');
    }

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }

    $status = employmentDatabaseUpdateStatus($pdo);
    if (!$status['complete']) {
        throw new RuntimeException('The database update finished without creating every required object.');
    }

    return count($statements);
}
