<?php
// =====================================================================
// Recette automatique des utilisateurs de test — OUTIL DE DÉVELOPPEMENT
// (inaccessible si MODE_TEST = false). À ne pas déployer en production.
//
// Les résultats ATTENDUS sont écrits ici à la main, à partir de la
// matrice des rôles (document de découpage) et du jeu d'essai, et NON
// recopiés depuis inc/droits.php : sinon le code se validerait lui-même.
// Chaque ligne = un test de la future fiche de recette (mission 5).
// =====================================================================
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/donnees.php';

if (!MODE_TEST) {
    http_response_code(404);
    exit;
}

// ---------------------------------------------------------------------
// 1. Connexion : identifiant, mot de passe, profils attendus
// ---------------------------------------------------------------------
$cas_connexion = [
    ['j.martin',   MDP_TEST, ['SECRETAIRE_MEDICALE'],         'Secrétaire médicale (Chirurgie)'],
    ['s.leroy',    MDP_TEST, ['SECRETAIRE_MEDICALE'],         'Secrétaire médicale (Neurologie)'],
    ['k.benali',   MDP_TEST, ['MEDECIN'],                     'Médecin (Chirurgie)'],
    ['h.faure',    MDP_TEST, ['MEDECIN', 'CHEF_SERVICE'],     'Chef de service Neurologie : 2 profils'],
    ['f.marquis',  MDP_TEST, ['MEDECIN', 'CHEF_SERVICE'],     'Chef de service Radiologie : 2 profils'],
    ['a.covillon', MDP_TEST, ['MEDECIN', 'CHEF_SERVICE'],     'Chef de service Chirurgie : 2 profils'],
    ['n.dupont',   MDP_TEST, ['ADMINISTRATIF'],               'Département administratif'],
    ['c.quirion',  MDP_TEST, ['ADMINISTRATIF'],               'Directeur financier'],
    ['p.lemort',   MDP_TEST, ['INFORMATICIEN_ADMIN'],         'RSSI'],
    ['m.gousse',   MDP_TEST, ['INFORMATICIEN_ADMIN'],         'Responsable pôle Applications'],
    ['t.petit',    MDP_TEST, [],                              'Refus : compte désactivé (actif = 0)'],
    ['e.roussel',  MDP_TEST, [],                              'Refus : habilitation SUSPENDUE'],
    ['l.girard',   MDP_TEST, [],                              'Refus : personnel sorti, habilitation REVOQUEE'],
    ['c.fontaine', MDP_TEST, [],                              'Refus : habilitation expirée (fin 30/06/2026)'],
    ['j.martin',   'mauvais', [],                             'Refus : mauvais mot de passe'],
    ['inconnu',    MDP_TEST, [],                              'Refus : identifiant inexistant'],
    ['J.MARTIN',   MDP_TEST, ['SECRETAIRE_MEDICALE'],         'Identifiant en majuscules (collation insensible à la casse)'],
];

// ---------------------------------------------------------------------
// 2. Accès aux écrans par profil (matrice des rôles, application 1)
//    true = accès attendu, false = 403 attendu
// ---------------------------------------------------------------------
$matrice = [
    //                      SECR   MED    CHEF   ADMIN  DSI
    'preadmissions'         => [true,  true,  false, true,  false],
    'nouvelle-preadmission' => [true,  false, false, false, false],
    'volet-medical'         => [false, true,  false, false, false],
    'prise-en-charge'       => [false, false, false, true,  false],
    'occupation'            => [false, false, false, true,  false],
    'planning-service'      => [false, false, true,  false, false],
    'comptes'               => [false, false, false, false, true ],
    'audit'                 => [false, false, false, false, true ],
];
$ordre_profils = ['SECRETAIRE_MEDICALE', 'MEDECIN', 'CHEF_SERVICE', 'ADMINISTRATIF', 'INFORMATICIEN_ADMIN'];

// ---------------------------------------------------------------------
// 3. Périmètre des données : pré-admissions visibles (id attendus,
//    d'après 02_jeu_essai_socle.sql : 1,2,5 Chirurgie ; 3 Neuro ; 4 Radio)
// ---------------------------------------------------------------------
$cas_perimetre = [
    ['j.martin',   'SECRETAIRE_MEDICALE', 4, [1, 2, 5],       'Secrétaire Chirurgie : son service'],
    ['s.leroy',    'SECRETAIRE_MEDICALE', 2, [3],             'Secrétaire Neurologie : son service'],
    ['k.benali',   'MEDECIN',             4, [1, 2, 5],       'Médecin Chirurgie : son service'],
    ['f.marquis',  'MEDECIN',             3, [4],             'Médecin Radiologie : son service'],
    ['n.dupont',   'ADMINISTRATIF',       5, [1, 2, 3, 4, 5], 'Administratif : toute la clinique'],
    ['a.covillon', 'CHEF_SERVICE',        4, [],              'Chef de service (profil CHEF) : aucune pré-admission'],
    ['m.gousse',   'INFORMATICIEN_ADMIN', 6, [],              'DSI : aucune donnée patient'],
];

// ---------------------------------------------------------------------
// Exécution
// ---------------------------------------------------------------------
$resultats = ['connexion' => [], 'acces' => [], 'perimetre' => []];
$total = $ok = 0;
$erreur_bdd = null;

