<?php
// producto.php?id=N — página de detalle de un producto (pública)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/tienda_lib.php';

$id = (int)($_GET['id'] ?? 0);

// Solo pide las columnas que existen (así funciona aunque aún no hayas ejecutado detalle_productos.sql)
$cols = ['p.id', 'p.nombre', 'p.sku', 'p.precio', 'p.stock', 'p.categoria_id', 'c.nombre AS categoria'];
foreach (['imagen', 'descripcion', 'material', 'color', 'medidas'] as $col) {
    if (columna_existe($pdo, 'productos', $col)) $cols[] = 'p.' . $col;
}
$tieneActivo = columna_existe($pdo, 'productos', 'activo');

$stmt = $pdo->prepare('SELECT ' . implode(', ', $cols) . ' FROM productos p JOIN categorias c ON c.id = p.categoria_id
                       WHERE p.id = ?' . ($tieneActivo ? ' AND p.activo = 1' : ''));
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) http_response_code(404);

$estilos = [
    'Cadenas'  => ['emoji' => '&#9939;',   'bg' => '#e8eefc'],
    'Relojes'  => ['emoji' => '&#8986;',   'bg' => '#fdf0e1'],
    'Pulseras' => ['emoji' => '&#128255;', 'bg' => '#e9f7ee'],
];
$estiloPorDefecto = ['emoji' => '&#10024;', 'bg' => '#f0eeea'];

// Ruta de la foto si existe el archivo; si no, '' (se muestra el icono)
function foto_producto(?string $archivo): string {
    if (!$archivo) return '';
    $archivo = basename($archivo);
    return is_file(__DIR__ . '/' . CARPETA_IMG . $archivo) ? CARPETA_IMG . rawurlencode($archivo) : '';
}

$relacionados = [];
if ($p) {
    $estilo = $estilos[$p['categoria']] ?? $estiloPorDefecto;
    $foto   = foto_producto($p['imagen'] ?? null);
    $stock  = (int)$p['stock'];
    if ($stock <= 0)     { $cls = 'out'; $txt = 'Agotado'; }
    elseif ($stock <= 5) { $cls = 'low'; $txt = '¡Últimas ' . $stock . ' unidades!'; }
    else                 { $cls = 'ok';  $txt = 'Disponible'; }

    // Ficha técnica: solo se muestran los datos que existen
    $ficha = ['Categoría' => $p['categoria']];
    foreach (['material' => 'Material', 'color' => 'Color', 'medidas' => 'Medidas'] as $k => $etiqueta) {
        if (!empty($p[$k])) $ficha[$etiqueta] = $p[$k];
    }
    if (!empty($p['sku'])) $ficha['Código'] = $p['sku'];

    // Otros productos de la misma categoría
    $colsRel = 'p.id, p.nombre, p.precio, p.stock' . (columna_existe($pdo, 'productos', 'imagen') ? ', p.imagen' : '');
    $stmt = $pdo->prepare("SELECT $colsRel FROM productos p WHERE p.categoria_id = ? AND p.id <> ?"
                          . ($tieneActivo ? ' AND p.activo = 1' : '') . ' ORDER BY RAND() LIMIT 3');
    $stmt->execute([$p['categoria_id'], $p['id']]);
    $relacionados = $stmt->fetchAll();
}

