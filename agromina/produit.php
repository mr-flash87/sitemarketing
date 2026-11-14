<?php
require __DIR__ . '/includes/bootstrap.php';

$slug = str_input('slug', 80, $_GET);
$p = $slug !== '' ? get_product_by_slug($slug) : null;

if (!$p) {
    http_response_code(404);
    page_header(['title' => 'Produit introuvable – AgroMina', 'noindex' => true]);
    echo '<div class="container" style="padding-block:6rem;text-align:center">Produit introuvable. <a class="see-all" href="'
        . e(url('boutique')) . '">Retour à la boutique</a></div>';
    page_footer();
    exit;
}

$related = array_slice(array_values(array_filter(get_products(), fn($x) => $x['category'] === $p['category'] && $x['slug'] !== $p['slug'])), 0, 4);
$cat = category_of($p['category']);
$out = $p['stock'] === 0;
$low = !$out && $p['stock'] < 5;
$disc = discount_pct($p);

page_header([
    'title' => $p['name'] . ' – AgroMina',
    'description' => $p['description'] ?: 'Produit local du Delta du Saloum.',
    'body_class' => 'body-has-buybar',
]);
?>
<div class="has-buybar">
  <div class="container">
    <nav class="breadcrumb" aria-label="Fil d'Ariane">
      <a href="<?= e(url('')) ?>">Accueil</a><?= icon('chevron-right') ?>
      <a href="<?= e(url('boutique', ['cat' => $p['category']])) ?>"><?= e($cat['name']) ?></a><?= icon('chevron-right') ?>
      <span><?= e($p['name']) ?></span>
    </nav>

    <div class="product">
      <div class="product-media">
        <img src="<?= e(product_image($p)) ?>" alt="<?= e($p['name']) ?>">
        <div class="pcard-tags">
          <?php if ($disc): ?><span class="tag tag-accent">−<?= $disc ?> %</span><?php endif; ?>
          <?php if ($p['is_new']): ?><span class="tag tag-primary">Nouveau</span><?php endif; ?>
          <?php if ($p['best_seller']): ?><span class="tag tag-card">Meilleure vente</span><?php endif; ?>
        </div>
      </div>

      <div style="min-width:0">
        <span class="cat-pill"><?= icon('leaf') ?><?= e($cat['name']) ?></span>
        <h1><?= e($p['name']) ?></h1>
        <?php if ($p['weight']): ?><p class="muted" style="margin-top:.25rem;font-size:.875rem"><?= e($p['weight']) ?></p><?php endif; ?>

        <div class="product-price">
          <strong><?= fcfa($p['price']) ?></strong>
          <?php if ($p['old_price']): ?><s><?= fcfa($p['old_price']) ?></s><?php endif; ?>
        </div>
        <p class="stock-line <?= $out ? 'is-out' : ($low ? 'is-low' : '') ?>">
          <?= $out ? 'Rupture de stock' : ($low ? 'Plus que ' . $p['stock'] . ' en stock' : 'En stock, prêt à être livré') ?>
        </p>

        <?php if ($p['description']): ?><p class="product-desc"><?= nl2br(e($p['description'])) ?></p><?php endif; ?>

        <div class="buy-row">
          <div class="qty" data-qty data-max="<?= max(1, $p['stock']) ?>" data-price="<?= $p['price'] ?>">
            <button type="button" aria-label="Moins"><?= icon('minus') ?></button><span id="qty-d">1</span><button type="button" data-inc aria-label="Plus"><?= icon('plus') ?></button>
          </div>
          <button type="button" class="btn btn-accent" data-add="<?= e($p['slug']) ?>" data-name="<?= e($p['name']) ?>" data-qty-from="#qty-d" <?= $out ? 'disabled' : '' ?>>
            <?= icon('cart') ?>Ajouter au panier · <span data-line-total><?= fcfa($p['price']) ?></span>
          </button>
          <button type="button" class="fav-big" data-fav="<?= e($p['slug']) ?>" aria-label="Favori"><?= icon('heart') ?></button>
        </div>

        <div class="perks">
          <div><?= icon('shield-check') ?><b>Inspection libre</b><small>Vérifiez avant de payer</small></div>
          <div><?= icon('truck') ?><b>Livraison rapide</b><small>Partout au Sénégal</small></div>
          <div><?= icon('rotate-ccw') ?><b>Échange 48 h</b><small>Satisfaction garantie</small></div>
        </div>

        <div class="made-in">
          <p><b>Fabriqué au Sénégal</b></p>
          <p>Produit local, transformé dans le Delta du Saloum par nos équipes et partenaires.</p>
        </div>
      </div>
    </div>

    <?php if ($related): ?>
      <section class="section">
        <h2 class="section-title" style="margin-bottom:1.5rem">Vous aimerez aussi</h2>
        <div class="grid-products md-4"><?php foreach ($related as $r) echo product_card($r); ?></div>
      </section>
    <?php endif; ?>
  </div>

  <div class="buybar">
    <div class="qty" data-qty data-max="<?= max(1, $p['stock']) ?>" data-price="<?= $p['price'] ?>">
      <button type="button" aria-label="Moins"><?= icon('minus') ?></button><span id="qty-m">1</span><button type="button" data-inc aria-label="Plus"><?= icon('plus') ?></button>
    </div>
    <button type="button" class="btn btn-accent" data-add="<?= e($p['slug']) ?>" data-name="<?= e($p['name']) ?>" data-qty-from="#qty-m" <?= $out ? 'disabled' : '' ?>>
      <?= icon('cart') ?><span><?= $out ? 'Indisponible' : 'Ajouter · <span data-line-total>' . fcfa($p['price']) . '</span>' ?></span>
    </button>
  </div>
</div>
<?php page_footer(); ?>
