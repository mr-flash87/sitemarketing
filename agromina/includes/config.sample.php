<?php
/**
 * Exemple de configuration. install.php crée automatiquement includes/config.php
 * à partir de vos réponses ; vous pouvez ensuite modifier ce fichier à la main
 * (Gestionnaire de fichiers Hostinger → includes/config.php).
 */
return [
    // Base de données MySQL (hPanel → Bases de données → MySQL)
    'db_host' => 'localhost',
    'db_name' => 'u123456789_agromina',
    'db_user' => 'u123456789_agromina',
    'db_pass' => 'mot-de-passe',

    // Laisser vide si le site est à la racine du domaine (ex. https://agromina.sn).
    // Sinon, le sous-dossier, ex. '/boutique'.
    'base_url' => '',

    // Boutique
    'delivery_fee' => 1500,

    // Coordonnées affichées dans le pied de page
    'contact_address' => 'Delta du Saloum, Sénégal',
    'contact_phone' => '+221 00 000 00 00',
    'contact_email' => 'contact@agromina.sn',
    'facebook_url' => '#',
    'instagram_url' => '#',

    // Adresse qui reçoit un e-mail à chaque commande et message de contact (laisser vide pour désactiver)
    'order_notify_email' => '',

    // Mettre à true seulement pour diagnostiquer une erreur (affiche les erreurs PHP)
    'debug' => false,
];
