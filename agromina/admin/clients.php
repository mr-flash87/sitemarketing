<?php
require __DIR__ . '/_layout.php';

$q = str_input('q', 100, $_GET);
$sql = 'SELECT * FROM customers';
$args = [];
if ($q !== '') {
    $sql .= ' WHERE full_name LIKE ? OR phone LIKE ? OR city LIKE ?';
    $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
    $args = [$like, $like, $like];
}
$st = db()->prepare($sql . ' ORDER BY order_count DESC, updated_at DESC LIMIT 500');
$st->execute($args);
$list = $st->fetchAll();
$total = (int) db()->query('SELECT COUNT(*) FROM customers')->fetchColumn();

$initials = static function (string $n): string {
    $words = array_slice(array_filter(preg_split('/\s+/', trim($n)) ?: []), 0, 2);
    return $words ? implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)), $words)) : '?';
};

admin_header('Clients', 'clients');
admin_title('Clients', $total . ' fiche(s) · créées automatiquement à la première commande');
?>
<form class="toolbar" method="get">
  <label class="a-search"><?= icon('search') ?><input name="q" value="<?= e($q) ?>" placeholder="Nom, téléphone, ville…"></label>
</form>
<div class="cust-grid">
  <?php foreach ($list as $c): ?>
    <div class="panel cust">
      <div class="cust-top">
        <span class="initials"><?= e($initials($c['full_name'])) ?></span>
        <div><p><?= e($c['full_name'] ?: '—') ?></p><small><?= icon('phone') ?><a href="tel:<?= e($c['phone']) ?>"><?= e($c['phone']) ?></a></small></div>
        <a class="cust-count" href="<?= e(url('admin/commandes', ['q' => $c['phone']])) ?>"><?= (int) $c['order_count'] ?> cmd</a>
      </div>
      <p class="cust-addr"><?= icon('map-pin') ?><span><?= e(implode(', ', array_filter([$c['address'], $c['city']])) ?: 'Adresse non renseignée') ?></span></p>
    </div>
  <?php endforeach; ?>
</div>
<?php if (!$list): ?><div class="panel empty-panel">Aucun client trouvé.</div><?php endif; ?>
<?php admin_footer(); ?>
