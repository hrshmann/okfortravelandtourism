<?php
declare(strict_types=1);

/**
 * Thin PDO layer. Works with MySQL/MariaDB (Hostinger) and SQLite (local / no-database plans).
 * Table and column names passed to insert()/update() always come from our own code, never user input.
 */
function db(): PDO
{
    if (!(($GLOBALS['_pdo'] ?? null) instanceof PDO)) {
        $GLOBALS['_pdo'] = db_connect((array) config('db', []));
    }
    return $GLOBALS['_pdo'];
}

/** Used by the installer, which connects before config.php exists. */
function db_use(PDO $pdo): void
{
    $GLOBALS['_pdo'] = $pdo;
}

function db_connect(array $cfg): PDO
{
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    if (($cfg['driver'] ?? 'mysql') === 'sqlite') {
        $file = $cfg['path'] ?? (STORAGE . '/database.sqlite');
        if (!str_starts_with($file, '/') && !preg_match('/^[A-Za-z]:/', $file)) {
            $file = ROOT . '/' . $file;
        }
        $pdo = new PDO('sqlite:' . $file, null, null, $opts);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        return $pdo;
    }
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $cfg['host'] ?? 'localhost',
        (int) ($cfg['port'] ?? 3306),
        $cfg['name'] ?? ''
    );
    // All timestamps are produced in PHP (see now()), so the database time zone never matters.
    return new PDO($dsn, $cfg['user'] ?? '', $cfg['pass'] ?? '', $opts);
}

function db_driver(): string
{
    return db()->getAttribute(PDO::ATTR_DRIVER_NAME);
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

function val(string $sql, array $params = [])
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

function insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
    q($sql, array_values($data));
    return (int) db()->lastInsertId();
}

function update(string $table, array $data, string $where, array $params = []): int
{
    $set = implode(', ', array_map(fn($c) => $c . ' = ?', array_keys($data)));
    return q('UPDATE ' . $table . ' SET ' . $set . ' WHERE ' . $where, array_merge(array_values($data), $params))->rowCount();
}

function delete_rows(string $table, string $where, array $params = []): int
{
    return q('DELETE FROM ' . $table . ' WHERE ' . $where, $params)->rowCount();
}

/** LIMIT/OFFSET are interpolated as integers: MySQL rejects them as bound strings. */
function limit_sql(int $limit, int $offset = 0): string
{
    return ' LIMIT ' . max(0, $limit) . ' OFFSET ' . max(0, $offset);
}

function paginate(string $baseSql, array $params, int $page, int $perPage, string $order = ''): array
{
    $total = (int) val('SELECT COUNT(*) FROM (' . $baseSql . ') t', $params);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    $items = rows($baseSql . ($order ? ' ORDER BY ' . $order : '') . limit_sql($perPage, ($page - 1) * $perPage), $params);
    return compact('items', 'total', 'pages', 'page', 'perPage');
}

function unique_slug(string $table, string $base, ?int $ignoreId = null): string
{
    $base = substr(slugify($base), 0, 180);
    $slug = $base;
    $i = 2;
    while (true) {
        $sql = 'SELECT id FROM ' . $table . ' WHERE slug = ?' . ($ignoreId ? ' AND id <> ?' : '');
        $exists = val($sql, $ignoreId ? [$slug, $ignoreId] : [$slug]);
        if (!$exists) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}
