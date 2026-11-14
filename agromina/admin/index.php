<?php
require __DIR__ . '/_layout.php';

$days = (int) ($_GET['p'] ?? 30);
$days = in_array($days, [7, 30, 90], true) ? $days : 30;
$DAY = 86400;
$end = strtotime('tomorrow') ; // fin de la journée en cours
$start = $end - $days * $DAY;
$prevStart = $start - $days * $DAY;

$orders = db()->query('SELECT id, ref, full_name, status, total, created_at FROM orders ORDER BY created_at DESC LIMIT 2000')->fetchAll();
foreach ($orders as &$o) {
    $o['ts'] = strtotime($o['created_at'] . ' UTC');
    $o['total'] = (int) $o['total'];
}
unset($o);

$inRange = static fn(int $a, int $b) => array_values(array_filter($orders, fn($o) => $o['ts'] >= $a && $o['ts'] < $b));
$valid = static fn(array $l) => array_values(array_filter($l, fn($o) => $o['status'] !== 'annulee'));
$rev = static fn(array $l) => array_sum(array_column($valid($l), 'total'));
$avg = static fn(array $l) => count($valid($l)) ? (int) round($rev($l) / count($valid($l))) : 0;
$delivered = static fn(array $l) => count(array_filter($l, fn($o) => $o['status'] === 'livree'));

$cur = $inRange($start, $end);
$prev = $inRange($prevStart, $start);

$buckets = $days === 7 ? 7 : ($days === 30 ? 15 : 13);
$size = $days * $DAY / $buckets;
$series = [];
for ($i = 0; $i < $buckets; $i++) {
    $a = (int) ($start + $i * $size);
    $series[] = $rev($inRange($a, (int) ($start + ($i + 1) * $size)));
}
$niceMax = max(15000, (int) ceil(max(1, ...$series) / 5000) * 5000);

$lowStock = (int) db()->query('SELECT COUNT(*) FROM products WHERE stock < 5')->fetchColumn();

admin_header('Tableau de bord', 'index');
admin_title('Tableau de bord');
?>
<div class="period">
  <?= icon('sliders') ?><b>Période</b>
  <?php foreach ([7, 30, 90] as $p): ?>
    <a href="<?= e(url('admin/', ['p' => $p])) ?>" class="<?= $days === $p ? 'is-active' : '' ?>"><?= $p ?> jours</a>
  <?php endforeach; ?>
  <span class="period-range"><?= e(fr_date($start, 'day')) ?> – <?= e(fr_date($end - 1, 'day')) ?> <?= date('Y') ?></span>
</div>

<div class="kpis">
  <?php foreach ([
      ["Chiffre d'affaires", fcfa($rev($cur)), $rev($cur), $rev($prev), fcfa($rev($prev)), 'eye'],
      ['Panier moyen', fcfa($avg($cur)), $avg($cur), $avg($prev), fcfa($avg($prev)), 'users'],
      ['Commandes livrées', (string) $delivered($cur), $delivered($cur), $delivered($prev), (string) $delivered($prev), 'package-check'],
      ['Commandes reçues', (string) count($cur), count($cur), count($prev), (string) count($prev), 'bag'],
  ] as [$label, $value, $n, $pv, $pl, $ic]): ?>
    <div class="panel kpi">
      <div class="kpi-top"><p><?= e($label) ?></p><span><?= icon($ic) ?></span></div>
      <div class="kpi-val"><b><?= e($value) ?></b><?= delta($n, $pv) ?></div>
      <small>Période préc. : <?= e($pl) ?></small>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($lowStock > 0): ?>
  <a class="low-stock" href="<?= e(url('admin/produits', ['vue' => 'stock'])) ?>">
    <?= icon('alert') ?><span><b><?= $lowStock ?> produit<?= $lowStock > 1 ? 's' : '' ?></b> en stock faible</span><em>Voir</em>
  </a>
<?php endif; ?>

<div class="dash-grid">
  <div class="panel panel-pad">
    <p class="muted" style="font-weight:600">Chiffre d'affaires total</p>
    <div class="big-number"><b><?= fcfa($rev($cur)) ?></b><?= delta($rev($cur), $rev($prev)) ?><span class="muted" style="font-size:.875rem">vs période préc.</span></div>
    <div class="chart">
      <?php foreach ([1, 2 / 3, 1 / 3, 0] as $f): ?>
        <div class="chart-line" style="bottom:<?= $f * 100 ?>%"><span><?= e(compact_number((int) round($niceMax * $f))) ?></span><i></i></div>
      <?php endforeach; ?>
      <div class="chart-bars">
        <?php foreach ($series as $v): ?>
          <div><b style="height:<?= round($v / $niceMax * 100, 2) ?>%;<?= $v ? 'min-height:4px' : '' ?>"></b><?php if ($v): ?><em><?= fcfa($v) ?></em><?php endif; ?></div>
        <?php endforeach; ?>
      </div>
      <?php if ($rev($cur) === 0): ?><p class="chart-empty">Vos ventes s'afficheront ici dès votre première commande.</p><?php endif; ?>
    </div>
    <div class="chart-axis"><span><?= e(fr_date($start, 'day')) ?></span><span>Aujourd'hui</span></div>
  </div>

  <div class="panel panel-pad">
    <p style="font-weight:600">Statuts des commandes</p>
    <div class="statusbar">
      <?php foreach (ORDER_STATUSES as $st => $label): $n = count(array_filter($cur, fn($o) => $o['status'] === $st)); if ($n): ?>
        <div class="st-<?= e($st) ?>" style="width:<?= round($n / max(1, count($cur)) * 100, 2) ?>%"></div>
      <?php endif; endforeach; ?>
    </div>
    <ul class="status-list">
      <?php foreach (ORDER_STATUSES as $st => $label): ?>
        <li><span class="dot st-<?= e($st) ?>"></span><span><?= e($label) ?></span><b><?= count(array_filter($cur, fn($o) => $o['status'] === $st)) ?></b></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<div class="panel" style="margin-top:1.25rem">
  <div class="list-head"><b>Dernières commandes</b><a href="<?= e(url('admin/commandes')) ?>">Tout voir</a></div>
  <div class="mini-orders">
    <?php foreach (array_slice($orders, 0, 5) as $o): ?>
      <a class="mini-order" href="<?= e(url('admin/commandes', ['q' => $o['ref']])) ?>">
        <div><p class="nm"><?= e($o['full_name']) ?></p><p class="meta"><?= e($o['ref']) ?> · <?= e(fr_date($o['created_at'], 'numeric')) ?></p></div>
        <div class="r"><b><?= fcfa($o['total']) ?></b><?= status_pill($o['status']) ?></div>
      </a>
    <?php endforeach; ?>
    <?php if (!$orders): ?><p class="empty-panel">Aucune commande pour le moment.</p><?php endif; ?>
  </div>
</div>
<?php admin_footer(); ?>
