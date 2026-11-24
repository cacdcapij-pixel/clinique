<?php
// En-tête / pied communs à tous les écrans.

function entete(string $titre, ?array $u = null, string $fil = ''): void
{
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titre) ?> - LPFH Pré-admission</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="app">
  <div class="logo"><a href="accueil.php" style="text-decoration:none">LPFH &middot; Pré-admission</a></div>
  <div class="user">
  <?php if ($u): ?>
    <?= e($u['prenom'] . ' ' . $u['nom']) ?> &middot;
    <strong><?= e($u['profil_actif'] ? LIBELLES_PROFILS[$u['profil_actif']] : 'profil à choisir') ?></strong> &middot;
    <?= e($u['libelle_service']) ?>
    <?php if (count($u['profils']) > 1): ?> &middot; <a href="choix-profil.php">Changer de profil</a><?php endif; ?>
    <?php if (!apercu_actif()): ?> &middot; <a href="deconnexion.php">Déconnexion</a><?php endif; ?>
  <?php elseif (MODE_TEST): ?>
    Écrans de test &mdash; <a href="recette.php">Recette automatique</a>
  <?php endif; ?>
  </div>
</header>
<?php if (apercu_actif()): ?>
<div class="barre-apercu">
  <strong>Aperçu sans connexion</strong> (dev) &mdash; voir en tant que :
  <?php foreach (APERCU_COMPTES as $id => $lib): ?>
    <a href="apercu.php?compte=<?= e($id) ?>"<?= ($u && $u['identifiant'] === $id) ? ' class="actif"' : '' ?>><?= e($lib) ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php if ($fil): ?><nav class="fil"><?= $fil ?></nav><?php endif; ?>
<main>
<?php
}

function pied(): void
{
    echo "</main>\n</body>\n</html>\n";
}
