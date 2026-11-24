<?php
// =====================================================================
// Création d'une pré-admission par la secrétaire médicale (US-02,
// couverture saisie à la création : US-03).
// Logique isolée de l'écran pour que recette.php puisse la tester.
//
// Règles appliquées (toutes vérifiées CÔTÉ SERVEUR, même si le
// formulaire HTML a déjà des « required ») :
//   - champs obligatoires du dictionnaire de données ;
//   - date de naissance dans le passé et >= 01/01/1900
//     (MariaDB refuse CURRENT_DATE dans un CHECK : contrôle en PHP) ;
//   - code postal sur 5 chiffres ; NIR sur 15 chiffres, 1er chiffre
//     cohérent avec le sexe (pas de contrôle de clé : les NIR du jeu
//     d'essai sont fictifs et n'ont pas de clé valide) ;
//   - date d'hospitalisation prévue postérieure à aujourd'hui ;
//   - si une mutuelle est choisie : n° d'adhérent obligatoire ;
//   - service = service de la secrétaire connectée (elle ne voit que
//     les dossiers de son service, cf. preadmissions_visibles()).
// =====================================================================
require_once __DIR__ . '/bdd.php';

/** Champs du formulaire (nom du champ POST => valeur par défaut). */
const CHAMPS_PREADMISSION = [
    'nom_usage' => '', 'nom_naissance' => '', 'prenom' => '', 'date_naissance' => '',
    'sexe' => '', 'num_secu' => '',
    'adresse' => '', 'code_postal' => '', 'ville' => '', 'telephone' => '', 'email' => '',
    'date_hospitalisation_prevue' => '', 'motif' => '',
    'id_mutuelle' => '', 'num_adherent' => '', 'date_fin_droits' => '',
];

/** Récupère les champs connus dans $_POST (chaînes, espaces retirés). */
function saisie_preadmission(array $post): array
{
    $s = [];
    foreach (CHAMPS_PREADMISSION as $champ => $defaut) {
        $s[$champ] = is_string($post[$champ] ?? null) ? trim($post[$champ]) : $defaut;
    }
    return $s;
}

/** Date AAAA-MM-JJ valide (refuse 2026-02-31) ou null. */
function date_valide(string $texte): ?DateTimeImmutable
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $texte);
    return ($d && $d->format('Y-m-d') === $texte) ? $d : null;
}

function mutuelles(): array
{
    return bdd()->query('SELECT id_mutuelle, nom_mutuelle FROM MUTUELLE ORDER BY nom_mutuelle')->fetchAll();
}

/**
 * Valide la saisie. Retourne [données normalisées prêtes pour la base,
 * erreurs indexées par nom de champ].
 */
