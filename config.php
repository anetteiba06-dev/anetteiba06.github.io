<?php
// ============================================================
//  config.php — Configuración global y conexión a la BD
// ============================================================

// ── Configuración de la base de datos ──────────────────────
define('DB_HOST',     'localhost');
define('DB_USER',     'root');        // Usuario XAMPP por defecto
define('DB_PASS',     '');            // Contraseña XAMPP por defecto (vacía)
define('DB_NAME',     'vinilos_store');
define('DB_CHARSET',  'utf8mb4');

// ── Configuración general ───────────────────────────────────
define('SITE_NAME',   'VINILOS STORE');
define('SITE_URL',    'http://localhost/vinilos_store');
define('CURRENCY',    '$');

// ── Inicio de sesión ────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Conexión PDO ────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:monospace;background:#1a0000;color:#ff4444;padding:2rem;border-radius:8px;margin:2rem;">
                <strong>Error de Conexión:</strong><br>' . $e->getMessage() . '<br><br>
                Asegúrate de que XAMPP está corriendo y de haber importado <code>database.sql</code>.
            </div>');
        }
    }
    return $pdo;
}

// ── Helpers de autenticación ────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['usuario_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/index.php?error=acceso');
        exit;
    }
}

// ── Flash messages ──────────────────────────────────────────
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// ── Sanitización ────────────────────────────────────────────
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ── Conteo del carrito ──────────────────────────────────────
function cartCount(): int {
    if (!isLoggedIn()) return 0;
    $db = getDB();
    $stmt = $db->prepare("SELECT COALESCE(SUM(cantidad),0) FROM carrito WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    return (int) $stmt->fetchColumn();
}
?>
