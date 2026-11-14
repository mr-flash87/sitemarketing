<?php
require __DIR__ . '/_layout.php';

if (!is_super($ADMIN)) {
    flash('error', "Seul un super admin peut gérer l'équipe.");
    redirect(url('admin/'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $pdo = db();

    if ($action === 'add') {
        $email = mb_strtolower(str_input('email', 190));
        $password = (string) ($_POST['password'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin', 'moderator'], true) ? $_POST['role'] : 'moderator';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'E-mail invalide.');
        } else {
            $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $st->execute([$email]);
            $uid = $st->fetchColumn();
            if ($uid) {
                $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $uid]);
                if ($password !== '') {
                    if (mb_strlen($password) < 8) {
                        flash('error', 'Mot de passe : 8 caractères minimum.');
                        redirect(url('admin/equipe'));
                    }
                    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $uid]);
                }
                flash('success', 'Accès mis à jour pour ' . $email);
            } elseif (mb_strlen($password) < 8) {
                flash('error', "Ce compte n'existe pas encore : choisissez un mot de passe (8 caractères minimum) pour le créer.");
            } else {
                $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)')->execute([$email, password_hash($password, PASSWORD_DEFAULT), $role]);
                flash('success', 'Compte créé pour ' . $email . '. Communiquez-lui son mot de passe.');
            }
        }
    }

    if ($action === 'remove') {
        $uid = (int) ($_POST['user_id'] ?? 0);
        if ($uid === (int) $ADMIN['id']) {
            flash('error', 'Vous ne pouvez pas retirer votre propre accès.');
        } else {
            $pdo->prepare("UPDATE users SET role = 'client' WHERE id = ?")->execute([$uid]);
            flash('success', 'Accès retiré.');
        }
    }

    if ($action === 'reset') {
        $uid = (int) ($_POST['user_id'] ?? 0);
        $password = (string) ($_POST['password'] ?? '');
        if (mb_strlen($password) < 8) {
            flash('error', 'Mot de passe : 8 caractères minimum.');
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $uid]);
            flash('success', 'Mot de passe réinitialisé.');
        }
    }
    redirect(url('admin/equipe'));
}

$team = db()->query("SELECT id, email, full_name, role, created_at FROM users WHERE role IN ('admin','moderator') ORDER BY role = 'admin' DESC, email")->fetchAll();

admin_header('Équipe', 'equipe');
admin_title('Équipe', 'Super admin : gère tout, y compris l\'équipe. Gérant : gère la boutique (commandes, produits, clients).');
?>
<div class="stack">
  <div class="panel">
    <?php foreach ($team as $m): $me = (int) $m['id'] === (int) $ADMIN['id']; ?>
      <div class="team-row">
        <span class="initials"><?= e(mb_strtoupper(mb_substr($m['email'], 0, 1))) ?></span>
        <div>
          <p><?= e($m['email']) ?><?= $me ? ' (vous)' : '' ?></p>
          <small><?= $m['role'] === 'admin' ? 'Super admin' : 'Gérant' ?> · depuis le <?= e(fr_date($m['created_at'], 'numeric')) ?></small>
        </div>
        <?php if (!$me): ?>
          <details style="position:relative">
            <summary class="btn btn-outline btn-sm" style="list-style:none">Gérer</summary>
            <div class="panel panel-pad" style="position:absolute;right:0;top:110%;z-index:10;width:min(80vw,18rem);display:grid;gap:.75rem">
              <form method="post" style="display:grid;gap:.5rem">
                <?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="user_id" value="<?= (int) $m['id'] ?>">
                <label class="label"><span>Nouveau mot de passe</span><input class="field" type="text" name="password" minlength="8" required autocomplete="off"></label>
                <button class="btn btn-primary btn-sm">Réinitialiser</button>
              </form>
              <form method="post" data-confirm="Retirer l'accès admin de <?= e($m['email']) ?> ?">
                <?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="user_id" value="<?= (int) $m['id'] ?>">
                <button class="btn btn-danger btn-sm btn-block">Retirer l'accès</button>
              </form>
            </div>
          </details>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <form class="panel panel-pad" method="post" style="display:grid;gap:.75rem">
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <h2 style="font-size:1.125rem">Ajouter un membre</h2>
    <p class="muted" style="font-size:.875rem">Si le compte existe déjà, il reçoit simplement le rôle choisi. Sinon, il est créé avec le mot de passe indiqué.</p>
    <div class="pform-grid">
      <label class="label"><span>E-mail</span><input class="field" type="email" name="email" required maxlength="190"></label>
      <label class="label"><span>Mot de passe (8 caractères minimum)</span><input class="field" type="text" name="password" minlength="8" autocomplete="off"></label>
      <label class="label"><span>Rôle</span>
        <select class="select" name="role"><option value="moderator">Gérant</option><option value="admin">Super admin</option></select>
      </label>
    </div>
    <div><button class="btn btn-primary"><?= icon('plus') ?>Ajouter</button></div>
  </form>
</div>
<?php admin_footer(); ?>
