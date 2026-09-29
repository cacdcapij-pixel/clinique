<?php
// Écran de connexion (US-01).
require_once __DIR__ . '/inc/auth.php';

if (utilisateur() !== null) {
    header('Location: accueil.php');
    exit;
}

$erreur = '';
$identifiant = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = trim($_POST['identifiant'] ?? '');
    $mdp = $_POST['mdp'] ?? '';

    if (!verifier_csrf()) {
        $erreur = 'Session expirée, merci de recommencer.';
    } elseif ($identifiant === '' || $mdp === '') {
        $erreur = 'Identifiant et mot de passe obligatoires.';
    } else {
        $lignes = verifier_identifiants($identifiant, $mdp);
        if ($lignes) {
            ouvrir_session($lignes);
            header('Location: ' . ($_SESSION['profil_actif'] ? 'accueil.php' : 'choix-profil.php'));
            exit;
        }
        // Message volontairement identique pour tous les cas de refus :
        // on ne dit pas quel champ est faux ni si le compte existe (US-01).
        $erreur = 'Identifiant ou mot de passe incorrect, ou compte non autorisé.';
        // TODO script 06 : journaliser la tentative échouée dans JOURNAL_AUDIT.
    }
}

entete('Connexion');
?>
<div class="login">
  <section class="carte">
    <h2>Connexion</h2>
    <?php if (isset($_GET['expire'])): ?>
      <div class="info">Votre session a expiré ou vos droits ont changé. Merci de vous reconnecter.</div>
    <?php endif; ?>
    <?php if ($erreur): ?><div class="erreur"><?= e($erreur) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= e(jeton_csrf()) ?>">
      <div class="champ">
        <label for="identifiant">Identifiant <span class="obli">*</span></label>
        <input id="identifiant" name="identifiant" value="<?= e($identifiant) ?>" autofocus required>
      </div>
      <div class="champ">
        <label for="mdp">Mot de passe <span class="obli">*</span></label>
        <input id="mdp" name="mdp" type="password" required>
      </div>
      <button type="submit" class="btn-primaire">Se connecter</button>
    </form>

    <?php if (MODE_TEST): ?>
    <details class="comptes-test">
      <summary>Comptes de test (mot de passe : <code><?= e(MDP_TEST) ?></code>)</summary>
      <table>
        <tr><th>Identifiant</th><th>Cas testé</th></tr>
        <tr><td>j.martin</td><td>Secrétaire médicale — Chirurgie</td></tr>
        <tr><td>s.leroy</td><td>Secrétaire médicale — Neurologie</td></tr>
        <tr><td>k.benali</td><td>Médecin — Chirurgie</td></tr>
        <tr><td>a.covillon</td><td>Médecin + chef de service (2 profils)</td></tr>
        <tr><td>n.dupont</td><td>Département administratif</td></tr>
        <tr><td>m.gousse</td><td>Informaticien admin</td></tr>
        <tr><td>t.petit</td><td><em>Refus</em> — compte désactivé</td></tr>
        <tr><td>e.roussel</td><td><em>Refus</em> — habilitation suspendue</td></tr>
        <tr><td>l.girard</td><td><em>Refus</em> — parti, habilitation révoquée</td></tr>
        <tr><td>c.fontaine</td><td><em>Refus</em> — habilitation expirée</td></tr>
      </table>
    </details>
    <?php endif; ?>
  </section>
</div>
<?php pied();
