-- =====================================================================
-- AP « Pre-admission LPFH » - Groupe Developpeurs (SLAM)
-- 01 - Structure du socle : connexion par profil + creation d'une
--      pre-admission par la secretaire medicale (increment 1, Sprint 3)
-- SGBD cible : MariaDB 10.11
-- Version : v2 du 29/09/2026 (extrait du script complet v1 du 22/09)
-- =====================================================================
-- A EXECUTER DANS LA BASE DEJA SELECTIONNEE (lpfh sur le serveur,
-- clinique en local) : le script ne cree pas la base lui-meme, pour
-- fonctionner avec un compte qui n'a pas le droit CREATE DATABASE.
--
-- Decoupage prevu (un fichier par increment, jamais de modification
-- d'un fichier deja passe sur le serveur : on AJOUTE un fichier) :
--   00_reset.sql                 vide la base (dev uniquement)
--   01_structure_socle.sql       CE FICHIER
--   02_jeu_essai_socle.sql       donnees de test du socle
--   03_...                       volet medical + allergies (Sprint 4)
--   04_...                       validation administrative (Sprint 4)
--   05_...                       chambres, sejours, taux d'occupation
--   06_...                       journal d'audit
-- Les fichiers suivants ajouteront leurs tables et leurs cles
-- etrangeres vers celles-ci (ALTER TABLE ... ADD CONSTRAINT), sans
-- toucher aux tables existantes.
-- =====================================================================

SET NAMES utf8mb4;


-- =====================================================================
-- 1. REFERENTIELS
-- =====================================================================

-- Services de la clinique. type_service distingue les services de soins
-- (proposes a la creation d'une pre-admission) des services support,
-- qui n'accueillent pas de patient mais hebergent du personnel.
CREATE TABLE SERVICE (
    id_service       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    libelle_service  VARCHAR(50)  NOT NULL,
    type_service     ENUM('MEDICAL','ADMINISTRATIF','TECHNIQUE','DIRECTION') NOT NULL DEFAULT 'MEDICAL',
    CONSTRAINT pk_service         PRIMARY KEY (id_service),
    CONSTRAINT uq_service_libelle UNIQUE (libelle_service)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profils applicatifs. libelle_profil sert de code dans le PHP :
-- SECRETAIRE_MEDICALE, MEDECIN, CHEF_SERVICE, ADMINISTRATIF,
-- INFORMATICIEN_ADMIN.
CREATE TABLE PROFIL (
    id_profil       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    libelle_profil  VARCHAR(50)  NOT NULL,
    description     VARCHAR(255) NULL,
    CONSTRAINT pk_profil         PRIMARY KEY (id_profil),
    CONSTRAINT uq_profil_libelle UNIQUE (libelle_profil)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Organismes complementaires.
CREATE TABLE MUTUELLE (
    id_mutuelle   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom_mutuelle  VARCHAR(100) NOT NULL,
    CONSTRAINT pk_mutuelle     PRIMARY KEY (id_mutuelle),
    CONSTRAINT uq_mutuelle_nom UNIQUE (nom_mutuelle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- 2. PERSONNEL, COMPTES ET HABILITATIONS
--    Bloc commun aux deux applications du projet : il ne reference
--    aucune table metier de la pre-admission.
-- =====================================================================

-- Fiche d'un membre du personnel. On ne la supprime jamais : on la
-- date en sortie (date_sortie).
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX ix_personnel_service ON PERSONNEL (id_service);
CREATE INDEX ix_personnel_nom     ON PERSONNEL (nom, prenom);

-- Compte de connexion, separe de PERSONNEL : passer actif a 0 bloque
-- l'acces sans toucher a la fiche. Un membre du personnel a au plus
-- un compte. mot_de_passe_hash contient le resultat de password_hash()
-- en PHP (bcrypt, 60 caracteres) : JAMAIS de mot de passe en clair.
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
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ck_compte_actif       CHECK (actif IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Habilitation : rattache un profil a un membre du personnel pour une
-- periode donnee. Une personne peut en avoir plusieurs (ex. un chef de
-- service est MEDECIN + CHEF_SERVICE). Suspension = droits retires sans
-- supprimer la ligne ; revocation = datee et motivee.
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX ix_habilitation_personnel ON HABILITATION (id_personnel, statut);
CREATE INDEX ix_habilitation_profil    ON HABILITATION (id_profil);


-- =====================================================================
-- 3. PATIENT ET PRE-ADMISSION
-- =====================================================================

-- La regle « date de naissance dans le passe » est controlee en PHP :
-- MariaDB refuse CURRENT_DATE dans un CHECK (erreur 1901).
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
    CONSTRAINT pk_patient           PRIMARY KEY (id_patient),
    CONSTRAINT uq_patient_num_secu  UNIQUE (num_secu),
    CONSTRAINT ck_patient_sexe      CHECK (sexe IN ('M', 'F')),
    CONSTRAINT ck_patient_naissance CHECK (date_naissance >= '1900-01-01'),
    CONSTRAINT ck_patient_cp        CHECK (code_postal REGEXP '^[0-9]{5}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX ix_patient_nom ON PATIENT (nom_usage, prenom);

-- Couverture complementaire du patient (donnee administrative saisie
-- par la secretaire a la creation de la pre-admission).
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dossier de pre-admission. id_compte_createur trace qui a saisi.
-- Les colonnes de validation administrative (statut_prise_en_charge,
-- id_compte_valideur, date_validation) seront AJOUTEES par le script 04.
CREATE TABLE PRE_ADMISSION (
    id_pre_admission            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    date_creation               DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_hospitalisation_prevue DATE         NOT NULL,
    motif                       VARCHAR(255) NOT NULL,
    statut                      ENUM('EN_ATTENTE','VALIDEE','REFUSEE','ANNULEE') NOT NULL DEFAULT 'EN_ATTENTE',
    id_patient                  INT UNSIGNED NOT NULL,
    id_service                  INT UNSIGNED NOT NULL,
    id_compte_createur          INT UNSIGNED NOT NULL,
    CONSTRAINT pk_pre_admission          PRIMARY KEY (id_pre_admission),
    CONSTRAINT fk_pre_admission_patient  FOREIGN KEY (id_patient)
        REFERENCES PATIENT (id_patient)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pre_admission_service  FOREIGN KEY (id_service)
        REFERENCES SERVICE (id_service)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pre_admission_createur FOREIGN KEY (id_compte_createur)
        REFERENCES COMPTE (id_compte)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX ix_pre_admission_patient ON PRE_ADMISSION (id_patient);
CREATE INDEX ix_pre_admission_service ON PRE_ADMISSION (id_service, statut);
CREATE INDEX ix_pre_admission_date    ON PRE_ADMISSION (date_hospitalisation_prevue);


-- =====================================================================
-- 4. VUE DE CONNEXION
--    Une ligne par profil ACTUELLEMENT utilisable par un compte.
--    Le PHP de login fait :
--      SELECT * FROM V_CONNEXION WHERE identifiant = ?
--    puis password_verify($mdp, mot_de_passe_hash).
--    Aucune ligne = compte inconnu, desactive, personnel sorti ou
--    aucune habilitation valide : dans tous les cas, acces refuse.
--    Plusieurs lignes = plusieurs profils (ex. MEDECIN + CHEF_SERVICE).
--    Les regles d'acces vivent ici et non dans chaque page PHP : les
--    applications 1 et 2 appelleront la meme vue.
-- =====================================================================

CREATE VIEW V_CONNEXION AS
SELECT
    c.id_compte,
    c.identifiant,
    c.mot_de_passe_hash,
    p.id_personnel,
    p.nom,
    p.prenom,
    p.id_service,
    pr.id_profil,
    pr.libelle_profil
FROM COMPTE c
JOIN PERSONNEL    p  ON p.id_personnel = c.id_personnel
JOIN HABILITATION h  ON h.id_personnel = p.id_personnel
JOIN PROFIL       pr ON pr.id_profil   = h.id_profil
WHERE c.actif = 1
  AND (p.date_sortie IS NULL OR p.date_sortie > CURDATE())
  AND h.statut = 'ACTIVE'
  AND h.date_debut <= CURDATE()
  AND (h.date_fin IS NULL OR h.date_fin >= CURDATE());


-- =====================================================================
-- 5. COMPTE MARIADB DE L'APPLICATION (a faire par le groupe SISR)
-- =====================================================================
-- Le PHP ne se connecte jamais en root. A remplacer : <mot_de_passe>
-- et l'hote (le serveur web, pas '%').
--
-- CREATE USER 'app_lpfh'@'192.168.20.%' IDENTIFIED BY '<mot_de_passe>';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON lpfh.* TO 'app_lpfh'@'192.168.20.%';
-- FLUSH PRIVILEGES;
--
-- Quand le journal d'audit sera ajoute (script 06), on retirera
-- UPDATE et DELETE sur JOURNAL_AUDIT a ce compte.
-- =====================================================================
