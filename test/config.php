<?php
// =====================================================================
// AP « Pré-admission LPFH » — écrans de test des utilisateurs
// Configuration. Valeurs par défaut = base locale décrite dans
// CONNEXION-BDD.md (MariaDB 10.11, port 3307, base clinique).
// Sur le serveur de la clinique : base lpfh + compte app_lpfh fourni
// par le groupe SISR (jamais root).
// =====================================================================

date_default_timezone_set('Europe/Paris');

const DB_HOST = '127.0.0.1';
const DB_PORT = 3307;
const DB_NAME = 'clinique';
// Identifiants : dans config.local.php, NON versionné (voir .gitignore).
// Le créer en copiant config.local.example.php puis en le remplissant.
if (!is_file(__DIR__ . '/config.local.php')) {
    exit('config.local.php manquant : copier config.local.example.php et le remplir.');
}
require __DIR__ . '/config.local.php';

// true  = affiche les comptes de test sur l'écran de connexion et
//         autorise la page recette.php.
// false = obligatoire hors poste de développement.
const MODE_TEST = true;

// APERÇU SANS CONNEXION (poste de développement uniquement).
// true  = pas d'écran de connexion : une session est ouverte
//         automatiquement sur APERCU_COMPTE, et une barre en haut de
//         page permet de passer d'un compte de test à l'autre.
//         Le contrôle d'accès par profil (403) reste ACTIF.
// false = fonctionnement normal (obligatoire pour la recette US-01).
// Ne fonctionne que si MODE_TEST vaut true.
const APERCU_SANS_CONNEXION = false;
const APERCU_COMPTE = 'j.martin';

