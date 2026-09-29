-- =====================================================================
-- AP « Pre-admission LPFH » - 00 - Remise a zero de la base
-- A N'UTILISER QU'EN DEVELOPPEMENT : supprime toutes les tables du
-- projet et leurs donnees, pour pouvoir rejouer 01, 02, 03...
-- A COMPLETER a chaque nouveau script : ajouter ici les tables creees.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW  IF EXISTS V_CONNEXION;

-- Tables des scripts suivants (deja listees pour ne pas les oublier)
DROP TABLE IF EXISTS JOURNAL_AUDIT;
DROP TABLE IF EXISTS SEJOUR;
DROP TABLE IF EXISTS CHAMBRE;
DROP TABLE IF EXISTS PATIENT_ALLERGIE;
DROP TABLE IF EXISTS ALLERGIE;
DROP TABLE IF EXISTS VOLET_MEDICAL;

-- Tables du script 01
DROP TABLE IF EXISTS PRE_ADMISSION;
DROP TABLE IF EXISTS PATIENT_MUTUELLE;
DROP TABLE IF EXISTS PATIENT;
DROP TABLE IF EXISTS HABILITATION;
DROP TABLE IF EXISTS COMPTE;
DROP TABLE IF EXISTS PERSONNEL;
DROP TABLE IF EXISTS MUTUELLE;
DROP TABLE IF EXISTS PROFIL;
DROP TABLE IF EXISTS SERVICE;

SET FOREIGN_KEY_CHECKS = 1;
