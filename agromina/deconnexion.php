<?php
require __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    logout_user();
    session_start();
    session_regenerate_id(true);
    flash('success', 'Vous êtes déconnecté.');
}
redirect(url('connexion'));
