-- =====================================================================
-- AP « Pre-admission LPFH » - 02 - Jeu d'essai du socle
-- A executer APRES 01_structure_socle.sql, dans la meme base.
-- =====================================================================
-- Personnes : organigramme de la clinique + personnel fictif ajoute
-- pour les tests (les secretaires medicales et les medecins de base
-- n'apparaissent pas nominativement dans l'organigramme).
-- Patients : entierement fictifs.
--
-- MOT DE PASSE DE TOUS LES COMPTES DE TEST : Lpfh2026!
-- (hash bcrypt genere par password_hash() en PHP ; a ne jamais
-- reutiliser en production)
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Referentiels
-- ---------------------------------------------------------------------
INSERT INTO SERVICE (id_service, libelle_service, type_service) VALUES
    (1, 'Direction',                  'DIRECTION'),
    (2, 'Neurologie',                 'MEDICAL'),
    (3, 'Radiologie',                 'MEDICAL'),
    (4, 'Chirurgie',                  'MEDICAL'),
    (5, 'Administration et finances', 'ADMINISTRATIF'),
    (6, 'DSI',                        'TECHNIQUE'),
    (7, 'Services techniques',        'TECHNIQUE');

INSERT INTO PROFIL (id_profil, libelle_profil, description) VALUES
    (1, 'SECRETAIRE_MEDICALE', 'Cree les pre-admissions (identite, coordonnees, motif, service, mutuelle)'),
    (2, 'MEDECIN',             'Consulte et complete le volet medical'),
    (3, 'CHEF_SERVICE',        'Supervise les rendez-vous des medecins de son service (application 2)'),
    (4, 'ADMINISTRATIF',       'Valide la prise en charge, consulte les statistiques d''occupation'),
    (5, 'INFORMATICIEN_ADMIN', 'Gere les comptes et les habilitations, sans acces aux donnees patients');

INSERT INTO MUTUELLE (id_mutuelle, nom_mutuelle) VALUES
    (1, 'MGEN'),
    (2, 'Harmonie Mutuelle'),
    (3, 'Malakoff Humanis'),
    (4, 'AG2R La Mondiale');

-- ---------------------------------------------------------------------
-- Personnel
-- ---------------------------------------------------------------------
INSERT INTO PERSONNEL (id_personnel, nom, prenom, email_pro, fonction, date_entree, date_sortie, id_service) VALUES
    -- Organigramme
    ( 1, 'HUPPE',    'Victor',     'victor.huppe@lpfh.local',       'Directeur de la clinique',            '2015-01-05', NULL, 1),
    ( 2, 'FAURE',    'Hugues',     'hugues.faure@lpfh.local',       'Chef de service Neurologie',          '2016-03-01', NULL, 2),
    ( 3, 'MARQUIS',  'Françoise',  'francoise.marquis@lpfh.local',  'Chef de service Radiologie',          '2017-09-01', NULL, 3),
    ( 4, 'COVILLON', 'Alexandrie', 'alexandrie.covillon@lpfh.local','Chef de service Chirurgie',           '2018-01-15', NULL, 4),
    ( 5, 'QUIRION',  'Claude',     'claude.quirion@lpfh.local',     'Directeur financier',                 '2016-06-01', NULL, 5),
    ( 6, 'LEMORT',   'Pierre',     'pierre.lemort@lpfh.local',      'Responsable sécurité des SI',         '2019-02-01', NULL, 6),
    ( 7, 'GOUSSE',   'Marc',       'marc.gousse@lpfh.local',        'Responsable pôle Applications',       '2020-04-01', NULL, 6),
    -- Personnel fictif (cas nominaux)
    ( 8, 'MARTIN',   'Julie',      'julie.martin@lpfh.local',       'Secrétaire médicale',                 '2021-05-03', NULL, 4),
    ( 9, 'LEROY',    'Sophie',     'sophie.leroy@lpfh.local',       'Secrétaire médicale',                 '2022-01-10', NULL, 2),
    (10, 'BENALI',   'Karim',      'karim.benali@lpfh.local',       'Chirurgien',                          '2020-09-01', NULL, 4),
    (11, 'DUPONT',   'Nadia',      'nadia.dupont@lpfh.local',       'Agent administratif - admissions',    '2019-11-04', NULL, 5),
    -- Personnel fictif (cas de refus de connexion, pour la recette)
    (12, 'PETIT',    'Thomas',     'thomas.petit@lpfh.local',       'Secrétaire médicale',                 '2023-02-01', NULL, 3),
    (13, 'ROUSSEL',  'Emma',       'emma.roussel@lpfh.local',       'Neurologue',                          '2021-03-15', NULL, 2),
    (14, 'GIRARD',   'Lucas',      'lucas.girard@lpfh.local',       'Radiologue',                          '2018-06-01', '2026-08-31', 3),
    (15, 'FONTAINE', 'Chloé',      'chloe.fontaine@lpfh.local',     'Secrétaire médicale (intérim)',       '2026-04-01', NULL, 4);

