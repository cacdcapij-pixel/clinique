<?php
// Liste des pré-admissions — même écran pour trois profils, avec un
// périmètre et des colonnes différents :
//   secrétaire médicale : son service, couverture visible ;
//   médecin             : son service, couverture MASQUÉE ;
//   administratif       : toute la clinique, couverture visible.
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/donnees.php';

$u = exiger_page('preadmissions');
$profil = $u['profil_actif'];
$lignes = preadmissions_visibles($profil, (int) $u['id_service']);
$voit_couverture = $profil !== 'MEDECIN';

$badges = [
    'EN_ATTENTE' => ['attente', 'En attente'],
    'VALIDEE'    => ['validee', 'Validée'],
    'REFUSEE'    => ['sensible', 'Refusée'],
    'ANNULEE'    => ['sensible', 'Annulée'],
];

entete('Pré-admissions', $u, '<a href="accueil.php">Accueil</a> &rsaquo; <strong>Pré-admissions</strong>');
?>
<?php
$cree = (int) ($_GET['cree'] ?? 0);
$ligne_cree = null;
foreach ($lignes as $l) {
    if ((int) $l['id_pre_admission'] === $cree) {
        $ligne_cree = $l;
    }
}
?>
<?php if ($ligne_cree): ?>
  <div class="succes">Pré-admission <strong>PA-<?= sprintf('%04d', $cree) ?></strong> enregistrée pour
    <strong><?= e($ligne_cree['nom_usage'] . ' ' . $ligne_cree['prenom']) ?></strong>
    (hospitalisation prévue le <?= e(date_fr($ligne_cree['date_hospitalisation_prevue'])) ?>) &mdash; statut « En attente ».</div>
<?php endif; ?>
<div class="titre-actions">
  <h1>Pré-admissions</h1>
  <?php if (peut_acceder($profil, 'nouvelle-preadmission')): ?>
    <a class="btn btn-primaire" href="nouvelle-preadmission.php">+ Nouvelle pré-admission</a>
  <?php endif; ?>
</div>
<p class="sous-titre">
  <?= $profil === 'ADMINISTRATIF'
      ? 'Toute la clinique'
      : 'Service ' . e($u['libelle_service']) . ' uniquement' ?>
  &middot; <?= count($lignes) ?> dossier(s)
</p>

<div class="rgpd">
  <strong>RGPD &mdash;</strong> données personnelles de patients. Aucune donnée du volet médical n'apparaît dans cette liste.
  <?php if (!$voit_couverture): ?>La couverture (mutuelle) est réservée au secrétariat et au département administratif.<?php endif; ?>
  Les consultations seront journalisées (script 06).
</div>

<section class="carte">
  <?php if (!$lignes): ?>
    <p class="note">Aucune pré-admission pour ce périmètre.</p>
  <?php else: ?>
  <table>
    <tr>
      <th>N°</th><th>Patient</th><th>Né(e) le</th><th>Service</th><th>Hospitalisation</th>
      <th>Motif</th><th>Statut</th><th>Mutuelle</th>
    </tr>
    <?php foreach ($lignes as $l): [$cls, $lib] = $badges[$l['statut']]; ?>
    <tr<?= (int) $l['id_pre_admission'] === $cree ? ' class="ligne-nouvelle"' : '' ?>>
      <td>PA-<?= sprintf('%04d', $l['id_pre_admission']) ?></td>
      <td><?= e($l['nom_usage'] . ' ' . $l['prenom']) ?></td>
      <td><?= e(date_fr($l['date_naissance'])) ?></td>
      <td><?= e($l['libelle_service']) ?></td>
      <td><?= e(date_fr($l['date_hospitalisation_prevue'])) ?></td>
      <td><?= e($l['motif']) ?></td>
      <td><span class="badge <?= $cls ?>"><?= $lib ?></span></td>
      <?php if ($voit_couverture): ?>
        <td><?= $l['mutuelles'] ? e($l['mutuelles']) : '<span class="badge attente">Aucune</span>' ?></td>
      <?php else: ?>
        <td class="masque-cell">Non accessible</td>
      <?php endif; ?>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</section>
<?php pied();
