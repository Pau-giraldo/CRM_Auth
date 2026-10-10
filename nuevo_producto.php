<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_login();

$titulo = 'Nuevo producto';
$mensaje = '';
$error = '';

$categorias = $pdo->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);

    if ($categoria_id <= 0 || $nombre === '' || $precio <= 0 || $stock < 0) {
        $error = 'Completa los campos obligatorios y verifica el precio y la cantidad.';
    } else {
        $check = $pdo->prepare('SELECT id FROM productos WHERE sku = ? LIMIT 1');
        $check->execute([$sku !== '' ? $sku : null]);
        if ($sku !== '' && $check->fetch()) {
            $error = 'Ya existe un producto con ese SKU.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO productos (categoria_id, nombre, sku, precio, stock) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$categoria_id, $nombre, $sku !== '' ? $sku : null, $precio, $stock]);
            $_SESSION['mensaje_producto'] = 'Producto creado correctamente.';
            header('Location: dashboard.php#productos');
            exit;
        }
    }
}

include 'includes/header.php';
?>
<div class="client-form-page">
    <div class="client-form-card">
        <div class="form-page-head">
            <div>
                <span class="eyebrow">PRODUCTOS</span>
                <h1>Nuevo producto</h1>
                <p>Agrega un producto al catálogo y define su cantidad inicial.</p>
            </div>
            <a class="back-link" href="dashboard.php#productos">← Volver al panel</a>
        </div>

        <?php if ($error): ?><div class="error-banner">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="post" class="client-form">
            <div class="form-grid">
                <div class="form-field">
                    <label>Nombre <span>*</span></label>
                    <input type="text" name="nombre" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" placeholder="Ej. Cadena dorada">
                </div>
                <div class="form-field">
                    <label>Categoría <span>*</span></label>
                    <select name="categoria_id" required>
                        <option value="">Selecciona una categoría</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= (int)$categoria['id'] ?>" <?= ((int)($_POST['categoria_id'] ?? 0) === (int)$categoria['id']) ? 'selected' : '' ?>><?= htmlspecialchars($categoria['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label>SKU</label>
                    <input type="text" name="sku" value="<?= htmlspecialchars($_POST['sku'] ?? '') ?>" placeholder="Ej. CAD-DOR">
                </div>
                <div class="form-field">
                    <label>Precio <span>*</span></label>
                    <input type="number" name="precio" min="1" step="0.01" required value="<?= htmlspecialchars($_POST['precio'] ?? '') ?>" placeholder="150000">
                </div>
                <div class="form-field">
                    <label>Cantidad inicial <span>*</span></label>
                    <input type="number" name="stock" min="0" required value="<?= htmlspecialchars($_POST['stock'] ?? '0') ?>">
                </div>
            </div>
            <div class="form-actions">
                <a class="btn btn-secondary" href="dashboard.php#productos">Cancelar</a>
                <button class="btn form-submit" type="submit">Guardar producto</button>
            </div>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
