<?php
require_once '../config/database.php';
require_once '../config/session.php';

if (!empty($_SESSION['usuario'])) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$nombre = '';
$correo = '';
$rol = 'Vendedor';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';
    $rol = $_POST['rol'] ?? 'Vendedor';

    if ($nombre === '' || $correo === '' || $password === '' || $confirmar === '') {
        $error = 'Completa todos los campos.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresa un correo válido.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener mínimo 6 caracteres.';
    } elseif ($password !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (!in_array($rol, ['Administrador', 'Vendedor'], true)) {
        $error = 'Rol no válido.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo = ? LIMIT 1');
        $stmt->execute([$correo]);
        if ($stmt->fetch()) {
            $error = 'Ese correo ya está registrado.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, correo, password, rol) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nombre, $correo, $hash, $rol]);
            header('Location: login.php?registro=ok');
            exit;
        }
    }
}

$titulo = 'Registro';
include '../includes/header.php';
?>
<section class="auth-card">
    <div class="auth-icon">+</div>
    <h1>Crear cuenta</h1>
    <p class="muted">Registra un nuevo usuario del CRM.</p>

    <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" autocomplete="on">
        <label>Nombre</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" maxlength="100" required>

        <label>Correo</label>
        <input type="email" name="correo" value="<?= htmlspecialchars($correo) ?>" maxlength="150" required>

        <label>Rol</label>
        <select name="rol" required>
            <option value="Vendedor" <?= $rol === 'Vendedor' ? 'selected' : '' ?>>Vendedor</option>
            <option value="Administrador" <?= $rol === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
        </select>

        <label>Contraseña</label>
        <input type="password" name="password" minlength="6" required>

        <label>Confirmar contraseña</label>
        <input type="password" name="confirmar" minlength="6" required>

        <button class="btn btn-primary" type="submit">Registrarme</button>
    </form>

    <p class="switch">¿Ya tienes una cuenta? <a href="login.php">Inicia sesión</a></p>
</section>
<?php include '../includes/footer.php'; ?>
