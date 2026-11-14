<?php
declare(strict_types=1);

/* ---------------------------------------------------------------- Constantes boutique */

const CATEGORIES = [
    'cereales' => ['name' => 'Céréales locales transformées', 'image' => 'cat-cereales.jpg'],
    'jus' => ['name' => 'Jus et boissons naturelles', 'image' => 'cat-jus.jpg'],
    'fruits-legumes' => ['name' => 'Fruits et légumes transformés', 'image' => 'cat-fruits.jpg'],
    'halieutiques' => ['name' => 'Produits halieutiques', 'image' => 'cat-halieutiques.jpg'],
    'artisanaux' => ['name' => 'Produits traditionnels et artisanaux', 'image' => 'cat-artisanaux.jpg'],
    'autres' => ['name' => 'Autres produits locaux', 'image' => 'cat-autres.jpg'],
];

const PAYMENT_METHODS = ['Paiement à la livraison', 'Wave', 'Orange Money'];

const ORDER_STATUSES = [
    'nouvelle' => 'Nouvelle',
    'confirmee' => 'Confirmée',
    'en_preparation' => 'En préparation',
    'livree' => 'Livrée',
    'annulee' => 'Annulée',
];

const BLOG_POSTS = [
    ['slug' => 'fonio', 'title' => 'Le fonio, céréale ancestrale aux mille vertus', 'date' => '4 novembre 2026', 'image' => 'cat-cereales.jpg', 'excerpt' => 'Riche en fibres et sans gluten, le fonio revient en force dans nos cuisines.'],
    ['slug' => 'saloum', 'title' => 'À la rencontre des transformatrices du Saloum', 'date' => '21 octobre 2026', 'image' => 'terroir.jpg', 'excerpt' => 'Portrait des femmes qui font vivre le savoir-faire halieutique du Delta.'],
    ['slug' => 'bissap', 'title' => 'Bissap, bouye, gingembre : nos jus naturels', 'date' => '8 octobre 2026', 'image' => 'cat-jus.jpg', 'excerpt' => 'Comment nous préparons nos boissons sans conservateur.'],
];

/* ---------------------------------------------------------------- Aides générales */

