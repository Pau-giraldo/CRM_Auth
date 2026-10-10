<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void {
    if (empty($_SESSION['usuario'])) {
        header('Location: /CRM_Auth/auth/login.php');
        exit;
    }
}
