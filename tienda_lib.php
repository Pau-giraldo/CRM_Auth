<?php
// includes/tienda_lib.php — funciones compartidas por la tienda y las pantallas de pedidos
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== CONFIGURA AQUÍ =====
if (!defined('NOMBRE_TIENDA'))   define('NOMBRE_TIENDA', 'Mi Tienda de Accesorios');
// WhatsApp de la tienda con indicativo, solo dígitos (ej. '573001234567'). Vacío = no se muestra.
if (!defined('WHATSAPP_TIENDA')) define('WHATSAPP_TIENDA', '');
if (!defined('CARPETA_IMG'))     define('CARPETA_IMG', 'assets/img/productos/');

if (!function_exists('e')) {
    function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
function moneda($n): string { return '$' . number_format((float)$n, 0, ',', '.'); }

// CSRF (para formularios públicos y del CRM)
function t_csrf_field(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}
function t_csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('La sesión expiró o la petición no es válida. Vuelve atrás, recarga la página e inténtalo de nuevo.');
    }
}

// Mensajes de una sola vez
function t_flash_set(string $k, string $m): void { $_SESSION['t_flash'][$k] = $m; }
function t_flash_get(string $k): ?string {
    $m = $_SESSION['t_flash'][$k] ?? null;
    unset($_SESSION['t_flash'][$k]);
    return $m;
}

function t_wa_link(?string $tel, string $texto = ''): ?string {
    $d = preg_replace('/\D+/', '', (string)$tel);
    if ($d === '') return null;
    if (strlen($d) === 10) $d = '57' . $d;               // asume Colombia
    return 'https://wa.me/' . $d . ($texto !== '' ? '?text=' . rawurlencode($texto) : '');
}

function columna_existe(PDO $pdo, string $tabla, string $col): bool {
    static $cache = [];
    $k = $tabla . '.' . $col;
    if (!isset($cache[$k])) {
        $s = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $s->execute([$tabla, $col]);
        $cache[$k] = (bool)$s->fetchColumn();
    }
    return $cache[$k];
}

// Producto vendible (existe y, si tienes la columna `activo`, está activo). Siempre lee precio y stock de la BD.
function producto_disponible(PDO $pdo, int $id): ?array {
    $act = columna_existe($pdo, 'productos', 'activo') ? 'activo' : '1 AS activo';
    $s = $pdo->prepare("SELECT id, nombre, precio, stock, $act FROM productos WHERE id = ?");
    $s->execute([$id]);
    $p = $s->fetch();
    return ($p && (int)$p['activo'] === 1) ? $p : null;
}

function carrito_cantidad(): int { return (int)array_sum($_SESSION['carrito'] ?? []); }

// Líneas del carrito con datos frescos de la BD. Quita lo que ya no existe y ajusta cantidades al stock.
function carrito_lineas(PDO $pdo): array {
    $lineas = []; $quitados = false;
    foreach (($_SESSION['carrito'] ?? []) as $pid => $cant) {
        $p = producto_disponible($pdo, (int)$pid);
        if (!$p || (int)$p['stock'] <= 0) { unset($_SESSION['carrito'][$pid]); $quitados = true; continue; }
        $cant = max(1, (int)$cant);
        $ajustado = $cant > (int)$p['stock'];
        $cant = min($cant, (int)$p['stock']);
        $_SESSION['carrito'][$pid] = $cant;
        $lineas[] = [
            'id' => (int)$p['id'], 'nombre' => $p['nombre'], 'precio' => (float)$p['precio'],
            'stock' => (int)$p['stock'], 'cantidad' => $cant,
            'subtotal' => $cant * (float)$p['precio'], 'ajustado' => $ajustado,
        ];
    }
    if ($quitados) t_flash_set('tienda', 'Algún producto ya no está disponible y se quitó del carrito.');
    return $lineas;
}
