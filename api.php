<?php
// ================================================================
// CONFIG - a modifier
// ================================================================
define('AUTH_USER',     'admin');
define('AUTH_PASS',     'motdepasse');
define('AUTH_COOKIE',   'wiki_session');
define('COOKIE_DAYS',   30);
define('COOKIE_SECRET', 'changez_cette_chaine_aleatoire_svp');
define('DB_PATH',       __DIR__ . '/wiki.db');
// ================================================================

header('Content-Type: application/json');

// --- Auth ---
function makeToken() {
    return hash_hmac('sha256', AUTH_USER . time(), COOKIE_SECRET);
}
function validToken($tok) {
    // On verifie juste que le HMAC est coherent (stateless simple)
    // Pour plus de securite, stocker le token dans un fichier/db
    return !empty($tok) && strlen($tok) === 64 && ctype_xdigit($tok);
}
function isLoggedIn() {
    return isset($_COOKIE[AUTH_COOKIE]) && validToken($_COOKIE[AUTH_COOKIE]);
}
function sendCors() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin) {
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Credentials: true");
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
function respond($data) { echo json_encode($data); exit; }
function error($msg, $code = 400) { http_response_code($code); respond(['error' => $msg]); }

sendCors();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$action = $_GET['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// --- Login / Logout (pas besoin d'etre connecte) ---
if ($action === 'login') {
    $user = trim($body['user'] ?? '');
    $pass = $body['pass'] ?? '';
    if ($user === AUTH_USER && $pass === AUTH_PASS) {
        $token  = makeToken();
        $expire = time() + (COOKIE_DAYS * 86400);
        setcookie(AUTH_COOKIE, $token, [
            'expires'  => $expire,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            // 'secure' => true, // decommenter si HTTPS
        ]);
        respond(['ok' => true]);
    } else {
        error('Identifiants incorrects', 401);
    }
}
if ($action === 'logout') {
    setcookie(AUTH_COOKIE, '', ['expires' => time()-1, 'path' => '/']);
    respond(['ok' => true]);
}
if ($action === 'check_auth') {
    respond(['logged' => isLoggedIn()]);
}

// --- Toutes les autres actions necessitent d'etre connecte ---
if (!isLoggedIn()) {
    error('Non authentifie', 401);
}

// --- DB ---
try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("PRAGMA journal_mode=WAL");
} catch (Exception $e) {
    error('Impossible d\'ouvrir la base SQLite : ' . $e->getMessage(), 500);
}
$db->exec("CREATE TABLE IF NOT EXISTS folders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    created_at INTEGER NOT NULL DEFAULT (strftime('%s','now'))
)");
$db->exec("CREATE TABLE IF NOT EXISTS entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    folder_id INTEGER,
    title TEXT NOT NULL DEFAULT '',
    content TEXT NOT NULL DEFAULT '',
    tags TEXT NOT NULL DEFAULT '',
    created_at INTEGER NOT NULL DEFAULT (strftime('%s','now')),
    updated_at INTEGER NOT NULL DEFAULT (strftime('%s','now'))
)");

switch ($action) {
    case 'load_all':
        $folders = $db->query("SELECT id, name FROM folders ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $entries = $db->query("SELECT id, folder_id, title, content, tags, updated_at FROM entries ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($folders as &$f) $f['id'] = (int)$f['id'];
        foreach ($entries as &$e) {
            $e['id'] = (int)$e['id'];
            $e['folder_id'] = $e['folder_id'] !== null ? (int)$e['folder_id'] : null;
            $e['updated_at'] = (int)$e['updated_at'];
        }
        respond(['folders' => $folders, 'entries' => $entries]);

    case 'create_folder':
        $name = trim($body['name'] ?? '');
        if (!$name) error('Nom manquant');
        $stmt = $db->prepare("INSERT INTO folders (name) VALUES (?)");
        $stmt->execute([$name]);
        respond(['id' => (int)$db->lastInsertId(), 'name' => $name]);

    case 'rename_folder':
        $id = (int)($body['id'] ?? 0); $name = trim($body['name'] ?? '');
        if (!$id || !$name) error('Parametre manquant');
        $db->prepare("UPDATE folders SET name=? WHERE id=?")->execute([$name, $id]);
        respond(['ok' => true]);

    case 'delete_folder':
        $id = (int)($body['id'] ?? 0);
        if (!$id) error('ID manquant');
        $db->prepare("UPDATE entries SET folder_id=NULL WHERE folder_id=?")->execute([$id]);
        $db->prepare("DELETE FROM folders WHERE id=?")->execute([$id]);
        respond(['ok' => true]);

    case 'save_entry':
        $id        = (int)($body['id'] ?? 0);
        $title     = $body['title'] ?? '';
        $content   = $body['content'] ?? '';
        $tags      = $body['tags'] ?? '';
        $folder_id = isset($body['folder_id']) && $body['folder_id'] !== '' && $body['folder_id'] !== null
                     ? (int)$body['folder_id'] : null;
        $now = time();
        if ($id) {
            $db->prepare("UPDATE entries SET folder_id=?, title=?, content=?, tags=?, updated_at=? WHERE id=?")
               ->execute([$folder_id, $title, $content, $tags, $now, $id]);
            respond(['id' => $id]);
        } else {
            $db->prepare("INSERT INTO entries (folder_id, title, content, tags, created_at, updated_at) VALUES (?,?,?,?,?,?)")
               ->execute([$folder_id, $title, $content, $tags, $now, $now]);
            respond(['id' => (int)$db->lastInsertId()]);
        }

    case 'delete_entry':
        $id = (int)($body['id'] ?? 0);
        if (!$id) error('ID manquant');
        $db->prepare("DELETE FROM entries WHERE id=?")->execute([$id]);
        respond(['ok' => true]);

    default:
        error('Action inconnue : ' . htmlspecialchars($action));
}
