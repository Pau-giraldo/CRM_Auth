<?php
// carrito.php — carrito, formulario de pedido y pantalla de gracias (público)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/tienda_lib.php';

$errores = [];
$datos = ['nombre' => '', 'apellido' => '', 'correo' => '', 'telefono' => '', 'nota' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    t_csrf_check();
    $accion = $_POST['accion'] ?? '';

    // ---- Agregar desde la tienda ----
    if ($accion === 'agregar') {
        $pid  = (int)($_POST['producto_id'] ?? 0);
        $cant = max(1, min(99, (int)($_POST['cantidad'] ?? 1)));
        $p = producto_disponible($pdo, $pid);
        if (!$p || (int)$p['stock'] <= 0) {
            t_flash_set('tienda', 'Ese producto no está disponible.');
        } else {
            $nuevo = min((int)$p['stock'], ($_SESSION['carrito'][$pid] ?? 0) + $cant);
            $_SESSION['carrito'][$pid] = $nuevo;
            t_flash_set('tienda', $p['nombre'] . ' se agregó al carrito.');
        }
        $desde = (int)($_POST['desde_producto'] ?? 0);
        if ($desde > 0) { header('Location: producto.php?id=' . $desde); exit; }
        $volver = [];
        if ((int)($_POST['categoria'] ?? 0) > 0) $volver['categoria'] = (int)$_POST['categoria'];
        if (in_array($_POST['orden'] ?? '', ['menor', 'mayor'], true)) $volver['orden'] = $_POST['orden'];
        header('Location: tienda.php' . ($volver ? '?' . http_build_query($volver) : ''));
        exit;
    }

    // ---- Actualizar cantidades / quitar ----
    if ($accion === 'actualizar') {
        if (isset($_POST['quitar'])) {
            unset($_SESSION['carrito'][(int)$_POST['quitar']]);
        } else {
            foreach (($_POST['cant'] ?? []) as $pid => $q) {
                $pid = (int)$pid; $q = (int)$q;
                if (!isset($_SESSION['carrito'][$pid])) continue;
                if ($q <= 0) unset($_SESSION['carrito'][$pid]);
                else $_SESSION['carrito'][$pid] = min($q, 99);   // se ajusta al stock en carrito_lineas()
            }
        }
        header('Location: carrito.php'); exit;
    }

    // ---- Enviar pedido ----
    if ($accion === 'pedir') {
        foreach ($datos as $k => $_) $datos[$k] = trim($_POST[$k] ?? '');

        // Anti-spam sencillo: campo trampa que un humano no ve
        if (!empty($_POST['sitio_web'])) { header('Location: tienda.php'); exit; }

        $ahora = time();
        $hist = array_values(array_filter($_SESSION['pedidos_ts'] ?? [], fn($t) => $ahora - $t < 3600));
        if (count($hist) >= 5) $errores[] = 'Has enviado varios pedidos seguidos. Inténtalo de nuevo más tarde.';
        elseif ($hist && $ahora - max($hist) < 20) $errores[] = 'Espera unos segundos antes de enviar otro pedido.';

        if (mb_strlen($datos['nombre']) < 2 || mb_strlen($datos['nombre']) > 100)     $errores[] = 'Escribe tu nombre.';
        if (mb_strlen($datos['apellido']) < 2 || mb_strlen($datos['apellido']) > 100) $errores[] = 'Escribe tu apellido.';
        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL) || mb_strlen($datos['correo']) > 150) $errores[] = 'Escribe un correo válido.';
        if (!preg_match('/^[0-9+\s()-]{7,20}$/', $datos['telefono'])) $errores[] = 'Escribe un teléfono válido para poder contactarte.';
        if (mb_strlen($datos['nota']) > 500) $errores[] = 'La nota no puede pasar de 500 caracteres.';

        $lineas = carrito_lineas($pdo);
        if (!$lineas) $errores[] = 'Tu carrito está vacío.';
        foreach ($lineas as $l) {
            if ($l['ajustado']) { $errores[] = 'Ajustamos algunas cantidades al stock disponible. Revísalas y vuelve a enviar.'; break; }
        }

        if (!$errores) {
            try {
                $pdo->beginTransaction();

                // Precio y stock se leen de la BD, nunca del navegador
                $total = 0.0; $items = [];
                foreach ($lineas as $l) {
                    $s = $pdo->prepare('SELECT precio, stock FROM productos WHERE id = ?');
                    $s->execute([$l['id']]);
                    $row = $s->fetch();
                    if (!$row || (int)$row['stock'] < $l['cantidad']) {
                        throw new RuntimeException('Ya no hay suficiente stock de ' . $l['nombre'] . '. Revisa tu carrito.');
                    }
                    $items[] = [$l['id'], $l['cantidad'], $row['precio']];
                    $total += $l['cantidad'] * (float)$row['precio'];
                }

                // Cliente: si el correo ya existe se reutiliza (sin modificar sus datos); si no, se crea
                $s = $pdo->prepare('SELECT id FROM clientes WHERE correo = ? LIMIT 1');
                $s->execute([$datos['correo']]);
                $clienteId = (int)$s->fetchColumn();
                if (!$clienteId) {
                    try {
                        $pdo->prepare('INSERT INTO clientes (nombre, apellido, correo, telefono, estado) VALUES (?, ?, ?, ?, ?)')
                            ->execute([$datos['nombre'], $datos['apellido'], $datos['correo'], $datos['telefono'], 'Contacto Inicial']);
                        $clienteId = (int)$pdo->lastInsertId();
                    } catch (PDOException $ex) {
                        if ($ex->getCode() !== '23000') throw $ex;      // correo duplicado por carrera: lo buscamos de nuevo
                        $s->execute([$datos['correo']]);
                        $clienteId = (int)$s->fetchColumn();
                    }
                }

                $pdo->prepare('INSERT INTO pedidos (cliente_id, total, nota) VALUES (?, ?, ?)')
                    ->execute([$clienteId, $total, $datos['nota'] !== '' ? $datos['nota'] : null]);
                $pedidoId = (int)$pdo->lastInsertId();

                $ins = $pdo->prepare('INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)');
                foreach ($items as [$pid, $cant, $precio]) $ins->execute([$pedidoId, $pid, $cant, $precio]);

                $pdo->commit();

                $hist[] = $ahora;
                $_SESSION['pedidos_ts'] = $hist;
                $_SESSION['carrito'] = [];
                $_SESSION['ultimo_pedido'] = $pedidoId;
                header('Location: carrito.php?gracias=1'); exit;
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errores[] = $ex instanceof RuntimeException ? $ex->getMessage() : 'No pudimos registrar tu pedido. Inténtalo de nuevo.';
            }
        }
    }
}

