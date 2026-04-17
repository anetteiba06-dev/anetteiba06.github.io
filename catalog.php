<?php
// ============================================================
//  catalog.php — Catálogo con filtros por artista
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/vinyl_svg.php';
define('PAGE_TITLE', 'Catálogo');

$db = getDB();

// Filtro por artista (parámetro GET)
$filtroArtista = strtolower(trim($_GET['artista'] ?? 'todos'));
$busqueda      = trim($_GET['q'] ?? '');

// Construir query dinámica
$where  = [];
$params = [];

if ($filtroArtista !== 'todos') {
    $where[]  = "LOWER(a.nombre) = ?";
    $params[] = $filtroArtista;
}

if (!empty($busqueda)) {
    $where[]  = "(p.titulo LIKE ? OR p.album LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$productos = $db->prepare("
    SELECT p.*, a.nombre AS artista_nombre, a.color AS artista_color
    FROM productos p
    JOIN artistas a ON p.artista_id = a.id
    $whereSQL
    ORDER BY a.nombre, p.anio DESC
");
$productos->execute($params);
$productos = $productos->fetchAll();

$artistas = $db->query("SELECT * FROM artistas ORDER BY nombre")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="catalog-page">

  <!-- Page header -->
  <div style="margin-bottom:2.5rem;padding-bottom:1.5rem;border-bottom:1px solid var(--border);">
    <p style="font-size:11px;letter-spacing:0.2em;text-transform:uppercase;color:var(--text-muted);margin-bottom:0.5rem;">
      <?= count($productos) ?> vinilos encontrados
    </p>
    <h1 style="font-family:var(--font-display);font-size:3.5rem;letter-spacing:0.05em;">
      <?= $filtroArtista === 'todos' ? 'CATÁLOGO COMPLETO' : strtoupper(h($filtroArtista)) ?>
    </h1>
  </div>

  <!-- Toolbar -->
  <div class="catalog-toolbar">
    <!-- Filtros por artista -->
    <div class="filter-bar">
      <a href="?artista=todos" class="filter-btn <?= $filtroArtista === 'todos' ? 'active' : '' ?>">
        Todos
      </a>
      <?php foreach ($artistas as $a): ?>
      <a href="?artista=<?= urlencode(strtolower($a['nombre'])) ?>"
         data-filter="<?= strtolower(h($a['nombre'])) ?>"
         class="filter-btn <?= $filtroArtista === strtolower($a['nombre']) ? 'active' : '' ?>"
         style="<?= $filtroArtista === strtolower($a['nombre']) ? "--active-color:{$a['color']}" : '' ?>">
        <?= h($a['nombre']) ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Búsqueda -->
    <form method="GET" style="display:flex;gap:0.5rem;align-items:center;">
      <?php if ($filtroArtista !== 'todos'): ?>
        <input type="hidden" name="artista" value="<?= h($filtroArtista) ?>">
      <?php endif; ?>
      <input
        type="text"
        name="q"
        class="form-control"
        placeholder="Buscar álbum..."
        value="<?= h($busqueda) ?>"
        style="width:220px;"
      >
      <button type="submit" class="btn btn-secondary btn-sm">Buscar</button>
      <?php if (!empty($busqueda)): ?>
        <a href="?artista=<?= h($filtroArtista) ?>" class="btn btn-secondary btn-sm">✕</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Grid de productos -->
  <?php if (empty($productos)): ?>
    <div style="text-align:center;padding:4rem 2rem;color:var(--text-muted);">
      <div style="font-size:4rem;margin-bottom:1rem;">💿</div>
      <p style="font-family:var(--font-display);font-size:2rem;margin-bottom:0.5rem;">SIN RESULTADOS</p>
      <p style="font-size:13px;">Prueba con otro filtro o término de búsqueda.</p>
    </div>
  <?php else: ?>
  <div class="product-grid">
    <?php foreach ($productos as $p): ?>
    <a href="<?= SITE_URL ?>/product.php?id=<?= $p['id'] ?>" class="product-card">
      <div class="product-card-img">
        <?php if (!empty($p['imagen_url'])): ?>
          <?= vinylSVG($p['artista_color'], $p['titulo'], 'vinyl-record', SITE_URL . '/images/' . h($p['imagen_url'])) ?>
        <?php else: ?>
          <?= vinylSVG($p['artista_color'], $p['titulo']) ?>
        <?php endif; ?>
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
  <?php endif; ?>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
