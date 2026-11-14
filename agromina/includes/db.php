<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    global $CONFIG;
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $CONFIG['db_host'], $CONFIG['db_name']);
    try {
        $pdo = new PDO($dsn, $CONFIG['db_user'], $CONFIG['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '+00:00'");
    } catch (PDOException $e) {
        http_response_code(503);
        error_log('AgroMina DB: ' . $e->getMessage());
        exit('<p style="font-family:sans-serif;padding:2rem">Le site est momentanément indisponible (connexion à la base de données impossible).</p>');
    }
    return $pdo;
}
