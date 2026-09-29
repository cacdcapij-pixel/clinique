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
const DB_USER = 'charle';
const DB_PASS = 'charle';

// true  = affiche les comptes de test sur l'écran de connexion et
//         autorise la page recette.php.
// false = obligatoire hors poste de développement.
const MODE_TEST = true;

// Mot de passe commun des comptes du jeu d'essai (02_jeu_essai_socle.sql).
const MDP_TEST = 'Lpfh2026!';
