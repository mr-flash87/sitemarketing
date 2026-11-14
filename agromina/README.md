# AgroMina : boutique en ligne (version Hostinger)

Version autonome du site AgroMina créé sur Lovable, réécrite en **PHP + MySQL** pour fonctionner
sur n'importe quelle offre d'hébergement web Hostinger (Premium, Business, Cloud), **sans Node.js,
sans Supabase et sans abonnement Lovable**.

## Ce que contient le site

**Boutique (côté clients)**
- Accueil : bannière, catégories, meilleures ventes, promotions, nouveautés, témoignages, blog, newsletter
- Boutique avec recherche, filtres (catégorie, prix max, en stock, nouveautés, promos, meilleures ventes) et tri
- Pages Catégories, Fiche produit (quantité, favoris, barre d'achat sur mobile, produits similaires)
- Panier et commande sans compte : paiement à la livraison, Wave ou Orange Money, frais de livraison 1 500 FCFA
- Ticket de confirmation avec référence `AM-XXXXXXXX`, puis page **Suivre ma commande** (référence + téléphone)
- Favoris, Contact (messages enregistrés), À propos, Engagements, Blog, CGV, Confidentialité
- Compte : connexion, coordonnées pré-remplies au panier, changement de mot de passe

**Espace admin** (`/admin/`)
- Tableau de bord : chiffre d'affaires, panier moyen, commandes livrées/reçues sur 7, 30 ou 90 jours, graphique, statuts, alerte de stock faible
- Commandes : regroupées par jour, recherche, filtre par statut, appel ou WhatsApp en un clic, changement de statut
  (une commande **annulée remet son stock** en boutique)
- Produits : ajout, modification, **envoi de photo**, duplication, suppression, publier ou masquer, stock, ventes
- Clients : fiches créées automatiquement à chaque commande (identifiées par le téléphone)
- Messages : formulaire de contact et inscrits à la newsletter (export CSV)
- Équipe (super admin) : ajouter des gérants ou des super admins, réinitialiser un mot de passe, retirer un accès
- Mode sombre, interface mobile avec barre de navigation en bas

Les 14 produits du site Lovable sont importés automatiquement à l'installation.

## Mise en ligne sur Hostinger (pas à pas)

### 1. Créer la base de données
hPanel → **Bases de données** → **Gestion des bases de données MySQL** → créez une base.
Notez les **4 informations** : nom de la base (ex. `u123456789_agromina`), nom d'utilisateur, mot de passe,
et l'hôte (en général `localhost`).

### 2. Envoyer les fichiers
hPanel → **Sites web** → **Gestionnaire de fichiers** → ouvrez `public_html`.
1. Supprimez le fichier `default.php` s'il existe.
2. Envoyez le fichier `agromina-hostinger.zip`, puis faites un clic droit et choisissez **Extraire** dans `public_html`.
3. Vérifiez que `index.php`, `.htaccess`, et les dossiers `admin`, `assets`, `includes`… sont **directement**
   dans `public_html`, pas dans un sous-dossier.

> Les fichiers commençant par un point (`.htaccess`) sont parfois masqués : activez « Afficher les fichiers cachés ».

### 3. Lancer l'installation
Ouvrez `https://votre-domaine.com/install.php` et remplissez :
- les accès MySQL de l'étape 1,
- l'e-mail et le mot de passe de **votre compte super admin**,
- le téléphone et l'e-mail affichés en bas du site.

Cliquez sur **Installer**. Ensuite, **supprimez `install.php`** dans le Gestionnaire de fichiers.

### 4. Activer le HTTPS
hPanel → **Sécurité** → **SSL** → installez le certificat gratuit. Ensuite, dans `.htaccess`, retirez les `#`
devant les deux lignes « Forcer HTTPS ».

### 5. C'est en ligne
- Boutique : `https://votre-domaine.com/`
- Admin : `https://votre-domaine.com/connexion` (vous êtes redirigé vers `/admin/`)

## Réglages utiles

Tout se modifie dans `includes/config.php` (Gestionnaire de fichiers → clic droit → Modifier) :

| Réglage | Rôle |
|---|---|
| `delivery_fee` | Frais de livraison en FCFA (1500 par défaut) |
| `contact_phone`, `contact_email`, `contact_address` | Coordonnées du pied de page |
| `facebook_url`, `instagram_url` | Liens des réseaux sociaux |
| `order_notify_email` | E-mail qui reçoit une alerte à chaque commande et chaque message de contact |
| `base_url` | Laisser vide. Si le site est dans un sous-dossier (ex. `/boutique`), l'indiquer ici **et** dans `RewriteBase` du `.htaccess` |
| `debug` | `true` uniquement pour afficher les erreurs PHP pendant un dépannage |

Les textes des pages (À propos, Engagements, CGV, Confidentialité) sont dans les fichiers `.php` du même nom.
Les articles du blog et les catégories sont définis en haut de `includes/functions.php`.

## Organisation des fichiers

```
index.php, boutique.php, produit.php, panier.php…  pages de la boutique
admin/                 espace administrateur
api/commande.php       enregistrement des commandes (prix et stock vérifiés côté serveur)
api/newsletter.php     inscription newsletter
assets/css, js, img    style, scripts et images (logo, bannière, catégories)
includes/              configuration, connexion MySQL, fonctions (accès web bloqué)
sql/                   structure de la base et catalogue de départ (accès web bloqué)
uploads/products/      photos envoyées depuis l'admin (exécution de scripts bloquée)
install.php            assistant d'installation : à supprimer après usage
```

## Sécurité
- Mots de passe chiffrés (`password_hash`), sessions protégées, limitation des tentatives de connexion
- Protection CSRF sur tous les formulaires, requêtes SQL préparées, échappement de tout le contenu affiché
- Prix, totaux et stock recalculés côté serveur dans une transaction : impossible de modifier un prix
  depuis le navigateur ou d'acheter un article déjà épuisé
- Photos vérifiées (JPG, PNG ou WebP, 5 Mo max), dossiers internes inaccessibles depuis le web
- `includes/config.php` (mots de passe MySQL) n'est jamais publié sur GitHub (voir `.gitignore`)

## Configuration requise
PHP 8.1 ou plus récent (8.2/8.3 recommandé, réglable dans hPanel → **Avancé** → **Configuration PHP**),
MySQL 5.7+ ou MariaDB 10.3+, extensions `pdo_mysql`, `mbstring`, `fileinfo` (toutes actives par défaut chez Hostinger).
