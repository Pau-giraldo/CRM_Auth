<?php
require_once 'config/session.php';
if (!empty($_SESSION['usuario'])) {
    header('Location: dashboard.php');
} else {
    header('Location: auth/login.php');
}
exit;
