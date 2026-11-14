<?php
require __DIR__ . '/includes/bootstrap.php';

$user = current_user();

if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'profile') {
        $name = str_input('full_name', 80);
        $phone = str_input('phone', 20);
        db()->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?')->execute([$name, $phone, $user['id']]);
        flash('success', 'Coordonnées enregistrées');
    } elseif ($action === 'password') {
        $current = (string) ($_POST['current'] ?? '');
        $new = (string) ($_POST['new'] ?? '');
        $st = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $st->execute([$user['id']]);
        if (!password_verify($current, (string) $st->fetchColumn())) {
            flash('error', 'Mot de passe actuel incorrect.');
        } elseif (mb_strlen($new) < 8) {
            flash('error', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
        } else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flash('success', 'Mot de passe mis à jour.');
        }
    }
    redirect(url('compte'));
}

page_header(['title' => 'Mon compte – AgroMina', 'description' => "Vos coordonnées AgroMina et l'accès au suivi de vos commandes.", 'noindex' => true]);

if (!$user): ?>
  <?php page_hero('Mon compte', 'Connectez-vous pour enregistrer vos coordonnées et suivre vos commandes.'); ?>
  <div class="container btn-row">
    <a class="btn btn-primary" href="<?= e(url('connexion', ['next' => '/compte'])) ?>">Se connecter</a>
    <a class="btn btn-outline" href="<?= e(url('suivi')) ?>">Suivre une commande sans compte</a>
  </div>
<?php else: ?>
  <?php page_hero('Mon compte', $user['email']); ?>
  <div class="container account-grid">
    <div class="stack">
      <form class="card form-card" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="profile">
        <h2 style="font-size:1.125rem;color:var(--fg)">Mes coordonnées</h2>
        <p class="sub">Elles sont pré-remplies à chaque commande.</p>
        <label class="label"><span>Nom complet</span><input class="field" name="full_name" maxlength="80" value="<?= e($user['full_name']) ?>"></label>
        <label class="label"><span>Téléphone</span><input class="field" name="phone" maxlength="20" value="<?= e($user['phone']) ?>"></label>
        <label class="label"><span>E-mail</span><input class="field" value="<?= e($user['email']) ?>" readonly style="background:var(--secondary);color:var(--muted-fg)"></label>
        <div><button class="btn btn-accent">Enregistrer</button></div>
      </form>
      <form class="card form-card" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="password">
        <h2 style="font-size:1.125rem;color:var(--fg)">Changer mon mot de passe</h2>
        <label class="label"><span>Mot de passe actuel</span><input class="field" type="password" name="current" autocomplete="current-password" required></label>
        <label class="label"><span>Nouveau mot de passe (8 caractères minimum)</span><input class="field" type="password" name="new" minlength="8" autocomplete="new-password" required></label>
        <div><button class="btn btn-outline">Mettre à jour</button></div>
      </form>
    </div>

    <div class="account-side">
      <div class="card box">
        <h2>Mes commandes</h2>
        <p>Chaque commande validée donne une référence (AM-…).</p>
        <a class="btn btn-primary" href="<?= e(url('suivi')) ?>">Suivre une commande</a>
      </div>
      <?php if (is_staff($user)): ?>
        <div class="box box-admin">
          <h2>Accès administrateur</h2>
          <p>Votre compte peut gérer le catalogue, les commandes et les clients.</p>
          <a class="btn btn-primary" href="<?= e(url('admin/')) ?>">Ouvrir l'espace admin</a>
        </div>
      <?php endif; ?>
      <form method="post" action="<?= e(url('deconnexion')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-danger btn-block">Se déconnecter</button>
      </form>
    </div>
  </div>
<?php endif;
page_footer();