$gracias = isset($_GET['gracias']) && !empty($_SESSION['ultimo_pedido']);
$lineas  = $gracias ? [] : carrito_lineas($pdo);
$total   = array_sum(array_column($lineas, 'subtotal'));
$mensaje = t_flash_get('tienda');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Carrito | <?= e(NOMBRE_TIENDA) ?></title>
<link rel="stylesheet" href="assets/css/tienda.css">
</head>
<body>
<header>
  <h1><a href="tienda.php"><?= e(NOMBRE_TIENDA) ?></a></h1>
  <p><?= $gracias ? 'Pedido recibido' : 'Tu carrito' ?></p>
</header>
<div class="wrap">

<?php if ($gracias): ?>
  <?php $wa = t_wa_link(WHATSAPP_TIENDA, 'Hola, hice el pedido #' . (int)$_SESSION['ultimo_pedido']); ?>
  <section class="panel center">
    <h2>¡Gracias por tu pedido!</h2>
    <p>Tu número de pedido es <strong>#<?= (int)$_SESSION['ultimo_pedido'] ?></strong>.</p>
    <p class="note">Lo recibimos y nos pondremos en contacto contigo para confirmar el pago y la entrega. El pedido queda reservado solo cuando lo confirmemos.</p>
    <?php if ($wa): ?><a class="btn inline" href="<?= e($wa) ?>" target="_blank" rel="noopener">Escribirnos por WhatsApp</a><?php endif; ?>
    <a class="btn sec inline" href="tienda.php">Seguir viendo productos</a>
  </section>

