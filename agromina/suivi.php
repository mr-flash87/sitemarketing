<?php
require __DIR__ . '/includes/bootstrap.php';

$ref = strtoupper(str_input('ref', 40, $_GET));
$phone = str_input('phone', 25, $_GET);
$order = null;
$items = [];
$searched = $ref !== '' && $phone !== '';

if ($searched) {
    $digits = preg_replace('/\s+/', '', $phone) ?? '';
    $candidates = array_unique([$digits, str_starts_with($digits, '+') ? $digits : '+221' . $digits]);
    $in = implode(',', array_fill(0, count($candidates), '?'));
    $st = db()->prepare("SELECT * FROM orders WHERE ref = ? AND phone IN ($in) LIMIT 1");
    $st->execute(array_merge([$ref], array_values($candidates)));
    $order = $st->fetch() ?: null;
    if ($order) {
        $items = load_order_items([(int) $order['id']])[(int) $order['id']] ?? [];
    }
}

$steps = ['nouvelle', 'confirmee', 'en_preparation', 'livree'];

page_header([
    'title' => 'Suivre ma commande – AgroMina',
    'description' => "Suivez l'avancement de votre commande AgroMina avec votre référence et votre numéro de téléphone.",
]);
page_hero('Suivre ma commande', 'Indiquez la référence reçue à la validation et le numéro de téléphone utilisé.');
?>
<div class="container narrow">
  <form class="card track-form" method="get" action="<?= e(url('suivi')) ?>">
    <label class="label"><span>Référence de commande</span><input class="field" name="ref" value="<?= e($ref) ?>" placeholder="AM-XXXXXXXX" required style="text-transform:uppercase"></label>
    <label class="label"><span>Téléphone</span><input class="field" name="phone" value="<?= e($phone) ?>" placeholder="+221 ..." required></label>
    <button class="btn btn-accent">Suivre</button>
  </form>

  <?php if ($searched && !$order): ?>
    <p class="alert-danger">Aucune commande ne correspond à cette référence et ce numéro.</p>
  <?php endif; ?>

  <?php if ($order): $idx = array_search($order['status'], $steps, true); ?>
    <div class="card track-result">
      <div class="track-head">
        <h2>Commande <?= e($order['ref']) ?></h2>
        <span class="status-pill"><?= e(ORDER_STATUSES[$order['status']] ?? $order['status']) ?></span>
      </div>
      <p class="muted" style="margin-top:.25rem;font-size:.875rem">Passée le <?= e(fr_date($order['created_at'], 'numeric')) ?></p>

      <?php if ($order['status'] === 'annulee'): ?>
        <p class="alert-danger">Cette commande a été annulée. Contactez-nous si besoin.</p>
      <?php else: ?>
        <ol class="steps">
          <?php foreach ($steps as $i => $s): $done = $idx !== false && $idx >= $i; ?>
            <li class="<?= $done ? 'done' : '' ?>"><?= icon($done ? 'check-circle' : 'circle') ?> <?= e(ORDER_STATUSES[$s]) ?></li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>

      <div class="rows">
        <?php foreach ($items as $it): ?>
          <div><span><?= e($it['product_name']) ?> × <?= (int) $it['quantity'] ?></span><b><?= fcfa((int) $it['unit_price'] * (int) $it['quantity']) ?></b></div>
        <?php endforeach; ?>
        <div><span>Sous-total</span><span><?= fcfa((int) $order['subtotal']) ?></span></div>
        <div><span>Livraison</span><span><?= fcfa((int) $order['delivery_fee']) ?></span></div>
        <div class="bold"><span>Total</span><span><?= fcfa((int) $order['total']) ?></span></div>
      </div>
      <p class="muted" style="margin-top:1rem;font-size:.875rem">
        Livraison : <?= e($order['address']) ?><?= $order['city'] ? ', ' . e($order['city']) : '' ?> · Paiement : <?= e($order['payment_method']) ?>
      </p>
    </div>
  <?php endif; ?>

  <p class="muted" style="margin-top:1.5rem;font-size:.875rem">Besoin d'aide ? <a class="see-all" href="<?= e(url('contact')) ?>">Contactez-nous</a>.</p>
</div>
<?php page_footer(); ?>
