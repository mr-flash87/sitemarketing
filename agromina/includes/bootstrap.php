<?php
/**
 * Chargé en premier par chaque page : configuration, session, base de données et fonctions.
 */
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
date_default_timezone_set('Africa/Dakar');
mb_internal_encoding('UTF-8');

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    http_response_code(503);
    echo '<!doctype html><meta charset="utf-8"><title>Installation requise</title>'
        . '<p style="font-family:sans-serif;padding:2rem">Le site n\'est pas encore installé. '
        . 'Ouvrez <a href="/install.php">install.php</a> pour le configurer.</p>';
    exit;
}

/** @var array<string, mixed> $CONFIG */
$CONFIG = require $configFile;

if (!empty($CONFIG['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_name('agromina');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $secure,
]);
session_start();

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/icons.php';
require __DIR__ . '/layout.php';
