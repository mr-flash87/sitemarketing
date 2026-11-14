<?php
require __DIR__ . '/_layout.php';

const UPLOAD_DIR = 'uploads/products';
const MAX_UPLOAD = 5 * 1024 * 1024;

/** Enregistre une photo envoyée et renvoie son chemin relatif, ou lève une erreur lisible. */
function store_upload(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("L'envoi de la photo a échoué (fichier trop lourd ?).");
    }
    if ($file['size'] > MAX_UPLOAD) {
        throw new RuntimeException('La photo dépasse 5 Mo.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Format non accepté : utilisez une photo JPG, PNG ou WebP.');
    }
    $dir = APP_ROOT . '/' . UPLOAD_DIR;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException("Le dossier d'images n'est pas accessible en écriture.");
    }
    $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
        throw new RuntimeException("Impossible d'enregistrer la photo.");
    }
    return UPLOAD_DIR . '/' . $name;
}

function delete_local_image(?string $path): void
{
    if ($path && str_starts_with($path, UPLOAD_DIR . '/') && !str_contains($path, '..')) {
        $count = db()->prepare('SELECT COUNT(*) FROM products WHERE image_url = ?');
        $count->execute([$path]);
        if ((int) $count->fetchColumn() === 0) {
            @unlink(APP_ROOT . '/' . $path);
        }
    }
}

function find_product(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ? normalize_product($r) : null;
}

$draft = null;
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $pdo = db();

    if ($action === 'toggle' && ($p = find_product($id))) {
        $pdo->prepare('UPDATE products SET is_active = ? WHERE id = ?')->execute([$p['is_active'] ? 0 : 1, $id]);
        flash('success', $p['is_active'] ? 'Produit masqué de la boutique' : 'Produit publié');
        redirect(url('admin/produits') . '#p' . $id);
    }

    if ($action === 'duplicate' && ($p = find_product($id))) {
        $slug = substr($p['slug'], 0, 64) . '-copie-' . substr(bin2hex(random_bytes(2)), 0, 4);
        $pdo->prepare(
            'INSERT INTO products (slug, name, category, price, old_price, stock, weight, description, is_new, best_seller, is_active, image_url)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)'
        )->execute([$slug, mb_substr($p['name'] . ' (copie)', 0, 120), $p['category'], $p['price'], $p['old_price'], $p['stock'], $p['weight'], $p['description'], (int) $p['is_new'], (int) $p['best_seller'], $p['image_url']]);
        flash('success', 'Produit dupliqué (masqué tant que vous ne le publiez pas)');
        redirect(url('admin/produits', ['edit' => $pdo->lastInsertId()]));
    }

    if ($action === 'delete' && ($p = find_product($id))) {
        $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        delete_local_image($p['image_url']);
        flash('success', 'Produit supprimé');
        redirect(url('admin/produits'));
    }

    if ($action === 'save') {
        $existing = $id ? find_product($id) : null;
        $name = str_input('name', 120);
        $slug = slugify(str_input('slug', 80) ?: $name);
        $category = str_input('category', 40);
        $price = (int) ($_POST['price'] ?? -1);
        $oldRaw = trim((string) ($_POST['old_price'] ?? ''));
        $oldPrice = $oldRaw === '' ? null : (int) $oldRaw;
        $stock = (int) ($_POST['stock'] ?? -1);
        $image = $existing['image_url'] ?? null;

        $draft = [
            'id' => $id ?: null, 'name' => $name, 'slug' => $slug, 'category' => $category, 'price' => $price,
            'old_price' => $oldPrice, 'stock' => $stock, 'weight' => str_input('weight', 40),
            'description' => str_input('description', 2000), 'is_new' => !empty($_POST['is_new']),
            'best_seller' => !empty($_POST['best_seller']), 'is_active' => !empty($_POST['is_active']), 'image_url' => $image,
        ];

        if (mb_strlen($name) < 2) $formError = 'Nom trop court.';
        elseif (strlen($slug) < 2) $formError = 'Le slug est trop court.';
        elseif (!isset(CATEGORIES[$category])) $formError = 'Catégorie invalide.';
        elseif ($price < 0 || $price > 100_000_000) $formError = 'Prix invalide.';
        elseif ($oldPrice !== null && ($oldPrice < 0 || $oldPrice > 100_000_000)) $formError = 'Prix barré invalide.';
        elseif ($stock < 0 || $stock > 1_000_000) $formError = 'Stock invalide.';

        if (!$formError) {
            $dupe = $pdo->prepare('SELECT id FROM products WHERE slug = ? AND id <> ?');
            $dupe->execute([$slug, $id]);
            if ($dupe->fetchColumn()) $formError = 'Ce slug est déjà utilisé par un autre produit.';
        }

        if (!$formError) {
            try {
                $uploaded = store_upload($_FILES['image'] ?? []);
                if ($uploaded) {
                    $image = $uploaded;
                } elseif (!empty($_POST['remove_image'])) {
                    $image = null;
                }
            } catch (RuntimeException $e) {
                $formError = $e->getMessage();
            }
        }

        if (!$formError) {
            $vals = [$slug, $name, $category, $price, $oldPrice, $stock, $draft['weight'], $draft['description'],
                (int) $draft['is_new'], (int) $draft['best_seller'], (int) $draft['is_active'], $image];
            if ($existing) {
                $pdo->prepare('UPDATE products SET slug=?, name=?, category=?, price=?, old_price=?, stock=?, weight=?, description=?, is_new=?, best_seller=?, is_active=?, image_url=? WHERE id=?')
                    ->execute([...$vals, $id]);
                if ($existing['image_url'] !== $image) delete_local_image($existing['image_url']);
            } else {
                $pdo->prepare('INSERT INTO products (slug, name, category, price, old_price, stock, weight, description, is_new, best_seller, is_active, image_url) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute($vals);
                $id = (int) $pdo->lastInsertId();
            }
            flash('success', 'Produit enregistré');
            redirect(url('admin/produits') . '#p' . $id);
        }
    }
}

