<?php
// Bascule de compte en mode aperçu sans connexion (dev uniquement).
// Hors aperçu, la page n'existe pas.
require_once __DIR__ . '/inc/auth.php';

if (!apercu_actif()) {
    http_response_code(404);
    exit('Page inconnue');
}

$compte = $_GET['compte'] ?? '';
if (isset(APERCU_COMPTES[$compte])) {
    fermer_session();
    session_start();
    ouvrir_session_apercu($compte);
}
header('Location: ' . (($_SESSION['profil_actif'] ?? null) ? 'accueil.php' : 'choix-profil.php'));
exit;
