<?php
// =====================================================================
// Authentification par profil (US-01) — s'appuie sur la vue V_CONNEXION
// du script 01_structure_socle.sql : si la vue ne renvoie rien, l'accès
// est refusé (compte inconnu, désactivé, personnel sorti, habilitation
// suspendue / révoquée / expirée), sans dire pourquoi à l'écran.
// =====================================================================
require_once __DIR__ . '/bdd.php';
require_once __DIR__ . '/droits.php';
require_once __DIR__ . '/layout.php';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict']);
session_start();

// Hash factice : on appelle password_verify() même si l'identifiant
// n'existe pas, pour que le temps de réponse ne révèle pas quels
// identifiants existent.
const HASH_FACTICE = '$2y$12$7TcVb5myUXSn1mSb7cd/gePSDxdjnLP10wHgDERragH891d4VfVxG';

/**
 * Vérifie identifiant + mot de passe.
 * Retourne les lignes de V_CONNEXION (une par profil utilisable),
 * ou un tableau vide si l'accès est refusé.
 */
function verifier_identifiants(string $identifiant, string $mdp): array
{
    $req = bdd()->prepare('SELECT * FROM V_CONNEXION WHERE identifiant = ? ORDER BY id_profil');
    $req->execute([$identifiant]);
    $lignes = $req->fetchAll();

    $hash = $lignes[0]['mot_de_passe_hash'] ?? HASH_FACTICE;
    $mdp_ok = password_verify($mdp, $hash);

    return ($lignes && $mdp_ok) ? $lignes : [];
}

/** Ouvre la session après une vérification réussie. */
function ouvrir_session(array $lignes): void
{
    session_regenerate_id(true); // contre la fixation de session
    $profils = array_column($lignes, 'libelle_profil');
    $_SESSION = [
        'id_compte'    => (int) $lignes[0]['id_compte'],
        'profil_actif' => count($profils) === 1 ? $profils[0] : null,
    ];
    bdd()->prepare('UPDATE COMPTE SET date_derniere_connexion = NOW() WHERE id_compte = ?')
         ->execute([$_SESSION['id_compte']]);
}

function fermer_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Utilisateur connecté, RELU EN BASE à chaque requête : une désactivation
 * ou une révocation faite par la DSI prend effet au clic suivant
 * (US-11 « une révocation prend effet immédiatement »).
 */
function utilisateur(): ?array
{
    if (empty($_SESSION['id_compte'])) {
        return null;
    }
    $req = bdd()->prepare(
        'SELECT v.id_compte, v.identifiant, v.nom, v.prenom, v.id_service,
                s.libelle_service, v.libelle_profil
         FROM V_CONNEXION v JOIN SERVICE s ON s.id_service = v.id_service
         WHERE v.id_compte = ? ORDER BY v.id_profil'
    );
    $req->execute([$_SESSION['id_compte']]);
    $lignes = $req->fetchAll();

    if (!$lignes) {           // droits retirés depuis la connexion
        fermer_session();
        return null;
    }
    $profils = array_column($lignes, 'libelle_profil');
    if ($_SESSION['profil_actif'] !== null && !in_array($_SESSION['profil_actif'], $profils, true)) {
        $_SESSION['profil_actif'] = null; // le profil choisi a été retiré
    }
    $u = $lignes[0];
    unset($u['libelle_profil']);
    $u['profils'] = $profils;
    $u['profil_actif'] = $_SESSION['profil_actif'];
    return $u;
}

/** Page réservée aux utilisateurs connectés ayant choisi un profil. */
function exiger_connexion(): array
{
    $u = utilisateur();
    if ($u === null) {
        header('Location: index.php?expire=1');
        exit;
    }
    if ($u['profil_actif'] === null) {
        header('Location: choix-profil.php');
        exit;
    }
    return $u;
}

/**
 * Contrôle d'accès d'un écran. Refus en 403 si le profil actif n'est pas
 * autorisé — y compris quand on tape l'URL à la main (US-05, US-10).
 */
function exiger_page(string $code_page): array
{
    $u = exiger_connexion();
    if (!peut_acceder($u['profil_actif'], $code_page)) {
        http_response_code(403);
        entete('Accès refusé', $u);
        $titre = PAGES[$code_page]['titre'] ?? $code_page;
        echo '<div class="erreur"><strong>Accès refusé (403).</strong> Le profil <strong>'
           . e(LIBELLES_PROFILS[$u['profil_actif']]) . '</strong> ne permet pas d\'ouvrir l\'écran « '
           . e($titre) . ' ».</div>';
        echo '<p><a href="accueil.php">Retour à l\'accueil</a></p>';
        pied();
        exit;
    }
    return $u;
}

// ---------------------------------------------------------------------
// Jeton CSRF pour les formulaires (connexion, choix du profil)
// ---------------------------------------------------------------------
function jeton_csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function verifier_csrf(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}