if (!$draft) {
    if (isset($_GET['edit']) && ($p = find_product((int) $_GET['edit']))) {
        $draft = $p;
    } elseif (isset($_GET['nouveau'])) {
        $draft = ['id' => null, 'name' => '', 'slug' => '', 'category' => 'cereales', 'price' => '', 'old_price' => null, 'stock' => 0,
            'weight' => '', 'description' => '', 'is_new' => false, 'best_seller' => false, 'is_active' => true, 'image_url' => null];
    }
}

$all = get_products(false);
$q = str_input('q', 100, $_GET);
$vue = in_array($_GET['vue'] ?? '', ['en_ligne', 'masques', 'stock'], true) ? $_GET['vue'] : 'tous';
$needle = normalize_text($q);
$list = array_values(array_filter($all, static function ($p) use ($needle, $vue) {
    if ($needle !== '' && !str_contains(normalize_text($p['name'] . ' ' . $p['slug']), $needle)) return false;
    return match ($vue) { 'en_ligne' => $p['is_active'], 'masques' => !$p['is_active'], 'stock' => $p['stock'] < 5, default => true };
}));

$sold = [];
foreach (db()->query("SELECT oi.product_id, SUM(oi.quantity) n FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status <> 'annulee' AND oi.product_id IS NOT NULL GROUP BY oi.product_id") as $r) {
    $sold[(int) $r['product_id']] = (int) $r['n'];
}

admin_header('Produits', 'produits');
admin_title('Produits', count($all) . ' produit(s) au catalogue', '<a class="btn btn-primary" href="' . e(url('admin/produits', ['nouveau' => 1])) . '">' . icon('plus') . 'Ajouter un produit</a>');

