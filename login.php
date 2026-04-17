<?php
// ============================================================
//  login.php — Inicio de sesión con hashes bcrypt
// ============================================================
require_once __DIR__ . '/config.php';
define('PAGE_TITLE', 'Iniciar Sesión');

// Redirigir si ya está logueado
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    // ── Validación básica ───────────────────────────────────
    if (empty($email))    $errors[] = 'El correo es requerido.';
    if (empty($password)) $errors[] = 'La contraseña es requerida.';

    if (empty($errors)) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // ── Sesión iniciada ─────────────────────────────
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['rol']        = $user['rol'];

            setFlash('success', '¡Bienvenido de vuelta, ' . $user['username'] . '!');

            $redirect = $_GET['redirect'] ?? ($user['rol'] === 'admin' ? '/admin/index.php' : '/index.php');
            header('Location: ' . SITE_URL . $redirect);
            exit;
        } else {
            $errors[] = 'Correo o contraseña incorrectos.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<main class="auth-page">
  <div class="auth-card">

    <!-- Vinyl deco -->
    <div style="position:absolute;top:-30px;right:-30px;width:80px;height:80px;opacity:0.15;
                border-radius:50%;border:12px solid var(--gorillaz);animation:spin-slow 20s linear infinite;"></div>

    <h1 class="auth-title">ENTRAR</h1>
    <p class="auth-subtitle">Accede a tu cuenta de Vinilos Store</p>

    <?php if (!empty($errors)): ?>
      <div class="flash flash-error">✗ <?= h(implode(' ', $errors)) ?></div>
    <?php endif; ?>

    <form method="POST" action="">

      <div class="form-group">
        <label class="form-label" for="email">Correo Electrónico</label>
        <input
          type="email"
          id="email"
          name="email"
          class="form-control"
          placeholder="tu@correo.com"
          value="<?= h($_POST['email'] ?? '') ?>"
          autocomplete="email"
          required
        >
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Contraseña</label>
        <input
          type="password"
          id="password"
          name="password"
          class="form-control"
          placeholder="••••••••"
          autocomplete="current-password"
          required
        >
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:0.5rem;">
        Iniciar Sesión
      </button>
    </form>

    <hr class="divider">

    <p style="font-size:12px;text-align:center;color:var(--text-muted);">
      ¿No tienes cuenta?
      <a href="<?= SITE_URL ?>/register.php" style="color:var(--gorillaz);">Regístrate gratis</a>
    </p>

    <!-- Credenciales de prueba -->
    <div style="margin-top:1.5rem;padding:1rem;background:rgba(205,255,0,0.05);
                border:1px solid rgba(205,255,0,0.2);border-radius:var(--radius);font-size:11px;">
      <p style="color:var(--gorillaz);letter-spacing:0.1em;text-transform:uppercase;margin-bottom:0.5rem;">
        💡 Credenciales de prueba
      </p>
      <p style="color:var(--text-muted);">
        <strong style="color:var(--text);">Admin:</strong> admin@vinilosstore.com / admin123<br>
        <strong style="color:var(--text);">Cliente:</strong> juan@example.com / cliente123
      </p>
    </div>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
