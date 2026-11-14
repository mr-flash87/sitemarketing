<?php
require __DIR__ . '/includes/bootstrap.php';

page_header(['title' => 'Mes favoris – AgroMina', 'description' => 'Vos produits AgroMina favoris.', 'noindex' => true]);
page_hero('Mes favoris');
?>
<div class="container" style="margin-top:2rem" data-fav-page>
  <div class="grid-products md-4"><?php foreach (get_products() as $p) echo product_card($p, ['hidden' => 'hidden']); ?></div>
  <p class="muted" data-fav-empty>Aucun favori pour l'instant. <a class="see-all" href="<?= e(url('boutique')) ?>">Parcourir la boutique</a></p>
</div>
<?php page_footer(); ?>