<?php else: ?>
  <?php if ($mensaje): ?><div class="msg" style="width:100%"><?= e($mensaje) ?></div><?php endif; ?>
  <?php if ($errores): ?><div class="errors"><?php foreach ($errores as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div><?php endif; ?>

  <?php if (!$lineas): ?>
    <section class="panel center">
      <p>Tu carrito está vacío.</p>
      <a class="btn inline" href="tienda.php">Ver productos</a>
    </section>
  <?php else: ?>
    <section class="panel">
      <form method="POST">
        <?= t_csrf_field() ?><input type="hidden" name="accion" value="actualizar">
        <table>
          <thead><tr><th>Producto</th><th class="num">Precio</th><th>Cantidad</th><th class="num">Subtotal</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($lineas as $l): ?>
            <tr>
              <td><strong><?= e($l['nombre']) ?></strong>
                <?php if ($l['ajustado']): ?><br><span class="note">Cantidad ajustada: solo quedan <?= $l['stock'] ?>.</span><?php endif; ?></td>
              <td class="num"><?= moneda($l['precio']) ?></td>
              <td><input class="qty" type="number" name="cant[<?= $l['id'] ?>]" value="<?= $l['cantidad'] ?>" min="0" max="<?= $l['stock'] ?>"></td>
              <td class="num"><?= moneda($l['subtotal']) ?></td>
              <td class="num"><button class="btn sec inline" name="quitar" value="<?= $l['id'] ?>" title="Quitar">Quitar</button></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <div class="total"><span>Total</span><span><?= moneda($total) ?></span></div>
        <button class="btn sec inline" type="submit">Actualizar cantidades</button>
        <a class="btn sec inline" href="tienda.php">Seguir comprando</a>
      </form>
    </section>

    <section class="panel">
      <h2>Tus datos</h2>
      <form method="POST">
        <?= t_csrf_field() ?><input type="hidden" name="accion" value="pedir">
        <div class="hp" aria-hidden="true"><label>No llenar<input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label></div>
        <div class="form-grid">
          <div class="field"><label>Nombre *</label><input type="text" name="nombre" maxlength="100" required value="<?= e($datos['nombre']) ?>"></div>
          <div class="field"><label>Apellido *</label><input type="text" name="apellido" maxlength="100" required value="<?= e($datos['apellido']) ?>"></div>
          <div class="field"><label>Correo *</label><input type="email" name="correo" maxlength="150" required value="<?= e($datos['correo']) ?>"></div>
          <div class="field"><label>Teléfono / WhatsApp *</label><input type="tel" name="telefono" maxlength="20" required value="<?= e($datos['telefono']) ?>"></div>
        </div>
        <div class="field" style="margin-top:14px"><label>Nota (opcional)</label><textarea name="nota" maxlength="500" placeholder="Dirección de entrega, comentarios…"><?= e($datos['nota']) ?></textarea></div>
        <p class="note">Al enviar, no se hace ningún cobro: te contactaremos para confirmar el pago y la entrega.</p>
        <button class="btn" type="submit">Enviar pedido · <?= moneda($total) ?></button>
      </form>
    </section>
  <?php endif; ?>
<?php endif; ?>

</div>
<footer>© <?= date('Y') ?> <?= e(NOMBRE_TIENDA) ?></footer>
</body>
</html>