if ($draft): $isEdit = !empty($draft['id']); ?>
  <form class="panel pform" method="post" enctype="multipart/form-data" action="<?= e(url('admin/produits')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($draft['id'] ?? 0) ?>">
    <div class="pform-head">
      <h2><?= $isEdit ? 'Modifier le produit' : 'Nouveau produit' ?></h2>
      <a class="icon-btn" href="<?= e(url('admin/produits')) ?>" aria-label="Fermer"><?= icon('x') ?></a>
    </div>
    <?php if ($formError): ?><p class="alert-danger" style="margin:0 0 1rem"><?= e($formError) ?></p><?php endif; ?>
    <div class="pform-grid">
      <label class="label"><span>Nom</span><input class="field" name="name" required maxlength="120" value="<?= e($draft['name']) ?>" data-name></label>
      <label class="label"><span>Slug (adresse de la page)</span><input class="field" name="slug" maxlength="80" value="<?= e($draft['slug']) ?>" data-slug<?= $isEdit ? '' : ' data-auto' ?>><small>ex. thiakry-mil — laissé vide, il est créé à partir du nom</small></label>
      <label class="label"><span>Catégorie</span>
        <select class="select" name="category">
          <?php foreach (CATEGORIES as $slug => $c): ?><option value="<?= e($slug) ?>" <?= $draft['category'] === $slug ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label class="label"><span>Poids / conditionnement</span><input class="field" name="weight" maxlength="40" placeholder="500 g" value="<?= e($draft['weight']) ?>"></label>
      <label class="label"><span>Prix (FCFA)</span><input class="field" type="number" name="price" min="0" required value="<?= e($draft['price']) ?>"></label>
      <label class="label"><span>Prix barré (facultatif)</span><input class="field" type="number" name="old_price" min="0" value="<?= e($draft['old_price'] ?? '') ?>"></label>
      <label class="label"><span>Stock</span><input class="field" type="number" name="stock" min="0" required value="<?= e($draft['stock']) ?>"></label>
    </div>

    <div class="pform-image">
      <img src="<?= e(product_image(['category' => $draft['category'], 'image_url' => $draft['image_url']])) ?>" alt="" data-preview>
      <label class="label"><span>Photo du produit (JPG, PNG ou WebP, 5 Mo max)</span>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" data-file>
        <small><?= $draft['image_url'] ? 'Une photo est enregistrée. Choisissez un fichier pour la remplacer.' : "Sans photo, l'image de la catégorie est utilisée." ?></small>
        <?php if ($draft['image_url']): ?><label class="check" style="margin-top:.5rem"><input type="checkbox" name="remove_image" value="1">Retirer la photo actuelle</label><?php endif; ?>
      </label>
    </div>

    <label class="label" style="margin-top:.75rem"><span>Description</span><textarea class="field" name="description" rows="3" maxlength="2000"><?= e($draft['description']) ?></textarea></label>

    <div class="pform-checks">
      <label class="check"><input type="checkbox" name="is_new" value="1" <?= $draft['is_new'] ? 'checked' : '' ?>>Nouveau</label>
      <label class="check"><input type="checkbox" name="best_seller" value="1" <?= $draft['best_seller'] ? 'checked' : '' ?>>Meilleure vente</label>
      <label class="check"><input type="checkbox" name="is_active" value="1" <?= $draft['is_active'] ? 'checked' : '' ?>>Visible dans la boutique</label>
    </div>
    <div class="pform-actions">
      <button class="btn btn-primary">Enregistrer</button>
      <a class="btn btn-outline" href="<?= e(url('admin/produits')) ?>">Annuler</a>
    </div>
  </form>
  <script>
  (function () {
    var name = document.querySelector("[data-name]"), slug = document.querySelector("[data-slug]");
    function slugify(s) { return s.normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "").slice(0, 80); }
    if (slug.hasAttribute("data-auto")) {
      slug.addEventListener("input", function () { slug.removeAttribute("data-auto"); });
      name.addEventListener("input", function () { if (slug.hasAttribute("data-auto")) slug.value = slugify(name.value); });
    }
    document.querySelector("[data-file]").addEventListener("change", function (e) {
      var f = e.target.files[0]; if (f) document.querySelector("[data-preview]").src = URL.createObjectURL(f);
    });
  })();
  </script>
