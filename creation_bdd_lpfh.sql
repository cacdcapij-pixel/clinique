-- =====================================================================
-- AP « Pre-admission LPFH » - Groupe Developpeurs (SLAM)
-- Mission 11 (Sprint 1) - Script de creation de la base de donnees
-- SGBD cible : MariaDB 10.11 (serveur SISR)
-- Version : v1 du 22/09/2026
-- =====================================================================
-- Ordre de creation : les tables sans dependance d'abord, puis celles
-- qui portent des cles etrangeres. Respecter cet ordre a l'import.
-- =====================================================================

DROP DATABASE IF EXISTS lpfh;
CREATE DATABASE lpfh
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE lpfh;


-- =====================================================================
-- 1. REFERENTIELS
-- =====================================================================

-- Services de la clinique. type_service distingue les services de soins
-- (proposes a la creation d'une pre-admission, US-02) des services
-- support, qui n'accueillent pas de patient mais hebergent du personnel.
CREATE TABLE SERVICE (
    id_service       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    libelle_service  VARCHAR(50)  NOT NULL,
    type_service     ENUM('MEDICAL','ADMINISTRATIF','TECHNIQUE','DIRECTION') NOT NULL DEFAULT 'MEDICAL',
    CONSTRAINT pk_service        PRIMARY KEY (id_service),
    CONSTRAINT uq_service_libelle UNIQUE (libelle_service)
) ENGINE=InnoDB;

-- Liste de reference des allergies : evite la saisie libre.
CREATE TABLE ALLERGIE (
    id_allergie       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    libelle_allergie  VARCHAR(100) NOT NULL,
    CONSTRAINT pk_allergie        PRIMARY KEY (id_allergie),
    CONSTRAINT uq_allergie_libelle UNIQUE (libelle_allergie)
) ENGINE=InnoDB;

-- Organismes complementaires.
CREATE TABLE MUTUELLE (
    id_mutuelle   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom_mutuelle  VARCHAR(100) NOT NULL,
    CONSTRAINT pk_mutuelle     PRIMARY KEY (id_mutuelle),
    CONSTRAINT uq_mutuelle_nom UNIQUE (nom_mutuelle)
) ENGINE=InnoDB;

-- Profils applicatifs (US-10, US-11).
CREATE TABLE PROFIL (
    id_profil       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    libelle_profil  VARCHAR(50)  NOT NULL,
    description     VARCHAR(255) NULL,
    CONSTRAINT pk_profil         PRIMARY KEY (id_profil),
    CONSTRAINT uq_profil_libelle UNIQUE (libelle_profil)
) ENGINE=InnoDB;


-- =====================================================================
-- 2. PERSONNEL ET HABILITATIONS
--    Bloc concu pour etre reutilise tel quel par la seconde application
--    du projet : il ne reference aucune table metier de la pre-admission.
-- =====================================================================

-- Fiche d'un membre du personnel. Elle survit a la desactivation de son
-- compte (US-10) : on ne supprime jamais la fiche, on la date en sortie.
CREATE TABLE PERSONNEL (
    id_personnel    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom             VARCHAR(50)  NOT NULL,
    prenom          VARCHAR(50)  NOT NULL,
    email_pro       VARCHAR(100) NULL,
    telephone_pro   VARCHAR(15)  NULL,
    fonction        VARCHAR(100) NULL,
    date_entree     DATE         NOT NULL,
    date_sortie     DATE         NULL,
    id_service      INT UNSIGNED NOT NULL,
    CONSTRAINT pk_personnel         PRIMARY KEY (id_personnel),
    CONSTRAINT uq_personnel_email   UNIQUE (email_pro),
    CONSTRAINT fk_personnel_service FOREIGN KEY (id_service)
        REFERENCES SERVICE (id_service)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ck_personnel_dates   CHECK (date_sortie IS NULL OR date_sortie >= date_entree)
) ENGINE=InnoDB;

CREATE INDEX ix_personnel_service ON PERSONNEL (id_service);
CREATE INDEX ix_personnel_nom     ON PERSONNEL (nom, prenom);

-- Compte de connexion. Separe de PERSONNEL pour que la desactivation
-- (actif = 0) bloque l'acces sans toucher a la fiche (US-10).
-- Un membre du personnel a au plus un compte.
CREATE TABLE COMPTE (
    id_compte               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    identifiant             VARCHAR(50)  NOT NULL,
    mot_de_passe_hash       VARCHAR(255) NOT NULL,
    date_creation           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actif                   TINYINT(1)   NOT NULL DEFAULT 1,
    date_derniere_connexion DATETIME     NULL,
    id_personnel            INT UNSIGNED NOT NULL,
    CONSTRAINT pk_compte             PRIMARY KEY (id_compte),
    CONSTRAINT uq_compte_identifiant UNIQUE (identifiant),
    CONSTRAINT uq_compte_personnel   UNIQUE (id_personnel),
    CONSTRAINT fk_compte_personnel   FOREIGN KEY (id_personnel)
        REFERENCES PERSONNEL (id_personnel)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Habilitation : rattache un profil a un membre du personnel pour une
-- periode donnee. Une suspension retire les droits sans supprimer la
-- ligne, une revocation est datee et motivee (US-11).
CREATE TABLE HABILITATION (
    id_habilitation  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    date_debut       DATE         NOT NULL,
    date_fin         DATE         NULL,
    statut           ENUM('ACTIVE','SUSPENDUE','REVOQUEE') NOT NULL DEFAULT 'ACTIVE',
    date_revocation  DATETIME     NULL,
    motif_revocation VARCHAR(255) NULL,
    id_personnel     INT UNSIGNED NOT NULL,
    id_profil        INT UNSIGNED NOT NULL,
    CONSTRAINT pk_habilitation           PRIMARY KEY (id_habilitation),
    CONSTRAINT fk_habilitation_personnel FOREIGN KEY (id_personnel)
        REFERENCES PERSONNEL (id_personnel)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_habilitation_profil    FOREIGN KEY (id_profil)
        REFERENCES PROFIL (id_profil)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ck_habilitation_dates     CHECK (date_fin IS NULL OR date_fin >= date_debut),
    CONSTRAINT ck_habilitation_revoc     CHECK (statut <> 'REVOQUEE' OR date_revocation IS NOT NULL)
) ENGINE=InnoDB;

CREATE INDEX ix_habilitation_personnel ON HABILITATION (id_personnel, statut);
CREATE INDEX ix_habilitation_profil    ON HABILITATION (id_profil);


-- =====================================================================
-- 3. PATIENT ET PRE-ADMISSION
-- =====================================================================

CREATE TABLE PATIENT (
    id_patient      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom_usage       VARCHAR(50)  NOT NULL,
    nom_naissance   VARCHAR(50)  NOT NULL,
    prenom          VARCHAR(50)  NOT NULL,
    date_naissance  DATE         NOT NULL,
    sexe            CHAR(1)      NOT NULL,
    adresse         VARCHAR(150) NOT NULL,
    code_postal     CHAR(5)      NOT NULL,
    ville           VARCHAR(50)  NOT NULL,
    telephone       VARCHAR(15)  NULL,
    email           VARCHAR(100) NULL,
    num_secu        VARCHAR(15)  NULL,
    CONSTRAINT pk_patient          PRIMARY KEY (id_patient),
    CONSTRAINT uq_patient_num_secu UNIQUE (num_secu),
    CONSTRAINT ck_patient_naissance CHECK (date_naissance < CURRENT_DATE),
    CONSTRAINT ck_patient_cp        CHECK (code_postal REGEXP '^[0-9]{5}$')
) ENGINE=InnoDB;

CREATE INDEX ix_patient_nom ON PATIENT (nom_usage, prenom);

-- Dossier de pre-admission. id_compte_createur trace l'auteur de la
-- saisie (US-02), id_compte_valideur et date_validation la validation
-- administrative (US-08).
CREATE TABLE PRE_ADMISSION (
    id_pre_admission            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    date_creation               DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_hospitalisation_prevue DATE         NOT NULL,
    motif                       VARCHAR(255) NOT NULL,
    statut                      ENUM('EN_ATTENTE','VALIDEE','REFUSEE','ANNULEE') NOT NULL DEFAULT 'EN_ATTENTE',
    statut_prise_en_charge      ENUM('EN_ATTENTE','VALIDEE','REFUSEE')           NOT NULL DEFAULT 'EN_ATTENTE',
    date_validation             DATETIME     NULL,
    id_patient                  INT UNSIGNED NOT NULL,
    id_service                  INT UNSIGNED NOT NULL,
    id_compte_createur          INT UNSIGNED NOT NULL,
    id_compte_valideur          INT UNSIGNED NULL,
    CONSTRAINT pk_pre_admission           PRIMARY KEY (id_pre_admission),
    CONSTRAINT fk_pre_admission_patient   FOREIGN KEY (id_patient)
        REFERENCES PATIENT (id_patient)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pre_admission_service   FOREIGN KEY (id_service)
        REFERENCES SERVICE (id_service)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pre_admission_createur  FOREIGN KEY (id_compte_createur)
        REFERENCES COMPTE (id_compte)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pre_admission_valideur  FOREIGN KEY (id_compte_valideur)
        REFERENCES COMPTE (id_compte)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    -- Une prise en charge validee porte toujours son valideur et sa date (US-08).
    CONSTRAINT ck_pre_admission_validation CHECK (
        statut_prise_en_charge <> 'VALIDEE'
        OR (id_compte_valideur IS NOT NULL AND date_validation IS NOT NULL)
    )
) ENGINE=InnoDB;

CREATE INDEX ix_pre_admission_patient ON PRE_ADMISSION (id_patient);
CREATE INDEX ix_pre_admission_service ON PRE_ADMISSION (id_service, statut);
CREATE INDEX ix_pre_admission_date    ON PRE_ADMISSION (date_hospitalisation_prevue);

-- Partie medicale, isolee dans sa propre table pour que la restriction
-- d'acces au profil medecin (US-05, US-06) porte sur un objet distinct.
CREATE TABLE VOLET_MEDICAL (
    id_volet             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    antecedents          TEXT         NULL,
    traitements_en_cours TEXT         NULL,
    avis_preoperatoire   TEXT         NULL,
    date_saisie          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_pre_admission     INT UNSIGNED NOT NULL,
    id_compte_auteur     INT UNSIGNED NOT NULL,
    CONSTRAINT pk_volet_medical          PRIMARY KEY (id_volet),
    CONSTRAINT uq_volet_pre_admission    UNIQUE (id_pre_admission),
    CONSTRAINT fk_volet_pre_admission    FOREIGN KEY (id_pre_admission)
        REFERENCES PRE_ADMISSION (id_pre_admission)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_volet_compte_auteur    FOREIGN KEY (id_compte_auteur)
        REFERENCES COMPTE (id_compte)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table de liaison PATIENT <-> ALLERGIE. C'est ce lien, et non la table
-- ALLERGIE seule, qui constitue la donnee de sante.
CREATE TABLE PATIENT_ALLERGIE (
    id_patient         INT UNSIGNED NOT NULL,
    id_allergie        INT UNSIGNED NOT NULL,
    precision_allergie VARCHAR(255) NULL,
    CONSTRAINT pk_patient_allergie          PRIMARY KEY (id_patient, id_allergie),
    CONSTRAINT fk_patient_allergie_patient  FOREIGN KEY (id_patient)
        REFERENCES PATIENT (id_patient)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_patient_allergie_allergie FOREIGN KEY (id_allergie)
        REFERENCES ALLERGIE (id_allergie)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table de liaison PATIENT <-> MUTUELLE : couverture complementaire.
CREATE TABLE PATIENT_MUTUELLE (
    id_patient       INT UNSIGNED NOT NULL,
    id_mutuelle      INT UNSIGNED NOT NULL,
    num_adherent     VARCHAR(30)  NOT NULL,
    date_fin_droits  DATE         NULL,
    CONSTRAINT pk_patient_mutuelle          PRIMARY KEY (id_patient, id_mutuelle),
    CONSTRAINT fk_patient_mutuelle_patient  FOREIGN KEY (id_patient)
        REFERENCES PATIENT (id_patient)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_patient_mutuelle_mutuelle FOREIGN KEY (id_mutuelle)
        REFERENCES MUTUELLE (id_mutuelle)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;


-- =====================================================================
-- 4. CHAMBRES ET SEJOURS
--    Base du calcul de taux d'occupation par service (US-09).
-- =====================================================================

CREATE TABLE CHAMBRE (
    id_chambre     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    numero_chambre VARCHAR(10)   NOT NULL,
    capacite       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    etat           ENUM('DISPONIBLE','MAINTENANCE','FERMEE') NOT NULL DEFAULT 'DISPONIBLE',
    id_service     INT UNSIGNED  NOT NULL,
    CONSTRAINT pk_chambre          PRIMARY KEY (id_chambre),
    -- Le numero de chambre n'est unique qu'au sein d'un service.
    CONSTRAINT uq_chambre_service  UNIQUE (id_service, numero_chambre),
    CONSTRAINT fk_chambre_service  FOREIGN KEY (id_service)
        REFERENCES SERVICE (id_service)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ck_chambre_capacite CHECK (capacite BETWEEN 1 AND 6)
) ENGINE=InnoDB;

-- Sejour effectif d'un patient dans une chambre.
-- date_sortie NULL = sejour en cours.
-- Le rattachement a une pre-admission est facultatif : une admission en
-- urgence produit un sejour sans pre-admission prealable.
CREATE TABLE SEJOUR (
    id_sejour        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    date_entree      DATETIME     NOT NULL,
    date_sortie      DATETIME     NULL,
    id_patient       INT UNSIGNED NOT NULL,
    id_chambre       INT UNSIGNED NOT NULL,
    id_pre_admission INT UNSIGNED NULL,
    CONSTRAINT pk_sejour               PRIMARY KEY (id_sejour),
    CONSTRAINT uq_sejour_pre_admission UNIQUE (id_pre_admission),
    CONSTRAINT fk_sejour_patient       FOREIGN KEY (id_patient)
        REFERENCES PATIENT (id_patient)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sejour_chambre       FOREIGN KEY (id_chambre)
        REFERENCES CHAMBRE (id_chambre)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sejour_pre_admission FOREIGN KEY (id_pre_admission)
        REFERENCES PRE_ADMISSION (id_pre_admission)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT ck_sejour_dates         CHECK (date_sortie IS NULL OR date_sortie >= date_entree)
) ENGINE=InnoDB;

-- Index dimensionne pour la requete de taux d'occupation : filtre sur
-- un intervalle de dates, regroupement par service via la chambre.
CREATE INDEX ix_sejour_periode ON SEJOUR (date_entree, date_sortie);
CREATE INDEX ix_sejour_chambre ON SEJOUR (id_chambre);
CREATE INDEX ix_sejour_patient ON SEJOUR (id_patient);


-- =====================================================================
-- 5. JOURNAL D'AUDIT
--    US-12 : consultable, non modifiable. Le compte applicatif ne doit
--    recevoir que INSERT et SELECT sur cette table (voir section 6).
-- =====================================================================

-- id_compte est NULL pour une tentative de connexion sur un identifiant
-- inexistant : identifiant_saisi conserve alors ce qui a ete tape.
-- type_objet / id_objet designent l'objet concerne sans cle etrangere :
-- une cle etrangere empecherait de journaliser un objet supprime, et le
-- journal doit survivre a ce qu'il trace.
CREATE TABLE JOURNAL_AUDIT (
    id_journal        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    date_heure        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    type_action       VARCHAR(50)  NOT NULL,
    resultat          ENUM('SUCCES','ECHEC','REFUS') NOT NULL,
    identifiant_saisi VARCHAR(50)  NULL,
    adresse_ip        VARCHAR(45)  NULL,
    type_objet        VARCHAR(30)  NULL,
    id_objet          INT UNSIGNED NULL,
    id_compte         INT UNSIGNED NULL,
    id_patient        INT UNSIGNED NULL,
    CONSTRAINT pk_journal_audit    PRIMARY KEY (id_journal),
    CONSTRAINT fk_journal_compte   FOREIGN KEY (id_compte)
        REFERENCES COMPTE (id_compte)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_journal_patient  FOREIGN KEY (id_patient)
        REFERENCES PATIENT (id_patient)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX ix_journal_date    ON JOURNAL_AUDIT (date_heure);
CREATE INDEX ix_journal_compte  ON JOURNAL_AUDIT (id_compte, date_heure);
CREATE INDEX ix_journal_patient ON JOURNAL_AUDIT (id_patient, date_heure);
CREATE INDEX ix_journal_action  ON JOURNAL_AUDIT (type_action, date_heure);


-- =====================================================================
-- 6. COMPTE APPLICATIF (a executer par le groupe SISR)
-- =====================================================================
-- L'application ne se connecte jamais en root. Le compte applicatif a
-- les droits courants sur la base, mais seulement INSERT et SELECT sur
-- le journal d'audit : c'est ce qui rend les journaux non modifiables
-- au sens de l'US-12, au niveau du SGBD et pas seulement de l'IHM.
--
-- Remplacer <mot_de_passe> et l'hote par les valeurs retenues avec le
-- groupe SISR. L'hote doit etre le serveur applicatif, pas '%'.
--
-- CREATE USER 'app_lpfh'@'192.168.20.%' IDENTIFIED BY '<mot_de_passe>';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON lpfh.* TO 'app_lpfh'@'192.168.20.%';
-- REVOKE UPDATE, DELETE ON lpfh.JOURNAL_AUDIT FROM 'app_lpfh'@'192.168.20.%';
-- FLUSH PRIVILEGES;
-- =====================================================================
