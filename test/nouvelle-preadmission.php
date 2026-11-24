<?php
// Création d'une pré-admission — profil secrétaire médicale (US-02, US-03).
// Parcours en deux temps, comme la maquette :
//   1. saisie (identité, coordonnées, hospitalisation, couverture) ;
//   2. récapitulatif + contrôle des doublons patient, puis confirmation.
// Chaque POST est revalidé côté serveur, y compris la confirmation.
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/preadmission.php';

$u = exiger_page('nouvelle-preadmission');

$s = CHAMPS_PREADMISSION;     // valeurs saisies (chaînes brutes)
$err = [];                    // erreurs par champ
$erreur_globale = '';
$etape = 'saisie';            // saisie | recap
$similaires = [];
$d = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $s = saisie_preadmission($_POST);
    $action = $_POST['action'] ?? 'verifier';

    if (!verifier_csrf()) {
        $erreur_globale = 'Session expirée, merci de vérifier la saisie et de recommencer.';
    } elseif ($action !== 'modifier') {
        [$d, $err] = valider_preadmission($s);
        if ($err) {
            $erreur_globale = 'La pré-admission n\'est pas enregistrée : corrigez les champs signalés.';
        } else {
            $similaires = patients_similaires($d);
            $meme_nir = array_values(array_filter($similaires, fn($p) => $p['meme_nir']));

            // Un NIR identique ne peut désigner qu'un seul patient : si l'identité
            // saisie ne correspond pas, c'est une erreur de saisie à corriger.
            if ($meme_nir && ($meme_nir[0]['nom_usage'] !== $d['nom_usage'] || $meme_nir[0]['date_naissance'] !== $d['date_naissance'])) {
                $err['num_secu'] = 'Ce numéro de sécurité sociale est déjà attribué à un autre patient ('
                    . $meme_nir[0]['nom_usage'] . ' ' . $meme_nir[0]['prenom'] . ', né(e) le '
                    . date_fr($meme_nir[0]['date_naissance']) . ').';
                $erreur_globale = 'La pré-admission n\'est pas enregistrée : corrigez les champs signalés.';
            } elseif ($action === 'enregistrer') {
                // Choix du patient : imposé si même NIR, sinon celui coché au récapitulatif.
                $choix = $_POST['patient'] ?? 'nouveau';
                $ids_similaires = array_map('strval', array_column($similaires, 'id_patient'));
                if ($meme_nir) {
                    $id_patient = (int) $meme_nir[0]['id_patient'];
                } elseif ($choix !== 'nouveau' && in_array($choix, $ids_similaires, true)) {
                    $id_patient = (int) $choix;
                } else {
                    $id_patient = null;
                }
                try {
                    $id = creer_preadmission($d, (int) $u['id_service'], (int) $u['id_compte'], $id_patient);
                    header('Location: preadmissions.php?cree=' . $id);
                    exit;
                } catch (PDOException $ex) {
                    $erreur_globale = $ex->getCode() === '23000'
                        ? 'Enregistrement refusé par la base (doublon ou donnée incohérente). Vérifiez le numéro de sécurité sociale.'
                        : 'Erreur technique : la pré-admission n\'a pas été enregistrée.';
                    $etape = 'recap';
                }
            } else {
                $etape = 'recap';
            }
        }
    }
}

$liste_mutuelles = mutuelles();
$nom_mutuelle = '';
foreach ($liste_mutuelles as $m) {
    if ((string) $m['id_mutuelle'] === $s['id_mutuelle']) {
        $nom_mutuelle = $m['nom_mutuelle'];
    }
}

