<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_login();

$titulo = 'Ventas';
$ventas = $pdo->query("SELECT v.id, v.total, v.fecha_venta, CONCAT(c.nombre, ' ', c.apellido) AS cliente, u.nombre AS vendedor, camp.nombre AS campana FROM ventas v JOIN clientes c ON c.id = v.cliente_id JOIN usuarios u ON u.id = v.usuario_id LEFT JOIN campanas camp ON camp.id = v.campana_id ORDER BY v.fecha_venta DESC, v.id DESC")->fetchAll();
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas | CRM Tienda</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-brand"><div class="brand-mark">C</div><div><strong>CRM Tienda</strong><small>Panel de gestión</small></div></div>
        <nav class="side-nav">
            <a class="side-link" href="dashboard.php"><span>⌂</span> Panel principal</a>
            <a class="side-link" href="clientes.php"><span>♙</span> Clientes</a>
            <a class="side-link" href="#"><span>▣</span> Productos</a>
            <a class="side-link active" href="ventas.php"><span>▤</span> Ventas</a>
            <a class="side-link" href="#"><span>◌</span> Interacciones</a>
            <a class="side-link" href="#"><span>⚑</span> Campañas</a>
            <a class="side-link" href="#"><span>▦</span> Categorías</a>
            <div class="nav-divider"></div><span class="nav-label">CUENTA</span>
            <a class="side-link" href="#"><span>⚙</span> Configuración</a>
            <a class="side-link logout-link" href="auth/logout.php"><span>↪</span> Cerrar sesión</a>
        </nav>
        <div class="sidebar-user"><div class="avatar"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div><div><strong><?= e($_SESSION['usuario']['nombre']) ?></strong><small><?= e($_SESSION['usuario']['rol']) ?></small></div></div>
    </aside>
    <section class="dashboard-area">
        <header class="dashboard-topbar">
            <div><span class="eyebrow">GESTIÓN DE VENTAS</span><h1>Ventas</h1><p>Consulta únicamente las ventas que han sido registradas en el sistema.</p></div>
            <div class="top-actions"><a href="nueva_venta.php" class="quick-button top-new-client"><span>＋</span> Nueva venta</a><a class="top-user" href="#"><div class="avatar small"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div><div><strong><?= e($_SESSION['usuario']['nombre']) ?></strong><small><?= e($_SESSION['usuario']['rol']) ?></small></div></a></div>
        </header>
        <main class="dashboard-content">
            <section class="panel sales-panel">
                <div class="panel-heading">
                    <div><h2>Ventas realizadas</h2><p><?= count($ventas) ?> venta<?= count($ventas) === 1 ? '' : 's' ?> registrada<?= count($ventas) === 1 ? '' : 's' ?> en la base de datos.</p></div>
                    <span class="panel-badge"><?= count($ventas) ?> realizadas</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>VENTA</th><th>CLIENTE</th><th>VENDEDOR</th><th>CAMPAÑA</th><th>FECHA</th><th>TOTAL</th></tr></thead>
                        <tbody>
                        <?php if (!$ventas): ?><tr><td colspan="6" class="empty-cell">Todavía no hay ventas realizadas.</td></tr>
                        <?php else: foreach ($ventas as $venta): ?>
                            <tr>
                                <td><span class="sale-id">#<?= (int)$venta['id'] ?></span></td>
                                <td><strong><?= e($venta['cliente']) ?></strong></td>
                                <td><?= e($venta['vendedor']) ?></td>
                                <td><?= $venta['campana'] ? e($venta['campana']) : '—' ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($venta['fecha_venta'])) ?></td>
                                <td><strong>$<?= number_format((float)$venta['total'], 0, ',', '.') ?></strong></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </section>
</div>
</body>
</html>
