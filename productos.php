<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_login();

$mensaje = $_SESSION['mensaje_producto'] ?? null;
unset($_SESSION['mensaje_producto']);
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'agregar_stock') {
    $productoId = (int)($_POST['producto_id'] ?? 0);
    $cantidad = (int)($_POST['cantidad'] ?? 0);
    if ($productoId <= 0 || $cantidad <= 0) {
        $errores[] = 'Indica una cantidad válida para agregar.';
    } else {
        $stmt = $pdo->prepare('UPDATE productos SET stock = stock + ? WHERE id = ?');
        $stmt->execute([$cantidad, $productoId]);
        $_SESSION['mensaje_producto'] = 'Cantidad del producto actualizada correctamente.';
        header('Location: productos.php');
        exit;
    }
}

$productos = $pdo->query("SELECT p.id, p.nombre, p.sku, p.precio, p.stock, c.nombre AS categoria
                          FROM productos p JOIN categorias c ON c.id = p.categoria_id
                          ORDER BY p.id DESC")->fetchAll();
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Productos | CRM Tienda</title><link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
<aside class="sidebar">
<div class="sidebar-brand"><div class="brand-mark">C</div><div><strong>CRM Tienda</strong><small>Panel de gestión</small></div></div>
<nav class="side-nav">
<a class="side-link" href="dashboard.php"><span>⌂</span> Panel principal</a>
<a class="side-link" href="clientes.php"><span>♙</span> Clientes</a>
<a class="side-link active" href="productos.php"><span>▣</span> Productos</a>
<a class="side-link" href="ventas.php"><span>▤</span> Ventas</a>
<a class="side-link" href="pedidos.php"><span>◫</span> Pedidos</a>
<a class="side-link" href="interacciones.php"><span>◌</span> Interacciones</a>
<a class="side-link" href="campanas.php"><span>⚑</span> Campañas</a>
<a class="side-link" href="categorias.php"><span>▦</span> Categorías</a>
<div class="nav-divider"></div>
            <a class="side-link logout-link" href="auth/logout.php"><span>↪</span> Cerrar sesión</a>
</nav>
<div class="sidebar-user"><div class="avatar"><?= e(substr($_SESSION['usuario']['nombre'],0,1)) ?></div><div><strong><?= e($_SESSION['usuario']['nombre']) ?></strong><small><?= e($_SESSION['usuario']['rol']) ?></small></div></div>
</aside>
<section class="dashboard-area">
<header class="dashboard-topbar"><div><span class="eyebrow">GESTIÓN DE PRODUCTOS</span><h1>Productos</h1><p>Consulta el inventario, agrega cantidades y registra nuevos productos.</p></div><div class="top-actions"><a href="nuevo_producto.php" class="quick-button top-new-client"><span>＋</span> Nuevo producto</a></div></header>
<main class="dashboard-content">
<?php if ($mensaje): ?><div class="success-banner">✓ <?= e($mensaje) ?></div><?php endif; ?>
<?php if ($errores): ?><div class="alert error"><?php foreach($errores as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>
<section class="panel products-panel">
<div class="panel-heading"><div><h2>Inventario</h2><p><?= count($productos) ?> producto<?= count($productos)===1?'':'s' ?> registrado<?= count($productos)===1?'':'s' ?>.</p></div><a href="nuevo_producto.php" class="top-new-client product-new-button"><span>＋</span> Nuevo producto</a></div>
<div class="table-wrap"><table class="products-table"><thead><tr><th>PRODUCTO</th><th>CATEGORÍA</th><th>SKU</th><th>PRECIO</th><th>CANTIDAD</th><th>AGREGAR CANTIDAD</th></tr></thead><tbody>
<?php if (!$productos): ?><tr><td colspan="6" class="empty-cell">No hay productos registrados.</td></tr><?php endif; ?>
<?php foreach($productos as $producto): ?><tr>
<td><strong><?= e($producto['nombre']) ?></strong></td><td><?= e($producto['categoria']) ?></td><td><?= e($producto['sku'] ?: '—') ?></td>
<td><strong>$<?= number_format((float)$producto['precio'],0,',','.') ?></strong></td>
<td><span class="stock-badge <?= (int)$producto['stock'] <= 5 ? 'stock-low':'stock-ok' ?>"><?= (int)$producto['stock'] ?></span></td>
<td><form method="post" class="stock-form"><input type="hidden" name="accion" value="agregar_stock"><input type="hidden" name="producto_id" value="<?= (int)$producto['id'] ?>"><input type="number" name="cantidad" min="1" value="1" aria-label="Cantidad a agregar"><button type="submit" class="stock-button">＋ Agregar</button></form></td>
</tr><?php endforeach; ?></tbody></table></div>
</section>
</main></section></div>
</body></html>