/** Champ de formulaire avec son message d'erreur éventuel. */
function champ(string $nom, string $libelle, array $s, array $err, bool $obli = false, string $type = 'text', string $attrs = '', string $classe = ''): void
{
    $invalide = isset($err[$nom]) ? ' class="invalide" aria-invalid="true"' : '';
    echo '<div class="champ ' . $classe . '"><label for="' . $nom . '">' . e($libelle)
       . ($obli ? ' <span class="obli">*</span>' : '') . '</label>'
       . '<input id="' . $nom . '" name="' . $nom . '" type="' . $type . '" value="' . e($s[$nom]) . '"'
       . ($obli ? ' required' : '') . $invalide . ' ' . $attrs . '>';
    if (isset($err[$nom])) {
        echo '<span class="err-champ">' . e($err[$nom]) . '</span>';
    }
    echo '</div>';
}

$demain = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');
$hier   = (new DateTimeImmutable('yesterday'))->format('Y-m-d');

entete('Nouvelle pré-admission', $u,
    '<a href="accueil.php">Accueil</a> &rsaquo; <a href="preadmissions.php">Pré-admissions</a> &rsaquo; <strong>Nouvelle pré-admission</strong>');
?>
<h1>Création d'une pré-admission</h1>
<p class="sous-titre">Service <strong><?= e($u['libelle_service']) ?></strong> &middot; saisie par <?= e($u['prenom'] . ' ' . $u['nom']) ?></p>

<div class="etapes">
  <?php foreach (['1. Identité patient', '2. Coordonnées', '3. Hospitalisation', '4. Couverture'] as $lib): ?>
    <div class="etape <?= $etape === 'saisie' ? 'active' : 'faite' ?>"><?= $lib ?></div>
  <?php endforeach; ?>
  <div class="etape <?= $etape === 'recap' ? 'active' : '' ?>">5. Récapitulatif</div>
</div>

<div class="rgpd">
  <strong>RGPD &mdash;</strong> ce profil ne saisit aucune donnée de santé. Le volet médical (antécédents, allergies,
  traitements, avis pré-opératoire) est réservé au médecin. Les champs marqués <span class="obli">*</span> sont obligatoires.
  La création sera tracée dans le journal d'audit (script 06).
</div>

<?php if ($erreur_globale): ?><div class="erreur"><?= e($erreur_globale) ?></div><?php endif; ?>