-- ---------------------------------------------------------------------
-- Comptes de connexion (le directeur n'utilise pas l'application)
-- ---------------------------------------------------------------------
INSERT INTO COMPTE (id_compte, identifiant, mot_de_passe_hash, actif, id_personnel) VALUES
    ( 1, 'h.faure',    '$2y$12$7TcVb5myUXSn1mSb7cd/gePSDxdjnLP10wHgDERragH891d4VfVxG', 1,  2),
    ( 2, 'f.marquis',  '$2y$12$7TcVb5myUXSn1mSb7cd/gePSDxdjnLP10wHgDERragH891d4VfVxG', 1,  3),
    ( 3, 'a.covillon', '$2y$12$7TcVb5myUXSn1mSb7cd/gePSDxdjnLP10wHgDERragH891d4VfVxG', 1,  4),
    ( 4, 'c.quirion',  '$2y$12$7TcVb5myUXSn1mSb7cd/gePSDxdjnLP10wHgDERragH891d4VfVxG', 1,  5),
    ( 5, 'p.lemort',   '$2y$12$7TcVb5myUXSn1mSb7cd/gePSDxdjnLP10wHgDERragH891d4VfVxG', 1,  6),
    ( 6, 'm.gousse',   '$2y$12$7TcVb5myUXSn1mSb7cd/gePSDxdjnLP10wHgDERragH891d4VfVxG', 1,  7),
    ( 7, 'j.martin',   '$2y$12$7TcVb5myUXSn1mSb7cd/gePSDxdjnLP10wHgDERragH891d4VfVxG', 1,  8),
    ( 8, 's.leroy',    '$2y$12$7TcVb5myUXSn1mSb7cd/gePSDxdjnLP10wHgDERragH891d4VfVxG', 1,  9),
    ( 9, 'k.benali',   '$2y$12$7TcVb5myUXSn1mSb7cd/gePSDxdjnLP10wHgDERragH891d4VfVxG', 1, 10),
    (10, 'n.dupont',   '$2y$12$7TcVb5myUXSn1mSb7cd/gePSDxdjnLP10wHgDERragH891d4VfVxG', 1, 11),
    (11, 't.petit',    '$2y$12$7TcVb5myUXSn1mSb7cd/gePSDxdjnLP10wHgDERragH891d4VfVxG', 0, 12), -- compte desactive
    (12, 'e.roussel',  '$2y$12$7TcVb5myUXSn1mSb7cd/gePSDxdjnLP10wHgDERragH891d4VfVxG', 1, 13), -- habilitation suspendue
    (13, 'l.girard',   '$2y$12$7TcVb5myUXSn1mSb7cd/gePSDxdjnLP10wHgDERragH891d4VfVxG', 1, 14), -- parti, habilitation revoquee
    (14, 'c.fontaine', '$2y$12$7TcVb5myUXSn1mSb7cd/gePSDxdjnLP10wHgDERragH891d4VfVxG', 1, 15); -- habilitation expiree

-- ---------------------------------------------------------------------
-- Habilitations
-- ---------------------------------------------------------------------
INSERT INTO HABILITATION (id_personnel, id_profil, date_debut, date_fin, statut, date_revocation, motif_revocation) VALUES
    -- Chefs de service : medecin ET chef de service
    ( 2, 2, '2016-03-01', NULL, 'ACTIVE', NULL, NULL),
    ( 2, 3, '2016-03-01', NULL, 'ACTIVE', NULL, NULL),
    ( 3, 2, '2017-09-01', NULL, 'ACTIVE', NULL, NULL),
    ( 3, 3, '2017-09-01', NULL, 'ACTIVE', NULL, NULL),
    ( 4, 2, '2018-01-15', NULL, 'ACTIVE', NULL, NULL),
    ( 4, 3, '2018-01-15', NULL, 'ACTIVE', NULL, NULL),
    -- Administratif
    ( 5, 4, '2016-06-01', NULL, 'ACTIVE', NULL, NULL),
    (11, 4, '2019-11-04', NULL, 'ACTIVE', NULL, NULL),
    -- DSI
    ( 6, 5, '2019-02-01', NULL, 'ACTIVE', NULL, NULL),
    ( 7, 5, '2020-04-01', NULL, 'ACTIVE', NULL, NULL),
    -- Secretaires medicales
    ( 8, 1, '2021-05-03', NULL, 'ACTIVE', NULL, NULL),
    ( 9, 1, '2022-01-10', NULL, 'ACTIVE', NULL, NULL),
    -- Medecin
    (10, 2, '2020-09-01', NULL, 'ACTIVE', NULL, NULL),
    -- Cas de refus
    (12, 1, '2023-02-01', NULL,         'ACTIVE',    NULL,                  NULL),                     -- mais compte actif = 0
    (13, 2, '2021-03-15', NULL,         'SUSPENDUE', NULL,                  NULL),
    (14, 2, '2018-06-01', NULL,         'REVOQUEE',  '2026-08-31 18:00:00', 'Départ de la clinique'),
    (15, 1, '2026-04-01', '2026-06-30', 'ACTIVE',    NULL,                  NULL);                     -- periode terminee

