<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_login();

$titulo = 'Nueva venta';
$errores = [];

$clienteId = (int)($_POST['cliente_id'] ?? 0);
$productoId = (int)($_POST['producto_id'] ?? 0);
$cantidad = (int)($_POST['cantidad'] ?? 1);
$campanaId = (int)($_POST['campana_id'] ?? 0);

$clientes = $pdo->query("SELECT id, nombre, apellido FROM clientes ORDER BY nombre, apellido")->fetchAll();
$productos = $pdo->query("SELECT id, nombre, precio, stock FROM productos ORDER BY nombre")->fetchAll();
$campanas = $pdo->query("SELECT id, nombre FROM campanas ORDER BY fecha_inicio DESC, nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($clienteId <= 0) $errores[] = 'Selecciona un cliente.';
    if ($productoId <= 0) $errores[] = 'Selecciona un producto.';
    if ($cantidad <= 0) $errores[] = 'La cantidad debe ser mayor que cero.';

    $producto = null;
    if (!$errores) {
        $stmt = $pdo->prepare('SELECT id, nombre, precio, stock FROM productos WHERE id = ? LIMIT 1');
        $stmt->execute([$productoId]);
        $producto = $stmt->fetch();
        if (!$producto) $errores[] = 'El producto seleccionado no existe.';
        elseif ((int)$producto['stock'] < $cantidad) $errores[] = 'No hay suficiente stock para la cantidad solicitada.';
    }

    if (!$errores) {
        $stmt = $pdo->prepare('SELECT id FROM clientes WHERE id = ? LIMIT 1');
        $stmt->execute([$clienteId]);
        if (!$stmt->fetch()) $errores[] = 'El cliente seleccionado no existe.';
    }

    if (!$errores && $campanaId > 0) {
        $stmt = $pdo->prepare('SELECT id FROM campanas WHERE id = ? LIMIT 1');
        $stmt->execute([$campanaId]);
        if (!$stmt->fetch()) $errores[] = 'La campaña seleccionada no existe.';
    }

    if (!$errores) {
        $total = (float)$producto['precio'] * $cantidad;
        $usuarioId = (int)$_SESSION['usuario']['id'];

        try {
            $pdo->beginTransaction();

            // Se vuelve a comprobar el stock dentro de la transacción para evitar vender más unidades de las disponibles.
            $stmt = $pdo->prepare('SELECT precio, stock FROM productos WHERE id = ? FOR UPDATE');
            $stmt->execute([$productoId]);
            $productoActual = $stmt->fetch();
            if (!$productoActual || (int)$productoActual['stock'] < $cantidad) {
                throw new RuntimeException('El stock cambió y ya no es suficiente para realizar esta venta.');
            }

            $stmt = $pdo->prepare('INSERT INTO ventas (cliente_id, usuario_id, campana_id, total) VALUES (?, ?, ?, ?)');
            $stmt->execute([$clienteId, $usuarioId, $campanaId > 0 ? $campanaId : null, $total]);
            $ventaId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO detalles_venta (venta_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)');
            $stmt->execute([$ventaId, $productoId, $cantidad, $productoActual['precio']]);

            $stmt = $pdo->prepare('UPDATE productos SET stock = stock - ? WHERE id = ?');
            $stmt->execute([$cantidad, $productoId]);

            $pdo->commit();
            $_SESSION['mensaje_venta'] = 'Venta #'.$ventaId.' registrada correctamente.';
            header('Location: dashboard.php');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errores[] = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo registrar la venta.';
        }
    }
}

include 'includes/header.php';
?>

<div class="client-form-page">
    <div class="client-form-card">
        <div class="form-page-head">
            <div>
                <span class="eyebrow">VENTAS</span>
                <h1>Nueva venta</h1>
                <p>Registra una venta realizada por un cliente.</p>
            </div>
            <a href="dashboard.php" class="back-link">← Volver al panel</a>
        </div>

        <?php if ($errores): ?>
            <div class="alert error">
                <?php foreach ($errores as $error): ?><div><?= htmlspecialchars($error) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="client-form">
            <div class="form-grid">
                <div class="form-field">
                    <label for="cliente_id">Cliente <span>*</span></label>
                    <select id="cliente_id" name="cliente_id" required>
                        <option value="">Selecciona un cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= (int)$cliente['id'] ?>" <?= $clienteId === (int)$cliente['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cliente['nombre'].' '.$cliente['apellido']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="producto_id">Producto <span>*</span></label>
                    <select id="producto_id" name="producto_id" required>
                        <option value="">Selecciona un producto</option>
                        <?php foreach ($productos as $producto): ?>
                            <option value="<?= (int)$producto['id'] ?>" <?= $productoId === (int)$producto['id'] ? 'selected' : '' ?> <?= (int)$producto['stock'] <= 0 ? 'disabled' : '' ?>>
                                <?= htmlspecialchars($producto['nombre']) ?> — $<?= number_format((float)$producto['precio'], 0, ',', '.') ?> (stock: <?= (int)$producto['stock'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="cantidad">Cantidad <span>*</span></label>
                    <input type="number" id="cantidad" name="cantidad" min="1" value="<?= max(1, $cantidad) ?>" required>
                </div>

                <div class="form-field">
                    <label for="campana_id">Campaña</label>
                    <select id="campana_id" name="campana_id">
                        <option value="0">Sin campaña</option>
                        <?php foreach ($campanas as $campana): ?>
                            <option value="<?= (int)$campana['id'] ?>" <?= $campanaId === (int)$campana['id'] ? 'selected' : '' ?>><?= htmlspecialchars($campana['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-required-note">* Campos obligatorios. La venta se guarda en <strong>ventas</strong> y su producto en <strong>detalles_venta</strong>. El stock se descuenta automáticamente.</div>

            <div class="form-actions">
                <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary form-submit">Guardar venta</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
