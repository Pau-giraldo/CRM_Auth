<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/tienda_lib.php';
require_login();
function esc($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$errores=[]; $editar=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $accion=$_POST['accion']??''; $id=(int)($_POST['id']??0);
  if ($accion==='guardar') {
    $nombre=trim($_POST['nombre']??''); $inicio=$_POST['fecha_inicio']?:null; $fin=$_POST['fecha_fin']?:null; $presupuesto=trim($_POST['presupuesto']??'');
    if ($nombre==='') $errores[]='Escribe el nombre de la campaña.';
    if ($inicio && $fin && $fin<$inicio) $errores[]='La fecha final no puede ser anterior a la inicial.';
    if ($presupuesto!=='' && (!is_numeric($presupuesto)||$presupuesto<0)) $errores[]='El presupuesto debe ser un valor positivo.';
    if (!$errores) {
      if ($id>0) { $q=$pdo->prepare('UPDATE campanas SET nombre=?, fecha_inicio=?, fecha_fin=?, presupuesto=? WHERE id=?'); $q->execute([$nombre,$inicio,$fin,$presupuesto===''?null:$presupuesto,$id]); t_flash_set('crm_ok','Campaña actualizada correctamente.'); }
      else { $q=$pdo->prepare('INSERT INTO campanas (nombre,fecha_inicio,fecha_fin,presupuesto) VALUES (?,?,?,?)'); $q->execute([$nombre,$inicio,$fin,$presupuesto===''?null:$presupuesto]); t_flash_set('crm_ok','Campaña creada correctamente.'); }
      header('Location: campanas.php'); exit;
    }
    $editar=['id'=>$id,'nombre'=>$nombre,'fecha_inicio'=>$inicio,'fecha_fin'=>$fin,'presupuesto'=>$presupuesto];
  } elseif ($accion==='eliminar' && $id>0) {
    $q=$pdo->prepare('DELETE FROM campanas WHERE id=?'); $q->execute([$id]); t_flash_set('crm_ok','Campaña eliminada. Las ventas asociadas conservan su registro.'); header('Location: campanas.php'); exit;
  }
}
if (!$editar && isset($_GET['editar'])) { $q=$pdo->prepare('SELECT * FROM campanas WHERE id=?'); $q->execute([(int)$_GET['editar']]); $editar=$q->fetch() ?: null; }
$campanas=$pdo->query('SELECT c.*, (SELECT COUNT(*) FROM ventas v WHERE v.campana_id=c.id) AS ventas_count FROM campanas c ORDER BY c.id DESC')->fetchAll();
$titulo='Campañas'; $eyebrow='GESTIÓN COMERCIAL'; $subtitulo='Crea y administra las campañas comerciales de la tienda.'; $activo='campanas';
include 'includes/crm_top.php';
?>
<style>.manage-grid{display:grid;grid-template-columns:minmax(260px,340px) 1fr;gap:20px}.manage-form label{display:block;margin:12px 0 5px;font-weight:600}.manage-form input,.manage-form textarea,.manage-form select{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;box-sizing:border-box;font:inherit}.manage-form button,.primary-action{margin-top:15px;background:#252b45;color:#fff;border:0;border-radius:8px;padding:10px 14px;text-decoration:none;display:inline-block;cursor:pointer}.manage-table{width:100%;border-collapse:collapse}.manage-table th,.manage-table td{padding:12px 10px;text-align:left;border-bottom:1px solid #e5e7eb}.manage-table th{font-size:12px;color:#6b7280}.table-scroll{overflow-x:auto}.link-action{color:#3949ab;font-weight:600;text-decoration:none;margin-right:8px}.danger-action{color:#b91c1c;background:none;border:0;cursor:pointer;font:inherit}.form-panel,.table-panel{background:#fff;border-radius:14px;padding:20px;box-shadow:0 3px 14px #18213b0a}.subtle{color:#6b7280;font-size:13px}@media(max-width:850px){.manage-grid{grid-template-columns:1fr}}</style>
<?php if ($errores): ?><div class="alert error"><?php foreach($errores as $err): ?><div><?=esc($err)?></div><?php endforeach; ?></div><?php endif; ?>
<div class="manage-grid"><section class="form-panel"><h2><?= $editar && (int)$editar['id']>0?'Editar campaña':'Nueva campaña' ?></h2><form class="manage-form" method="post"><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= (int)($editar['id']??0) ?>"><label>Nombre de campaña</label><input name="nombre" maxlength="150" required value="<?=esc($editar['nombre']??'')?>"><label>Fecha de inicio</label><input type="date" name="fecha_inicio" value="<?=esc($editar['fecha_inicio']??'')?>"><label>Fecha final</label><input type="date" name="fecha_fin" value="<?=esc($editar['fecha_fin']??'')?>"><label>Presupuesto (COP)</label><input type="number" name="presupuesto" min="0" step="0.01" value="<?=esc($editar['presupuesto']??'')?>"><button type="submit"><?= $editar && (int)$editar['id']>0?'Guardar cambios':'Crear campaña' ?></button><?php if($editar && (int)$editar['id']>0): ?> <a class="link-action" href="campanas.php">Cancelar</a><?php endif; ?></form></section>
<section class="table-panel"><h2>Campañas registradas</h2><p class="subtle"><?=count($campanas)?> campaña(s)</p><div class="table-scroll"><table class="manage-table"><thead><tr><th>NOMBRE</th><th>VIGENCIA</th><th>PRESUPUESTO</th><th>VENTAS</th><th>ACCIONES</th></tr></thead><tbody><?php if(!$campanas): ?><tr><td colspan="5">No hay campañas registradas.</td></tr><?php endif; foreach($campanas as $c): ?><tr><td><strong><?=esc($c['nombre'])?></strong></td><td><?=esc($c['fecha_inicio']?:'—')?> a <?=esc($c['fecha_fin']?:'—')?></td><td><?= $c['presupuesto']===null?'—':'$'.number_format((float)$c['presupuesto'],0,',','.') ?></td><td><?= (int)$c['ventas_count'] ?></td><td><a class="link-action" href="campanas.php?editar=<?=(int)$c['id']?>">Editar</a><form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar esta campaña?')"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?=(int)$c['id']?>"><button class="danger-action" type="submit">Eliminar</button></form></td></tr><?php endforeach; ?></tbody></table></div></section></div>
<?php include 'includes/crm_bottom.php'; ?>