function cfg(string $key, mixed $default = null): mixed
{
    global $CONFIG;
    return $CONFIG[$key] ?? $default;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Adresse interne du site, en tenant compte d'un éventuel sous-dossier (base_url). */
function url(string $path = '', array $query = []): string
{
    $base = rtrim((string) cfg('base_url', ''), '/');
    $path = '/' . ltrim($path, '/');
    $query = array_filter($query, static fn($v) => $v !== null && $v !== '' && $v !== false);
    return $base . $path . ($query ? '?' . http_build_query($query) : '');
}

function asset(string $path): string
{
    $file = APP_ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return url('assets/' . ltrim($path, '/')) . $v;
}

function img(string $file): string
{
    return url('assets/img/' . $file);
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function fcfa(int|float $n): string
{
    return number_format((float) $n, 0, ',', "\u{202F}") . ' FCFA';
}

function compact_number(int|float $n): string
{
    if ($n >= 1_000_000) {
        return number_format($n / 1_000_000, 1, ',', '') . 'M';
    }
    if ($n >= 1000) {
        return (string) round($n / 1000) . 'K';
    }
    return (string) $n;
}

const FR_MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
const FR_MONTHS_SHORT = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
const FR_DAYS = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

function fr_date(string|int $date, string $style = 'short'): string
{
    $ts = is_int($date) ? $date : strtotime($date . ' UTC');
    $d = (int) date('j', $ts);
    $m = (int) date('n', $ts) - 1;
    $y = date('Y', $ts);
    return match ($style) {
        'long' => ucfirst(FR_DAYS[(int) date('w', $ts)]) . ' ' . $d . ' ' . FR_MONTHS[$m] . ' ' . $y,
        'day' => $d . ' ' . FR_MONTHS_SHORT[$m],
        'numeric' => date('d/m/Y', $ts),
        default => $d . ' ' . FR_MONTHS_SHORT[$m] . ' ' . $y,
    };
}

function fr_time(string $date): string
{
    return date('H:i', strtotime($date . ' UTC'));
}

/** Minuscule sans accents, pour la recherche. */
function normalize_text(string $s): string
{
    $s = mb_strtolower($s, 'UTF-8');
    return strtr($s, [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a',
        'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u', 'ÿ' => 'y', 'œ' => 'oe', 'æ' => 'ae',
    ]);
}

function slugify(string $s): string
{
    $s = normalize_text($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    return substr(trim($s, '-'), 0, 80);
}

function str_input(string $key, int $max = 500, ?array $source = null): string
{
    $source ??= $_POST;
    $v = $source[$key] ?? '';
    if (!is_string($v)) {
        return '';
    }
    return mb_substr(trim($v), 0, $max);
}

/* ---------------------------------------------------------------- Flash & CSRF */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function check_csrf(): void
{
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Session expirée. Rechargez la page et réessayez.');
    }
}

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------------------------------------------------------------- Comptes */

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $id = $_SESSION['user_id'] ?? null;
    if (!$id) {
        return $user = null;
    }
    $st = db()->prepare('SELECT id, email, full_name, phone, role FROM users WHERE id = ?');
    $st->execute([$id]);
    $user = $st->fetch() ?: null;
    if (!$user) {
        unset($_SESSION['user_id']);
    }
    return $user;
}

function is_staff(?array $u = null): bool
{
    $u ??= current_user();
    return $u !== null && in_array($u['role'], ['admin', 'moderator'], true);
}

function is_super(?array $u = null): bool
{
    $u ??= current_user();
    return $u !== null && $u['role'] === 'admin';
}

function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function require_staff(): array
{
    $u = current_user();
    if (!$u) {
        redirect(url('connexion', ['next' => '/admin']));
    }
    if (!is_staff($u)) {
        flash('error', "Accès refusé : ce compte n'est pas administrateur.");
        redirect(url('compte'));
    }
    return $u;
}

function safe_next(?string $next, string $fallback): string
{
    if (is_string($next) && preg_match('#^/(?!/)[A-Za-z0-9/_\-?=&.]*$#', $next)) {
        return url($next);
    }
    return $fallback;
}

/* ---------------------------------------------------------------- Catalogue */

function category_of(string $slug): array
{
    return CATEGORIES[$slug] ?? CATEGORIES['autres'];
}

function product_image(array $p): string
{
    if (!empty($p['image_url'])) {
        return preg_match('#^https?://#', $p['image_url']) ? $p['image_url'] : url($p['image_url']);
    }
    return img(category_of($p['category'])['image']);
}

function normalize_product(array $r): array
{
    foreach (['id', 'price', 'stock'] as $k) {
        $r[$k] = (int) $r[$k];
    }
    $r['old_price'] = $r['old_price'] === null ? null : (int) $r['old_price'];
    foreach (['is_new', 'best_seller', 'is_active'] as $k) {
        $r[$k] = (bool) $r[$k];
    }
    return $r;
}

/** @return array<int, array> */
function get_products(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM products' . ($activeOnly ? ' WHERE is_active = 1' : '')
        . ($activeOnly ? ' ORDER BY created_at ASC, name ASC' : ' ORDER BY created_at DESC, name ASC');
    return array_map('normalize_product', db()->query($sql)->fetchAll());
}

function get_product_by_slug(string $slug): ?array
{
    $st = db()->prepare('SELECT * FROM products WHERE slug = ? AND is_active = 1');
    $st->execute([$slug]);
    $r = $st->fetch();
    return $r ? normalize_product($r) : null;
}

function discount_pct(array $p): int
{
    if (!$p['old_price'] || $p['old_price'] <= $p['price']) {
        return 0;
    }
    return (int) round((1 - $p['price'] / $p['old_price']) * 100);
}

/** Données minimales d'un produit pour le panier côté navigateur. */
function product_for_js(array $p): array
{
    return [
        'slug' => $p['slug'],
        'name' => $p['name'],
        'price' => $p['price'],
        'stock' => $p['stock'],
        'image' => product_image($p),
        'url' => url('produit/' . $p['slug']),
    ];
}

function load_order_items(array $orderIds): array
{
    if (!$orderIds) {
        return [];
    }
    $in = implode(',', array_fill(0, count($orderIds), '?'));
    $st = db()->prepare("SELECT * FROM order_items WHERE order_id IN ($in) ORDER BY id");
    $st->execute(array_values($orderIds));
    $by = [];
    foreach ($st->fetchAll() as $it) {
        $by[(int) $it['order_id']][] = $it;
    }
    return $by;
}
