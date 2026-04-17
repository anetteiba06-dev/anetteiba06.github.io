<?php
// ============================================================
//  register.php — Registro con contraseña cifrada (bcrypt)
// ============================================================
require_once __DIR__ . '/config.php';
define('PAGE_TITLE', 'Crear Cuenta');

if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = $_POST['password']       ?? '';
    $password2 = $_POST['password2']      ?? '';

    // ── Validaciones ────────────────────────────────────────
    if (strlen($username) < 3)
        $errors[] = 'El usuario debe tener al menos 3 caracteres.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'El correo no es válido.';
    if (strlen($password) < 8)
        $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($password !== $password2)
        $errors[] = 'Las contraseñas no coinciden.';

    if (empty($errors)) {
        $db = getDB();

        // Verificar unicidad
        $check = $db->prepare("SELECT id FROM usuarios WHERE email = ? OR username = ?");
        $check->execute([$email, $username]);
        if ($check->fetch()) {
            $errors[] = 'El correo o nombre de usuario ya está en uso.';
        }
    }

    if (empty($errors)) {
        $db   = getDB();
        // ── Hash bcrypt (cost=12) ────────────────────────────
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $db->prepare(
            "INSERT INTO usuarios (username, email, password, rol) VALUES (?, ?, ?, 'cliente')"
        );
        $stmt->execute([$username, $email, $hash]);

        $userId = $db->lastInsertId();

        // Auto-login
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $userId;
        $_SESSION['username']   = $username;
        $_SESSION['rol']        = 'cliente';

        setFlash('success', '¡Cuenta creada! Bienvenido, ' . $username . '.');
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

include __DIR__ . '/includes/header.php';
?>

<main class="auth-page">
  <div class="auth-card">

    <div style="position:absolute;top:-30px;right:-30px;width:80px;height:80px;opacity:0.15;
                border-radius:50%;border:12px solid var(--twice);animation:spin-slow 20s linear infinite reverse;"></div>

    <h1 class="auth-title">UNIRSE</h1>
    <p class="auth-subtitle">Crea tu cuenta en Vinilos Store</p>

    <?php if (!empty($errors)): ?>
      <div class="flash flash-error">
        ✗ <?= h(implode('<br>✗ ', $errors)) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">

      <div class="form-group">
        <label class="form-label" for="username">Nombre de Usuario</label>
        <input
          type="text"
          id="username"
          name="username"
          class="form-control"
          placeholder="rockvinyl_fan"
          value="<?= h($_POST['username'] ?? '') ?>"
          minlength="3"
          maxlength="50"
          required
        >
      </div>

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
        <label class="form-label" for="password">
          Contraseña
          <span style="color:#555;font-size:10px;margin-left:0.5rem;">(mín. 8 caracteres)</span>
        </label>
        <input
          type="password"
          id="password"
          name="password"
          class="form-control"
          placeholder="••••••••"
          minlength="8"
          required
        >
      </div>

      <div class="form-group">
        <label class="form-label" for="password2">Confirmar Contraseña</label>
        <input
          type="password"
          id="password2"
          name="password2"
          class="form-control"
          placeholder="••••••••"
          minlength="8"
          required
        >
      </div>

      <!-- Indicador de fortaleza de contraseña -->
      <div style="margin-bottom:1rem;">
        <div style="font-size:10px;letter-spacing:0.1em;text-transform:uppercase;
                    color:var(--text-muted);margin-bottom:0.4rem;">
          Fortaleza de contraseña
        </div>
        <div id="strength-bar" style="height:3px;background:var(--border);border-radius:2px;transition:all 0.3s;">
          <div id="strength-fill" style="height:100%;width:0%;border-radius:2px;transition:all 0.3s;"></div>
        </div>
        <div id="strength-label" style="font-size:10px;color:var(--text-muted);margin-top:0.25rem;"></div>
      </div>

      <button type="submit" class="btn btn-twice btn-block btn-lg" style="margin-top:0.5rem;">
        Crear Cuenta
      </button>
    </form>

    <hr class="divider">

    <p style="font-size:12px;text-align:center;color:var(--text-muted);">
      ¿Ya tienes cuenta?
      <a href="<?= SITE_URL ?>/login.php" style="color:var(--twice);">Inicia sesión</a>
    </p>

  </div>
</main>

<script>
// Indicador de fortaleza de contraseña
const pwInput   = document.getElementById('password');
const bar       = document.getElementById('strength-fill');
const label     = document.getElementById('strength-label');

pwInput.addEventListener('input', () => {
  const pw = pwInput.value;
  let score = 0;
  if (pw.length >= 8)  score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;

  const levels = [
    { pct: '0%',   color: 'var(--border)',   text: '' },
    { pct: '25%',  color: 'var(--danger)',   text: 'Débil' },
    { pct: '50%',  color: 'var(--warning)',  text: 'Regular' },
    { pct: '75%',  color: 'var(--joji)',     text: 'Buena' },
    { pct: '100%', color: 'var(--success)',  text: 'Excelente' },
  ];

  bar.style.width           = levels[score].pct;
  bar.style.backgroundColor = levels[score].color;
  label.textContent         = levels[score].text;
  label.style.color         = levels[score].color;
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