function valider_preadmission(array $s, ?DateTimeImmutable $aujourdhui = null): array
{
    $aujourdhui ??= new DateTimeImmutable('today');
    $err = [];
    $d = [];

    // --- 1. Identité -------------------------------------------------
    $re_nom = '/^\p{L}[\p{L} \'\-]*$/u';
    foreach (['nom_usage' => "Nom d'usage", 'nom_naissance' => 'Nom de naissance', 'prenom' => 'Prénom'] as $c => $lib) {
        if ($s[$c] === '') {
            $err[$c] = "$lib obligatoire.";
        } elseif (mb_strlen($s[$c]) > 50) {
            $err[$c] = "$lib : 50 caractères maximum.";
        } elseif (!preg_match($re_nom, $s[$c])) {
            $err[$c] = "$lib : lettres, espaces, tirets et apostrophes uniquement.";
        }
    }
    $d['nom_usage']     = mb_strtoupper($s['nom_usage']);
    $d['nom_naissance'] = mb_strtoupper($s['nom_naissance']);
    $d['prenom']        = mb_convert_case(mb_strtolower($s['prenom']), MB_CASE_TITLE);

    $naissance = date_valide($s['date_naissance']);
    if ($s['date_naissance'] === '') {
        $err['date_naissance'] = 'Date de naissance obligatoire.';
    } elseif (!$naissance) {
        $err['date_naissance'] = 'Date de naissance invalide.';
    } elseif ($naissance >= $aujourdhui) {
        $err['date_naissance'] = 'La date de naissance doit être dans le passé.';
    } elseif ($naissance < new DateTimeImmutable('1900-01-01')) {
        $err['date_naissance'] = 'Date de naissance antérieure à 1900.';
    }
    $d['date_naissance'] = $s['date_naissance'];

    if (!in_array($s['sexe'], ['F', 'M'], true)) {
        $err['sexe'] = 'Sexe obligatoire.';
    }
    $d['sexe'] = $s['sexe'];

    $nir = preg_replace('/\s+/', '', $s['num_secu']);
    if ($nir !== '') {
        if (!preg_match('/^\d{15}$/', $nir)) {
            $err['num_secu'] = 'Le numéro de sécurité sociale comporte 15 chiffres.';
        } elseif (in_array($d['sexe'], ['F', 'M'], true) && in_array($nir[0], ['1', '2'], true)
                  && $nir[0] !== ($d['sexe'] === 'M' ? '1' : '2')) {
            $err['num_secu'] = 'Le NIR commence par 1 pour un homme, 2 pour une femme.';
        }
    }
    $d['num_secu'] = $nir === '' ? null : $nir;

    // --- 2. Coordonnées ----------------------------------------------
    if ($s['adresse'] === '') {
        $err['adresse'] = 'Adresse obligatoire.';
    } elseif (mb_strlen($s['adresse']) > 150) {
        $err['adresse'] = 'Adresse : 150 caractères maximum.';
    }
    $d['adresse'] = $s['adresse'];

    if (!preg_match('/^\d{5}$/', $s['code_postal'])) {
        $err['code_postal'] = 'Code postal : 5 chiffres.';
    }
    $d['code_postal'] = $s['code_postal'];

    if ($s['ville'] === '') {
        $err['ville'] = 'Ville obligatoire.';
    } elseif (mb_strlen($s['ville']) > 50) {
        $err['ville'] = 'Ville : 50 caractères maximum.';
    }
    $d['ville'] = $s['ville'];

    $tel = preg_replace('/[\s.\-]+/', '', $s['telephone']);
    if ($tel !== '' && !preg_match('/^(0\d{9}|\+33\d{9})$/', $tel)) {
        $err['telephone'] = 'Téléphone invalide (ex. 06 12 34 56 78).';
    }
    $d['telephone'] = $tel === '' ? null : $tel;

    if ($s['email'] !== '' && (!filter_var($s['email'], FILTER_VALIDATE_EMAIL) || strlen($s['email']) > 100)) {
        $err['email'] = 'Adresse e-mail invalide.';
    }
    $d['email'] = $s['email'] === '' ? null : mb_strtolower($s['email']);

    // --- 3. Hospitalisation ------------------------------------------
    $hospi = date_valide($s['date_hospitalisation_prevue']);
    if ($s['date_hospitalisation_prevue'] === '') {
        $err['date_hospitalisation_prevue'] = "Date d'hospitalisation obligatoire.";
    } elseif (!$hospi) {
        $err['date_hospitalisation_prevue'] = "Date d'hospitalisation invalide.";
    } elseif ($hospi <= $aujourdhui) {
        $err['date_hospitalisation_prevue'] = "La date d'hospitalisation doit être postérieure à aujourd'hui.";
    }
    $d['date_hospitalisation_prevue'] = $s['date_hospitalisation_prevue'];

    if ($s['motif'] === '') {
        $err['motif'] = "Motif d'hospitalisation obligatoire.";
    } elseif (mb_strlen($s['motif']) > 255) {
        $err['motif'] = 'Motif : 255 caractères maximum.';
    }
    $d['motif'] = $s['motif'];

    // --- 4. Couverture -----------------------------------------------
    $d['id_mutuelle'] = null;
    $d['num_adherent'] = null;
    $d['date_fin_droits'] = null;
    if ($s['id_mutuelle'] !== '') {
        $ids = array_map('strval', array_column(mutuelles(), 'id_mutuelle'));
        if (!in_array($s['id_mutuelle'], $ids, true)) {
            $err['id_mutuelle'] = 'Mutuelle inconnue.';
        }
        if ($s['num_adherent'] === '') {
            $err['num_adherent'] = "N° d'adhérent obligatoire quand une mutuelle est choisie.";
        } elseif (mb_strlen($s['num_adherent']) > 30) {
            $err['num_adherent'] = "N° d'adhérent : 30 caractères maximum.";
        }
        if ($s['date_fin_droits'] !== '' && !date_valide($s['date_fin_droits'])) {
            $err['date_fin_droits'] = 'Date de fin des droits invalide.';
        }
        $d['id_mutuelle'] = (int) $s['id_mutuelle'];
        $d['num_adherent'] = $s['num_adherent'];
        $d['date_fin_droits'] = $s['date_fin_droits'] === '' ? null : $s['date_fin_droits'];
    }

    return [$d, $err];
}

