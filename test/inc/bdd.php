<?php
require_once __DIR__ . '/../config.php';

/** Connexion PDO unique pour toute la requête. */
function bdd(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/** Échappement HTML court, à utiliser sur TOUT ce qui vient de la base ou de l'utilisateur. */
function e(?string $texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}

/** Date SQL (AAAA-MM-JJ) vers JJ/MM/AAAA. */
function date_fr(?string $date): string
{
    if (!$date) {
        return '';
    }
    $d = DateTime::createFromFormat('Y-m-d', substr($date, 0, 10));
    return $d ? $d->format('d/m/Y') : $date;
}