<?php if ($etape === 'saisie'): ?>
<!-- ============================ SAISIE ============================ -->
<form method="post" novalidate>
  <input type="hidden" name="csrf" value="<?= e(jeton_csrf()) ?>">
  <input type="hidden" name="action" value="verifier">

  <section class="carte">
    <h2>1. Identité du patient <span class="badge sensible">Données personnelles</span></h2>
    <div class="grille">
      <?php champ('nom_usage', "Nom d'usage", $s, $err, true, 'text', 'maxlength="50" autofocus'); ?>
      <?php champ('nom_naissance', 'Nom de naissance', $s, $err, true, 'text', 'maxlength="50"'); ?>
      <?php champ('prenom', 'Prénom', $s, $err, true, 'text', 'maxlength="50"'); ?>
      <?php champ('date_naissance', 'Date de naissance', $s, $err, true, 'date', 'min="1900-01-01" max="' . $hier . '"'); ?>
      <div class="champ">
        <label for="sexe">Sexe <span class="obli">*</span></label>
        <select id="sexe" name="sexe" required<?= isset($err['sexe']) ? ' class="invalide"' : '' ?>>
          <option value="">— choisir —</option>
          <option value="F"<?= $s['sexe'] === 'F' ? ' selected' : '' ?>>F</option>
          <option value="M"<?= $s['sexe'] === 'M' ? ' selected' : '' ?>>M</option>
        </select>
        <?php if (isset($err['sexe'])): ?><span class="err-champ"><?= e($err['sexe']) ?></span><?php endif; ?>
      </div>
      <?php champ('num_secu', 'N° de sécurité sociale (15 chiffres)', $s, $err, false, 'text', 'inputmode="numeric" maxlength="21" placeholder="2 78 04 59 122 034 21"'); ?>
    </div>
    <p class="note">Avant l'enregistrement, l'application recherche les patients déjà connus (même NIR, ou même nom, prénom et date de naissance) pour éviter les doublons.</p>
  </section>

  <section class="carte">
    <h2>2. Coordonnées</h2>
    <div class="grille">
      <?php champ('adresse', 'Adresse', $s, $err, true, 'text', 'maxlength="150"', 'large'); ?>
      <?php champ('code_postal', 'Code postal', $s, $err, true, 'text', 'inputmode="numeric" maxlength="5" pattern="[0-9]{5}"'); ?>
      <?php champ('ville', 'Ville', $s, $err, true, 'text', 'maxlength="50"'); ?>
      <?php champ('telephone', 'Téléphone', $s, $err, false, 'tel', 'maxlength="20"'); ?>
      <?php champ('email', 'E-mail', $s, $err, false, 'email', 'maxlength="100"'); ?>
    </div>
  </section>

  <section class="carte">
    <h2>3. Hospitalisation</h2>
    <div class="grille">
      <div class="champ"><label>Service concerné</label><input value="<?= e($u['libelle_service']) ?>" readonly></div>
      <?php champ('date_hospitalisation_prevue', "Date d'hospitalisation prévue", $s, $err, true, 'date', 'min="' . $demain . '"'); ?>
      <?php champ('motif', "Motif d'hospitalisation", $s, $err, true, 'text', 'maxlength="255"', 'large'); ?>
      <div class="champ"><label>Statut de la pré-admission</label><input value="En attente" readonly></div>
      <div class="champ"><label>Créé par</label><input value="<?= e($u['prenom'] . ' ' . $u['nom'] . ' — ' . date('d/m/Y')) ?>" readonly></div>
    </div>
    <p class="note">Le service est celui de votre rattachement : vous ne voyez que les pré-admissions de votre service.
      Motif administratif uniquement &mdash; aucun élément de diagnostic n'est saisi ici.</p>
  </section>

  <section class="carte">
    <h2>4. Couverture / prise en charge</h2>
    <div class="grille trois">
      <div class="champ">
        <label for="id_mutuelle">Mutuelle</label>
        <select id="id_mutuelle" name="id_mutuelle"<?= isset($err['id_mutuelle']) ? ' class="invalide"' : '' ?>>
          <option value="">Aucune</option>
          <?php foreach ($liste_mutuelles as $m): ?>
            <option value="<?= (int) $m['id_mutuelle'] ?>"<?= (string) $m['id_mutuelle'] === $s['id_mutuelle'] ? ' selected' : '' ?>><?= e($m['nom_mutuelle']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($err['id_mutuelle'])): ?><span class="err-champ"><?= e($err['id_mutuelle']) ?></span><?php endif; ?>
      </div>
      <?php champ('num_adherent', "N° d'adhérent (si mutuelle)", $s, $err, false, 'text', 'maxlength="30"'); ?>
      <?php champ('date_fin_droits', 'Fin des droits', $s, $err, false, 'date'); ?>
    </div>
    <p class="note">La validation de la prise en charge relève du département administratif : la secrétaire saisit, elle ne valide pas.</p>
  </section>

  <section class="carte">
    <h2>5. Volet médical</h2>
    <div class="masque">Section réservée au profil <strong>Médecin</strong> &mdash; non accessible depuis ce profil.<br>
      (antécédents, allergies, traitements en cours, avis pré-opératoire)</div>
  </section>

  <div class="actions">
    <a class="btn btn-danger" href="preadmissions.php">Annuler</a>
    <button type="submit" class="btn-primaire">Vérifier la saisie &rsaquo;</button>
  </div>
</form>

