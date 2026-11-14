<?php
require __DIR__ . '/includes/bootstrap.php';

$products = get_products();
$best = array_slice(array_values(array_filter($products, fn($p) => $p['best_seller'])), 0, 4);
$promo = array_slice(array_values(array_filter($products, fn($p) => $p['old_price'])), 0, 2);
$news = array_slice(array_values(array_filter($products, fn($p) => $p['is_new'])), 0, 4);

page_header(['active' => '']);
?>
<section class="container">
  <div class="hero">
    <img src="<?= e(img('hero.jpg')) ?>" alt="Produits AgroMina : thiakry, bissap, bouye, confiture de mangue" width="1920" height="832">
    <div class="hero-fade"></div>
    <div class="hero-content">
      <div class="hero-inner">
        <p class="eyebrow">Delta du Saloum · Sénégal</p>
        <h1>Le meilleur du terroir local, transformé avec passion.</h1>
        <p class="hero-text">Découvrez une sélection de produits locaux de qualité, issus du savoir-faire et des richesses du Delta du Saloum.</p>
        <div class="hero-cta">
          <a href="<?= e(url('boutique')) ?>" class="btn btn-accent">Découvrir nos produits</a>
          <a href="<?= e(url('categories')) ?>" class="btn btn-primary">Explorer la boutique</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="container section">
  <div class="section-head"><h2 class="section-title">Nos catégories</h2><a class="see-all" href="<?= e(url('boutique')) ?>">Tout voir <?= icon('arrow-right') ?></a></div>
  <div class="cat-tiles">
    <?php foreach (CATEGORIES as $slug => $c): ?>
      <a class="cat-tile" href="<?= e(url('boutique', ['cat' => $slug])) ?>">
        <img src="<?= e(img($c['image'])) ?>" alt="<?= e($c['name']) ?>" loading="lazy">
        <span><?= e($c['name']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<?php if ($best): ?>
<section class="container section">
  <div class="section-head"><h2 class="section-title">Nos meilleures ventes</h2><a class="see-all" href="<?= e(url('boutique', ['best' => 1])) ?>">Tout voir <?= icon('arrow-right') ?></a></div>
  <div class="grid-products cols-4"><?php foreach ($best as $p) echo product_card($p); ?></div>
</section>
<?php endif; ?>

<section class="container section">
  <div class="promo-band pattern-band">
    <div>
      <span class="pill">OFFRES SPÉCIALES</span>
      <h2>Jusqu'à −20 % sur une sélection du terroir</h2>
      <p>Fonio, bouye, mangue séchée… profitez de nos promotions de la semaine.</p>
      <a href="<?= e(url('boutique', ['promo' => 1])) ?>" class="btn btn-accent">Voir les promotions</a>
    </div>
    <div class="promo-cards"><?php foreach ($promo as $p) echo product_card($p); ?></div>
  </div>
</section>

<?php if ($news): ?>
<section class="container section">
  <div class="section-head"><h2 class="section-title">Nos nouveautés</h2><a class="see-all" href="<?= e(url('boutique', ['nouveau' => 1])) ?>">Tout voir <?= icon('arrow-right') ?></a></div>
  <div class="grid-products cols-4"><?php foreach ($news as $p) echo product_card($p); ?></div>
</section>
<?php endif; ?>

<section class="container section">
  <div class="section-head"><h2 class="section-title">Pourquoi choisir AgroMina ?</h2></div>
  <div class="why-grid">
    <?php foreach ([['sprout', 'Valorisation des produits locaux'], ['hand-heart', 'Transformation et savoir-faire'], ['award', 'Qualité des produits'], ['users', "Soutien à l'économie locale"], ['leaf', 'Authenticité et proximité']] as [$ic, $t]): ?>
      <div class="card why"><div class="why-icon"><?= icon($ic) ?></div><p><?= e($t) ?></p></div>
    <?php endforeach; ?>
  </div>
</section>

<section class="container section">
  <div class="terroir">
    <img src="<?= e(img('terroir.jpg')) ?>" alt="Mangroves du Delta du Saloum au coucher du soleil" loading="lazy">
    <div class="terroir-text">
      <p class="eyebrow">Notre terroir</p>
      <h2>Le Delta du Saloum, une terre généreuse</h2>
      <p>Entre mangroves, bolongs et terres agricoles, le Delta du Saloum offre des céréales, des fruits, des produits de la mer et un savoir-faire artisanal transmis de génération en génération.</p>
      <p>AgroMina travaille avec les producteurs et les femmes transformatrices de la région pour faire connaître ces richesses partout au Sénégal.</p>
      <a href="<?= e(url('a-propos')) ?>" class="see-all">Notre histoire <?= icon('arrow-right') ?></a>
    </div>
  </div>
</section>

<section class="container section">
  <div class="section-head"><h2 class="section-title">Ils nous font confiance</h2></div>
  <div class="testimonials">
    <?php foreach ([['Aminata D.', 'Dakar', 'Le thiakry est exactement comme celui de ma grand-mère. Livraison rapide !'], ['Moussa S.', 'Kaolack', 'Le jus de bouye est délicieux et bien emballé. Je recommande.'], ['Fatou N.', 'Thiès', 'Des produits authentiques et de qualité, enfin en ligne.']] as [$n, $v, $t]): ?>
      <figure class="card testimonial">
        <div class="stars" aria-label="5 étoiles sur 5">★★★★★</div>
        <blockquote>« <?= e($t) ?> »</blockquote>
        <figcaption><?= e($n) ?> <span>· <?= e($v) ?></span></figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
</section>

<section class="container section">
  <div class="section-head"><h2 class="section-title">Blog et actualités</h2></div>
  <div class="posts">
    <?php foreach (BLOG_POSTS as $post): ?>
      <a href="<?= e(url('blog')) ?>#<?= e($post['slug']) ?>" class="card post">
        <img src="<?= e(img($post['image'])) ?>" alt="" loading="lazy">
        <div class="post-body"><time><?= e($post['date']) ?></time><h3><?= e($post['title']) ?></h3></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="container section">
  <div class="newsletter">
    <h2>Recevez nos nouveautés et promotions</h2>
    <form data-newsletter novalidate>
      <input type="email" name="email" placeholder="Votre adresse e-mail" aria-label="Votre adresse e-mail" required>
      <button class="btn btn-accent">S'inscrire</button>
    </form>
  </div>
</section>
<?php page_footer(); ?>
