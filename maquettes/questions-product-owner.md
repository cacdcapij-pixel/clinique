# Questions ouvertes pour le Product Owner
AP « Pré-admission LPFH » — Sprint 2 / Mission 3 — Séance 3 (22/09/2026)

## Parcours
1. La secrétaire peut-elle enregistrer une pré-admission **incomplète** (brouillon), ou la saisie doit-elle être validée en une fois ?
2. Qui déclenche l'affectation du dossier à un médecin : la secrétaire choisit le praticien, ou tout médecin du service voit les dossiers de son service ?
3. Une pré-admission peut-elle être **modifiée** après création par la secrétaire, et jusqu'à quel statut ?

## Champs et règles de gestion
4. Le numéro de sécurité sociale est-il obligatoire à la création, ou renseignable plus tard ?
5. Le motif d'hospitalisation est-il un texte libre ou doit-il venir d'un **catalogue d'actes** (comme en mission 6) ?
6. Délai minimum entre la date de saisie et la date d'hospitalisation prévue ? Faut-il un contrôle bloquant ?
7. Un patient sans mutuelle : quel comportement sur la prise en charge (bloquant, ou statut « à régulariser » ) ?
8. L'avis pré-opératoire est-il obligatoire pour que la pré-admission passe au statut VALIDEE ?

## Droits et RGPD
9. Le médecin doit-il voir les pré-admissions **de tout le service** ou seulement celles où il est désigné ?
10. Le chef de service a-t-il un accès en lecture sur le volet médical de son service dès l'application 1 ?
11. Les données masquées doivent-elles être **grisées et visibles** (l'utilisateur sait qu'elles existent) ou totalement **absentes** de l'écran ?
12. Durée de conservation des pré-admissions annulées / refusées ?

## Outil de maquettage
13. On reste sur des maquettes HTML/CSS versionnées sur le dépôt Git (réutilisables comme base d'intégration en mission 4), ou le PO attend-il du Figma/Balsamiq ?

## Nouvelles questions — Séance 4 (Mission 12-2)
Repérées dans la BDD socle et les ordres de mission. Statut : posées, réponse du PO à noter.

N1. Comment se calcule le taux d'occupation : lits occupés / lits disponibles, à partir des pré-admissions validées (prévisionnel) ou des séjours réels ? *(Tableau de bord d'occupation — la BDD socle n'a pas encore de table CHAMBRE ni SÉJOUR, prévues pour un script 05_)*
N2. Les colonnes de validation de la prise en charge seront-elles dans PRE_ADMISSION ou dans une table dédiée ? *(Validation de la prise en charge — colonnes reportées dans la BDD socle)*
N3. L'informaticien peut-il **supprimer** un compte, ou seulement le **révoquer** ? *(Gestion des comptes — HABILITATION n'a que ACTIVE, SUSPENDUE et REVOQUEE)*
N4. Le département administratif voit-il le motif d'hospitalisation ? Donnée médicale ou administrative ? *(Validation de la prise en charge — restriction RGPD à trancher)*