<?php else: ?>
<!-- ========================= RÉCAPITULATIF ======================== -->
<form method="post">
  <input type="hidden" name="csrf" value="<?= e(jeton_csrf()) ?>">
  <?php foreach ($s as $champ_nom => $valeur): ?>
    <input type="hidden" name="<?= e($champ_nom) ?>" value="<?= e($valeur) ?>">
  <?php endforeach; ?>

  <?php if ($similaires): ?>
  <section class="carte alerte-doublon">
    <h2>Patient déjà connu ?</h2>
    <?php if ($similaires[0]['meme_nir']): ?>
      <div class="info">Ce numéro de sécurité sociale correspond au patient
        <strong><?= e($similaires[0]['nom_usage'] . ' ' . $similaires[0]['prenom']) ?></strong>.
        La pré-admission sera rattachée à son dossier ; ses coordonnées actuelles sont conservées.</div>
    <?php else: ?>
      <p class="note" style="margin-top:0">Un ou plusieurs patients portent déjà ce nom, ce prénom et cette date de naissance. Choisissez :</p>
      <div class="choix-patient">
        <?php foreach ($similaires as $i => $p): ?>
          <label><input type="radio" name="patient" value="<?= (int) $p['id_patient'] ?>"<?= $i === 0 ? ' checked' : '' ?>>
            Rattacher au patient existant <strong><?= e($p['nom_usage'] . ' ' . $p['prenom']) ?></strong>,
            né(e) le <?= e(date_fr($p['date_naissance'])) ?>, <?= e($p['ville']) ?>
            <?= $p['num_secu'] ? '' : '(sans NIR)' ?> &mdash; coordonnées existantes conservées</label>
        <?php endforeach; ?>
        <label><input type="radio" name="patient" value="nouveau"> C'est une autre personne : créer un nouveau patient</label>
      </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <section class="carte">
    <h2>5. Récapitulatif</h2>
    <table class="recap">
      <tr><th colspan="2">Identité</th></tr>
      <tr><td>Patient</td><td><strong><?= e($d['nom_usage'] . ' ' . $d['prenom']) ?></strong>
        <?= $d['nom_naissance'] !== $d['nom_usage'] ? '(né(e) ' . e($d['nom_naissance']) . ')' : '' ?></td></tr>
      <tr><td>Date de naissance &middot; sexe</td><td><?= e(date_fr($d['date_naissance'])) ?> &middot; <?= e($d['sexe']) ?></td></tr>
      <tr><td>N° de sécurité sociale</td><td><?= $d['num_secu'] ? e($d['num_secu']) : '<em>non renseigné</em>' ?></td></tr>
      <tr><th colspan="2">Coordonnées</th></tr>
      <tr><td>Adresse</td><td><?= e($d['adresse'] . ', ' . $d['code_postal'] . ' ' . $d['ville']) ?></td></tr>
      <tr><td>Téléphone &middot; e-mail</td><td><?= e($d['telephone'] ?? '—') ?> &middot; <?= e($d['email'] ?? '—') ?></td></tr>
      <tr><th colspan="2">Hospitalisation</th></tr>
      <tr><td>Service</td><td><?= e($u['libelle_service']) ?></td></tr>
      <tr><td>Date prévue</td><td><strong><?= e(date_fr($d['date_hospitalisation_prevue'])) ?></strong></td></tr>
      <tr><td>Motif</td><td><?= e($d['motif']) ?></td></tr>
      <tr><td>Statut à la création</td><td><span class="badge attente">En attente</span></td></tr>
      <tr><th colspan="2">Couverture</th></tr>
      <tr><td>Mutuelle</td><td><?= $d['id_mutuelle']
          ? e($nom_mutuelle . ' — n° ' . $d['num_adherent'] . ($d['date_fin_droits'] ? ', droits jusqu\'au ' . date_fr($d['date_fin_droits']) : ''))
          : '<span class="badge attente">Aucune</span>' ?></td></tr>
    </table>
  </section>

  <div class="actions">
    <button type="submit" name="action" value="modifier" class="btn-secondaire" formnovalidate>&lsaquo; Modifier</button>
    <button type="submit" name="action" value="enregistrer" class="btn-primaire">Enregistrer la pré-admission</button>
  </div>
</form>
<?php endif; ?>
<?php pied();
