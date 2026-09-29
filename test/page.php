<?php
// Écrans pas encore développés. Le contrôle d'accès est DÉJÀ actif :
// un profil non autorisé reçoit un 403, un profil autorisé voit un
// écran d'attente. On pourra ainsi recetter les droits avant le code.
require_once __DIR__ . '/inc/auth.php';

$code = $_GET['p'] ?? '';
if (!isset(PAGES[$code])) {
    http_response_code(404);
    exit('Écran inconnu');
}
$u = exiger_page($code);
$page = PAGES[$code];

entete($page['titre'], $u, '<a href="accueil.php">Accueil</a> &rsaquo; <strong>' . e($page['titre']) . '</strong>');
?>
<h1><?= e($page['titre']) ?></h1>
<p class="sous-titre"><?= e($page['desc']) ?></p>
<section class="carte">
  <div class="info">Accès autorisé pour le profil <strong><?= e(LIBELLES_PROFILS[$u['profil_actif']]) ?></strong>.
    Écran prévu au <strong><?= e($page['etat']) ?></strong> &mdash; voir la maquette correspondante dans <code>maquettes/</code>.</div>
</section>
<?php pied();
