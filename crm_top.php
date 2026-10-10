<?php
// includes/crm_top.php — sidebar + topbar para las pantallas de pedidos del CRM
// Antes define: $titulo, $eyebrow, $subtitulo, $activo, y opcional $acciones (HTML) / $errores (array)
$u = $_SESSION['usuario'];
$inicial = strtoupper(mb_substr($u['nombre'], 0, 1));
$pendientes = (int)$pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'Pendiente'")->fetchColumn();
$okMsg = t_flash_get('crm_ok');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo ?? '') ?> | CRM Tienda</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600}
        .badge-orange{background:#ffedd5;color:#c2410c}.badge-green{background:#dcfce7;color:#15803d}
        .badge-red{background:#fee2e2;color:#b91c1c}.badge-gray{background:#e5e7eb;color:#374151}
        .pill{background:#ef4444;color:#fff;border-radius:999px;font-size:11px;padding:1px 7px;margin-left:auto}
        .detail-list{display:grid;grid-template-columns:120px 1fr;gap:8px 12px;margin:0}
        .detail-list dt{color:#6b7280}.detail-list dd{margin:0;font-weight:500}
        .actions-row{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:16px}
        .actions-row select{padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font:inherit}
        .lack{color:#b91c1c;font-weight:600}
    </style>
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-brand"><div class="brand-mark">C</div><div><strong>CRM Tienda</strong><small>Panel de gestión</small></div></div>
        <nav class="side-nav">
            <a class="side-link" href="dashboard.php"><span>⌂</span> Panel principal</a>
            <a class="side-link" href="clientes.php"><span>♙</span> Clientes</a>
            <a class="side-link <?= ($activo ?? '') === 'productos' ? 'active' : '' ?>" href="productos.php"><span>▣</span> Productos</a>
            <a class="side-link <?= ($activo ?? '') === 'pedidos' ? 'active' : '' ?>" href="pedidos.php"><span>◫</span> Pedidos
                <?php if ($pendientes): ?><span class="pill"><?= $pendientes ?></span><?php endif; ?></a>
            <a class="side-link" href="ventas.php"><span>▤</span> Ventas</a>
            <a class="side-link <?= ($activo ?? '') === 'interacciones' ? 'active' : '' ?>" href="interacciones.php"><span>◌</span> Interacciones</a>
            <a class="side-link <?= ($activo ?? '') === 'campanas' ? 'active' : '' ?>" href="campanas.php"><span>⚑</span> Campañas</a>
            <a class="side-link <?= ($activo ?? '') === 'categorias' ? 'active' : '' ?>" href="categorias.php"><span>▦</span> Categorías</a>
            <a class="side-link" href="tienda.php" target="_blank"><span>↗</span> Ver tienda</a>
            <div class="nav-divider"></div>
            <a class="side-link logout-link" href="auth/logout.php"><span>↪</span> Cerrar sesión</a>
        </nav>
        <div class="sidebar-user"><div class="avatar"><?= e($inicial) ?></div><div><strong><?= e($u['nombre']) ?></strong><small><?= e($u['rol']) ?></small></div></div>
    </aside>
    <section class="dashboard-area">
        <header class="dashboard-topbar">
            <div><span class="eyebrow"><?= e($eyebrow ?? '') ?></span><h1><?= e($titulo ?? '') ?></h1><p><?= e($subtitulo ?? '') ?></p></div>
            <div class="top-actions"><?= $acciones ?? '' ?></div>
        </header>
        <main class="dashboard-content">
            <?php if ($okMsg): ?><div class="success-banner">✓ <?= e($okMsg) ?></div><?php endif; ?>
            <?php if (!empty($errores)): ?><div class="alert error"><?php foreach ($errores as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div><?php endif; ?>
