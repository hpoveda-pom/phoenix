<?php
// Cargar lo mínimo para procesar POST/redirect ANTES de enviar HTML
require_once('config.php');
require_once('restrict.php');
require_once('functions.php');
require_once('conn/phoenix.php');
require_once('models/class_recordset.php');
require_once('models/class_connections.php');
require_once('models/class_querymysqli.php');
require_once('models/class_connmysqli.php');
require_once('controllers/pipelines.php');
// Si hubo redirect, ya salimos. Si no, cargar el HTML completo.
require_once('header.php');
?>
<div class="mb-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
      <?php if ($action === 'add' || $action === 'edit'): ?>
      <li class="breadcrumb-item"><a href="pipelines.php">Pipelines</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?php echo $action === 'add' ? 'Crear' : 'Editar'; ?></li>
      <?php else: ?>
      <li class="breadcrumb-item active" aria-current="page">Pipelines</li>
      <?php endif; ?>
    </ol>
  </nav>
  <h4 class="mb-0 mt-2"><?php echo $action === 'add' ? 'Crear Pipeline' : ($action === 'edit' ? 'Editar Pipeline' : 'Pipelines'); ?></h4>
</div>

<?php
if ($action === 'add' || $action === 'edit') {
  require_once('views/pipelines_form.php');
} else {
  require_once('views/pipelines_list.php');
}
?>

<?php require_once('footer.php'); ?>
