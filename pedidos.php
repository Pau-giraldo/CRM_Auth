<?php
// pedidos.php — bandeja de pedidos de la tienda (CRM, requiere iniciar sesión)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
if (empty($_SESSION['usuario'])) { header('Location: auth/login.php'); exit; }
require_once __DIR__ . '/includes/tienda_lib.php';

$estados = ['Pendiente', 'Confirmado', 'Cancelado'];
$f = in_array($_GET['estado'] ?? '', $estados, true) ? $_GET['estado'] : '';

$sql = "SELECT p.id, p.estado, p.total, p.fecha_pedido, p.venta_id,
               CONCAT(c.nombre, ' ', c.apellido) AS cliente,
               (SELECT COALESCE(SUM(cantidad), 0) FROM detalles_pedido WHERE pedido_id = p.id) AS unidades
        FROM pedidos p JOIN clientes c ON c.id = p.cliente_id"
     . ($f ? ' WHERE p.estado = ?' : '')
     . " ORDER BY (p.estado = 'Pendiente') DESC, p.fecha_pedido DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($f ? [$f] : []);
$pedidos = $stmt->fetchAll();

$clases = ['Pendiente' => 'badge-orange', 'Confirmado' => 'badge-green', 'Cancelado' => 'badge-gray'];
$titulo = 'Pedidos'; $eyebrow = 'TIENDA EN LÍNEA';
$subtitulo = 'Pedidos hechos desde la tienda. Confírmalos para convertirlos en venta.';
$activo = 'pedidos'; $acciones = '';
require __DIR__ . '/includes/crm_top.php';
?>
<section class="panel">
    <div class="panel-heading">
        <div><h2>Pedidos</h2><p><?= count($pedidos) ?> mostrado<?= count($pedidos) === 1 ? '' : 's' ?>.</p></div>
        <form method="GET">
            <select name="estado" onchange="this.form.submit()" style="padding:8px 12px;border:1px solid #d1d5db;border-radius:8px">
                <option value="">Todos los estados</option>
                <?php foreach ($estados as $es): ?><option <?= $f === $es ? 'selected' : '' ?>><?= e($es) ?></option><?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>PEDIDO</th><th>FECHA</th><th>CLIENTE</th><th>UNIDADES</th><th>TOTAL</th><th>ESTADO</th><th></th></tr></thead>
        <tbody>
        <?php if (!$pedidos): ?><tr><td colspan="7" class="empty-cell">No hay pedidos todavía.</td></tr><?php endif; ?>
        <?php foreach ($pedidos as $p): ?>
            <tr>
                <td><span class="sale-id">#<?= (int)$p['id'] ?></span></td>
                <td><?= date('d/m/Y H:i', strtotime($p['fecha_pedido'])) ?></td>
                <td><strong><?= e($p['cliente']) ?></strong></td>
                <td><?= (int)$p['unidades'] ?></td>
                <td><strong><?= moneda($p['total']) ?></strong></td>
                <td><span class="badge <?= $clases[$p['estado']] ?>"><?= e($p['estado']) ?></span></td>
                <td><a class="view-link" href="pedido.php?id=<?= (int)$p['id'] ?>">Ver →</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
</section>
<?php require __DIR__ . '/includes/crm_bottom.php'; ?>
