<?php
// ============================================================
//  product.php — Detalle de producto con agregar al carrito
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/vinyl_svg.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . SITE_URL . '/catalog.php');
    exit;
}

$db   = getDB();
$stmt = $db->prepare("
    SELECT p.*, a.nombre AS artista_nombre, a.color AS artista_color, a.descripcion AS artista_desc
    FROM productos p
    JOIN artistas a ON p.artista_id = a.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    header('Location: ' . SITE_URL . '/catalog.php');
    exit;
}

define('PAGE_TITLE', $p['titulo']);

// ── Agregar al carrito ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_cart') {
    requireLogin();
    $qty = max(1, min((int)($_POST['qty'] ?? 1), $p['stock']));

    $stmt2 = $db->prepare("
        INSERT INTO carrito (usuario_id, producto_id, cantidad)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)
    ");
    $stmt2->execute([$_SESSION['usuario_id'], $p['id'], $qty]);

    setFlash('success', '"' . $p['titulo'] . '" añadido al carrito.');
    header('Location: ' . SITE_URL . '/product.php?id=' . $id);
    exit;
}

// Productos relacionados (mismo artista)
$related = $db->prepare("
    SELECT p.*, a.nombre AS artista_nombre, a.color AS artista_color
    FROM productos p
    JOIN artistas a ON p.artista_id = a.id
    WHERE p.artista_id = ? AND p.id != ?
    ORDER BY RAND() LIMIT 4
");
$related->execute([$p['artista_id'], $p['id']]);
$related = $related->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<div style="padding:1.5rem 2rem;border-bottom:1px solid var(--border);max-width:1200px;margin:0 auto;width:100%;
            font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-muted);display:flex;gap:0.75rem;align-items:center;">
  <a href="<?= SITE_URL ?>/index.php" style="color:var(--text-muted);transition:color .2s" onmouseover="this.style.color='#fff'" onmouseout="this.style.color=''">Inicio</a>
  <span>›</span>
  <a href="<?= SITE_URL ?>/catalog.php" style="color:var(--text-muted)">Catálogo</a>
  <span>›</span>
  <a href="<?= SITE_URL ?>/catalog.php?artista=<?= urlencode(strtolower($p['artista_nombre'])) ?>"
     style="color:<?= h($p['artista_color']) ?>"><?= h($p['artista_nombre']) ?></a>
  <span>›</span>
  <span style="color:var(--text)"><?= h($p['titulo']) ?></span>
</div>

<!-- ── Detalle principal ──────────────────────────────────── -->
<div class="product-detail">

  <!-- Imagen / Vinyl mockup -->
  <div class="product-detail-img">
    <div style="width:70%;max-width:340px;">
      <?= vinylSVG($p['artista_color'], $p['titulo'], 'vinyl-record', !empty($p['imagen_url']) ? SITE_URL . '/images/' . h($p['imagen_url']) : '') ?>
    </div>
  </div>

  <!-- Info -->
  <div class="product-detail-info">

    <span style="font-size:11px;letter-spacing:0.25em;text-transform:uppercase;
                 color:<?= h($p['artista_color']) ?>;font-weight:700;">
      <?= h($p['artista_nombre']) ?>
    </span>

    <h1 class="product-detail-title"><?= h($p['titulo']) ?></h1>

    <div class="product-detail-price" style="color:<?= h($p['artista_color']) ?>">
      <?= CURRENCY ?><?= number_format($p['precio'], 2) ?>
    </div>

    <?php if ($p['descripcion']): ?>
    <p style="font-size:13px;color:var(--text-muted);line-height:1.8;margin:0.5rem 0;">
      <?= nl2br(h($p['descripcion'])) ?>
    </p>
    <?php endif; ?>

    <!-- Especificaciones -->
    <div class="product-specs">
      <div class="spec-row">
        <span class="spec-label">Álbum</span>
        <span class="spec-value"><?= h($p['album']) ?></span>
      </div>
      <div class="spec-row">
        <span class="spec-label">Año</span>
        <span class="spec-value"><?= h((string)$p['anio']) ?></span>
      </div>
      <div class="spec-row">
        <span class="spec-label">Color de vinilo</span>
        <span class="spec-value"><?= h($p['color_vinilo'] ?? 'Negro') ?></span>
      </div>
      <div class="spec-row">
        <span class="spec-label">Edición</span>
        <span class="spec-value"><?= h($p['edicion'] ?? 'Estándar') ?></span>
      </div>
      <div class="spec-row">
        <span class="spec-label">Stock</span>
        <span class="spec-value">
          <?php $stock = (int)$p['stock'];
            if ($stock === 0) echo '<span class="stock-badge stock-none">Agotado</span>';
            elseif ($stock <= 3) echo '<span class="stock-badge stock-low">' . $stock . ' unidades</span>';
            else echo '<span class="stock-badge stock-ok">' . $stock . ' unidades</span>';
          ?>
        </span>
      </div>
    </div>

    <!-- Add to cart -->
    <?php if ($p['stock'] > 0): ?>
      <?php if (isLoggedIn()): ?>
      <form method="POST" action="" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin-top:0.5rem;">
        <input type="hidden" name="action" value="add_cart">

        <div class="qty-control">
          <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
          <input type="number" name="qty" id="qty" value="1"
                 min="1" max="<?= $p['stock'] ?>" class="qty-input">
          <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
        </div>

        <button type="submit" class="btn btn-lg"
                style="background:<?= h($p['artista_color']) ?>;
                       color:<?= $p['artista_color'] === '#CDFF00' ? 'var(--bg)' : '#fff' ?>;
                       border-color:<?= h($p['artista_color']) ?>;">
          💿 Agregar al Carrito
        </button>
      </form>
      <?php else: ?>
      <div style="margin-top:0.5rem;">
        <a href="<?= SITE_URL ?>/login.php" class="btn btn-primary btn-lg">
          Inicia sesión para comprar
        </a>
      </div>
      <?php endif; ?>
    <?php else: ?>
      <button class="btn btn-secondary btn-lg" disabled style="cursor:not-allowed;opacity:0.5;margin-top:0.5rem;">
        Producto Agotado
      </button>
    <?php endif; ?>

  </div>
