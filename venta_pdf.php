<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_login();

$ventaId = (int)($_GET['id'] ?? 0);
if ($ventaId <= 0) { http_response_code(400); exit('Venta no válida.'); }

$stmt = $pdo->prepare("SELECT v.id, v.total, v.fecha_venta,
    CONCAT(c.nombre, ' ', c.apellido) AS cliente, c.correo AS cliente_correo,
    c.telefono AS cliente_telefono, c.empresa AS cliente_empresa,
    u.nombre AS vendedor, u.correo AS vendedor_correo,
    camp.nombre AS campana
    FROM ventas v
    JOIN clientes c ON c.id = v.cliente_id
    JOIN usuarios u ON u.id = v.usuario_id
    LEFT JOIN campanas camp ON camp.id = v.campana_id
    WHERE v.id = ? LIMIT 1");
$stmt->execute([$ventaId]);
$venta = $stmt->fetch();
if (!$venta) { http_response_code(404); exit('La venta no existe.'); }

$stmt = $pdo->prepare("SELECT p.nombre, p.sku, dv.cantidad, dv.precio_unitario,
    (dv.cantidad * dv.precio_unitario) AS subtotal
    FROM detalles_venta dv
    JOIN productos p ON p.id = dv.producto_id
    WHERE dv.venta_id = ? ORDER BY dv.id");
$stmt->execute([$ventaId]);
$detalles = $stmt->fetchAll();

function pdfText($value) {
    $value = (string)$value;
    $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', $value);
    return $converted !== false ? $converted : preg_replace('/[^\x20-\x7E]/', '', $value);
}
function pdfEscape($value) {
    return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], pdfText($value));
}
function money($value) { return '$' . number_format((float)$value, 0, ',', '.'); }

$lines = [];
$lines[] = ['CRM TIENDA', 18, 55];
$lines[] = ['COMPROBANTE DE VENTA', 12, 55];
$lines[] = ['Venta #' . $venta['id'], 11, 55];
$lines[] = ['Fecha: ' . date('d/m/Y H:i', strtotime($venta['fecha_venta'])), 10, 55];
$lines[] = ['Cliente: ' . $venta['cliente'], 10, 55];
if ($venta['cliente_correo']) $lines[] = ['Correo: ' . $venta['cliente_correo'], 9, 55];
if ($venta['cliente_telefono']) $lines[] = ['Teléfono: ' . $venta['cliente_telefono'], 9, 55];
if ($venta['cliente_empresa']) $lines[] = ['Empresa: ' . $venta['cliente_empresa'], 9, 55];
$lines[] = ['Vendedor: ' . $venta['vendedor'], 9, 55];
if ($venta['campana']) $lines[] = ['Campaña: ' . $venta['campana'], 9, 55];
$lines[] = ['---------------------------------------------', 9, 55];
$lines[] = ['Producto / Cantidad / Precio / Subtotal', 9, 55];
$lines[] = ['---------------------------------------------', 9, 55];
foreach ($detalles as $d) {
    $name = $d['nombre'];
    $lines[] = [$name, 9, 55];
    $lines[] = ['  ' . (int)$d['cantidad'] . ' x ' . money($d['precio_unitario']) . ' = ' . money($d['subtotal']), 9, 55];
}
$lines[] = ['---------------------------------------------', 9, 55];
$lines[] = ['TOTAL: ' . money($venta['total']), 14, 55];
$lines[] = ['Gracias por su compra.', 9, 55];

// Construcción de un PDF sencillo sin librerías externas.
$width = 595; $height = 842;
$content = "BT\n";
$y = 790;
foreach ($lines as [$text, $size, $x]) {
    $content .= sprintf("/F1 %d Tf\n1 0 0 1 %d %d Tm\n(%s) Tj\n", $size, $x, $y, pdfEscape($text));
    $y -= ($size >= 14 ? 28 : 18);
    if ($y < 55) break;
}
$content .= "ET\n";

$objects = [];
$objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
$objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
$objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $width $height] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
$objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
$objects[] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";

$pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
$offsets = [0];
foreach ($objects as $i => $obj) {
    $num = $i + 1;
    $offsets[$num] = strlen($pdf);
    $pdf .= "$num 0 obj\n$obj\nendobj\n";
}
$xref = strlen($pdf);
$pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
$pdf .= "0000000000 65535 f \n";
for ($i = 1; $i <= count($objects); $i++) $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
$pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="venta-' . $ventaId . '.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
exit;
