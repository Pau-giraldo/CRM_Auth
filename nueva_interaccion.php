<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_login();
$errores=[];
$clientes=$pdo->query("SELECT id,nombre,apellido FROM clientes ORDER BY nombre,apellido")->fetchAll();
$tipos=['Llamada','Correo','WhatsApp','Reunión'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $clienteId=(int)($_POST['cliente_id']??0); $tipo=$_POST['tipo']??''; $nota=trim($_POST['nota']??'');
    if($clienteId<=0) $errores[]='Selecciona un cliente.';
    if(!in_array($tipo,$tipos,true)) $errores[]='Selecciona un tipo de interacción válido.';
    if($nota==='') $errores[]='Escribe una nota para la interacción.';
    if(!$errores){
        $stmt=$pdo->prepare('INSERT INTO interacciones (cliente_id, usuario_id, tipo, nota) VALUES (?,?,?,?)');
        $stmt->execute([$clienteId,(int)$_SESSION['usuario']['id'],$tipo,$nota]);
        $_SESSION['mensaje_interaccion']='Nueva interacción registrada correctamente.';
        header('Location: interacciones.php'); exit;
    }
}
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Nueva interacción | CRM Tienda</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body><div class="app-layout"><aside class="sidebar"><div class="sidebar-brand"><div class="brand-mark">C</div><div><strong>CRM Tienda</strong><small>Panel de gestión</small></div></div><nav class="side-nav">
<a class="side-link" href="dashboard.php"><span>⌂</span> Panel principal</a><a class="side-link" href="clientes.php"><span>♙</span> Clientes</a><a class="side-link" href="productos.php"><span>▣</span> Productos</a><a class="side-link" href="ventas.php"><span>▤</span> Ventas</a><a class="side-link" href="pedidos.php"><span>◫</span> Pedidos</a><a class="side-link active" href="interacciones.php"><span>◌</span> Interacciones</a><a class="side-link" href="campanas.php"><span>⚑</span> Campañas</a><a class="side-link" href="categorias.php"><span>▦</span> Categorías</a><div class="nav-divider"></div>
            <a class="side-link logout-link" href="auth/logout.php"><span>↪</span> Cerrar sesión</a></nav></aside>
<section class="dashboard-area"><header class="dashboard-topbar"><div><span class="eyebrow">SEGUIMIENTO COMERCIAL</span><h1>Nueva interacción</h1><p>Registra una nueva interacción con un cliente.</p></div></header><main class="dashboard-content"><section class="panel form-panel"><div class="panel-heading"><div><h2>Registrar interacción</h2><p>Completa los datos de la interacción.</p></div></div><?php if($errores): ?><div class="alert error"><?php foreach($errores as $x): ?><div><?=e($x)?></div><?php endforeach;?></div><?php endif; ?><form method="post" class="client-form"><div class="form-grid"><label>Cliente<select name="cliente_id" required><option value="">Selecciona un cliente</option><?php foreach($clientes as $c): ?><option value="<?= (int)$c['id'] ?>" <?= ((int)($_POST['cliente_id']??0)===(int)$c['id'])?'selected':'' ?>><?=e($c['nombre'].' '.$c['apellido'])?></option><?php endforeach;?></select></label><label>Tipo<select name="tipo" required><option value="">Selecciona</option><?php foreach($tipos as $t): ?><option <?= (($_POST['tipo']??'')===$t)?'selected':'' ?>><?=e($t)?></option><?php endforeach;?></select></label><label style="grid-column:1/-1">Nota<textarea name="nota" rows="5" required><?=e($_POST['nota']??'')?></textarea></label></div><div class="actions-row"><button class="primary-button" type="submit">Guardar interacción</button><a class="quick-button" href="interacciones.php">Cancelar</a></div></form></section></main></section></div></body></html>
