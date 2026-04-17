<?php
// ============================================================
//  cart.php — Carrito de compras (CRUD)
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/vinyl_svg.php';
define('PAGE_TITLE', 'Mi Carrito');

requireLogin();

$db  = getDB();
$uid = $_SESSION['usuario_id'];

// ── Acciones del carrito ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action']     ?? '';
    $productoId = (int)($_POST['producto_id'] ?? 0);

    switch ($action) {

        case 'update':
            $qty = max(1, (int)($_POST['qty'] ?? 1));
            // Verificar stock disponible
            $stockRow = $db->prepare("SELECT stock FROM productos WHERE id = ?");
            $stockRow->execute([$productoId]);
            $stockDisp = (int)$stockRow->fetchColumn();
            $qty = min($qty, $stockDisp);

            $db->prepare("UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?")
               ->execute([$qty, $uid, $productoId]);
            setFlash('success', 'Cantidad actualizada.');
            break;

        case 'remove':
            $db->prepare("DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?")
               ->execute([$uid, $productoId]);
            setFlash('info', 'Producto eliminado del carrito.');
            break;

        case 'clear':
            $db->prepare("DELETE FROM carrito WHERE usuario_id = ?")->execute([$uid]);
            setFlash('info', 'Carrito vaciado.');
            break;

        case 'checkout':
            // Crear pedido
            $items = $db->prepare("
                SELECT c.cantidad, p.precio, p.stock, p.id AS prod_id
                FROM carrito c
                JOIN productos p ON c.producto_id = p.id
                WHERE c.usuario_id = ?
            ");
            $items->execute([$uid]);
            $cartItems = $items->fetchAll();

            if (empty($cartItems)) {
                setFlash('error', 'El carrito está vacío.');
                break;
            }

            $total = array_reduce($cartItems, fn($carry, $item) => $carry + ($item['precio'] * $item['cantidad']), 0);
            $direccion = trim($_POST['direccion'] ?? '');

            $db->beginTransaction();
            try {
                $db->prepare("INSERT INTO pedidos (usuario_id, total, estado, direccion) VALUES (?,?,'pagado',?)")
                   ->execute([$uid, $total, $direccion]);
                $pedidoId = $db->lastInsertId();

                foreach ($cartItems as $item) {
                    $db->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unit) VALUES (?,?,?,?)")
                       ->execute([$pedidoId, $item['prod_id'], $item['cantidad'], $item['precio']]);
                    // Reducir stock
                    $db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?")
                       ->execute([$item['cantidad'], $item['prod_id']]);
                }
                // Vaciar carrito
                $db->prepare("DELETE FROM carrito WHERE usuario_id = ?")->execute([$uid]);
                $db->commit();
                setFlash('success', '¡Pedido #' . $pedidoId . ' realizado! Total: $' . number_format($total, 2));
            } catch (Exception $e) {
                $db->rollBack();
                setFlash('error', 'Error al procesar el pedido. Inténtalo de nuevo.');
            }
            break;
    }

    header('Location: ' . SITE_URL . '/cart.php');
    exit;
}

