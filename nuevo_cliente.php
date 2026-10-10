<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_login();

$titulo = 'Nuevo cliente';
$errores = [];

$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$empresa = trim($_POST['empresa'] ?? '');
$estado = $_POST['estado'] ?? 'Contacto Inicial';

$estadosPermitidos = ['Contacto Inicial', 'Propuesta', 'Negociación', 'Ganado', 'Perdido'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($nombre === '') $errores[] = 'El nombre es obligatorio.';
    if ($apellido === '') $errores[] = 'El apellido es obligatorio.';
    if ($correo === '') {
        $errores[] = 'El correo es obligatorio.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un correo electrónico válido.';
    }
    if (!in_array($estado, $estadosPermitidos, true)) {
        $errores[] = 'El estado seleccionado no es válido.';
    }

    if (!$errores) {
        $stmt = $pdo->prepare('SELECT id FROM clientes WHERE correo = ? LIMIT 1');
        $stmt->execute([$correo]);

        if ($stmt->fetch()) {
            $errores[] = 'Ya existe un cliente registrado con ese correo electrónico.';
        }
    }

    if (!$errores) {
        $stmt = $pdo->prepare('INSERT INTO clientes (nombre, apellido, correo, telefono, empresa, estado) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $nombre,
            $apellido,
            $correo,
            $telefono !== '' ? $telefono : null,
            $empresa !== '' ? $empresa : null,
            $estado
        ]);

        $_SESSION['mensaje_cliente'] = 'Cliente agregado correctamente.';
        header('Location: dashboard.php');
        exit;
    }
}

include 'includes/header.php';
?>

<div class="client-form-page">
    <div class="client-form-card">
        <div class="form-page-head">
            <div>
                <span class="eyebrow">CLIENTES</span>
                <h1>Nuevo cliente</h1>
                <p>Registra un nuevo cliente y agrégalo al CRM.</p>
            </div>
            <a href="dashboard.php" class="back-link">← Volver al panel</a>
        </div>

        <?php if ($errores): ?>
            <div class="alert error">
                <?php foreach ($errores as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="client-form" novalidate>
            <div class="form-grid">
                <div class="form-field">
                    <label for="nombre">Nombre <span>*</span></label>
                    <input type="text" id="nombre" name="nombre" maxlength="100" required value="<?= htmlspecialchars($nombre) ?>" placeholder="Ej. María">
                </div>

                <div class="form-field">
                    <label for="apellido">Apellido <span>*</span></label>
                    <input type="text" id="apellido" name="apellido" maxlength="100" required value="<?= htmlspecialchars($apellido) ?>" placeholder="Ej. González">
                </div>

                <div class="form-field">
                    <label for="correo">Correo electrónico <span>*</span></label>
                    <input type="email" id="correo" name="correo" maxlength="150" required value="<?= htmlspecialchars($correo) ?>" placeholder="cliente@correo.com">
                </div>

                <div class="form-field">
                    <label for="telefono">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" maxlength="20" value="<?= htmlspecialchars($telefono) ?>" placeholder="Ej. 3001234567">
                </div>

                <div class="form-field">
                    <label for="empresa">Empresa</label>
                    <input type="text" id="empresa" name="empresa" maxlength="150" value="<?= htmlspecialchars($empresa) ?>" placeholder="Nombre de la empresa">
                </div>

                <div class="form-field">
                    <label for="estado">Estado del cliente</label>
                    <select id="estado" name="estado">
                        <?php foreach ($estadosPermitidos as $opcion): ?>
                            <option value="<?= htmlspecialchars($opcion) ?>" <?= $estado === $opcion ? 'selected' : '' ?>><?= htmlspecialchars($opcion) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-required-note">* Campos obligatorios</div>

            <div class="form-actions">
                <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary form-submit">Guardar cliente</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
