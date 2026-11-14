<?php
require __DIR__ . '/includes/bootstrap.php';
http_response_code(404);
page_header(['title' => 'Page introuvable – AgroMina', 'noindex' => true]);
?>
<div class="container empty-cart">
  <div>
    <span class="round-icon"><?= icon('leaf') ?></span>
    <h1>Page introuvable</h1>
    <p>La page demandée n'existe pas ou a été déplacée.</p>
    <a href="<?= e(url('')) ?>" class="btn btn-accent">Retour à l'accueil <?= icon('arrow-right') ?></a>
  </div>
</div>
<?php page_footer(); ?>
