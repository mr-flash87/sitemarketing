<?php
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete') {
        db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
        flash('success', 'Message supprimé.');
    } elseif (($_POST['action'] ?? '') === 'read_all') {
        db()->exec('UPDATE contact_messages SET is_read = 1');
    }
    redirect(url('admin/messages'));
}

if (isset($_GET['export']) && $_GET['export'] === 'newsletter') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="newsletter-agromina.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['email', 'inscrit_le'], ';');
    foreach (db()->query('SELECT email, created_at FROM newsletter ORDER BY created_at DESC') as $r) {
        fputcsv($out, [$r['email'], $r['created_at']], ';');
    }
    exit;
}

$messages = db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 200')->fetchAll();
$subs = db()->query('SELECT email, created_at FROM newsletter ORDER BY created_at DESC LIMIT 200')->fetchAll();
$subCount = (int) db()->query('SELECT COUNT(*) FROM newsletter')->fetchColumn();

admin_header('Messages', 'messages');
admin_title('Messages', 'Formulaire de contact et inscriptions à la newsletter');
?>
<div class="stack">
  <div class="panel">
    <div class="list-head">
      <b>Messages reçus (<?= count($messages) ?>)</b>
      <?php if ($messages): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read_all"><button class="see-all">Tout marquer comme lu</button></form>
      <?php endif; ?>
    </div>
    <?php foreach ($messages as $m): ?>
      <div class="msg <?= $m['is_read'] ? '' : 'unread' ?>">
        <div class="msg-head">
          <b><?= e($m['name']) ?> · <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></b>
          <span class="muted"><?= e(fr_date($m['created_at'])) ?> · <?= e(fr_time($m['created_at'])) ?></span>
        </div>
        <p><?= e($m['message']) ?></p>
        <form method="post" data-confirm="Supprimer ce message ?" style="margin-top:.5rem">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
          <button class="icon-btn danger" aria-label="Supprimer"><?= icon('trash') ?></button>
        </form>
      </div>
    <?php endforeach; ?>
    <?php if (!$messages): ?><p class="empty-panel">Aucun message pour le moment.</p><?php endif; ?>
  </div>

  <div class="panel">
    <div class="list-head">
      <b>Newsletter (<?= $subCount ?> inscrit<?= $subCount > 1 ? 's' : '' ?>)</b>
      <?php if ($subCount): ?><a href="<?= e(url('admin/messages', ['export' => 'newsletter'])) ?>">Exporter (CSV)</a><?php endif; ?>
    </div>
    <?php foreach ($subs as $s): ?>
      <div class="team-row"><div><p><?= e($s['email']) ?></p><small>Inscrit le <?= e(fr_date($s['created_at'], 'numeric')) ?></small></div></div>
    <?php endforeach; ?>
    <?php if (!$subs): ?><p class="empty-panel">Aucune inscription pour le moment.</p><?php endif; ?>
  </div>
</div>
<?php
// Les messages affichés sont considérés comme lus.
db()->exec('UPDATE contact_messages SET is_read = 1 WHERE is_read = 0');
admin_footer();