</div>

<!-- ── Artista info ───────────────────────────────────────── -->
<div style="border-top:1px solid var(--border);border-bottom:1px solid var(--border);
            background:linear-gradient(135deg,<?= $p['artista_color'] ?>08,transparent);
            padding:2rem;margin:0 auto;max-width:100%;">
  <div style="max-width:1200px;margin:0 auto;display:flex;gap:1.5rem;align-items:flex-start;">
    <div style="width:4px;height:60px;background:<?= h($p['artista_color']) ?>;border-radius:2px;flex-shrink:0;margin-top:4px;"></div>
    <div>
      <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:var(--text-muted);margin-bottom:0.25rem;">
        Sobre el artista
      </div>
      <div style="font-family:var(--font-display);font-size:2rem;color:<?= h($p['artista_color']) ?>;margin-bottom:0.5rem;">
        <?= h($p['artista_nombre']) ?>
      </div>
      <p style="font-size:13px;color:var(--text-muted);max-width:700px;line-height:1.8;">
        <?= h($p['artista_desc']) ?>
      </p>
    </div>
  </div>
</div>

<!-- ── Productos relacionados ────────────────────────────── -->
<?php if (!empty($related)): ?>
<div style="max-width:1200px;margin:0 auto;padding:4rem 2rem;">
  <div class="section-header">
    <h2 class="section-title">Más de <?= h($p['artista_nombre']) ?></h2>
    <a href="<?= SITE_URL ?>/catalog.php?artista=<?= urlencode(strtolower($p['artista_nombre'])) ?>"
       class="section-link">Ver todos →</a>
  </div>
  <div class="product-grid">
    <?php foreach ($related as $r): ?>
    <a href="<?= SITE_URL ?>/product.php?id=<?= $r['id'] ?>" class="product-card">
      <div class="product-card-img">
        <?php if (!empty($r['imagen_url'])): ?>
          <?= vinylSVG($r['artista_color'], $r['titulo'], 'vinyl-record', SITE_URL . '/images/' . h($r['imagen_url'])) ?>
        <?php else: ?>
          <?= vinylSVG($r['artista_color'], $r['titulo']) ?>
        <?php endif; ?>
        <div class="product-overlay"><span class="btn btn-primary btn-sm">Ver detalles</span></div>
      </div>
      <div class="product-card-body">
        <span class="product-artist-tag" style="color:<?= h($r['artista_color']) ?>"><?= h($r['artista_nombre']) ?></span>
        <div class="product-title"><?= h($r['titulo']) ?></div>
        <div class="product-meta"><?= h($r['album']) ?> · <?= (int)$r['anio'] ?></div>
      </div>
      <div class="product-card-footer">
        <span class="product-price" style="color:<?= h($r['artista_color']) ?>">
          <?= CURRENCY ?><?= number_format($r['precio'], 2) ?>
        </span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<script>
function changeQty(delta) {
  const input = document.getElementById('qty');
  const max   = parseInt(input.max);
  let   val   = parseInt(input.value) + delta;
  if (val < 1)   val = 1;
  if (val > max) val = max;
  input.value = val;
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
