<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_login();

$interacciones = $pdo->query("SELECT i.id, i.tipo, i.nota, i.fecha_interaccion,
    CONCAT(c.nombre, ' ', c.apellido) AS cliente, u.nombre AS usuario
    FROM interacciones i
    JOIN clientes c ON c.id = i.cliente_id
    JOIN usuarios u ON u.id = i.usuario_id
    ORDER BY i.fecha_interaccion DESC, i.id DESC")->fetchAll();
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Interacciones | CRM Tienda</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body><div class="app-layout">
<aside class="sidebar"><div class="sidebar-brand"><div class="brand-mark">C</div><div><strong>CRM Tienda</strong><small>Panel de gestión</small></div></div>
<nav class="side-nav">
<a class="side-link" href="dashboard.php"><span>⌂</span> Panel principal</a><a class="side-link" href="clientes.php"><span>♙</span> Clientes</a><a class="side-link" href="productos.php"><span>▣</span> Productos</a><a class="side-link" href="ventas.php"><span>▤</span> Ventas</a><a class="side-link" href="pedidos.php"><span>◫</span> Pedidos</a><a class="side-link active" href="interacciones.php"><span>◌</span> Interacciones</a><a class="side-link" href="campanas.php"><span>⚑</span> Campañas</a><a class="side-link" href="categorias.php"><span>▦</span> Categorías</a>
<div class="nav-divider"></div>
            <a class="side-link logout-link" href="auth/logout.php"><span>↪</span> Cerrar sesión</a>
</nav><div class="sidebar-user"><div class="avatar"><?= e(substr($_SESSION['usuario']['nombre'],0,1)) ?></div><div><strong><?= e($_SESSION['usuario']['nombre']) ?></strong><small><?= e($_SESSION['usuario']['rol']) ?></small></div></div></aside>
<section class="dashboard-area"><header class="dashboard-topbar"><div><span class="eyebrow">SEGUIMIENTO COMERCIAL</span><h1>Interacciones</h1><p>Consulta las interacciones registradas con tus clientes.</p></div><div class="top-actions"><a href="nueva_interaccion.php" class="quick-button top-new-client"><span>＋</span> Nueva interacción</a></div></header>
<main class="dashboard-content"><section class="panel"><div class="panel-heading"><div><h2>Interacciones registradas</h2><p><?= count($interacciones) ?> interacción<?= count($interacciones)===1?'':'es' ?> en el sistema.</p></div><a href="nueva_interaccion.php" class="top-new-client"><span>＋</span> Nueva interacción</a></div>
<div class="table-wrap"><table><thead><tr><th>TIPO</th><th>CLIENTE</th><th>NOTA</th><th>USUARIO</th><th>FECHA</th></tr></thead><tbody>
<?php if (!$interacciones): ?><tr><td colspan="5" class="empty-cell">Todavía no hay interacciones registradas.</td></tr><?php endif; ?>
<?php foreach($interacciones as $i): ?><tr><td><span class="sale-id"><?= e($i['tipo']) ?></span></td><td><strong><?= e($i['cliente']) ?></strong></td><td><?= e($i['nota']) ?></td><td><?= e($i['usuario']) ?></td><td><?= date('d/m/Y H:i', strtotime($i['fecha_interaccion'])) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section></main></section></div></body></html>
