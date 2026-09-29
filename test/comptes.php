<?php
// Comptes et habilitations — informaticien admin uniquement (US-10, US-11).
// Lecture seule pour l'instant : création / désactivation / révocation
// arrivent au Sprint 4. Aucune donnée patient sur cet écran.
require_once __DIR__ . '/inc/auth.php';
$u = exiger_page('comptes');

$comptes = bdd()->query(
    "SELECT c.identifiant, c.actif, c.date_derniere_connexion,
            p.nom, p.prenom, p.fonction, p.date_sortie, s.libelle_service,
            GROUP_CONCAT(CONCAT(pr.libelle_profil, '|', h.statut, '|', IFNULL(h.date_fin, ''))
                         ORDER BY pr.id_profil SEPARATOR ';') AS habilitations,
            (SELECT COUNT(*) FROM V_CONNEXION v WHERE v.id_compte = c.id_compte) AS nb_profils_utilisables
     FROM COMPTE c
     JOIN PERSONNEL p ON p.id_personnel = c.id_personnel
     JOIN SERVICE s   ON s.id_service   = p.id_service
     LEFT JOIN HABILITATION h ON h.id_personnel = p.id_personnel
     LEFT JOIN PROFIL pr      ON pr.id_profil    = h.id_profil
     GROUP BY c.id_compte
     ORDER BY s.libelle_service, p.nom"
)->fetchAll();

$badge_statut = ['ACTIVE' => 'validee', 'SUSPENDUE' => 'attente', 'REVOQUEE' => 'sensible'];

entete('Comptes et habilitations', $u, '<a href="accueil.php">Accueil</a> &rsaquo; <strong>Comptes et habilitations</strong>');
?>
<h1>Comptes et habilitations</h1>
<p class="sous-titre"><?= count($comptes) ?> comptes &middot; lecture seule (gestion au Sprint 4)</p>

<div class="rgpd">
  <strong>RGPD &mdash;</strong> la DSI gère les accès mais n'a <strong>aucun accès fonctionnel</strong> aux données patients.
  Toute action sur un compte sera journalisée.
</div>

<section class="carte">
  <table>
    <tr><th>Identifiant</th><th>Personnel</th><th>Service</th><th>Habilitations</th><th>Compte</th><th>Connexion possible</th><th>Dernière connexion</th></tr>
    <?php foreach ($comptes as $c): ?>
    <tr>
      <td><code><?= e($c['identifiant']) ?></code></td>
      <td><?= e($c['nom'] . ' ' . $c['prenom']) ?><br><span class="note"><?= e($c['fonction']) ?>
        <?= $c['date_sortie'] ? ' &middot; sorti le ' . e(date_fr($c['date_sortie'])) : '' ?></span></td>
      <td><?= e($c['libelle_service']) ?></td>
      <td>
        <?php foreach (array_filter(explode(';', (string) $c['habilitations'])) as $h):
              [$profil, $statut, $fin] = explode('|', $h); ?>
          <?= e(LIBELLES_PROFILS[$profil] ?? $profil) ?>
          <span class="badge <?= $badge_statut[$statut] ?>"><?= e($statut) ?></span>
          <?= $fin ? '<span class="note">jusqu\'au ' . e(date_fr($fin)) . '</span>' : '' ?><br>
        <?php endforeach; ?>
      </td>
      <td><?= $c['actif'] ? '<span class="badge ok">Actif</span>' : '<span class="badge ko">Désactivé</span>' ?></td>
      <td><?= $c['nb_profils_utilisables'] > 0
              ? '<span class="badge ok">Oui (' . (int) $c['nb_profils_utilisables'] . ' profil' . ($c['nb_profils_utilisables'] > 1 ? 's' : '') . ')</span>'
              : '<span class="badge ko">Non</span>' ?></td>
      <td><?= $c['date_derniere_connexion'] ? e(date('d/m/Y H:i', strtotime($c['date_derniere_connexion']))) : '&mdash;' ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <p class="note">« Connexion possible » est calculé par la vue V_CONNEXION : compte actif, personnel présent,
    habilitation ACTIVE et dans sa période de validité.</p>
</section>
<?php pied();
