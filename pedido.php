<?php
// pedido.php?id=N — detalle de un pedido; aquí se CONFIRMA (crea la venta y descuenta stock) o se cancela
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
if (empty($_SESSION['usuario'])) { header('Location: auth/login.php'); exit; }
require_once __DIR__ . '/includes/tienda_lib.php';

$id = (int)($_GET['id'] ?? 0);
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    t_csrf_check();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'cancelar') {
        $s = $pdo->prepare("UPDATE pedidos SET estado = 'Cancelado' WHERE id = ? AND estado = 'Pendiente'");
        $s->execute([$id]);
        t_flash_set('crm_ok', $s->rowCount() ? 'Pedido cancelado.' : 'Ese pedido ya no estaba pendiente.');
        header('Location: pedido.php?id=' . $id); exit;
    }

    if ($accion === 'confirmar') {
        $campanaId = (int)($_POST['campana_id'] ?? 0);
        $usuarioId = (int)$_SESSION['usuario']['id'];
        try {
            if ($campanaId > 0) {
                $s = $pdo->prepare('SELECT id FROM campanas WHERE id = ?'); $s->execute([$campanaId]);
                if (!$s->fetch()) throw new RuntimeException('La campaña seleccionada no existe.');
            }
            $pdo->beginTransaction();

            $s = $pdo->prepare('SELECT estado, cliente_id, total FROM pedidos WHERE id = ? FOR UPDATE');
            $s->execute([$id]);
            $ped = $s->fetch();
            if (!$ped || $ped['estado'] !== 'Pendiente') throw new RuntimeException('Este pedido ya fue procesado.');

            $s = $pdo->prepare('SELECT dp.producto_id, dp.cantidad, dp.precio_unitario, p.nombre
                                FROM detalles_pedido dp JOIN productos p ON p.id = dp.producto_id
                                WHERE dp.pedido_id = ? ORDER BY dp.producto_id');
            $s->execute([$id]);
            $items = $s->fetchAll();
            if (!$items) throw new RuntimeException('El pedido no tiene productos.');

            // Bloquea cada producto y comprueba stock real
            $lock = $pdo->prepare('SELECT stock FROM productos WHERE id = ? FOR UPDATE');
            foreach ($items as $it) {
                $lock->execute([$it['producto_id']]);
                $stock = (int)$lock->fetchColumn();
                if ($stock < (int)$it['cantidad']) {
                    throw new RuntimeException("Stock insuficiente de {$it['nombre']}: hay $stock y el pedido pide {$it['cantidad']}.");
                }
            }

            $pdo->prepare('INSERT INTO ventas (cliente_id, usuario_id, campana_id, total) VALUES (?, ?, ?, ?)')
                ->execute([$ped['cliente_id'], $usuarioId, $campanaId > 0 ? $campanaId : null, $ped['total']]);
            $ventaId = (int)$pdo->lastInsertId();

            $insDet = $pdo->prepare('INSERT INTO detalles_venta (venta_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)');
            $baja = $pdo->prepare('UPDATE productos SET stock = stock - ? WHERE id = ?');
            foreach ($items as $it) {
                $insDet->execute([$ventaId, $it['producto_id'], $it['cantidad'], $it['precio_unitario']]);
                $baja->execute([$it['cantidad'], $it['producto_id']]);
            }

            $pdo->prepare("UPDATE pedidos SET estado = 'Confirmado', venta_id = ? WHERE id = ?")->execute([$ventaId, $id]);
            $pdo->prepare("UPDATE clientes SET estado = 'Ganado' WHERE id = ?")->execute([$ped['cliente_id']]);

            $pdo->commit();
            t_flash_set('crm_ok', "Pedido #$id confirmado: se creó la venta #$ventaId y se descontó el stock.");
            header('Location: pedido.php?id=' . $id); exit;
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errores[] = $ex instanceof RuntimeException ? $ex->getMessage() : 'No se pudo confirmar el pedido.';
        }
    }
}

$s = $pdo->prepare("SELECT p.*, c.nombre, c.apellido, c.correo, c.telefono FROM pedidos p JOIN clientes c ON c.id = p.cliente_id WHERE p.id = ?");
$s->execute([$id]);
$pedido = $s->fetch();
if (!$pedido) { http_response_code(404); exit('Pedido no encontrado.'); }