-- ---------------------------------------------------------------------
-- Patients (fictifs)
-- ---------------------------------------------------------------------
INSERT INTO PATIENT (id_patient, nom_usage, nom_naissance, prenom, date_naissance, sexe, adresse, code_postal, ville, telephone, email, num_secu) VALUES
    (1, 'LEFEBVRE', 'LEFEBVRE', 'Jean',      '1958-04-12', 'M', '12 rue de Nice',            '59400', 'Cambrai',      '0611223344', NULL,                      '158045911900112'),
    (2, 'DUBOIS',   'MOREAU',   'Catherine', '1972-11-03', 'F', '4 place Aristide Briand',   '59400', 'Cambrai',      '0622334455', 'c.dubois@example.com',    '272115911900245'),
    (3, 'LAMBERT',  'LAMBERT',  'Hugo',      '1995-07-21', 'M', '27 avenue de Valenciennes', '59400', 'Cambrai',      '0633445566', 'hugo.lambert@example.com','195075911900378'),
    (4, 'ROGER',    'ROGER',    'Martine',   '1949-02-28', 'F', '3 rue du Moulin',           '59161', 'Escaudœuvres', NULL,         NULL,                      '249025920400411'),
    (5, 'CARON',    'CARON',    'Léa',       '2012-09-09', 'F', '18 rue Sadi Carnot',        '59267', 'Proville',     '0644556677', NULL,                      '212095947600534');

INSERT INTO PATIENT_MUTUELLE (id_patient, id_mutuelle, num_adherent, date_fin_droits) VALUES
    (1, 1, 'MGEN-4481203',   NULL),
    (2, 2, 'HM-00987321',    '2026-12-31'),
    (3, 3, 'MH-5567120',     NULL),
    (5, 1, 'MGEN-4481999',   NULL);
-- Patient 4 sans mutuelle : cas a gerer dans l'ecran de saisie.

-- ---------------------------------------------------------------------
-- Pre-admissions (dates calculees a partir d'aujourd'hui pour que le
-- jeu d'essai reste valable quelle que soit la date d'import)
-- ---------------------------------------------------------------------
INSERT INTO PRE_ADMISSION (id_pre_admission, date_hospitalisation_prevue, motif, statut, id_patient, id_service, id_compte_createur) VALUES
    (1, DATE_ADD(CURDATE(), INTERVAL 12 DAY), 'Prothèse totale de hanche droite',       'EN_ATTENTE', 1, 4, 7),
    (2, DATE_ADD(CURDATE(), INTERVAL 5 DAY),  'Cholécystectomie sous cœlioscopie',      'EN_ATTENTE', 2, 4, 7),
    (3, DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'Bilan d''épilepsie - EEG prolongé',      'EN_ATTENTE', 3, 2, 8),
    (4, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'IRM cérébrale sous anesthésie',          'VALIDEE',    4, 3, 7),
    (5, DATE_ADD(CURDATE(), INTERVAL 8 DAY),  'Appendicectomie programmée',             'ANNULEE',    5, 4, 7);


-- =====================================================================
-- VERIFICATIONS (a lancer a la main apres l'import)
-- =====================================================================
-- Doit renvoyer 13 lignes (10 comptes utilisables, dont 3 chefs de
-- service a 2 profils) et AUCUNE ligne pour t.petit, e.roussel,
-- l.girard et c.fontaine :
--   SELECT identifiant, libelle_profil FROM V_CONNEXION ORDER BY identifiant;
--
-- Pre-admissions en attente avec le nom du patient et du service :
--   SELECT pa.id_pre_admission, p.nom_usage, p.prenom, s.libelle_service,
--          pa.date_hospitalisation_prevue, pa.motif
--   FROM PRE_ADMISSION pa
--   JOIN PATIENT p ON p.id_patient = pa.id_patient
--   JOIN SERVICE s ON s.id_service = pa.id_service
--   WHERE pa.statut = 'EN_ATTENTE';
-- =====================================================================
