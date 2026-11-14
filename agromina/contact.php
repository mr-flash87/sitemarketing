<?php
require __DIR__ . '/includes/bootstrap.php';

$sent = false;
$err = '';
$name = $email = $message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name = str_input('name', 120);
    $email = str_input('email', 190);
    $message = str_input('message', 4000);
    $honeypot = str_input('website', 100);
    if ($honeypot !== '') {
        $sent = true; // robot : on fait semblant
    } elseif (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 5) {
        $err = 'Merci de remplir correctement tous les champs.';
    } else {
        db()->prepare('INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)')->execute([$name, $email, $message]);
        $notify = (string) cfg('order_notify_email', '');
        if ($notify !== '' && function_exists('mail')) {
            @mail($notify, '=?UTF-8?B?' . base64_encode('Nouveau message de ' . $name) . '?=', "$name <$email>\n\n$message", "Content-Type: text/plain; charset=UTF-8\r\nReply-To: $email");
        }
        flash('success', 'Message envoyé, merci !');
        redirect(url('contact'));
    }
}

page_header([
    'title' => 'Contact – AgroMina',
    'description' => "Une question, une commande professionnelle ? Écrivez à l'équipe AgroMina.",
    'active' => 'contact',
]);
page_hero('Contact', 'Particuliers ou professionnels, écrivez-nous.');
?>
<form class="container narrow contact-form" method="post" action="<?= e(url('contact')) ?>">
  <?= csrf_field() ?>
  <?php if ($err): ?><p class="alert-danger" style="margin:0"><?= e($err) ?></p><?php endif; ?>
  <input class="field" name="name" required maxlength="120" placeholder="Nom" aria-label="Nom" value="<?= e($name) ?>">
  <input class="field" name="email" type="email" required maxlength="190" placeholder="E-mail" aria-label="E-mail" value="<?= e($email) ?>">
  <textarea class="field" name="message" required rows="5" maxlength="4000" placeholder="Votre message" aria-label="Votre message"><?= e($message) ?></textarea>
  <input type="text" name="website" tabindex="-1" autocomplete="off" class="sr-only" aria-hidden="true">
  <button class="btn btn-accent">Envoyer</button>
</form>
<?php page_footer(); ?>
