# Écrans de test des utilisateurs — AP « Pré-admission LPFH »

Socle de l'incrément 1 (Sprint 3) : connexion par profil + contrôle d'accès
de chaque écran, branchés sur `sql/01_structure_socle.sql` et
`sql/02_jeu_essai_socle.sql`. Mot de passe de tous les comptes de test :
`Lpfh2026!`.

## Lancer

Pré-requis : base `clinique` importée (scripts 01 puis 02), MariaDB 10.11
sur le port 3307 (voir `CONNEXION-BDD.md`). Réglages dans `config.php`.

Depuis le dossier `clinique`, avec le PHP de WAMP :

    C:\wamp64\bin\php\php8.x.x\php.exe -S localhost:8000 -t test

puis ouvrir <http://localhost:8000>. (Ou copier le dossier `test` dans
`C:\wamp64\www\` et ouvrir <http://localhost/test>.)

## Écrans

| Fichier | Rôle | US |
|---|---|---|
| `index.php` | Connexion. Même message pour tous les refus. | US-01 |
| `choix-profil.php` | Comptes à plusieurs profils (chefs de service = MEDECIN + CHEF_SERVICE). Droits = profil actif seul. | US-01 |
| `accueil.php` | Menu construit selon le profil ; écrans interdits affichés hachurés. | — |
| `preadmissions.php` | Liste réelle. Secrétaire / médecin : leur service ; administratif : toute la clinique ; mutuelle masquée au médecin. | US-04, US-05, US-07 |
| `comptes.php` | Comptes + habilitations, lecture seule. DSI uniquement. | US-10, US-11 |
| `page.php?p=…` | Écrans pas encore codés : le 403 est déjà actif, l'écran d'attente s'affiche pour le bon profil. | — |
| `recette.php` | Recette automatique : 64 tests (connexion, matrice d'accès, périmètre des données). **Outil de dev.** | Mission 5 |

## Règles appliquées

- Les droits sont lus dans la vue `V_CONNEXION` : compte désactivé,
  personnel sorti, habilitation suspendue / révoquée / expirée = refus.
- L'utilisateur est **relu en base à chaque clic** : une suspension
  ou une révocation faite par la DSI coupe la session immédiatement (US-11).
- Une seule table de droits dans le code : `inc/droits.php` (PAGES).
  Chaque page appelle `exiger_page('<code>')` → 403 sinon, y compris par URL directe.
- Les résultats attendus de `recette.php` sont écrits à la main à partir
  de la matrice des rôles, pas recopiés de `inc/droits.php`.
- Mots de passe : `password_verify()` (bcrypt), requêtes préparées,
  `session_regenerate_id()` à la connexion, jeton CSRF, sortie échappée.

## Avant la mise en recette / production

- `MODE_TEST = false` dans `config.php` (cache les comptes de test et `recette.php`).
- Compte MariaDB `app_lpfh` fourni par le SISR à la place de `charle`.
- Journalisation des connexions / consultations : à brancher quand
  `JOURNAL_AUDIT` existera (script 06) — repérée par des `TODO` / mentions dans le code.
