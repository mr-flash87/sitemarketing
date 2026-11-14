<?php
/**
 * Commande invitée (sans compte). Les prix et totaux sont recalculés depuis la base,
 * et le stock est réservé dans une transaction : un client ne peut ni modifier le prix
 * ni acheter le dernier article en même temps qu'un autre.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'error' => 'Méthode non autorisée.'], 405);
}

$raw = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($raw)) {
    json_out(['ok' => false, 'error' => 'Requête invalide.'], 400);
}

$s = static fn(string $k, int $max) => is_string($raw[$k] ?? null) ? mb_substr(trim($raw[$k]), 0, $max) : '';
$fullName = $s('fullName', 80);
$phone = preg_replace('/\s+/', '', $s('phone', 25)) ?? '';
$email = $s('email', 160);
$city = $s('city', 80);
$address = $s('address', 240);
$note = $s('note', 400);
$payment = $s('paymentMethod', 40);
$items = $raw['items'] ?? null;

if (mb_strlen($fullName) < 2) json_out(['ok' => false, 'error' => 'Indiquez votre nom.'], 422);
if (!preg_match('/^\+?[0-9]{6,20}$/', $phone)) json_out(['ok' => false, 'error' => 'Numéro de téléphone invalide.'], 422);
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['ok' => false, 'error' => 'Adresse e-mail invalide.'], 422);
if (mb_strlen($address) < 4) json_out(['ok' => false, 'error' => 'Indiquez votre adresse de livraison.'], 422);
if (!in_array($payment, PAYMENT_METHODS, true)) json_out(['ok' => false, 'error' => 'Mode de paiement invalide.'], 422);
if (!is_array($items) || !$items || count($items) > 50) json_out(['ok' => false, 'error' => 'Votre panier est vide.'], 422);

// Regroupe les lignes par produit.
$wanted = [];
foreach ($items as $it) {
    $slug = is_array($it) && is_string($it['slug'] ?? null) ? trim($it['slug']) : '';
    $qty = is_array($it) ? (int) ($it['qty'] ?? 0) : 0;
    if ($slug === '' || strlen($slug) > 80 || $qty < 1 || $qty > 99) {
        json_out(['ok' => false, 'error' => 'Panier invalide.'], 422);
    }
    $wanted[$slug] = min(99, ($wanted[$slug] ?? 0) + $qty);
}

// Limite simple contre les envois répétés : 5 commandes par minute et par session.
$now = time();
$_SESSION['order_times'] = array_values(array_filter($_SESSION['order_times'] ?? [], fn($t) => $t > $now - 60));
if (count($_SESSION['order_times']) >= 5) {
    json_out(['ok' => false, 'error' => 'Trop de commandes en peu de temps. Patientez une minute.'], 429);
}

$fee = (int) cfg('delivery_fee', 1500);
$pdo = db();

try {
    $pdo->beginTransaction();

    $in = implode(',', array_fill(0, count($wanted), '?'));
    $st = $pdo->prepare("SELECT id, slug, name, price, stock FROM products WHERE is_active = 1 AND slug IN ($in) FOR UPDATE");
    $st->execute(array_keys($wanted));
    $rows = [];
    foreach ($st->fetchAll() as $r) {
        $rows[$r['slug']] = $r;
    }

    $lines = [];
    $subtotal = 0;
    foreach ($wanted as $slug => $qty) {
        $p = $rows[$slug] ?? null;
        if (!$p) {
            throw new DomainException("Un article du panier n'est plus disponible.");
        }
        if ((int) $p['stock'] < $qty) {
            throw new DomainException(sprintf('Stock insuffisant pour %s : %d disponible(s).', $p['name'], (int) $p['stock']));
        }
        $lines[] = ['p' => $p, 'qty' => $qty];
        $subtotal += (int) $p['price'] * $qty;
    }
    $total = $subtotal + $fee;

    $dec = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
    foreach ($lines as $l) {
        $dec->execute([$l['qty'], $l['p']['id'], $l['qty']]);
        if ($dec->rowCount() !== 1) {
            throw new DomainException('Stock insuffisant pour ' . $l['p']['name'] . '.');
        }
    }

    // Fiche client réutilisable, identifiée par le numéro de téléphone.
    $pdo->prepare(
        'INSERT INTO customers (phone, full_name, email, city, address, order_count) VALUES (?, ?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), email = VALUES(email), city = VALUES(city),
         address = VALUES(address), order_count = order_count + 1'
    )->execute([$phone, $fullName, $email, $city, $address]);
    $cs = $pdo->prepare('SELECT id FROM customers WHERE phone = ?');
    $cs->execute([$phone]);
    $customerId = (int) $cs->fetchColumn();

    $ref = '';
    $check = $pdo->prepare('SELECT 1 FROM orders WHERE ref = ?');
    do {
        $ref = 'AM-' . strtoupper(bin2hex(random_bytes(4)));
        $check->execute([$ref]);
    } while ($check->fetchColumn());

    $pdo->prepare(
        'INSERT INTO orders (ref, customer_id, full_name, phone, email, city, address, payment_method, status, subtotal, delivery_fee, total, note)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'nouvelle\', ?, ?, ?, ?)'
    )->execute([$ref, $customerId, $fullName, $phone, $email, $city, $address, $payment, $subtotal, $fee, $total, $note]);
    $orderId = (int) $pdo->lastInsertId();

    $ins = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity) VALUES (?, ?, ?, ?, ?)');
    foreach ($lines as $l) {
        $ins->execute([$orderId, $l['p']['id'], $l['p']['name'], $l['p']['price'], $l['qty']]);
    }

    $pdo->commit();
} catch (DomainException $e) {
    $pdo->rollBack();
    json_out(['ok' => false, 'error' => $e->getMessage()], 409);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('AgroMina commande: ' . $e->getMessage());
    json_out(['ok' => false, 'error' => "Impossible d'enregistrer la commande. Réessayez."], 500);
}

$_SESSION['order_times'][] = $now;

// Alerte e-mail facultative pour l'équipe (si une adresse est configurée et que mail() est disponible).
$notify = (string) cfg('order_notify_email', '');
if ($notify !== '' && function_exists('mail')) {
    $body = "Nouvelle commande $ref\n\n$fullName — $phone\n$address" . ($city ? ", $city" : '') . "\nPaiement : $payment\n\n";
    foreach ($lines as $l) {
        $body .= sprintf("- %s × %d = %s\n", $l['p']['name'], $l['qty'], fcfa((int) $l['p']['price'] * $l['qty']));
    }
    $body .= "\nTotal : " . fcfa($total) . ($note ? "\nNote : $note" : '');
    @mail($notify, "=?UTF-8?B?" . base64_encode("Nouvelle commande $ref") . "?=", $body, "Content-Type: text/plain; charset=UTF-8");
}

json_out(['ok' => true, 'ref' => $ref, 'subtotal' => $subtotal, 'deliveryFee' => $fee, 'total' => $total]);
