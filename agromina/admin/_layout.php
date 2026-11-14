<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

$ADMIN = require_staff();

const ADMIN_TABS = [
    'index' => ['Accueil', 'compass', 'admin/'],
    'commandes' => ['Commandes', 'bag', 'admin/commandes'],
    'produits' => ['Produits', 'package', 'admin/produits'],
    'clients' => ['Clients', 'users', 'admin/clients'],
];

function admin_header(string $title, string $active): void
{
    global $ADMIN;
    $newOrders = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status = 'nouvelle'")->fetchColumn();
    $unread = (int) db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
    $initial = strtoupper(mb_substr($ADMIN['email'], 0, 1)) ?: 'A';
    ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> – AgroMina Admin</title>
  <meta name="robots" content="noindex">
  <meta name="theme-color" content="#126333">
  <link rel="icon" type="image/png" href="<?= e(img('favicon.png')) ?>">
  <link rel="apple-touch-icon" href="<?= e(img('admin-icon-180.png')) ?>">
  <link rel="manifest" href="<?= e(url('admin/manifest.webmanifest')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  <script>try { if (localStorage.getItem("am-admin-theme") === "dark") document.documentElement.classList.add("dark"); } catch (e) {}</script>
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="admin-body">
<header class="a-top">
  <div class="a-top-row">
    <a href="<?= e(url('admin/')) ?>" class="a-brand"><img src="<?= e(img('logo.jpeg')) ?>" alt="AgroMina"><span>Admin</span></a>
    <nav class="a-nav">
      <?php foreach (ADMIN_TABS as $k => [$label, , $path]): ?>
        <a href="<?= e(url($path)) ?>" class="<?= $active === $k ? 'is-active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
      <a href="<?= e(url('admin/messages')) ?>" class="<?= $active === 'messages' ? 'is-active' : '' ?>">Messages</a>
      <?php if (is_super($ADMIN)): ?><a href="<?= e(url('admin/equipe')) ?>" class="<?= $active === 'equipe' ? 'is-active' : '' ?>">Équipe</a><?php endif; ?>
    </nav>
    <div class="a-tools">
      <button type="button" class="a-round" data-theme-toggle aria-label="Changer de thème"><?= icon('moon') ?></button>
      <a href="<?= e(url('admin/commandes', ['statut' => 'nouvelle'])) ?>" class="a-round" aria-label="Nouvelles commandes"><?= icon('bell') ?><?php if ($newOrders): ?><span class="badge"><?= $newOrders ?></span><?php endif; ?></a>
      <button type="button" class="a-avatar" data-sheet-open aria-label="Mon compte"><?= e($initial) ?></button>
    </div>
  </div>
</header>
<?php foreach (take_flashes() as $f): ?>
  <div class="flash flash-<?= e($f['type']) ?>" data-flash><?= e($f['message']) ?></div>
<?php endforeach; ?>
<main class="a-main">
<?php
    $GLOBALS['__admin_active'] = $active;
    $GLOBALS['__admin_unread'] = $unread;
}

function admin_footer(): void
{
    global $ADMIN;
    $active = $GLOBALS['__admin_active'] ?? '';
    $unread = $GLOBALS['__admin_unread'] ?? 0;
    ?>
</main>
<nav class="a-bottom">
  <?php foreach (ADMIN_TABS as $k => [$label, $ic, $path]): ?>
    <a href="<?= e(url($path)) ?>" class="<?= $active === $k ? 'is-active' : '' ?>"><?= icon($ic) ?><?= e($label) ?></a>
  <?php endforeach; ?>
  <button type="button" data-sheet-open class="<?= in_array($active, ['equipe', 'messages'], true) ? 'is-active' : '' ?>"><?= icon('more') ?>Plus</button>
</nav>

<div class="sheet-backdrop" data-sheet hidden>
  <div class="sheet" role="dialog" aria-label="Plus">
    <div class="sheet-head">
      <div><b>Plus</b><p><?= e($ADMIN['email']) ?></p></div>
      <button type="button" class="icon-btn" data-sheet-close aria-label="Fermer"><?= icon('x') ?></button>
    </div>
    <div class="sheet-grid">
      <a class="sheet-item" href="<?= e(url('admin/messages')) ?>"><?= icon('inbox') ?>Messages<?= $unread ? ' (' . (int) $unread . ')' : '' ?></a>
      <?php if (is_super($ADMIN)): ?><a class="sheet-item" href="<?= e(url('admin/equipe')) ?>"><?= icon('shield-check') ?>Équipe</a><?php endif; ?>
      <button type="button" class="sheet-item" data-theme-toggle><?= icon('moon') ?>Mode sombre</button>
      <a class="sheet-item" href="<?= e(url('')) ?>" target="_blank"><?= icon('store') ?>Voir la boutique</a>
      <a class="sheet-item" href="<?= e(url('compte')) ?>"><?= icon('user') ?>Mon compte</a>
    </div>
    <form method="post" action="<?= e(url('deconnexion')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn-danger"><?= icon('log-out') ?> Se déconnecter</button>
    </form>
  </div>
</div>
<script>
(function () {
  var root = document.documentElement;
  document.querySelectorAll("[data-theme-toggle]").forEach(function (b) {
    b.addEventListener("click", function () {
      var dark = root.classList.toggle("dark");
      try { localStorage.setItem("am-admin-theme", dark ? "dark" : "light"); } catch (e) {}
    });
  });
  var sheet = document.querySelector("[data-sheet]");
  document.querySelectorAll("[data-sheet-open]").forEach(function (b) { b.addEventListener("click", function () { sheet.hidden = false; }); });
  sheet.addEventListener("click", function (e) { if (e.target === sheet || e.target.closest("[data-sheet-close]")) sheet.hidden = true; });
  document.querySelectorAll("[data-confirm]").forEach(function (f) {
    f.addEventListener("submit", function (e) { if (!confirm(f.getAttribute("data-confirm"))) e.preventDefault(); });
  });
  document.querySelectorAll("[data-autosubmit]").forEach(function (el) { el.addEventListener("change", function () { el.form.submit(); }); });
  document.querySelectorAll("[data-flash]").forEach(function (f) { setTimeout(function () { f.remove(); }, 5000); });
})();
</script>
</body>
</html>
<?php
}

function admin_title(string $title, ?string $subtitle = null, string $action = ''): void
{
    echo '<div class="a-title"><div><h1>' . e($title) . '</h1>' . ($subtitle ? '<p>' . e($subtitle) . '</p>' : '') . '</div>' . $action . '</div>';
}

function status_pill(string $status): string
{
    return '<span class="pill pill-' . e($status) . '">' . e(ORDER_STATUSES[$status] ?? $status) . '</span>';
}

function delta(int|float $now, int|float $prev): string
{
    $pct = $prev == 0 ? ($now > 0 ? 100 : 0) : (int) round(($now - $prev) / $prev * 100);
    return '<span class="delta' . ($pct < 0 ? ' is-down' : '') . '">' . ($pct > 0 ? '+' : '') . $pct . '%</span>';
}
