<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_login();

$titulo = 'Panel principal';

$clientes = (int)$pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn();
$productos = (int)$pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn();
$ventas = (int)$pdo->query('SELECT COUNT(*) FROM ventas')->fetchColumn();
$usuarios = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();

$totalVentas = (float)$pdo->query('SELECT COALESCE(SUM(total),0) FROM ventas')->fetchColumn();
$stockBajo = (int)$pdo->query('SELECT COUNT(*) FROM productos WHERE stock <= 5')->fetchColumn();

$estados = ['Contacto Inicial', 'Propuesta', 'Negociación', 'Ganado', 'Perdido'];
$pipeline = [];
$stmt = $pdo->query('SELECT estado, COUNT(*) cantidad FROM clientes GROUP BY estado');
foreach ($stmt as $row) {
    $pipeline[$row['estado']] = (int)$row['cantidad'];
}

$ventasRecientes = $pdo->query("
    SELECT v.id, v.total, v.fecha_venta,
           CONCAT(c.nombre, ' ', c.apellido) AS cliente,
           u.nombre AS vendedor
    FROM ventas v
    JOIN clientes c ON c.id = v.cliente_id
    JOIN usuarios u ON u.id = v.usuario_id
    ORDER BY v.fecha_venta DESC
    LIMIT 5
")->fetchAll();

$interaccionesRecientes = $pdo->query("
    SELECT i.tipo, i.nota, i.fecha_interaccion,
           CONCAT(c.nombre, ' ', c.apellido) AS cliente,
           u.nombre AS usuario
    FROM interacciones i
    JOIN clientes c ON c.id = i.cliente_id
    JOIN usuarios u ON u.id = i.usuario_id
    ORDER BY i.fecha_interaccion DESC
    LIMIT 4
")->fetchAll();

include 'includes/header.php';

$mensajeCliente = $_SESSION['mensaje_cliente'] ?? null;
unset($_SESSION['mensaje_cliente']);
?>

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
            <a class="side-link active" href="dashboard.php">
                <span>⌂</span> Panel principal
            </a>
            <a class="side-link" href="clientes.php">
                <span>♙</span> Clientes
            </a>
            <a class="side-link" href="#">
                <span>▣</span> Productos
            </a>
            <a class="side-link" href="#">
                <span>▤</span> Ventas
            </a>
            <a class="side-link" href="#">
                <span>◌</span> Interacciones
            </a>
            <a class="side-link" href="#">
                <span>⚑</span> Campañas
            </a>
            <a class="side-link" href="#">
                <span>▦</span> Categorías
            </a>

            <div class="nav-divider"></div>
            <span class="nav-label">CUENTA</span>

            <a class="side-link" href="#">
                <span>⚙</span> Configuración
            </a>
            <a class="side-link logout-link" href="auth/logout.php">
                <span>↪</span> Cerrar sesión
            </a>
        </nav>

        <div class="sidebar-user">
            <div class="avatar"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <div>
                <strong><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></strong>
                <small><?= htmlspecialchars($_SESSION['usuario']['rol']) ?></small>
            </div>
        </div>
    </aside>

    <section class="dashboard-area">
        <header class="dashboard-topbar">
            <div>
                <span class="eyebrow">PANEL PRINCIPAL</span>
                <h1>Hola, <?= htmlspecialchars($_SESSION['usuario']['nombre']) ?> 👋</h1>
                <p>Todo lo importante de tu tienda, en un solo lugar.</p>
            </div>
            <div class="top-actions">
                <button class="icon-button" title="Notificaciones">♧</button>
                <a class="top-user" href="#">
                    <div class="avatar small"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
                    <div>
                        <strong><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></strong>
                        <small><?= htmlspecialchars($_SESSION['usuario']['rol']) ?></small>
                    </div>
                </a>
            </div>
        </header>

        <main class="dashboard-content">
            <?php if ($mensajeCliente): ?>
                <div class="success-banner">✓ <?= htmlspecialchars($mensajeCliente) ?></div>
            <?php endif; ?>
            <div class="stat-grid">
                <article class="stat-card">
                    <div class="stat-icon clients">♙</div>
                    <div class="stat-info">
                        <span>Clientes</span>
                        <strong><?= $clientes ?></strong>
                        <small>Registrados en el CRM</small>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon products">▣</div>
                    <div class="stat-info">
                        <span>Productos</span>
                        <strong><?= $productos ?></strong>
                        <small><?= $stockBajo ?> con stock bajo</small>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon sales">▤</div>
                    <div class="stat-info">
                        <span>Ventas</span>
                        <strong><?= $ventas ?></strong>
                        <small>$<?= number_format($totalVentas, 0, ',', '.') ?> acumulado</small>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon users">◉</div>
                    <div class="stat-info">
                        <span>Usuarios</span>
                        <strong><?= $usuarios ?></strong>
                        <small>Administradores y vendedores</small>
                    </div>
                </article>
            </div>

            <div class="quick-actions">
                <div>
                    <h2>Acciones rápidas</h2>
                    <p>Accede rápidamente a las tareas principales.</p>
                </div>
                <div class="quick-buttons">
                    <a href="nuevo_cliente.php" class="quick-button"><span>＋</span> Nuevo cliente</a>
                    <a href="#" class="quick-button"><span>＋</span> Nueva venta</a>
                    <a href="#" class="quick-button"><span>＋</span> Registrar interacción</a>
                </div>
            </div>

            <div class="content-grid">
                <section class="panel pipeline-panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Embudo de clientes</h2>
                            <p>Estado actual de tus oportunidades.</p>
                        </div>
                        <span class="panel-badge"><?= $clientes ?> clientes</span>
                    </div>

                    <div class="pipeline">
                        <?php
                        $colors = ['blue', 'purple', 'orange', 'green', 'red'];
                        foreach ($estados as $index => $estado):
                            $cantidad = $pipeline[$estado] ?? 0;
                            $porcentaje = $clientes > 0 ? round(($cantidad / $clientes) * 100) : 0;
                        ?>
                            <div class="pipeline-row">
                                <div class="pipeline-label">
                                    <span class="dot <?= $colors[$index] ?>"></span>
                                    <span><?= htmlspecialchars($estado) ?></span>
                                    <strong><?= $cantidad ?></strong>
                                </div>
                                <div class="progress"><i class="<?= $colors[$index] ?>" style="width: <?= $porcentaje ?>%"></i></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Actividad reciente</h2>
                            <p>Últimas interacciones registradas.</p>
                        </div>
                    </div>

                    <div class="activity-list">
                        <?php if (!$interaccionesRecientes): ?>
                            <div class="empty-state">Todavía no hay interacciones.</div>
                        <?php else: ?>
                            <?php foreach ($interaccionesRecientes as $item): ?>
                                <div class="activity-item">
                                    <div class="activity-icon"><?= $item['tipo'] === 'WhatsApp' ? '◉' : ($item['tipo'] === 'Llamada' ? '⌕' : '✉') ?></div>
                                    <div>
                                        <strong><?= htmlspecialchars($item['tipo']) ?> · <?= htmlspecialchars($item['cliente']) ?></strong>
                                        <p><?= htmlspecialchars($item['nota']) ?></p>
                                        <small><?= htmlspecialchars($item['usuario']) ?> · <?= date('d/m/Y H:i', strtotime($item['fecha_interaccion'])) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <section class="panel sales-panel">
                <div class="panel-heading">
                    <div>
                        <h2>Ventas recientes</h2>
                        <p>Las últimas ventas registradas en el sistema.</p>
                    </div>
                    <a href="#" class="view-link">Ver todas →</a>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>VENTA</th>
                                <th>CLIENTE</th>
                                <th>VENDEDOR</th>
                                <th>FECHA</th>
                                <th>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$ventasRecientes): ?>
                            <tr><td colspan="5" class="empty-cell">No hay ventas registradas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ventasRecientes as $venta): ?>
                                <tr>
                                    <td><span class="sale-id">#<?= (int)$venta['id'] ?></span></td>
                                    <td><strong><?= htmlspecialchars($venta['cliente']) ?></strong></td>
                                    <td><?= htmlspecialchars($venta['vendedor']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($venta['fecha_venta'])) ?></td>
                                    <td><strong>$<?= number_format((float)$venta['total'], 0, ',', '.') ?></strong></td>
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

<?php include 'includes/footer.php'; ?>