try {
    foreach ($cas_connexion as [$id, $mdp, $attendu, $libelle]) {
        $obtenu = array_column(verifier_identifiants($id, $mdp), 'libelle_profil');
        sort($obtenu);
        $att = $attendu;
        sort($att);
        $conforme = $obtenu === $att;
        $resultats['connexion'][] = compact('id', 'mdp', 'attendu', 'obtenu', 'libelle', 'conforme');
        $total++;
        $ok += $conforme ? 1 : 0;
    }
    foreach ($matrice as $page => $droits) {
        foreach ($ordre_profils as $i => $profil) {
            $conforme = peut_acceder($profil, $page) === $droits[$i];
            $resultats['acces'][$page][$profil] = ['attendu' => $droits[$i], 'conforme' => $conforme];
            $total++;
            $ok += $conforme ? 1 : 0;
        }
    }
    foreach ($cas_perimetre as [$id, $profil, $service, $attendu, $libelle]) {
        $obtenu = array_map('intval', array_column(preadmissions_visibles($profil, $service), 'id_pre_admission'));
        sort($obtenu);
        $conforme = $obtenu === $attendu;
        $resultats['perimetre'][] = compact('id', 'profil', 'attendu', 'obtenu', 'libelle', 'conforme');
        $total++;
        $ok += $conforme ? 1 : 0;
    }
} catch (PDOException $ex) {
    $erreur_bdd = $ex->getMessage();
}

$liste = fn(array $t) => $t ? implode(', ', $t) : '—';
$ok_ko = fn(bool $c) => $c ? '<span class="badge ok">Conforme</span>' : '<span class="badge ko">Non conforme</span>';

entete('Recette des utilisateurs', null, '<strong>Recette automatique</strong> &middot; <a href="index.php">Écran de connexion</a>');
?>
<h1>Recette des utilisateurs de test</h1>
<p class="sous-titre">Exécutée le <?= date('d/m/Y à H:i') ?> sur la base <code><?= e(DB_NAME) ?></code>
  (<?= e(DB_HOST) ?>:<?= DB_PORT ?>) &middot; mot de passe de test <code><?= e(MDP_TEST) ?></code></p>

<?php if ($erreur_bdd): ?>
  <div class="erreur"><strong>Base inaccessible :</strong> <?= e($erreur_bdd) ?><br>
    Vérifiez config.php et que les scripts sql/01 et sql/02 ont bien été importés.</div>
<?php pied(); exit; endif; ?>

<div class="resume">
  <div><span>Tests</span><strong><?= $total ?></strong></div>
  <div><span>Conformes</span><strong style="color:var(--vert)"><?= $ok ?></strong></div>
  <div><span>Non conformes</span><strong style="color:var(--rouge)"><?= $total - $ok ?></strong></div>
</div>

<section class="carte">
  <h2>1. Connexion (US-01) &mdash; <?= count($resultats['connexion']) ?> tests</h2>
  <table>
    <tr><th>#</th><th>Identifiant</th><th>Cas</th><th>Attendu</th><th>Obtenu</th><th>Résultat</th></tr>
    <?php foreach ($resultats['connexion'] as $n => $r): ?>
    <tr class="<?= $r['conforme'] ? '' : 'ligne-ko' ?>">
      <td>C<?= sprintf('%02d', $n + 1) ?></td>
      <td><code><?= e($r['id']) ?></code><?= $r['mdp'] !== MDP_TEST ? ' <span class="note">(mdp « ' . e($r['mdp']) . ' »)</span>' : '' ?></td>
      <td><?= e($r['libelle']) ?></td>
      <td><?= $r['attendu'] ? e($liste($r['attendu'])) : '<em>Accès refusé</em>' ?></td>
      <td><?= $r['obtenu'] ? e($liste($r['obtenu'])) : '<em>Accès refusé</em>' ?></td>
      <td><?= $ok_ko($r['conforme']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <p class="note">Tous les refus affichent le même message à l'écran (on ne révèle pas la cause) ; la cause n'est visible qu'ici.</p>
</section>

<section class="carte">
  <h2>2. Droits d'accès aux écrans par profil &mdash; <?= count($matrice) * count($ordre_profils) ?> tests</h2>
  <table>
    <tr><th>Écran</th>
      <?php foreach ($ordre_profils as $p): ?><th><?= e(LIBELLES_PROFILS[$p]) ?></th><?php endforeach; ?>
    </tr>
    <?php foreach ($resultats['acces'] as $page => $parProfil): ?>
    <tr>
      <td><strong><?= e(PAGES[$page]['titre'] ?? $page) ?></strong><br><code><?= e(PAGES[$page]['url'] ?? '') ?></code></td>
      <?php foreach ($parProfil as $r): ?>
        <td style="<?= $r['conforme'] ? '' : 'background:#fdedec' ?>">
          <?= $r['attendu'] ? '✔ accès' : '✖ 403' ?>
          <?= $r['conforme'] ? '' : '<br><span class="badge ko">Non conforme</span>' ?>
        </td>
      <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
  </table>
  <p class="note">Test manuel complémentaire (capture pour la fiche de recette) : connecté en secrétaire,
    taper l'URL <code>page.php?p=volet-medical</code> &rarr; refus 403 attendu.</p>
</section>

<section class="carte">
  <h2>3. Périmètre des données (RGPD) &mdash; <?= count($resultats['perimetre']) ?> tests</h2>
  <table>
    <tr><th>#</th><th>Compte / profil</th><th>Cas</th><th>Pré-admissions attendues</th><th>Obtenues</th><th>Résultat</th></tr>
    <?php foreach ($resultats['perimetre'] as $n => $r): ?>
    <tr class="<?= $r['conforme'] ? '' : 'ligne-ko' ?>">
      <td>P<?= sprintf('%02d', $n + 1) ?></td>
      <td><code><?= e($r['id']) ?></code><br><span class="note"><?= e(LIBELLES_PROFILS[$r['profil']]) ?></span></td>
      <td><?= e($r['libelle']) ?></td>
      <td><?= e($liste($r['attendu'])) ?></td>
      <td><?= e($liste($r['obtenu'])) ?></td>
      <td><?= $ok_ko($r['conforme']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</section>
<?php pied();
