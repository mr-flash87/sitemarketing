<?php
require __DIR__ . '/includes/bootstrap.php';

$q = str_input('q', 100, $_GET);
$cat = str_input('cat', 40, $_GET);
$cat = isset(CATEGORIES[$cat]) ? $cat : '';
$flag = static fn(string $k) => !empty($_GET[$k]) && $_GET[$k] !== '0';
$promo = $flag('promo');
$nouveau = $flag('nouveau');
$best = $flag('best');
$dispo = $flag('dispo');
$max = (int) ($_GET['max'] ?? 0);
$max = $max > 0 && $max < 5000 ? $max : 0;
$tri = in_array($_GET['tri'] ?? '', ['asc', 'desc'], true) ? $_GET['tri'] : '';

$needle = normalize_text($q);
$list = array_values(array_filter(get_products(), static function ($p) use ($needle, $cat, $promo, $nouveau, $best, $dispo, $max) {
    if ($needle !== '' && !str_contains(normalize_text($p['name'] . ' ' . category_of($p['category'])['name']), $needle)) return false;
    if ($cat && $p['category'] !== $cat) return false;
    if ($promo && !$p['old_price']) return false;
    if ($nouveau && !$p['is_new']) return false;
    if ($best && !$p['best_seller']) return false;
    if ($dispo && $p['stock'] === 0) return false;
    if ($max && $p['price'] > $max) return false;
    return true;
}));
if ($tri === 'asc') usort($list, fn($a, $b) => $a['price'] <=> $b['price']);
if ($tri === 'desc') usort($list, fn($a, $b) => $b['price'] <=> $a['price']);

page_header([
    'title' => 'Boutique – AgroMina',
    'description' => 'Tous les produits AgroMina : céréales, jus, fruits transformés, produits halieutiques et artisanaux.',
    'active' => 'boutique',
]);
page_hero('Boutique', 'Les produits locaux transformés du Delta du Saloum.');
$n = count($list);
?>
<div class="container shop-layout">
  <form class="card filters" method="get" action="<?= e(url('boutique')) ?>" data-filters>
    <input type="hidden" name="tri" value="<?= e($tri) ?>">
    <div>
      <h3>Recherche</h3>
      <input class="field" name="q" value="<?= e($q) ?>" placeholder="Nom ou catégorie">
    </div>
    <div>
      <h3>Catégorie</h3>
      <select class="select" name="cat">
        <option value="">Toutes</option>
        <?php foreach (CATEGORIES as $slug => $c): ?>
          <option value="<?= e($slug) ?>" <?= $cat === $slug ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <h3>Prix maximum : <span data-max-label><?= $max ? e($max . ' FCFA') : 'tous' ?></span></h3>
      <input type="range" name="max" min="500" max="5000" step="500" value="<?= $max ?: 5000 ?>">
    </div>
    <div class="checks">
      <label class="check"><input type="checkbox" name="dispo" value="1" <?= $dispo ? 'checked' : '' ?>>En stock uniquement</label>
      <label class="check"><input type="checkbox" name="nouveau" value="1" <?= $nouveau ? 'checked' : '' ?>>Nouveautés</label>
      <label class="check"><input type="checkbox" name="promo" value="1" <?= $promo ? 'checked' : '' ?>>Promotions</label>
      <label class="check"><input type="checkbox" name="best" value="1" <?= $best ? 'checked' : '' ?>>Meilleures ventes</label>
    </div>
    <noscript><button class="btn btn-primary btn-sm">Filtrer</button></noscript>
  </form>
  <div>
    <div class="shop-bar">
      <span class="muted"><?= $n ?> produit<?= $n > 1 ? 's' : '' ?></span>
      <select class="select" style="width:auto" data-sort aria-label="Trier">
        <option value="">Pertinence</option>
        <option value="asc" <?= $tri === 'asc' ? 'selected' : '' ?>>Prix croissant</option>
        <option value="desc" <?= $tri === 'desc' ? 'selected' : '' ?>>Prix décroissant</option>
      </select>
    </div>
    <?php if ($list): ?>
      <div class="grid-products"><?php foreach ($list as $p) echo product_card($p); ?></div>
    <?php else: ?>
      <p class="card empty-box">Aucun produit ne correspond à votre recherche.</p>
    <?php endif; ?>
  </div>
</div>
<?php page_footer(); ?>
