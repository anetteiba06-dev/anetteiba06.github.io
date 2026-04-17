<?php
// ============================================================
//  index.php — Página de inicio
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/vinyl_svg.php';
define('PAGE_TITLE', 'Inicio');

$db = getDB();

// Productos destacados (últimos 4)
$featured = $db->query("
    SELECT p.*, a.nombre AS artista_nombre, a.color AS artista_color
    FROM productos p
    JOIN artistas a ON p.artista_id = a.id
    ORDER BY p.created_at DESC
    LIMIT 8
")->fetchAll();

// Conteo por artista
$artistCounts = $db->query("
    SELECT a.nombre, a.color, COUNT(p.id) AS total
    FROM artistas a
    LEFT JOIN productos p ON a.id = p.artista_id
    GROUP BY a.id
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- ── HERO ──────────────────────────────────────────────────── -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-vinyl-bg"></div>
  <div class="hero-content">
    <p class="hero-kicker">Tienda de vinilos de colección</p>
    <h1 class="hero-title">
      <span class="accent-g">GO</span>RI<br>
      <span class="accent-j">LL</span>AZ<br>
      <span style="font-size:50%;">×</span>
      <span class="accent-j">JO</span>JI<br>
      <span style="font-size:50%;">×</span>
      <span class="accent-t">TW</span>ICE
    </h1>
    <p class="hero-subtitle">
      Vinilos originales, ediciones limitadas y reediciones
      de tus artistas favoritos. Envío a todo México.
    </p>
    <div class="hero-actions">
      <a href="<?= SITE_URL ?>/catalog.php" class="btn btn-primary btn-lg">Ver Catálogo</a>
      <?php if (!isLoggedIn()): ?>
        <a href="<?= SITE_URL ?>/register.php" class="btn btn-secondary btn-lg">Crear Cuenta</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ── ARTIST STRIPS ─────────────────────────────────────────── -->
<div class="artist-strips">
  <?php foreach ($artistCounts as $a): ?>
  <a href="<?= SITE_URL ?>/catalog.php?artista=<?= urlencode(strtolower($a['nombre'])) ?>"
     class="artist-strip">
    <div class="artist-strip-color" style="background:<?= h($a['color']) ?>"></div>
    <div>
      <div class="artist-strip-name" style="color:<?= h($a['color']) ?>"><?= h($a['nombre']) ?></div>
      <div class="artist-strip-count"><?= (int)$a['total'] ?> vinilos disponibles</div>
    </div>
    <span style="margin-left:auto;color:var(--text-muted);font-size:20px;">→</span>
  </a>
  <?php endforeach; ?>
</div>

<!-- ── FEATURED PRODUCTS ─────────────────────────────────────── -->
<div style="max-width:1400px;margin:0 auto;">
  <div class="section">
    <div class="section-header">
      <h2 class="section-title">Últimos Lanzamientos</h2>
      <a href="<?= SITE_URL ?>/catalog.php" class="section-link">Ver todos →</a>
    </div>

    <div class="product-grid">
      <?php foreach ($featured as $p): ?>
      <a href="<?= SITE_URL ?>/product.php?id=<?= $p['id'] ?>" class="product-card">
        <div class="product-card-img">
          <?= vinylSVG($p['artista_color'], $p['titulo']) ?>
          <div class="product-overlay">
            <span class="btn btn-primary btn-sm">Ver detalles</span>
          </div>
        </div>
        <div class="product-card-body">
          <span class="product-artist-tag" style="color:<?= h($p['artista_color']) ?>">
            <?= h($p['artista_nombre']) ?>
          </span>
          <div class="product-title"><?= h($p['titulo']) ?></div>
          <div class="product-meta"><?= h($p['album']) ?> · <?= h((string)$p['anio']) ?></div>
          <div class="product-meta" style="font-size:10px;color:#555;">
            <?= h($p['color_vinilo'] ?? 'Negro') ?> · <?= h($p['edicion'] ?? 'Estándar') ?>
          </div>
        </div>
        <div class="product-card-footer">
          <span class="product-price" style="color:<?= h($p['artista_color']) ?>">
            <?= CURRENCY ?><?= number_format($p['precio'], 2) ?>
          </span>
          <?php
            $stock = (int)$p['stock'];
            if ($stock === 0) echo '<span class="stock-badge stock-none">Agotado</span>';
            elseif ($stock <= 3) echo '<span class="stock-badge stock-low">Últimas ' . $stock . '</span>';
            else echo '<span class="stock-badge stock-ok">En stock</span>';
          ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ── BANNER CTA ─────────────────────────────────────────────── -->
<div style="border-top:1px solid var(--border);border-bottom:1px solid var(--border);
            background:linear-gradient(135deg, rgba(205,255,0,0.05), rgba(255,45,120,0.05));
            padding:4rem 2rem;text-align:center;">
  <p style="font-size:11px;letter-spacing:0.3em;text-transform:uppercase;color:var(--text-muted);margin-bottom:1rem;">
    Colección exclusiva
  </p>
  <h2 style="font-family:var(--font-display);font-size:clamp(2rem,6vw,5rem);margin-bottom:1rem;">
    Vinilos de edición limitada
  </h2>
  <p style="color:var(--text-muted);max-width:500px;margin:0 auto 2rem;font-size:13px;">
    Splatter, transparentes, holográficos y más. Stock limitado.
    Crea tu cuenta para recibir alertas de nuevos lanzamientos.
  </p>
  <?php if (!isLoggedIn()): ?>
    <a href="<?= SITE_URL ?>/register.php" class="btn btn-primary btn-lg">Registrarse Gratis</a>
  <?php else: ?>
    <a href="<?= SITE_URL ?>/catalog.php" class="btn btn-primary btn-lg">Explorar Catálogo</a>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
