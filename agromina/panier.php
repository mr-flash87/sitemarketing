<?php
require __DIR__ . '/includes/bootstrap.php';

$catalog = array_map('product_for_js', get_products());
$user = current_user();
$fee = (int) cfg('delivery_fee', 1500);
$phoneLocal = $user ? preg_replace('/^\+221\s*/', '', $user['phone']) : '';

page_header([
    'title' => 'Mon panier – AgroMina',
    'description' => 'Finalisez votre commande AgroMina, paiement à la livraison ou Mobile Money.',
    'noindex' => true,
]);
?>
<div data-cart-page>
  <div class="container empty-cart" data-cart-empty hidden>
    <div>
      <span class="round-icon"><?= icon('banknote') ?></span>
      <h1>Votre panier est vide</h1>
      <p>Découvrez nos produits du terroir sénégalais.</p>
      <a href="<?= e(url('boutique')) ?>" class="btn btn-accent">Découvrir la boutique <?= icon('arrow-right') ?></a>
    </div>
  </div>

  <div class="checkout-wrap" data-cart-full hidden>
    <div class="container">
      <h1>Finaliser ma commande</h1>
      <p class="muted" style="font-size:.875rem"><span data-count></span> · Paiement sécurisé à la remise</p>

      <div class="checkout">
        <div class="checkout-main">
          <div class="perks">
            <div><?= icon('shield-check') ?><b>Inspection libre</b><small>Vérifiez avant de payer</small></div>
            <div><?= icon('truck') ?><b>Livraison rapide</b><small>Partout au Sénégal</small></div>
            <div><?= icon('rotate-ccw') ?><b>Échange 48 h</b><small>Garantie satisfaction</small></div>
          </div>

          <form class="card co-form" data-checkout>
            <h2>Adresse de livraison &amp; paiement</h2>
            <p class="muted" style="font-size:.875rem">Renseignez vos coordonnées pour la remise en main propre.</p>

            <p class="co-label">Comment souhaitez-vous payer ?</p>
            <div class="pay-tiles">
              <button type="button" class="pay-tile is-active" data-mode="cash" aria-pressed="true"><span class="tick"><?= icon('check') ?></span><?= icon('banknote') ?><b>À la livraison</b><small>Espèces</small></button>
              <button type="button" class="pay-tile" data-mode="mobile" aria-pressed="false"><span class="tick"><?= icon('check') ?></span><?= icon('smartphone') ?><b>Mobile Money</b><small>Wave, Orange Money</small></button>
            </div>
            <div class="wallets" data-wallets hidden>
              <button type="button" class="wallet is-active" data-wallet="Wave">Wave</button>
              <button type="button" class="wallet" data-wallet="Orange Money">Orange Money</button>
            </div>

            <div class="co-fields">
              <label class="co-field"><span><?= icon('user') ?>Nom et prénom<em>*</em></span>
                <input class="co-input" name="fullName" required minlength="2" maxlength="80" placeholder="Ex : Fatou Diop" autocomplete="name" value="<?= e($user['full_name'] ?? '') ?>">
              </label>
              <label class="co-field"><span><?= icon('phone') ?>Numéro WhatsApp joignable<em>*</em></span>
                <div class="phone-group"><span>+221</span><input name="phone" type="tel" inputmode="tel" required minlength="6" maxlength="20" placeholder="77 000 00 00" autocomplete="tel-national" value="<?= e($phoneLocal) ?>"></div>
                <small>Le livreur vous contactera sur ce numéro.</small>
              </label>
              <div class="co-two">
                <label class="co-field"><span><?= icon('map-pin') ?>Adresse (quartier)<em>*</em></span>
                  <input class="co-input" name="address" required minlength="4" maxlength="240" placeholder="Ex : Sacré-Cœur 3" autocomplete="street-address">
                </label>
                <label class="co-field"><span>Ville</span>
                  <input class="co-input" name="city" maxlength="80" placeholder="Dakar" autocomplete="address-level2">
                </label>
              </div>
              <label class="co-field"><span>Indications d'adresse</span>
                <textarea class="co-input" name="note" rows="2" maxlength="400" placeholder="Ex : en face de la pharmacie, immeuble bleu, 2e étage…"></textarea>
              </label>
              <label class="co-field"><span>E-mail (facultatif)</span>
                <input class="co-input" name="email" type="email" maxlength="160" placeholder="vous@exemple.com" autocomplete="email" value="<?= e($user['email'] ?? '') ?>">
              </label>
            </div>

            <button type="submit" class="btn btn-primary co-submit"><span>Valider la commande (<span data-submit-total></span>)</span> <?= icon('arrow-right') ?></button>
            <p class="co-hint" data-pay-hint>Vous payez en espèces au livreur, après vérification.</p>
          </form>
        </div>

        <aside class="card summary">
          <h2>Votre panier</h2>
          <div data-lines></div>
          <div class="totals">
            <div><span class="muted">Sous-total</span><span data-subtotal></span></div>
            <div><span class="muted">Livraison</span><span><?= fcfa($fee) ?></span></div>
            <div class="grand"><b style="font-family:var(--font-sans);font-size:1rem;color:var(--fg)">Total</b><b data-grand></b></div>
          </div>
        </aside>
      </div>
    </div>
  </div>
</div>
<?php page_footer(['<script>window.AM_CATALOG = ' . json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . ';</script>']); ?>
