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

$Title = $row_reports_info['Title']." (Copia)";
if (isset($_POST['Title'])) {
  $Title = $_POST['Title'];
}

$Description = "Esto es una copia del reporte ".$row_reports_info['ReportsId'].". ".$row_reports_info['Title'].", ".$row_reports_info['Description'];
if (isset($_POST['Description'])) {
  $Description = $_POST['Description'];
}

$Query = $row_reports_info['Query'];
if (isset($_POST['Query'])) {
  $Query = $_POST['Query'];
}

$CategoryId = $row_reports_info['CategoryId'];
if (isset($_POST['CategoryId'])) {
  $CategoryId = $_POST['CategoryId'];
}

$UsersId = $row_reports_info['UsersId'];
/*
if (isset($_SESSION['UsersId'])) {
  $UsersId = $_SESSION['UsersId'];
}
*/

$TypeId = $row_reports_info['TypeId'];
if (isset($_POST['TypeId'])) {
  $TypeId = $_POST['TypeId'];
}

$ParentId = $row_reports_info['ParentId'];
if (isset($_POST['ParentId'])) {
  $ParentId = $_POST['ParentId'];
}

$ConnectionId = $row_reports_info['ConnectionId'];
if (isset($_POST['ConnectionId'])) {
  $ConnectionId = $_POST['ConnectionId'];
}

$Order = 0;
if (isset($_POST['Order'])) {
  $Order = $_POST['Order'];
}

$Version = "1.0.0";
if (isset($_POST['Version'])) {
  $Version = $_POST['Version'];
}

$PipelinesId = $row_reports_info['PipelinesId'];
if (isset($_POST['PipelinesId'])) {
  $PipelinesId = $_POST['PipelinesId'];
}

$Status = $row_reports_info['Status'];
if (isset($_POST['Status'])) {
  $Status = $_POST['Status'];
}

$UserUpdated = null;
if (isset($_SESSION['UsersId'])) {
  $UserUpdated = $_SESSION['UsersId'];
}

// CRUD - Query Editor
if ($action == "copy" && $forms_id == 'cruds_copy') {

  $ParentId = $ParentId ?: 0;
  $Order = $Order ?: 0;
  $PipelinesId = $PipelinesId ?: 0;

    // Construir la consulta de inserción
    $qry_ins_reports = 'INSERT INTO reports (
        Title,
        Description,
        Query,
        CategoryId,
        UsersId,
        TypeId,
        ParentId,
        ConnectionId,
        `Order`,
        Version,
        PipelinesId,
        UserUpdated,
        Status
    ) VALUES (
        "'.$Title.'",
        "'.$Description.'",
        "'.$Query.'",
        '.$CategoryId.',
        '.$UsersId.',
        '.$TypeId.',
        '.$ParentId.',
        '.$ConnectionId.',
        '.$Order.',
        "'.$Version.'",
        '.$PipelinesId.',
        '.$UserUpdated.',
        '.$Status.'
    )';

    // Ejecutar la consulta de inserción
    $ins_reports = class_queryMysqliExe(1, $qry_ins_reports);

    // Obtener la URL actual
    $lastURL = $_SERVER['REQUEST_URI'];

    // Redirigir a la misma URL
    header("Location: $lastURL");
    exit();
}
?>
<button class="btn btn-subtle-success w-100 w-md-auto mb-2" type="button" data-bs-toggle="modal" data-bs-target="#cruds_copy">
  <i class="fas fa-copy"></i> Clonar
</button>
<div class="modal fade" id="cruds_copy" tabindex="-1" aria-labelledby="scrollingLongModalLabel2" style="display: none;" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="scrollingLongModalLabel2">Copiar Reporte No. <?php echo $row_reports_info['ReportsId']; ?></h5><button class="btn btn-close p-1" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-body-tertiary lh-lg mb-0">
          <form action="" method="POST" id="query_form" class="needs-validation" novalidate=""">
            <input type="hidden" name="forms_id" value="cruds_copy">

              <div class="col-md-12">
                Desea copiar este reporte?
              </div>

            <div class="col-12 pt-3">
              <button class="btn btn-primary" type="submit" name="action" value="copy">Copiar</button>
              <button class="btn btn-outline-primary" type="button" data-bs-dismiss="modal">Cancelar</button>
            </div>
          </form>
        </p>
      </div>
    </div>
  </div>
</div>