// ── Obtener items del carrito ───────────────────────────────
$stmt = $db->prepare("
    SELECT c.id, c.cantidad, c.producto_id,
           p.titulo, p.album, p.precio, p.stock, p.color_vinilo, p.edicion,
           a.nombre AS artista_nombre, a.color AS artista_color
    FROM carrito c
    JOIN productos p ON c.producto_id = p.id
    JOIN artistas a ON p.artista_id = a.id
    WHERE c.usuario_id = ?
    ORDER BY c.added_at DESC
");
$stmt->execute([$uid]);
$cartItems = $stmt->fetchAll();

$subtotal = array_reduce($cartItems, fn($carry, $item) => $carry + ($item['precio'] * $item['cantidad']), 0);
$envio    = $subtotal > 0 ? 99.00 : 0;
$total    = $subtotal + $envio;

include __DIR__ . '/includes/header.php';
?>

<div style="padding:3rem 2rem 1rem;max-width:1200px;margin:0 auto;">
  <h1 style="font-family:var(--font-display);font-size:3.5rem;letter-spacing:0.05em;">MI CARRITO</h1>
  <p style="font-size:12px;color:var(--text-muted);margin-top:0.25rem;">
    <?= count($cartItems) ?> <?= count($cartItems) === 1 ? 'artículo' : 'artículos' ?>
  </p>
</div>

<?php if (empty($cartItems)): ?>
  <div style="text-align:center;padding:5rem 2rem;color:var(--text-muted);">
    <div style="font-size:5rem;margin-bottom:1rem;">💿</div>
    <p style="font-family:var(--font-display);font-size:2.5rem;margin-bottom:0.5rem;">CARRITO VACÍO</p>
    <p style="font-size:13px;margin-bottom:2rem;">Agrega vinilos desde el catálogo.</p>
    <a href="<?= SITE_URL ?>/catalog.php" class="btn btn-primary btn-lg">Ir al Catálogo</a>
  </div>
<?php else: ?>

<div class="cart-layout">

  <!-- Items -->
  <div>
    <!-- Vaciar carrito -->
    <form method="POST" style="text-align:right;margin-bottom:0.5rem;">
      <input type="hidden" name="action" value="clear">
      <button type="submit" class="btn btn-secondary btn-sm"
              onclick="return confirm('¿Vaciar todo el carrito?')">
        ✕ Vaciar carrito
      </button>
    </form>

    <div style="border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;">
      <?php foreach ($cartItems as $item): ?>
      <div class="cart-item">

        <!-- Vinyl mini -->
        <div class="cart-item-img">
          <div style="width:56px;height:56px;">
            <?= vinylSVG($item['artista_color'], $item['titulo']) ?>
          </div>
        </div>

        <!-- Info -->
        <div class="cart-item-info">
          <span style="font-size:10px;letter-spacing:0.15em;text-transform:uppercase;
                       color:<?= h($item['artista_color']) ?>;font-weight:700;">
            <?= h($item['artista_nombre']) ?>
          </span>
          <div style="font-family:var(--font-display);font-size:1.3rem;margin:0.1rem 0;">
            <?= h($item['titulo']) ?>
          </div>
          <div style="font-size:11px;color:var(--text-muted);">
            <?= h($item['album']) ?> · <?= h($item['color_vinilo'] ?? 'Negro') ?>
          </div>
          <div style="font-size:12px;color:var(--text-muted);margin-top:0.25rem;">
            <?= CURRENCY ?><?= number_format($item['precio'], 2) ?> c/u
          </div>
        </div>

        <!-- Qty update -->
        <form method="POST" style="display:flex;align-items:center;gap:0.5rem;">
          <input type="hidden" name="action"      value="update">
          <input type="hidden" name="producto_id" value="<?= $item['producto_id'] ?>">
          <div class="qty-control">
            <button type="button" class="qty-btn"
                    onclick="let i=this.nextElementSibling;i.value=Math.max(1,+i.value-1)">−</button>
            <input type="number" name="qty" value="<?= $item['cantidad'] ?>"
                   min="1" max="<?= $item['stock'] ?>" class="qty-input"
                   onchange="this.form.submit()">
            <button type="button" class="qty-btn"
                    onclick="let i=this.previousElementSibling;i.value=Math.min(<?= $item['stock'] ?>,+i.value+1);i.dispatchEvent(new Event('change'))">+</button>
          </div>
        </form>

        <!-- Subtotal -->
        <div style="font-family:var(--font-display);font-size:1.5rem;
                    color:<?= h($item['artista_color']) ?>;min-width:80px;text-align:right;">
          <?= CURRENCY ?><?= number_format($item['precio'] * $item['cantidad'], 2) ?>
        </div>

        <!-- Eliminar -->
        <form method="POST">
          <input type="hidden" name="action"      value="remove">
          <input type="hidden" name="producto_id" value="<?= $item['producto_id'] ?>">
          <button type="submit" class="btn btn-secondary btn-sm"
                  style="padding:0.4rem 0.6rem;border-color:transparent;color:var(--text-muted);"
                  title="Eliminar">✕</button>
        </form>

      </div>
      <?php endforeach; ?>
    </div>

    <div style="margin-top:1rem;">
      <a href="<?= SITE_URL ?>/catalog.php" class="btn btn-secondary btn-sm">← Seguir comprando</a>
    </div>
  </div>

  <!-- Resumen + Checkout -->
  <div>
    <div class="cart-summary">
      <h2 style="font-family:var(--font-display);font-size:2rem;margin-bottom:1.25rem;
                 letter-spacing:0.05em;border-bottom:1px solid var(--border);padding-bottom:1rem;">
        RESUMEN
      </h2>

      <div class="summary-row">
        <span>Subtotal</span>
        <span><?= CURRENCY ?><?= number_format($subtotal, 2) ?></span>
      </div>
      <div class="summary-row">
        <span>Envío</span>
        <span><?= CURRENCY ?><?= number_format($envio, 2) ?></span>
      </div>
      <div class="summary-row total">
        <span>Total</span>
        <span style="color:var(--gorillaz)"><?= CURRENCY ?><?= number_format($total, 2) ?></span>
      </div>

      <form method="POST" style="margin-top:1.5rem;">
        <input type="hidden" name="action" value="checkout">
        <div class="form-group">
          <label class="form-label">Dirección de envío</label>
          <textarea name="direccion" class="form-control"
                    placeholder="Calle, número, colonia, ciudad, CP..."
                    rows="3" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg"
                onclick="return confirm('¿Confirmar pedido por <?= CURRENCY . number_format($total,2) ?>?')">
          Confirmar Pedido
        </button>
      </form>

      <div style="margin-top:1rem;font-size:11px;color:var(--text-muted);text-align:center;line-height:1.6;">
        🔒 Pago simulado · Proyecto educativo
      </div>
    </div>
  </div>

</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
