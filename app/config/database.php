<?php
/** Conexão PDO (MySQL/InnoDB, ACID) e atalhos de prepared statements (proteção contra SQL Injection). */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . env('DB_HOST', '127.0.0.1') . ';dbname=' . env('DB_NAME', 'woodrop') . ';charset=utf8mb4';
        $pdo = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASS', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
function q(string $sql, array $p = []): PDOStatement { $s = db()->prepare($sql); $s->execute($p); return $s; }
function qa(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function q1(string $sql, array $p = []): ?array { $r = q($sql, $p)->fetch(); return $r ?: null; }
