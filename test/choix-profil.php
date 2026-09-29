<?php
// Choix du profil quand un compte en a plusieurs (ex. chef de service =
// MEDECIN + CHEF_SERVICE). Les droits appliqués sont ceux du profil
// ACTIF uniquement, jamais la somme des profils.
require_once __DIR__ . '/inc/auth.php';

$u = utilisateur();
if ($u === null) {
    header('Location: index.php?expire=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifier_csrf()) {
    $choix = $_POST['profil'] ?? '';
    if (in_array($choix, $u['profils'], true)) {   // jamais un profil que le compte n'a pas
        $_SESSION['profil_actif'] = $choix;
        header('Location: accueil.php');
        exit;
    }
}

entete('Choix du profil', $u);
?>
<div class="login">
  <section class="carte">
    <h2>Avec quel profil travaillez-vous ?</h2>
    <p class="note" style="margin-bottom:14px">Votre compte dispose de plusieurs habilitations actives.
      Les écrans accessibles dépendent du profil choisi ; vous pourrez en changer à tout moment.</p>
    <form method="post" class="profils">
      <input type="hidden" name="csrf" value="<?= e(jeton_csrf()) ?>">
      <?php foreach ($u['profils'] as $p): ?>
        <button type="submit" name="profil" value="<?= e($p) ?>"
          class="<?= $p === $u['profil_actif'] ? 'btn-primaire' : 'btn-secondaire' ?>">
          <?= e(LIBELLES_PROFILS[$p] ?? $p) ?>
        </button>
      <?php endforeach; ?>
    </form>
  </section>
</div>
<?php pied();