$s = $pdo->prepare('SELECT dp.cantidad, dp.precio_unitario, p.nombre, p.stock
                    FROM detalles_pedido dp JOIN productos p ON p.id = dp.producto_id WHERE dp.pedido_id = ? ORDER BY p.nombre');
$s->execute([$id]);
$items = $s->fetchAll();
$hayFalta = false;
foreach ($items as $it) if ((int)$it['stock'] < (int)$it['cantidad']) $hayFalta = true;

$campanas = $pdo->query('SELECT id, nombre FROM campanas ORDER BY fecha_inicio DESC, nombre')->fetchAll();
$clases = ['Pendiente' => 'badge-orange', 'Confirmado' => 'badge-green', 'Cancelado' => 'badge-gray'];
$wa = t_wa_link($pedido['telefono'], 'Hola ' . $pedido['nombre'] . ', te escribimos por tu pedido #' . $id);

$titulo = 'Pedido #' . $id; $eyebrow = 'TIENDA EN LÍNEA';
$subtitulo = 'Recibido el ' . date('d/m/Y H:i', strtotime($pedido['fecha_pedido']));
$activo = 'pedidos';
$acciones = '<a href="pedidos.php" class="back-link">← Volver a pedidos</a>';
require __DIR__ . '/includes/crm_top.php';
?>
<div class="content-grid">
    <section class="panel">
        <div class="panel-heading"><div><h2>Cliente</h2></div><span class="badge <?= $clases[$pedido['estado']] ?>"><?= e($pedido['estado']) ?></span></div>
        <dl class="detail-list">
            <dt>Nombre</dt><dd><?= e($pedido['nombre'] . ' ' . $pedido['apellido']) ?></dd>
            <dt>Correo</dt><dd><?= e($pedido['correo']) ?></dd>
            <dt>Teléfono</dt><dd><?= $pedido['telefono'] ? e($pedido['telefono']) : '—' ?>
                <?php if ($wa): ?> · <a class="view-link" href="<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?></dd>
            <dt>Nota</dt><dd><?= $pedido['nota'] ? nl2br(e($pedido['nota'])) : '—' ?></dd>
            <?php if ($pedido['venta_id']): ?><dt>Venta</dt><dd><span class="sale-id">#<?= (int)$pedido['venta_id'] ?></span></dd><?php endif; ?>
        </dl>
    </section>

    <section class="panel">
        <div class="panel-heading"><div><h2>Productos</h2></div><span class="panel-badge"><?= moneda($pedido['total']) ?></span></div>
        <div class="table-wrap"><table>
            <thead><tr><th>PRODUCTO</th><th>CANT.</th><th>PRECIO</th><th>SUBTOTAL</th><?php if ($pedido['estado'] === 'Pendiente'): ?><th>STOCK ACTUAL</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($items as $it): $falta = (int)$it['stock'] < (int)$it['cantidad']; ?>
                <tr>
                    <td><strong><?= e($it['nombre']) ?></strong></td>
                    <td><?= (int)$it['cantidad'] ?></td>
                    <td><?= moneda($it['precio_unitario']) ?></td>
                    <td><?= moneda($it['cantidad'] * $it['precio_unitario']) ?></td>
                    <?php if ($pedido['estado'] === 'Pendiente'): ?><td class="<?= $falta ? 'lack' : '' ?>"><?= (int)$it['stock'] ?><?= $falta ? ' (insuficiente)' : '' ?></td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody></table></div>
    </section>
</div>

<?php if ($pedido['estado'] === 'Pendiente'): ?>
<section class="panel">
    <div class="panel-heading"><div><h2>Procesar pedido</h2>
        <p>Confirma cuando ya tengas el pago acordado. Se crea la venta a tu nombre y se descuenta el stock.</p></div></div>
    <?php if ($hayFalta): ?><div class="alert error">Falta stock en algún producto. Ajusta el inventario antes de confirmar.</div><?php endif; ?>
    <form method="POST" class="actions-row">
        <?= t_csrf_field() ?>
        <select name="campana_id" aria-label="Campaña">
            <option value="0">Sin campaña</option>
            <?php foreach ($campanas as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['nombre']) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-primary" name="accion" value="confirmar" type="submit" <?= $hayFalta ? 'disabled' : '' ?>>Confirmar y crear venta</button>
        <button class="btn btn-danger" name="accion" value="cancelar" type="submit"
                onclick="return confirm('¿Cancelar este pedido?')">Cancelar pedido</button>
    </form>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/crm_bottom.php'; ?>
