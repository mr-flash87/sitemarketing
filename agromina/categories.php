<?php
require __DIR__ . '/includes/bootstrap.php';

$counts = [];
foreach (get_products() as $p) {
    $counts[$p['category']] = ($counts[$p['category']] ?? 0) + 1;
}

page_header([
    'title' => 'Catégories – AgroMina',
    'description' => 'Parcourez les catégories de produits AgroMina : céréales, jus, fruits, produits halieutiques et artisanaux.',
    'active' => 'categories',
]);
page_hero('Nos catégories', 'Six familles de produits issus du Delta du Saloum.');
?>
<div class="container cat-cards">
  <?php foreach (CATEGORIES as $slug => $c): $n = $counts[$slug] ?? 0; ?>
    <a class="card cat-card" href="<?= e(url('boutique', ['cat' => $slug])) ?>">
      <img src="<?= e(img($c['image'])) ?>" alt="<?= e($c['name']) ?>" loading="lazy">
      <div><h2><?= e($c['name']) ?></h2><p><?= $n ?> produit<?= $n > 1 ? 's' : '' ?></p></div>
    </a>
  <?php endforeach; ?>
</div>
<?php page_footer(); ?>