<?php endif; ?>

<form class="toolbar" method="get">
  <label class="a-search"><?= icon('search') ?><input name="q" value="<?= e($q) ?>" placeholder="Rechercher un produit…"></label>
  <?php if ($vue !== 'tous'): ?><input type="hidden" name="vue" value="<?= e($vue) ?>"><?php endif; ?>
</form>
<div class="chips">
  <?php foreach (['tous' => 'Tous', 'en_ligne' => 'En ligne', 'masques' => 'Masqués', 'stock' => 'Stock faible'] as $k => $l): ?>
    <a class="chip <?= $vue === $k ? 'is-active' : '' ?>" href="<?= e(url('admin/produits', ['vue' => $k === 'tous' ? '' : $k, 'q' => $q])) ?>"><?= e($l) ?></a>
  <?php endforeach; ?>
</div>

<div class="prod-grid">
  <?php foreach ($list as $p): $n = $sold[$p['id']] ?? 0; ?>
    <div class="panel aprod" id="p<?= $p['id'] ?>">
      <div class="aprod-top">
        <img src="<?= e(product_image($p)) ?>" alt="">
        <div class="aprod-info">
          <div class="aprod-name"><p><?= e($p['name']) ?></p><span class="state <?= $p['is_active'] ? 'on' : '' ?>"><?= $p['is_active'] ? 'Publié' : 'Masqué' ?></span></div>
          <p class="aprod-cat"><?= e(category_of($p['category'])['name']) ?><?= $p['weight'] ? ' · ' . e($p['weight']) : '' ?></p>
          <p class="aprod-price"><?= fcfa($p['price']) ?><?php if ($p['old_price'] !== null): ?><s><?= fcfa($p['old_price']) ?></s><?php endif; ?></p>
        </div>
      </div>
      <div class="aprod-stats">
        <div><b class="<?= $p['stock'] === 0 ? 'out' : ($p['stock'] < 5 ? 'low' : '') ?>"><?= $p['stock'] ?></b><small>Stock</small></div>
        <div><b><?= $n ?></b><small>Vendus</small></div>
        <div><b><?= e(compact_number($n * $p['price'])) ?></b><small>Ventes</small></div>
      </div>
      <form method="post" class="aprod-vis">
        <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $p['id'] ?>">
        <div><p>Visible en boutique</p><small><?= $p['is_active'] ? 'Les clients le voient' : 'Caché aux clients' ?></small></div>
        <button class="switch <?= $p['is_active'] ? 'on' : '' ?>" role="switch" aria-checked="<?= $p['is_active'] ? 'true' : 'false' ?>" aria-label="Visible en boutique"></button>
      </form>
      <div class="aprod-actions">
        <a class="btn btn-primary" href="<?= e(url('admin/produits', ['edit' => $p['id']])) ?>"><?= icon('pencil') ?>Modifier</a>
        <?php if ($p['is_active']): ?><a class="btn btn-outline" href="<?= e(url('produit/' . $p['slug'])) ?>" target="_blank"><?= icon('eye') ?>Voir</a><?php endif; ?>
        <div class="push">
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="duplicate"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="icon-btn" aria-label="Dupliquer" title="Dupliquer"><?= icon('copy') ?></button></form>
          <form method="post" data-confirm="Supprimer « <?= e($p['name']) ?> » ? Cette action est définitive."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="icon-btn danger" aria-label="Supprimer" title="Supprimer"><?= icon('trash') ?></button></form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php if (!$list): ?><div class="panel empty-panel">Aucun produit trouvé.</div><?php endif; ?>
<?php admin_footer(); ?>
