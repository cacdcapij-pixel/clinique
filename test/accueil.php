<?php
// Accueil propre au profil actif : le menu est construit à partir de
// PAGES (inc/droits.php). Les écrans hors profil restent affichés en
// hachuré pour rendre la restriction visible (fil rouge RGPD).
require_once __DIR__ . '/inc/auth.php';
$u = exiger_connexion();
$profil = $u['profil_actif'];

$autorisees = $interdites = [];
foreach (PAGES as $code => $page) {
    if (peut_acceder($profil, $code)) {
        $autorisees[$code] = $page;
    } else {
        $interdites[$code] = $page;
    }
}

entete('Accueil', $u, 'Accueil');
?>
<h1>Bonjour <?= e($u['prenom']) ?></h1>
<p class="sous-titre">Profil <strong><?= e(LIBELLES_PROFILS[$profil]) ?></strong> &middot; service <?= e($u['libelle_service']) ?>
  &middot; identifiant <code><?= e($u['identifiant']) ?></code></p>

<section class="carte">
  <h2>Vos écrans</h2>
  <?php if (!$autorisees): ?>
    <p class="note">Aucun écran de l'application de pré-admission n'est ouvert à ce profil
      (voir matrice des rôles). Ses écrans arrivent avec l'application 2.</p>
  <?php endif; ?>
  <div class="tuiles">
    <?php foreach ($autorisees as $page): ?>
      <a class="tuile" href="<?= e($page['url']) ?>">
        <strong><?= e($page['titre']) ?></strong>
        <span><?= e($page['desc']) ?></span><br>
        <?php if ($page['etat'] !== 'dispo'): ?><span class="badge avenir"><?= e($page['etat']) ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="carte">
  <h2>Hors de votre profil</h2>
  <div class="tuiles">
    <?php foreach ($interdites as $page): ?>
      <div class="tuile interdite">
        <strong><?= e($page['titre']) ?></strong>
        <span>Réservé : <?= e(implode(', ', array_map(fn($p) => LIBELLES_PROFILS[$p], $page['profils']))) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (MODE_TEST && $interdites): ?>
    <p class="note">Test d'accès direct par URL : ouvrez par exemple
      <a href="<?= e(reset($interdites)['url']) ?>"><code><?= e(reset($interdites)['url']) ?></code></a>
      &mdash; la réponse attendue est un refus 403.</p>
  <?php endif; ?>
</section>
<?php pied();
