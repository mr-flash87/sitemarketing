<?php
/**
 * Assistant d'installation AgroMina.
 * 1. Crée includes/config.php avec vos accès MySQL
 * 2. Crée les tables et importe le catalogue de départ
 * 3. Crée le compte super administrateur
 * Il se verrouille ensuite (fichier includes/install.lock). Supprimez-le après usage.
 */
declare(strict_types=1);

$root = __DIR__;
$configFile = "$root/includes/config.php";
$lockFile = "$root/includes/install.lock";

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$locked = is_file($lockFile);
$errors = [];
$done = false;
$guessBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$v = [
    'db_host' => 'localhost', 'db_name' => '', 'db_user' => '', 'db_pass' => '',
    'base_url' => $guessBase, 'admin_email' => '', 'admin_password' => '',
    'contact_phone' => '+221 00 000 00 00', 'contact_email' => 'contact@agromina.sn', 'order_notify_email' => '',
];

if (!$locked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $k => $_) {
        $v[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    if ($v['db_name'] === '' || $v['db_user'] === '') $errors[] = 'Indiquez le nom de la base et l\'utilisateur MySQL.';
    if (!filter_var($v['admin_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail administrateur invalide.';
    if (strlen($v['admin_password']) < 8) $errors[] = 'Mot de passe administrateur : 8 caractères minimum.';
    if ($v['base_url'] !== '' && !preg_match('#^/[A-Za-z0-9/_\-]*$#', $v['base_url'])) $errors[] = 'Sous-dossier invalide (ex. /boutique ou vide).';

    if (!$errors) {
        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $v['db_host'], $v['db_name']),
                $v['db_user'],
                $v['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $pdo->exec("SET time_zone = '+00:00'");
            foreach (['schema.sql', 'seed.sql'] as $file) {
                $sql = (string) file_get_contents("$root/sql/$file");
                $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? '';
                foreach (preg_split('/;\s*(\r?\n|$)/', $sql) ?: [] as $stmt) {
                    if (trim($stmt) !== '') {
                        $pdo->exec($stmt);
                    }
                }
            }
            $email = strtolower($v['admin_email']);
            $hash = password_hash($v['admin_password'], PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'admin')
                           ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'admin'")
                ->execute([$email, $hash]);

            $config = [
                'db_host' => $v['db_host'], 'db_name' => $v['db_name'], 'db_user' => $v['db_user'], 'db_pass' => $v['db_pass'],
                'base_url' => rtrim($v['base_url'], '/'),
                'delivery_fee' => 1500,
                'contact_address' => 'Delta du Saloum, Sénégal',
                'contact_phone' => $v['contact_phone'],
                'contact_email' => $v['contact_email'],
                'facebook_url' => '#',
                'instagram_url' => '#',
                'order_notify_email' => $v['order_notify_email'],
                'debug' => false,
            ];
            $php = "<?php\n// Généré par install.php le " . date('Y-m-d H:i') . " — modifiable à la main.\nreturn " . var_export($config, true) . ";\n";
            if (file_put_contents($configFile, $php) === false) {
                throw new RuntimeException("Impossible d'écrire includes/config.php (droits du dossier).");
            }
            @chmod($configFile, 0640);
            file_put_contents($lockFile, date('c'));
            $done = true;
        } catch (Throwable $e) {
            $errors[] = 'Erreur : ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Installation – AgroMina</title>
<style>
  body { margin: 0; font-family: system-ui, sans-serif; background: #f3f4ee; color: #1d3326; }
  .box { max-width: 36rem; margin: 2rem auto; background: #fff; border: 1px solid #e0e8da; border-radius: 1rem; padding: 2rem; }
  h1 { margin: 0 0 .25rem; color: #0b4a25; } h2 { font-size: 1rem; margin: 1.5rem 0 .5rem; color: #126333; }
  label { display: block; margin-top: .75rem; font-size: .9rem; font-weight: 600; }
  input { width: 100%; box-sizing: border-box; margin-top: .25rem; padding: .6rem .75rem; border: 1px solid #cdd8c5; border-radius: .5rem; font: inherit; }
  small { color: #62756a; font-weight: 400; }
  button { margin-top: 1.5rem; width: 100%; padding: .85rem; border: 0; border-radius: 999px; background: #126333; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
  .err { background: #fde8e6; color: #b42318; padding: .75rem 1rem; border-radius: .5rem; margin-top: 1rem; }
  .ok { background: #eef5e6; color: #0b4a25; padding: 1rem; border-radius: .5rem; }
  a { color: #126333; font-weight: 600; }
</style>
</head>
<body>
<div class="box">
  <h1>Installation AgroMina</h1>
  <?php if ($done): ?>
    <div class="ok">
      <p><b>Installation terminée.</b> La base est prête, le catalogue de 14 produits est importé et votre compte super admin est créé.</p>
      <p><b>Important :</b> supprimez maintenant le fichier <code>install.php</code> dans le Gestionnaire de fichiers.</p>
    </div>
    <p><a href="<?= h(rtrim($v['base_url'], '/') . '/') ?>">Voir la boutique</a> · <a href="<?= h(rtrim($v['base_url'], '/') . '/connexion') ?>">Se connecter à l'admin</a></p>
  <?php elseif ($locked): ?>
    <p class="err">Le site est déjà installé. Pour des raisons de sécurité, cet assistant est verrouillé. Supprimez <code>install.php</code> du serveur.</p>
  <?php else: ?>
    <p>Renseignez les accès à la base MySQL créée dans hPanel, puis votre compte administrateur.</p>
    <?php foreach ($errors as $err): ?><p class="err"><?= h($err) ?></p><?php endforeach; ?>
    <form method="post" autocomplete="off">
      <h2>Base de données MySQL</h2>
      <label>Hôte <small>(souvent « localhost » chez Hostinger)</small><input name="db_host" value="<?= h($v['db_host']) ?>" required></label>
      <label>Nom de la base <small>(ex. u123456789_agromina)</small><input name="db_name" value="<?= h($v['db_name']) ?>" required></label>
      <label>Utilisateur<input name="db_user" value="<?= h($v['db_user']) ?>" required></label>
      <label>Mot de passe<input name="db_pass" type="password" value="<?= h($v['db_pass']) ?>"></label>

      <h2>Compte super administrateur</h2>
      <label>E-mail<input name="admin_email" type="email" value="<?= h($v['admin_email']) ?>" required></label>
      <label>Mot de passe <small>(8 caractères minimum)</small><input name="admin_password" type="password" minlength="8" required></label>

      <h2>Boutique</h2>
      <label>Téléphone affiché<input name="contact_phone" value="<?= h($v['contact_phone']) ?>"></label>
      <label>E-mail affiché<input name="contact_email" value="<?= h($v['contact_email']) ?>"></label>
      <label>E-mail qui reçoit les alertes de commande <small>(facultatif)</small><input name="order_notify_email" type="email" value="<?= h($v['order_notify_email']) ?>"></label>
      <label>Sous-dossier du site <small>(vide si le site est à la racine du domaine)</small><input name="base_url" value="<?= h($v['base_url']) ?>"></label>

      <button>Installer</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
