<?php
require __DIR__ . '/includes/bootstrap.php';
page_header(['title' => 'Blog – AgroMina', 'description' => 'Actualités, produits locaux et transformation agroalimentaire.', 'active' => 'blog']);
page_hero('Blog et actualités', "Produits locaux, transformation et vie d'AgroMina.");
?>
<div class="container posts" style="margin-top:2rem">
  <?php foreach (BLOG_POSTS as $post): ?>
    <article class="card post" id="<?= e($post['slug']) ?>">
      <img src="<?= e(img($post['image'])) ?>" alt="" loading="lazy">
      <div class="post-body"><time><?= e($post['date']) ?></time><h2><?= e($post['title']) ?></h2><p><?= e($post['excerpt']) ?></p></div>
    </article>
  <?php endforeach; ?>
</div>
<?php page_footer(); ?>
