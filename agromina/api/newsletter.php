<?php
require dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'error' => 'Méthode non autorisée.'], 405);
}
$raw = json_decode((string) file_get_contents('php://input'), true);
$email = is_array($raw) && is_string($raw['email'] ?? null) ? mb_substr(trim($raw['email']), 0, 190) : '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(['ok' => false, 'error' => 'Adresse e-mail invalide.'], 422);
}
db()->prepare('INSERT IGNORE INTO newsletter (email) VALUES (?)')->execute([mb_strtolower($email)]);
json_out(['ok' => true]);
