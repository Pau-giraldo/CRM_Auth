<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_login();

$titulo = 'Clientes';

$clientes = $pdo->query("SELECT id, nombre, apellido, correo, telefono, empresa, estado, fecha_registro FROM clientes ORDER BY fecha_registro DESC, id DESC")->fetchAll();

$estadoClases = [
    'Contacto Inicial' => 'status-blue',
    'Propuesta' => 'status-purple',
    'Negociación' => 'status-orange',
    'Ganado' => 'status-green',
    'Perdido' => 'status-red'
];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes | CRM Tienda</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark">C</div>
            <div>
                <strong>CRM Tienda</strong>
                <small>Panel de gestión</small>
            </div>
        </div>

        <nav class="side-nav">
            <a class="side-link" href="dashboard.php"><span>⌂</span> Panel principal</a>
            <a class="side-link active" href="clientes.php"><span>♙</span> Clientes</a>
            <a class="side-link" href="#"><span>▣</span> Productos</a>
            <a class="side-link" href="#"><span>▤</span> Ventas</a>
            <a class="side-link" href="#"><span>◌</span> Interacciones</a>
            <a class="side-link" href="#"><span>⚑</span> Campañas</a>
            <a class="side-link" href="#"><span>▦</span> Categorías</a>

            <div class="nav-divider"></div>
            <span class="nav-label">CUENTA</span>
            <a class="side-link" href="#"><span>⚙</span> Configuración</a>
            <a class="side-link logout-link" href="auth/logout.php"><span>↪</span> Cerrar sesión</a>
        </nav>

        <div class="sidebar-user">
            <div class="avatar"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <div>
                <strong><?= e($_SESSION['usuario']['nombre']) ?></strong>
                <small><?= e($_SESSION['usuario']['rol']) ?></small>
            </div>
        </div>
    </aside>

    <section class="dashboard-area">
        <header class="dashboard-topbar">
            <div>
                <span class="eyebrow">GESTIÓN DE CLIENTES</span>
                <h1>Clientes</h1>
                <p>Consulta todos los clientes registrados en tu CRM.</p>
            </div>
            <div class="top-actions">
                <a href="nuevo_cliente.php" class="quick-button top-new-client"><span>＋</span> Nuevo cliente</a>
                <a class="top-user" href="#">
                    <div class="avatar small"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
                    <div>
                        <strong><?= e($_SESSION['usuario']['nombre']) ?></strong>
                        <small><?= e($_SESSION['usuario']['rol']) ?></small>
                    </div>
                </a>
            </div>
        </header>

        <main class="dashboard-content">
            <section class="panel clients-list-panel">
                <div class="panel-heading clients-heading">
                    <div>
                        <h2>Clientes registrados</h2>
                        <p><?= count($clientes) ?> cliente<?= count($clientes) === 1 ? '' : 's' ?> guardado<?= count($clientes) === 1 ? '' : 's' ?> en la base de datos.</p>
                    </div>
                    <span class="panel-badge"><?= count($clientes) ?> registrados</span>
                </div>

                <div class="table-wrap">
                    <table class="clients-table">
                        <thead>
                            <tr>
                                <th>CLIENTE</th>
                                <th>CORREO</th>
                                <th>TELÉFONO</th>
                                <th>EMPRESA</th>
                                <th>ESTADO</th>
                                <th>REGISTRO</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$clientes): ?>
                            <tr>
                                <td colspan="6" class="empty-cell">
                                    No hay clientes registrados todavía. <a href="nuevo_cliente.php" class="view-link">Agregar el primero →</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clientes as $cliente): ?>
                                <?php $nombreCompleto = trim($cliente['nombre'] . ' ' . $cliente['apellido']); ?>
                                <tr>
                                    <td>
                                        <div class="client-name-cell">
                                            <div class="client-avatar"><?= e(strtoupper(substr($cliente['nombre'], 0, 1))) ?></div>
                                            <div>
                                                <strong><?= e($nombreCompleto) ?></strong>
                                                <small>#<?= (int)$cliente['id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= e($cliente['correo']) ?></td>
                                    <td><?= $cliente['telefono'] ? e($cliente['telefono']) : '—' ?></td>
                                    <td><?= $cliente['empresa'] ? e($cliente['empresa']) : '—' ?></td>
                                    <td><span class="client-status <?= e($estadoClases[$cliente['estado']] ?? 'status-blue') ?>"><?= e($cliente['estado']) ?></span></td>
                                    <td><?= date('d/m/Y', strtotime($cliente['fecha_registro'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </section>
</div>
</body>
</html>
