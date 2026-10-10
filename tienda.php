<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/tienda_lib.php';

$tieneActivo = columna_existe($pdo, 'productos', 'activo');
$tieneImagen = columna_existe($pdo, 'productos', 'imagen');  

$categorias = $pdo->query('SELECT id, nombre FROM categorias ORDER BY id')->fetchAll();
$idsValidos = array_map('intval', array_column($categorias, 'id'));
$catSel = (int)($_GET['categoria'] ?? 0);
if (!in_array($catSel, $idsValidos, true)) $catSel = 0;

$orden = in_array($_GET['orden'] ?? '', ['menor', 'mayor'], true) ? $_GET['orden'] : '';
$orderBy = match ($orden) {
    'menor' => 'p.precio ASC',
    'mayor' => 'p.precio DESC',
    default => 'p.categoria_id, p.nombre',
};

$where = []; $params = [];
if ($tieneActivo) $where[] = 'p.activo = 1';
if ($catSel) { $where[] = 'p.categoria_id = ?'; $params[] = $catSel; }
$sql = 'SELECT p.id, p.nombre, p.precio, p.stock, ' . ($tieneImagen ? 'p.imagen, ' : '') . 'c.nombre AS categoria
        FROM productos p JOIN categorias c ON c.id = p.categoria_id'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . " ORDER BY $orderBy";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

$estilos = [
    'Cadenas'  => ['emoji' => '&#9939;',   'bg' => '#e8eefc'],
    'Relojes'  => ['emoji' => '&#8986;',   'bg' => '#fdf0e1'],
    'Pulseras' => ['emoji' => '&#128255;', 'bg' => '#e9f7ee'],
];
$estiloPorDefecto = ['emoji' => '&#10024;', 'bg' => '#f0eeea'];

$enCarrito = carrito_cantidad();
$mensaje = t_flash_get('tienda');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(NOMBRE_TIENDA) ?></title>
<link rel="stylesheet" href="assets/css/tienda.css">
</head>
<body>
<header>
  <h1><a href="tienda.php"><?= e(NOMBRE_TIENDA) ?></a></h1>
  <p>Cadenas, relojes y pulseras</p>
</header>

<div class="bar">
  <nav class="tabs">
    <a href="?<?= e(http_build_query(['orden' => $orden])) ?>" class="<?= $catSel === 0 ? 'on' : '' ?>">Todo</a>
    <?php foreach ($categorias as $c): ?>
      <a href="?<?= e(http_build_query(['categoria' => $c['id'], 'orden' => $orden])) ?>"
         class="<?= $catSel === (int)$c['id'] ? 'on' : '' ?>"><?= e($c['nombre']) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="right">
    <form method="GET">
      <?php if ($catSel): ?><input type="hidden" name="categoria" value="<?= $catSel ?>"><?php endif; ?>
      <select name="orden" onchange="this.form.submit()" aria-label="Ordenar">
        <option value="">Ordenar</option>
        <option value="menor" <?= $orden === 'menor' ? 'selected' : '' ?>>Precio: menor a mayor</option>
        <option value="mayor" <?= $orden === 'mayor' ? 'selected' : '' ?>>Precio: mayor a menor</option>
      </select>
    </form>
    <a class="cartlink" href="carrito.php">&#128722; Carrito (<?= $enCarrito ?>)</a>
  </div>
</div>

<?php if ($mensaje): ?><div class="msg"><?= e($mensaje) ?></div><?php endif; ?>

<main class="grid">
  <?php if (!$productos): ?><div class="empty">No hay productos en esta categoría.</div><?php endif; ?>
  <?php foreach ($productos as $p):
      $estilo = $estilos[$p['categoria']] ?? $estiloPorDefecto;
      $emoji  = $estilo['emoji'] ?? $estiloPorDefecto['emoji'];
      $bg     = $estilo['bg'] ?? $estiloPorDefecto['bg'];
      $foto = '';
      if ($tieneImagen && !empty($p['imagen'])) {
          $archivo = basename($p['imagen']);
          if (is_file(__DIR__ . '/' . CARPETA_IMG . $archivo)) $foto = CARPETA_IMG . rawurlencode($archivo);
      }
      $stock = (int)$p['stock'];
      if ($stock <= 0)     { $cls = 'out'; $txt = 'Agotado'; }
      elseif ($stock <= 5) { $cls = 'low'; $txt = '¡Últimas ' . $stock . ' unidades!'; }
      else                 { $cls = 'ok';  $txt = 'Disponible'; }
  ?>
    <article class="card">
      <a class="thumb" href="producto.php?id=<?= (int)$p['id'] ?>" style="background:<?= e($bg) ?>" aria-label="Ver detalle de <?= e($p['nombre']) ?>">
        <?php if ($foto): ?><img src="<?= e($foto) ?>" alt="<?= e($p['nombre']) ?>" loading="lazy"><?php else: ?><?= $emoji ?><?php endif; ?>
      </a>
      <div class="body">
        <span class="cat"><?= e($p['categoria']) ?></span>
        <a class="name" href="producto.php?id=<?= (int)$p['id'] ?>"><?= e($p['nombre']) ?></a>
        <span class="tag <?= $cls ?>"><?= e($txt) ?></span>
        <span class="price"><?= moneda($p['precio']) ?></span>
        <form method="POST" action="carrito.php">
          <?= t_csrf_field() ?>
          <input type="hidden" name="accion" value="agregar">
          <input type="hidden" name="producto_id" value="<?= (int)$p['id'] ?>">
          <input type="hidden" name="categoria" value="<?= $catSel ?>">
          <input type="hidden" name="orden" value="<?= e($orden) ?>">
          <button class="btn" type="submit" <?= $stock <= 0 ? 'disabled' : '' ?>>Agregar al carrito</button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</main>

<footer>© <?= date('Y') ?> <?= e(NOMBRE_TIENDA) ?></footer>
</body>
</html>
