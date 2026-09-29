<?php
// =====================================================================
// Écrans de l'application et profils autorisés.
// C'est la SEULE table de droits du code : chaque page appelle
// exiger_page('<code>') et le menu d'accueil est construit à partir
// d'ici. Traduction de la matrice des rôles et privilèges (document
// de découpage des missions), limitée à l'application 1.
//
// etat : 'dispo'    = écran branché sur la base
//        autre texte = écran pas encore développé (sprint prévu)
// =====================================================================

const LIBELLES_PROFILS = [
    'SECRETAIRE_MEDICALE' => 'Secrétaire médicale',
    'MEDECIN'             => 'Médecin',
    'CHEF_SERVICE'        => 'Chef de service',
    'ADMINISTRATIF'       => 'Département administratif',
    'INFORMATICIEN_ADMIN' => 'Informaticien admin (DSI)',
];

const PAGES = [
    'preadmissions' => [
        'titre'   => 'Pré-admissions',
        'desc'    => 'Liste des dossiers (périmètre selon le profil)',
        'url'     => 'preadmissions.php',
        'profils' => ['SECRETAIRE_MEDICALE', 'MEDECIN', 'ADMINISTRATIF'],
        'etat'    => 'dispo',
    ],
    'nouvelle-preadmission' => [
        'titre'   => 'Nouvelle pré-admission',
        'desc'    => 'Identité, coordonnées, motif, service, mutuelle (US-02, US-03)',
        'url'     => 'page.php?p=nouvelle-preadmission',
        'profils' => ['SECRETAIRE_MEDICALE'],
        'etat'    => 'Sprint 3',
    ],
    'volet-medical' => [
        'titre'   => 'Volet médical',
        'desc'    => 'Antécédents, allergies, traitements, avis pré-op (US-05, US-06)',
        'url'     => 'page.php?p=volet-medical',
        'profils' => ['MEDECIN'],
        'etat'    => 'Sprint 4',
    ],
    'prise-en-charge' => [
        'titre'   => 'Validation prise en charge',
        'desc'    => 'Vérification assurance / mutuelle (US-07, US-08)',
        'url'     => 'page.php?p=prise-en-charge',
        'profils' => ['ADMINISTRATIF'],
        'etat'    => 'Sprint 4',
    ],
    'occupation' => [
        'titre'   => "Taux d'occupation",
        'desc'    => 'Chambres par service sur une période (US-09)',
        'url'     => 'page.php?p=occupation',
        'profils' => ['ADMINISTRATIF'],
        'etat'    => 'Sprint 4',
    ],
    'planning-service' => [
        'titre'   => 'Planning du service',
        'desc'    => 'Rendez-vous des médecins du service (application 2)',
        'url'     => 'page.php?p=planning-service',
        'profils' => ['CHEF_SERVICE'],
        'etat'    => 'Sprint 6',
    ],
    'comptes' => [
        'titre'   => 'Comptes et habilitations',
        'desc'    => 'Comptes, profils, statuts (US-10, US-11)',
        'url'     => 'comptes.php',
        'profils' => ['INFORMATICIEN_ADMIN'],
        'etat'    => 'dispo',
    ],
    'audit' => [
        'titre'   => "Journaux d'audit",
        'desc'    => 'Traçabilité des accès aux données sensibles (US-12)',
        'url'     => 'page.php?p=audit',
        'profils' => ['INFORMATICIEN_ADMIN'],
        'etat'    => 'Sprint 4',
    ],
];

function peut_acceder(string $profil, string $code_page): bool
{
    return isset(PAGES[$code_page]) && in_array($profil, PAGES[$code_page]['profils'], true);
}
