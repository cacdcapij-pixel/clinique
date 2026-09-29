# Environnement base de données — projet clinique LPFH

## Serveur local

| | |
|---|---|
| SGBD | MariaDB **10.11.19** (même branche LTS que le serveur de déploiement) |
| Hôte | `127.0.0.1` |
| **Port** | **3307** (pas 3306 : ce port est pris par MySQL 8.0) |
| Service Windows | `MariaDB1011` (démarrage automatique) |
| Base | `clinique` — `utf8mb4` / `utf8mb4_unicode_ci` |
| Utilisateur applicatif | `charle` / `charle` (tous droits sur `clinique`) |
| Compte root | `root` / `root` |
| Binaires | `C:\Program Files\MariaDB 10.11\bin` (ajouté au PATH) |

## Pourquoi une installation séparée et pas le MariaDB de WAMP

WAMP embarque MariaDB **11.4.9**. Depuis la 11.4, MariaDB crée par défaut ses
tables avec les collations `utf8mb4_uca1400_*`, **qui n'existent pas en 10.11**.
Un dump produit en 11.4 échouerait donc à l'import sur le serveur de la clinique.
Travailler directement en 10.11 supprime cette classe de problèmes.

## Se connecter

### En ligne de commande (nouveau terminal)

    mysql -u charle -p --port=3307 --host=127.0.0.1 clinique

### Avec phpMyAdmin (WAMP)

Démarre WAMP, puis ouvre <http://localhost/phpmyadmin>.
Un serveur **« MariaDB 10.11 - clinique »** a été ajouté dans le sélecteur
de la page de connexion — choisis-le, puis `charle` / `charle`.

Config modifiée : `C:\wamp64\apps\phpmyadmin5.2.3\config.inc.php`
(sauvegarde : `config.inc.php.bak`).

### Depuis du code PHP

    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3307;dbname=clinique;charset=utf8mb4',
        'charle',
        'charle',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

## Exporter vers le serveur de la clinique

    mysqldump -u charle -p --port=3307 --host=127.0.0.1 ^
      --default-character-set=utf8mb4 clinique > clinique.sql

## Remarques

- `MariaDB1011` (3307) et `MySQL80` (3306) tournent en parallèle sans conflit.
- Ne démarre pas les services MariaDB/MySQL de WAMP en même temps que MySQL 8.0 :
  ils se disputeraient le port 3306. Apache seul suffit pour phpMyAdmin.
- `docker-compose.yml` et `.env` sont restés dans ce dossier : ils montent la même
  stack (MariaDB 10.11 + Adminer) si Docker Desktop est réparé un jour. Inutilisés
  pour l'instant, le backend Docker de cette machine ne démarre plus depuis mai 2026.
- Le dossier `sql/` sert aux scripts de création de tables et au jeu d'essai.
