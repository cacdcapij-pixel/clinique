<?php
// Requêtes métier dont le PÉRIMÈTRE dépend du profil. Isolées ici pour
// que la page de recette puisse vérifier le filtrage sans passer par
// l'écran.
require_once __DIR__ . '/bdd.php';

/**
 * Pré-admissions visibles par l'utilisateur :
 *   - secrétaire médicale, médecin : son service uniquement ;
 *   - administratif : toute la clinique ;
 *   - autres profils : rien.
 */
function preadmissions_visibles(string $profil, int $id_service): array
{
    $sql = 'SELECT pa.id_pre_admission, pa.date_hospitalisation_prevue, pa.motif, pa.statut,
                   p.nom_usage, p.prenom, p.date_naissance, s.libelle_service,
                   GROUP_CONCAT(m.nom_mutuelle ORDER BY m.nom_mutuelle SEPARATOR \', \') AS mutuelles
            FROM PRE_ADMISSION pa
            JOIN PATIENT p ON p.id_patient = pa.id_patient
            JOIN SERVICE s ON s.id_service = pa.id_service
            LEFT JOIN PATIENT_MUTUELLE pm ON pm.id_patient = p.id_patient
            LEFT JOIN MUTUELLE m ON m.id_mutuelle = pm.id_mutuelle';
    $params = [];

    if (in_array($profil, ['SECRETAIRE_MEDICALE', 'MEDECIN'], true)) {
        $sql .= ' WHERE pa.id_service = ?';
        $params[] = $id_service;
    } elseif ($profil !== 'ADMINISTRATIF') {
        return [];
    }
    $sql .= ' GROUP BY pa.id_pre_admission ORDER BY pa.date_hospitalisation_prevue';

    $req = bdd()->prepare($sql);
    $req->execute($params);
    return $req->fetchAll();
}
