<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$basePath = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/auth/') ? '../' : './';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'CRM Tienda') ?></title>
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/style.css">
</head>
<body>
<?php if (($titulo ?? '') !== 'Panel principal'): ?>
<nav class="navbar">
    <a class="brand" href="<?= $basePath ?>dashboard.php">CRM Tienda</a>
    <?php if (!empty($_SESSION['usuario'])): ?>
        <div class="nav-right">
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?> · <?= htmlspecialchars($_SESSION['usuario']['rol']) ?></span>
            <a class="btn btn-danger btn-small" href="<?= $basePath ?>auth/logout.php">Cerrar sesión</a>
        </div>
    <?php endif; ?>
</nav>
<main class="container">
<?php endif; ?>
