<?php
require __DIR__ . '/includes/bootstrap.php';

$next = isset($_GET['next']) && is_string($_GET['next']) ? $_GET['next'] : (string) ($_POST['next'] ?? '');
if (current_user()) {
    redirect(safe_next($next, url('compte')));
}

$email = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = mb_strtolower(str_input('email', 190));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    $now = time();
    $_SESSION['login_fail'] = array_values(array_filter($_SESSION['login_fail'] ?? [], fn($t) => $t > $now - 900));
    if (count($_SESSION['login_fail']) >= 8) {
        $err = 'Trop de tentatives. Réessayez dans quelques minutes.';
    } else {
        $st = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch();
        if ($u && password_verify($password, $u['password_hash'])) {
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $u['id']]);
            }
            unset($_SESSION['login_fail']);
            login_user((int) $u['id']);
            $target = safe_next($next, '');
            if ($target === '') {
                $target = is_staff(current_user()) ? url('admin/') : url('compte');
            }
            redirect($target);
        }
        $_SESSION['login_fail'][] = $now;
        $err = 'Connexion impossible. Vérifiez votre e-mail et votre mot de passe.';
    }
}

page_header(['title' => 'Connexion – AgroMina', 'description' => 'Connectez-vous à votre compte AgroMina.', 'noindex' => true]);
?>
<div class="container narrow-sm" style="margin-top:3rem">
  <div style="display:flex;justify-content:center;margin-bottom:1.5rem"><?php logo_link('logo-lg') ?></div>
  <form class="card form-card" method="post" action="<?= e(url('connexion')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <h1>Connexion</h1>
    <p class="sub">Accès réservé aux comptes existants.</p>
    <?php if ($err): ?><p class="alert-danger" style="margin:0"><?= e($err) ?></p><?php endif; ?>
    <label class="label"><span>Adresse e-mail</span><input class="field" type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required></label>
    <label class="label"><span>Mot de passe</span><input class="field" type="password" name="password" autocomplete="current-password" required></label>
    <button class="btn btn-accent btn-block" style="margin-top:.5rem">Se connecter</button>
    <p class="muted" style="text-align:center;font-size:.875rem">Mot de passe oublié ? Demandez à un administrateur de le réinitialiser depuis l'espace « Équipe ».</p>
    <p class="muted" style="padding-top:1rem;text-align:center;font-size:.75rem">
      En continuant, vous acceptez nos <a href="<?= e(url('cgv')) ?>" style="text-decoration:underline">conditions de vente</a> et notre
      <a href="<?= e(url('confidentialite')) ?>" style="text-decoration:underline">politique de confidentialité</a>.
    </p>
  </form>
</div>
<?php page_footer(); ?>
