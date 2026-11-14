/* AgroMina – panier, favoris et interactions du site (sans dépendance). */
(function () {
  "use strict";

  var AM = window.AM || { base: "", icons: {}, deliveryFee: 1500 };
  var CART_KEY = "am-cart";
  var FAV_KEY = "am-fav";

  /* ------------------------------------------------------------ stockage */
  function read(key) {
    try { var v = JSON.parse(localStorage.getItem(key) || "[]"); return Array.isArray(v) ? v : []; }
    catch (e) { return []; }
  }
  function write(key, value) {
    try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* stockage indisponible */ }
  }
  var cart = read(CART_KEY).filter(function (l) { return l && typeof l.slug === "string" && l.qty > 0; });
  var favs = read(FAV_KEY).filter(function (s) { return typeof s === "string"; });

  function saveCart() { write(CART_KEY, cart); refreshBadges(); document.dispatchEvent(new Event("am:cart")); }
  function saveFavs() { write(FAV_KEY, favs); refreshBadges(); refreshFavButtons(); document.dispatchEvent(new Event("am:fav")); }

  function addToCart(slug, qty) {
    qty = qty || 1;
    var line = cart.find(function (l) { return l.slug === slug; });
    if (line) line.qty = Math.min(99, line.qty + qty); else cart.push({ slug: slug, qty: Math.min(99, qty) });
    saveCart();
  }
  function setQty(slug, qty) {
    if (qty <= 0) cart = cart.filter(function (l) { return l.slug !== slug; });
    else cart.forEach(function (l) { if (l.slug === slug) l.qty = Math.min(99, qty); });
    saveCart();
  }
  function toggleFav(slug) {
    favs = favs.indexOf(slug) >= 0 ? favs.filter(function (s) { return s !== slug; }) : favs.concat([slug]);
    saveFavs();
  }

  /* ------------------------------------------------------------ aides */
  function fcfa(n) {
    return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, " ") + " FCFA";
  }
  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]; });
  }
  function toast(message, type) {
    var box = document.querySelector("[data-toasts]");
    if (!box) return;
    var t = document.createElement("div");
    t.className = "toast is-" + (type || "success");
    t.textContent = message;
    box.appendChild(t);
    setTimeout(function () { t.style.opacity = "0"; t.style.transition = "opacity .3s"; }, 2600);
    setTimeout(function () { t.remove(); }, 3000);
  }
  window.amToast = toast;

  function refreshBadges() {
    var count = cart.reduce(function (s, l) { return s + l.qty; }, 0);
    document.querySelectorAll("[data-cart-count]").forEach(function (b) { b.textContent = count; });
    document.querySelectorAll("[data-fav-count]").forEach(function (b) { b.textContent = favs.length; b.hidden = favs.length === 0; });
  }
  function refreshFavButtons() {
    document.querySelectorAll("[data-fav]").forEach(function (b) {
      var on = favs.indexOf(b.getAttribute("data-fav")) >= 0;
      b.classList.toggle("is-fav", on);
      b.setAttribute("aria-pressed", on ? "true" : "false");
    });
  }

  /* ------------------------------------------------------------ clics globaux */
  document.addEventListener("click", function (ev) {
    var add = ev.target.closest("[data-add]");
    if (add && !add.disabled) {
      var qtyEl = add.getAttribute("data-qty-from") ? document.querySelector(add.getAttribute("data-qty-from")) : null;
      var qty = qtyEl ? parseInt(qtyEl.textContent, 10) || 1 : 1;
      addToCart(add.getAttribute("data-add"), qty);
      toast((add.getAttribute("data-name") || "Produit") + " ajouté au panier");
      return;
    }
    var fav = ev.target.closest("[data-fav]");
    if (fav) { ev.preventDefault(); toggleFav(fav.getAttribute("data-fav")); return; }
    var menu = ev.target.closest("[data-menu-toggle]");
    if (menu) {
      var nav = document.querySelector("[data-menu]");
      var open = nav.classList.toggle("is-open");
      menu.setAttribute("aria-expanded", open ? "true" : "false");
      menu.innerHTML = open ? '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>' : '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 12h16M4 6h16M4 18h16"/></svg>';
    }
  });

  /* ------------------------------------------------------------ fiche produit : quantité */
  document.querySelectorAll("[data-qty]").forEach(function (box) {
    var max = parseInt(box.getAttribute("data-max"), 10) || 1;
    var out = box.querySelector("span");
    box.addEventListener("click", function (ev) {
      var b = ev.target.closest("button");
      if (!b) return;
      var v = parseInt(out.textContent, 10) || 1;
      v = b.hasAttribute("data-inc") ? Math.min(max, v + 1) : Math.max(1, v - 1);
      document.querySelectorAll("[data-qty] span").forEach(function (s) { s.textContent = v; });
      var price = parseInt(box.getAttribute("data-price"), 10) || 0;
      document.querySelectorAll("[data-line-total]").forEach(function (el) { el.textContent = fcfa(price * v); });
    });
  });

  /* ------------------------------------------------------------ boutique : filtres instantanés */
  var filters = document.querySelector("[data-filters]");
  if (filters) {
    var timer;
    filters.addEventListener("change", function () { filters.submit(); });
    filters.addEventListener("input", function (ev) {
      if (ev.target.type === "range") {
        var lbl = filters.querySelector("[data-max-label]");
        var v = parseInt(ev.target.value, 10);
        if (lbl) lbl.textContent = v >= 5000 ? "tous" : v + " FCFA";
      }
      if (ev.target.name === "q") { clearTimeout(timer); timer = setTimeout(function () { filters.submit(); }, 600); }
    });
    var sort = document.querySelector("[data-sort]");
    if (sort) sort.addEventListener("change", function () { filters.querySelector("[name=tri]").value = sort.value; filters.submit(); });
  }

  /* ------------------------------------------------------------ favoris */
  var favPage = document.querySelector("[data-fav-page]");
  function renderFavPage() {
    if (!favPage) return;
    var shown = 0;
    favPage.querySelectorAll(".pcard").forEach(function (c) {
      var on = favs.indexOf(c.getAttribute("data-slug")) >= 0;
      c.hidden = !on; if (on) shown++;
    });
    document.querySelector("[data-fav-empty]").hidden = shown > 0;
  }
  document.addEventListener("am:fav", renderFavPage);

  /* ------------------------------------------------------------ newsletter */
  var news = document.querySelector("[data-newsletter]");
  if (news) {
    news.addEventListener("submit", function (ev) {
      ev.preventDefault();
      var email = news.querySelector("input").value.trim();
      if (!/^\S+@\S+\.\S+$/.test(email)) { toast("Adresse e-mail invalide", "error"); return; }
      post("api/newsletter.php", { email: email }).then(function (r) {
        if (r.ok) { toast("Merci pour votre inscription !"); news.reset(); } else toast(r.error || "Inscription impossible.", "error");
      });
    });
  }

  function post(path, data) {
    return fetch(AM.base + "/" + path, {
      method: "POST",
      headers: { "Content-Type": "application/json", "X-CSRF-Token": AM.csrf },
      credentials: "same-origin",
      body: JSON.stringify(data),
    }).then(function (r) { return r.json().catch(function () { return { ok: false }; }); })
      .catch(function () { return { ok: false, error: "Connexion impossible. Vérifiez votre réseau." }; });
  }

  /* ------------------------------------------------------------ panier & commande */
  var cartPage = document.querySelector("[data-cart-page]");
  var catalog = {};
  (window.AM_CATALOG || []).forEach(function (p) { catalog[p.slug] = p; });

  function cartLines() {
    return cart.map(function (l) { return { slug: l.slug, qty: l.qty, product: catalog[l.slug] }; })
      .filter(function (l) { return l.product; });
  }

  function renderCart() {
    if (!cartPage || cartPage.getAttribute("data-done") === "1") return;
    var lines = cartLines();
    var empty = document.querySelector("[data-cart-empty]");
    var full = document.querySelector("[data-cart-full]");
    if (!lines.length) { empty.hidden = false; full.hidden = true; return; }
    empty.hidden = true; full.hidden = false;
    var subtotal = lines.reduce(function (s, l) { return s + l.qty * l.product.price; }, 0);
    var count = lines.reduce(function (s, l) { return s + l.qty; }, 0);
    var grand = subtotal + AM.deliveryFee;
    var I = AM.icons;
    document.querySelector("[data-lines]").innerHTML = lines.map(function (l) {
      var p = l.product;
      return '<div class="line"><img src="' + esc(p.image) + '" alt="">' +
        '<div class="line-info"><a href="' + esc(p.url) + '">' + esc(p.name) + '</a><p>' + fcfa(p.price) + '</p>' +
        '<div class="line-ctrl"><div class="qty qty-sm"><button type="button" data-dec="' + esc(l.slug) + '" aria-label="Moins">' + I.minus + '</button><span>' + l.qty + '</span>' +
        '<button type="button" data-inc="' + esc(l.slug) + '" aria-label="Plus">' + I.plus + '</button></div>' +
        '<button type="button" class="rm" data-rm="' + esc(l.slug) + '" aria-label="Retirer">' + I.trash + '</button></div></div>' +
        '<span class="line-total">' + fcfa(p.price * l.qty) + '</span></div>';
    }).join("");
    document.querySelector("[data-subtotal]").textContent = fcfa(subtotal);
    document.querySelector("[data-grand]").textContent = fcfa(grand);
    document.querySelector("[data-submit-total]").textContent = fcfa(grand);
    document.querySelector("[data-count]").textContent = count + " article" + (count > 1 ? "s" : "");
  }

  if (cartPage) {
    var orderForm = cartPage.querySelector("[data-checkout]");
    var mode = "cash";
    var wallet = "Wave";

    cartPage.addEventListener("click", function (ev) {
      var t;
      if ((t = ev.target.closest("[data-inc]")) && t.closest("[data-lines]")) {
        var s = t.getAttribute("data-inc"); var l = cart.find(function (x) { return x.slug === s; });
        if (l) setQty(s, Math.min(l.qty + 1, (catalog[s] || {}).stock || 99));
      } else if ((t = ev.target.closest("[data-dec]"))) {
        var s2 = t.getAttribute("data-dec"); var l2 = cart.find(function (x) { return x.slug === s2; });
        if (l2) setQty(s2, l2.qty - 1);
      } else if ((t = ev.target.closest("[data-rm]"))) {
        setQty(t.getAttribute("data-rm"), 0);
      } else if ((t = ev.target.closest("[data-mode]"))) {
        mode = t.getAttribute("data-mode");
        cartPage.querySelectorAll("[data-mode]").forEach(function (b) { var on = b === t; b.classList.toggle("is-active", on); b.setAttribute("aria-pressed", on); });
        cartPage.querySelector("[data-wallets]").hidden = mode !== "mobile";
        updateHint();
      } else if ((t = ev.target.closest("[data-wallet]"))) {
        wallet = t.getAttribute("data-wallet");
        cartPage.querySelectorAll("[data-wallet]").forEach(function (b) { b.classList.toggle("is-active", b === t); });
        updateHint();
      }
    });

    function updateHint() {
      cartPage.querySelector("[data-pay-hint]").textContent = mode === "cash"
        ? "Vous payez en espèces au livreur, après vérification."
        : "Nous vous envoyons la demande de paiement " + wallet + " après confirmation.";
    }

    orderForm.addEventListener("submit", function (ev) {
      ev.preventDefault();
      var btn = orderForm.querySelector("[type=submit]");
      var label = btn.innerHTML;
      btn.disabled = true; btn.textContent = "Envoi en cours…";
      var f = new FormData(orderForm);
      var digits = String(f.get("phone") || "").replace(/\s+/g, "");
      var phone = digits.charAt(0) === "+" ? digits : "+221" + digits;
      var payment = mode === "cash" ? "Paiement à la livraison" : wallet;
      var count = cartLines().reduce(function (s, l) { return s + l.qty; }, 0);
      post("api/commande.php", {
        fullName: f.get("fullName"), phone: phone, email: f.get("email"), city: f.get("city"),
        address: f.get("address"), note: f.get("note"), paymentMethod: payment,
        items: cartLines().map(function (l) { return { slug: l.slug, qty: l.qty }; }),
      }).then(function (r) {
        btn.disabled = false; btn.innerHTML = label;
        if (!r.ok) { toast(r.error || "Commande impossible. Réessayez.", "error"); return; }
        cart = []; saveCart();
        showConfirmation(r, String(f.get("fullName") || ""), payment, phone, count);
      });
    });

    function showConfirmation(r, fullName, payment, phone, count) {
      cartPage.setAttribute("data-done", "1");
      var I = AM.icons;
      var now = new Date();
      var months = ["janv.", "févr.", "mars", "avr.", "mai", "juin", "juil.", "août", "sept.", "oct.", "nov.", "déc."];
      var date = now.getDate() + " " + months[now.getMonth()] + " " + now.getFullYear() + " · " + String(now.getHours()).padStart(2, "0") + ":" + String(now.getMinutes()).padStart(2, "0");
      var track = AM.base + "/suivi?ref=" + encodeURIComponent(r.ref) + "&phone=" + encodeURIComponent(phone);
      var steps = ["Commande reçue", "Appel de confirmation", "Livraison et paiement"].map(function (s, i) {
        return '<li class="' + (i === 0 ? "done" : "") + '"><span>' + (i === 0 ? I.check : i + 1) + "</span>" + s + "</li>";
      }).join("");
      cartPage.innerHTML =
        '<div class="ticket-wrap"><div class="ticket-box"><div class="ticket">' +
        '<div class="ticket-head"><div class="ticket-check"><span>' + I.check + '</span></div>' +
        "<h1>Merci " + esc(fullName.split(" ")[0]) + " !</h1>" +
        "<p>Votre commande est bien enregistrée. Nous vous appelons très vite pour confirmer la livraison.</p></div>" +
        '<div class="ticket-cut"><i></i></div>' +
        '<div class="ticket-body">' +
        '<div class="ticket-row"><div><small>Référence</small><p class="ticket-ref">' + esc(r.ref) + '</p></div><div class="r"><small>Montant</small><p class="ticket-amount">' + fcfa(r.total) + "</p></div></div>" +
        '<div class="ticket-row"><div><small>Date</small><p><b>' + date + '</b></p></div><div class="r"><small>Articles</small><p><b>' + count + "</b></p></div></div>" +
        '<div class="ticket-pay"><span>' + (payment === "Paiement à la livraison" ? I.banknote : I.smartphone) + "</span><div><b>" + esc(payment) + "</b><p>" + esc(phone) + "</p></div></div>" +
        "</div>" +
        '<ol class="ticket-steps">' + steps + "</ol></div>" +
        '<div class="ticket-actions"><a class="btn btn-primary" href="' + esc(track) + '">Suivre ma commande</a><a class="btn btn-outline" href="' + AM.base + '/boutique">Continuer mes achats</a></div>' +
        '<p class="ticket-note">Conservez votre référence pour suivre votre colis.</p></div></div>';
      window.scrollTo({ top: 0 });
    }

    document.addEventListener("am:cart", renderCart);
  }

  /* ------------------------------------------------------------ messages flash */
  document.querySelectorAll("[data-flash]").forEach(function (f) {
    setTimeout(function () { f.style.transition = "opacity .4s"; f.style.opacity = "0"; setTimeout(function () { f.remove(); }, 400); }, 5000);
  });

  refreshBadges();
  refreshFavButtons();
  renderFavPage();
  renderCart();
})();