$enCarrito = carrito_cantidad();
$mensaje = t_flash_get('tienda');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $p ? e($p['nombre']) : 'Producto no encontrado' ?> | <?= e(NOMBRE_TIENDA) ?></title>
<link rel="stylesheet" href="assets/css/tienda.css">
<style>
/* Estilos de la página de detalle (incluidos aquí para no depender de otro archivo) */
body.pd .bar,body.pd .msg{max-width:960px}
.detail{max-width:960px;margin:0 auto;padding:0 20px 40px;display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start}
.detail-img{width:100%;aspect-ratio:1/1;border:1px solid var(--line);border-radius:16px;overflow:hidden;display:grid;place-items:center;font-size:110px}
.detail-img img{width:100%;height:100%;object-fit:cover;display:block}
.detail-info{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:24px;display:flex;flex-direction:column;gap:12px}
.detail-info h2{margin:0;font-size:1.7rem;letter-spacing:-.01em}
.detail-price{font-size:1.7rem;font-weight:700;color:var(--accent)}
.detail-desc{margin:0;line-height:1.6}
.specs{width:100%;border-collapse:collapse;font-size:.95rem}
.specs th{width:36%;text-align:left;text-transform:none;letter-spacing:0;font-size:.9rem;font-weight:500;color:var(--muted);padding:9px 0;border-top:1px solid var(--line)}
.specs td{padding:9px 0;font-weight:500;border-top:1px solid var(--line)}
.buy{display:flex;gap:10px;margin-top:6px}
.buy .qty{width:76px;padding:10px;text-align:center}
.buy .btn{margin-top:0;flex:1}
.related-title{max-width:960px;margin:0 auto;padding:0 20px 14px;font-size:1.15rem}
.related{max-width:960px;margin:0 auto;padding:0 20px 50px;display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
@media (max-width:760px){.detail{grid-template-columns:1fr}.related{grid-template-columns:1fr 1fr}}
</style>
</head>
<body class="pd">
<header>
  <h1><a href="tienda.php"><?= e(NOMBRE_TIENDA) ?></a></h1>
  <p>Cadenas, relojes y pulseras</p>
</header>

<div class="bar">
  <a class="back" style="margin:0" href="tienda.php<?= $p ? '?categoria=' . (int)$p['categoria_id'] : '' ?>">&larr; Volver a la tienda</a>
  <div class="right"><a class="cartlink" href="carrito.php">&#128722; Carrito (<?= $enCarrito ?>)</a></div>
</div>

<?php if ($mensaje): ?><div class="msg"><?= e($mensaje) ?></div><?php endif; ?>

<?php if (!$p): ?>
  <div class="wrap center">
    <section class="panel">
      <h2>No encontramos este producto</h2>
      <p class="note">Puede que ya no esté disponible.</p>
      <a class="btn inline" href="tienda.php">Ver todos los productos</a>
    </section>
  </div>
<?php else: ?>
  <main class="detail">
    <div class="detail-img" style="background:<?= e($estilo['bg']) ?>">
      <?php if ($foto): ?><img src="<?= e($foto) ?>" alt="<?= e($p['nombre']) ?>"><?php else: ?><?= $estilo['emoji'] ?><?php endif; ?>
    </div>

    <div class="detail-info">
      <span class="cat"><?= e($p['categoria']) ?></span>
      <h2><?= e($p['nombre']) ?></h2>
      <span class="tag <?= $cls ?>"><?= e($txt) ?></span>
      <span class="detail-price"><?= moneda($p['precio']) ?></span>

      <?php if (!empty($p['descripcion'])): ?>
        <p class="detail-desc"><?= nl2br(e($p['descripcion'])) ?></p>
      <?php endif; ?>

      <table class="specs">
        <?php foreach ($ficha as $etiqueta => $valor): ?>
          <tr><th><?= e($etiqueta) ?></th><td><?= e($valor) ?></td></tr>
        <?php endforeach; ?>
      </table>

      <form method="POST" action="carrito.php" class="buy">
        <?= t_csrf_field() ?>
        <input type="hidden" name="accion" value="agregar">
        <input type="hidden" name="producto_id" value="<?= (int)$p['id'] ?>">
        <input type="hidden" name="desde_producto" value="<?= (int)$p['id'] ?>">
        <input class="qty" type="number" name="cantidad" value="1" min="1" max="<?= max(1, min($stock, 99)) ?>" aria-label="Cantidad" <?= $stock <= 0 ? 'disabled' : '' ?>>
        <button class="btn" type="submit" <?= $stock <= 0 ? 'disabled' : '' ?>>Agregar al carrito</button>
      </form>
    </div>
  </main>

  <?php if ($relacionados): ?>
    <h3 class="related-title">Más en <?= e($p['categoria']) ?></h3>
    <div class="related">
      <?php foreach ($relacionados as $r): $fr = foto_producto($r['imagen'] ?? null); ?>
        <article class="card">
          <a class="thumb" href="producto.php?id=<?= (int)$r['id'] ?>" style="background:<?= e($estilo['bg']) ?>" aria-label="Ver <?= e($r['nombre']) ?>">
            <?php if ($fr): ?><img src="<?= e($fr) ?>" alt="<?= e($r['nombre']) ?>" loading="lazy"><?php else: ?><?= $estilo['emoji'] ?><?php endif; ?>
          </a>
          <div class="body">
            <a class="name" href="producto.php?id=<?= (int)$r['id'] ?>"><?= e($r['nombre']) ?></a>
            <span class="price"><?= moneda($r['precio']) ?></span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<footer>© <?= date('Y') ?> <?= e(NOMBRE_TIENDA) ?></footer>
</body>
</html>
