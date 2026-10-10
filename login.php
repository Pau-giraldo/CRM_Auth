<?php
require_once '../config/database.php';
require_once '../config/session.php';

if (!empty($_SESSION['usuario'])) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($correo === '' || $password === '') {
        $error = 'Completa todos los campos.';
    } else {
        $stmt = $pdo->prepare('SELECT id, nombre, correo, password, rol FROM usuarios WHERE correo = ? LIMIT 1');
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {
            session_regenerate_id(true);
            unset($usuario['password']);
            $_SESSION['usuario'] = $usuario;
            header('Location: ../dashboard.php');
            exit;
        }
        $error = 'Correo o contraseña incorrectos.';
    }
}

$titulo = 'Iniciar sesión';
include '../includes/header.php';
?>
<section class="auth-card">
    <div class="auth-icon">CRM</div>
    <h1>Iniciar sesión</h1>
    <p class="muted">Accede al sistema de gestión de clientes.</p>

    <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" autocomplete="on">
        <label>Correo</label>
        <input type="email" name="correo" value="<?= htmlspecialchars($correo) ?>" required>

        <label>Contraseña</label>
        <input type="password" name="password" required>

        <button class="btn btn-primary" type="submit">Ingresar</button>
    </form>

    <p class="switch">¿No tienes una cuenta? <a href="registro.php">Regístrate</a></p>
</section>
<?php include '../includes/footer.php'; ?>
