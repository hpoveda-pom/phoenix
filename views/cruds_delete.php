<?php
$action = null;
if (isset($_POST['action'])) {
  $action = $_POST['action'];
}

$forms_id = null;
if (isset($_POST['forms_id'])) {
  $forms_id = $_POST['forms_id'];
}

$ReportsId = $row_reports_info['ReportsId'];
if (isset($_POST['ReportsId'])) {
  $ReportsId = $_POST['ReportsId'];
}

// CRUD - Query Editor
if ($action == "delete" && $forms_id == 'cruds_delete') {

    // Construir la consulta de inserción
    $qry_del_reports = "DELETE FROM reports WHERE ReportsId = ".$ReportsId;

    // Ejecutar la consulta de inserción
    $del_reports = class_queryMysqliExe(1, $qry_del_reports);

    // Obtener la URL actual
    $lastURL = "index.php";

    // Redirigir a la misma URL
    header("Location: $lastURL");
    exit();
}
?>
<button class="btn btn-subtle-danger w-100 w-md-auto mb-2" type="button" data-bs-toggle="modal" data-bs-target="#cruds_delete">
  <i class="fas fa-trash-alt"></i> Eliminar
</button>

<div class="modal fade" id="cruds_delete" tabindex="-1" aria-labelledby="scrollingLongModalLabel2" style="display: none;" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="scrollingLongModalLabel2">Eliminar</h5><button class="btn btn-close p-1" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-body-tertiary lh-lg mb-0">
          <div class="alert alert-subtle-warning alert-dismissible fade show" role="alert">
            <strong>Adevertencia!</strong> Confirma que quiere eliminar este reporte?
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        </p>
      </div>
      <form action="" method="POST" id="query_form" class="needs-validation" novalidate=""">
        <input type="hidden" name="forms_id" value="cruds_delete">
        <div class="modal-footer">
          <button class="btn btn-danger" type="submit" name="action" value="delete">Eliminar</button>
          <button class="btn btn-outline-primary" type="button" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
    </div>
  </div>
</div>