/**
 * Patients déjà connus qui ressemblent à la saisie, pour éviter les
 * doublons : même NIR, ou même nom d'usage + prénom + date de naissance.
 */
function patients_similaires(array $d): array
{
    $req = bdd()->prepare(
        'SELECT id_patient, nom_usage, prenom, date_naissance, ville, num_secu,
                (num_secu IS NOT NULL AND num_secu = ?) AS meme_nir
         FROM PATIENT
         WHERE (num_secu IS NOT NULL AND num_secu = ?)
            OR (nom_usage = ? AND prenom = ? AND date_naissance = ?)
         ORDER BY meme_nir DESC, id_patient'
    );
    $req->execute([$d['num_secu'], $d['num_secu'], $d['nom_usage'], $d['prenom'], $d['date_naissance']]);
    return $req->fetchAll();
}

/**
 * Enregistre la pré-admission dans une transaction unique.
 * $id_patient_existant : rattache le dossier à un patient déjà connu
 * (ses coordonnées ne sont pas modifiées), null = nouveau patient.
 * Retourne l'id de la pré-admission créée.
 */
function creer_preadmission(array $d, int $id_service, int $id_compte, ?int $id_patient_existant = null): int
{
    $pdo = bdd();
    $pdo->beginTransaction();
    try {
        if ($id_patient_existant === null) {
            $pdo->prepare(
                'INSERT INTO PATIENT (nom_usage, nom_naissance, prenom, date_naissance, sexe,
                                      adresse, code_postal, ville, telephone, email, num_secu)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $d['nom_usage'], $d['nom_naissance'], $d['prenom'], $d['date_naissance'], $d['sexe'],
                $d['adresse'], $d['code_postal'], $d['ville'], $d['telephone'], $d['email'], $d['num_secu'],
            ]);
            $id_patient = (int) $pdo->lastInsertId();
        } else {
            $id_patient = $id_patient_existant;
        }

        if ($d['id_mutuelle'] !== null) {
            // Mutuelle déjà connue pour ce patient : on met à jour l'adhésion.
            $pdo->prepare(
                'INSERT INTO PATIENT_MUTUELLE (id_patient, id_mutuelle, num_adherent, date_fin_droits)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE num_adherent = VALUES(num_adherent),
                                         date_fin_droits = VALUES(date_fin_droits)'
            )->execute([$id_patient, $d['id_mutuelle'], $d['num_adherent'], $d['date_fin_droits']]);
        }

        $pdo->prepare(
            "INSERT INTO PRE_ADMISSION (date_hospitalisation_prevue, motif, statut,
                                        id_patient, id_service, id_compte_createur)
             VALUES (?, ?, 'EN_ATTENTE', ?, ?, ?)"
        )->execute([$d['date_hospitalisation_prevue'], $d['motif'], $id_patient, $id_service, $id_compte]);
        $id = (int) $pdo->lastInsertId();

        // TODO script 06 : tracer la création dans JOURNAL_AUDIT.
        $pdo->commit();
        return $id;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}
