<?php
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');
    if ($id && isset(ORDER_STATUSES[$status])) {
        $pdo = db();
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT status FROM orders WHERE id = ? FOR UPDATE');
        $st->execute([$id]);
        $old = $st->fetchColumn();
        if ($old !== false && $old !== $status) {
            // Une commande annulée rend son stock ; la réactiver le reprend.
            if ($status === 'annulee' || $old === 'annulee') {
                $sign = $status === 'annulee' ? '+' : '-';
                $items = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ? AND product_id IS NOT NULL');
                $items->execute([$id]);
                $upd = $pdo->prepare("UPDATE products SET stock = GREATEST(0, CAST(stock AS SIGNED) $sign ?) WHERE id = ?");
                foreach ($items->fetchAll() as $it) {
                    $upd->execute([(int) $it['quantity'], (int) $it['product_id']]);
                }
            }
            $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $id]);
        }
        $pdo->commit();
        flash('success', 'Statut mis à jour');
    } else {
        flash('error', 'Changement de statut impossible.');
    }
    redirect(url('admin/commandes', ['statut' => $_POST['f_statut'] ?? '', 'q' => $_POST['f_q'] ?? '']) . '#o' . $id);
}

$filter = isset(ORDER_STATUSES[$_GET['statut'] ?? '']) ? $_GET['statut'] : '';
$q = str_input('q', 100, $_GET);

$sql = 'SELECT * FROM orders WHERE 1';
$args = [];
if ($filter) {
    $sql .= ' AND status = ?';
    $args[] = $filter;
}
if ($q !== '') {
    $sql .= ' AND (ref LIKE ? OR full_name LIKE ? OR phone LIKE ?)';
    $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
    array_push($args, $like, $like, $like);
}
$sql .= ' ORDER BY created_at DESC LIMIT 300';
$st = db()->prepare($sql);
$st->execute($args);
$orders = $st->fetchAll();
$items = load_order_items(array_map(fn($o) => (int) $o['id'], $orders));

$groups = [];
foreach ($orders as $o) {
    $groups[date('Y-m-d', strtotime($o['created_at'] . ' UTC'))][] = $o;
}
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
if (!isset($groups[$today]) && !$filter && $q === '') {
    $groups = [$today => []] + $groups;
}
$dayLabel = static function (string $k) use ($today, $yesterday) {
    $long = fr_date(strtotime($k . ' 12:00:00'), 'long');
    return $k === $today ? "Aujourd'hui · $long" : ($k === $yesterday ? "Hier · $long" : $long);
};

admin_header('Commandes', 'commandes');
admin_title('Commandes');
?>
<form class="toolbar" method="get">
  <?php if ($filter): ?><input type="hidden" name="statut" value="<?= e($filter) ?>"><?php endif; ?>
  <label class="a-search"><?= icon('search') ?><input name="q" value="<?= e($q) ?>" placeholder="Référence, nom, téléphone…"></label>
  <button class="btn btn-primary">Rechercher</button>
</form>
<div class="chips">
  <a class="chip <?= $filter === '' ? 'is-active' : '' ?>" href="<?= e(url('admin/commandes', ['q' => $q])) ?>">Toutes</a>
  <?php foreach (ORDER_STATUSES as $s => $label): ?>
    <a class="chip <?= $filter === $s ? 'is-active' : '' ?>" href="<?= e(url('admin/commandes', ['statut' => $s, 'q' => $q])) ?>"><span class="dot st-<?= e($s) ?>"></span><?= e($label) ?></a>
  <?php endforeach; ?>
</div>

<?php foreach ($groups as $k => $list): ?>
  <section class="day-group">
    <h2 class="day-title"><?= e($dayLabel($k)) ?></h2>
    <?php if (!$list): ?>
      <div class="panel empty-panel">Aucune commande aujourd'hui pour le moment. Les commandes des jours passés sont rangées ci-dessous.</div>
    <?php else: ?>
      <div class="orders-stack">
        <?php foreach ($list as $o): $its = $items[(int) $o['id']] ?? []; $n = array_sum(array_column($its, 'quantity')); ?>
          <details class="panel order" id="o<?= (int) $o['id'] ?>" <?= $q !== '' && count($orders) === 1 ? 'open' : '' ?>>
            <summary>
              <div style="min-width:0">
                <p class="nm"><?= e($o['full_name']) ?> <?= icon('chevron-down') ?></p>
                <p class="meta"><?= e($o['ref']) ?> · <?= e(fr_time($o['created_at'])) ?> · <?= (int) $n ?> article(s)</p>
              </div>
              <div class="r"><b><?= fcfa((int) $o['total']) ?></b><?= status_pill($o['status']) ?></div>
            </summary>
            <div class="order-body">
              <div class="contact-chips">
                <a href="tel:<?= e($o['phone']) ?>"><?= icon('phone') ?><?= e($o['phone']) ?></a>
                <a class="wa" href="https://wa.me/<?= e(preg_replace('/\D/', '', $o['phone'])) ?>" target="_blank" rel="noopener">WhatsApp</a>
              </div>
              <p class="muted"><?= e($o['address']) ?><?= $o['city'] ? ', ' . e($o['city']) : '' ?> · <?= e($o['payment_method']) ?><?= $o['email'] ? ' · ' . e($o['email']) : '' ?></p>
              <?php if ($o['note']): ?><p class="note-box">Note : <?= e($o['note']) ?></p><?php endif; ?>
              <div class="items-box">
                <?php foreach ($its as $it): ?>
                  <div><span><?= e($it['product_name']) ?> <span class="muted">× <?= (int) $it['quantity'] ?></span></span><span><?= fcfa((int) $it['unit_price'] * (int) $it['quantity']) ?></span></div>
                <?php endforeach; ?>
                <div class="muted-row"><span>Livraison</span><span><?= fcfa((int) $o['delivery_fee']) ?></span></div>
                <div class="tot"><span>Total</span><span><?= fcfa((int) $o['total']) ?></span></div>
              </div>
              <form method="post" class="status-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                <input type="hidden" name="f_statut" value="<?= e($filter) ?>">
                <input type="hidden" name="f_q" value="<?= e($q) ?>">
                <span class="dot st-<?= e($o['status']) ?>"></span>
                <span class="muted">Statut</span>
                <select name="status" data-autosubmit aria-label="Statut de <?= e($o['ref']) ?>">
                  <?php foreach (ORDER_STATUSES as $s => $label): ?>
                    <option value="<?= e($s) ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
                <noscript><button class="btn btn-primary btn-sm">OK</button></noscript>
              </form>
            </div>
          </details>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
<?php endforeach; ?>
<?php if (!$groups): ?><div class="panel empty-panel">Aucune commande trouvée.</div><?php endif; ?>
<?php admin_footer(); ?>
