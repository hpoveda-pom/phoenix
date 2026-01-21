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

$Title = $row_reports_info['Title'];
if (isset($_POST['Title'])) {
  $Title = $_POST['Title'];
}

$Description = $row_reports_info['Description'];
if (isset($_POST['Description'])) {
  $Description = $_POST['Description'];
}

$CategoryId = $row_reports_info['CategoryId'];
if (isset($_POST['CategoryId'])) {
  $CategoryId = $_POST['CategoryId'];
}

$UsersId = $row_reports_info['UsersId'];
if (isset($_POST['UsersId'])) {
  $UsersId = $_POST['UsersId'];
}

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

$Order = $row_reports_info['Order'];
if (isset($_POST['Order'])) {
  $Order = $_POST['Order'];
}

$PipelinesId = $row_reports_info['PipelinesId'];
if (isset($_POST['PipelinesId'])) {
  $PipelinesId = $_POST['PipelinesId'];
}

$MaskingStatus = $row_reports_info['MaskingStatus'];
if (isset($_POST['MaskingStatus'])) {
  $MaskingStatus = $_POST['MaskingStatus'];
}

$TotalAxisX = $row_reports_info['TotalAxisX'];
if (isset($_POST['TotalAxisX'])) {
  $TotalAxisX = $_POST['TotalAxisX'];
}

$TotalAxisY = $row_reports_info['TotalAxisY'];
if (isset($_POST['TotalAxisY'])) {
  $TotalAxisY = $_POST['TotalAxisY'];
}

$LayoutGridClass = $row_reports_info['LayoutGridClass'];
if (isset($_POST['LayoutGridClass'])) {
  $LayoutGridClass = $_POST['LayoutGridClass'];
}

$Status = $row_reports_info['Status'];
if (isset($_POST['Status'])) {
  $Status = $_POST['Status'];
}

$UserUpdated = $UsersId;
if (isset($_POST['UserUpdated'])) {
  $UserUpdated = $_POST['UserUpdated'];
}

// CRUD - Query Editor
if ($action == "update" && $forms_id == 'cruds_edit') {

  $ParentId = $ParentId ?: 0;
  $Order = $Order ?: 0;
  $PipelinesId = $PipelinesId ?: 0;

    $qry_upd_reports = "UPDATE reports a SET
        a.Title = '".$Title."',
        a.Description = '".$Description."',
        a.CategoryId = ".$CategoryId.",
        a.UsersId = ".$UsersId.",
        a.TypeId = ".$TypeId.",
        a.ParentId = ".$ParentId.",
        a.ConnectionId = ".$ConnectionId.",
        a.Order = ".$Order.",
        a.PipelinesId = ".$PipelinesId.",
        a.MaskingStatus = ".$MaskingStatus.",
        a.TotalAxisX = ".$TotalAxisX.",
        a.TotalAxisY = ".$TotalAxisY.",
        a.LayoutGridClass = '".$LayoutGridClass."',
        a.UserUpdated = ".$UserUpdated.",
        a.Status = ".$Status."
    WHERE a.ReportsId = ".$ReportsId;

    $upd_reports = class_queryMysqliExe(1, $qry_upd_reports);

    $lastURL = $_SERVER['REQUEST_URI'];

    header("Location: $lastURL");
    exit();
}

//Category
$qry_categories = "SELECT a.CategoryId,a.Title FROM category a WHERE a.Status = 1 AND a.ParentId IS NULL ORDER BY a.Title ASC";
$lst_categories = class_Recordset(1, $qry_categories, null, null, null);
$arr_categories = $lst_categories['data'];

//Owners
$qry_owners = "SELECT a.UsersId,a.Fullname FROM users a WHERE a.Status = 1 ORDER BY a.Fullname ASC";
$lst_owners = class_Recordset(1, $qry_owners, null, null, null);
$arr_owners = $lst_owners['data'];

//Types
$qry_types  = "SELECT a.TypesId,a.Title FROM types a WHERE a.Status = 1 ORDER BY a.Title ASC";
$lst_types  = class_Recordset(1, $qry_types, null, null, null);
$arr_types  = $lst_types['data'];

//Parents
$qry_parents  = "SELECT a.ReportsId,a.Title FROM reports a WHERE a.Status = 1 AND a.TypeId = 2 ORDER BY a.Title ASC";
$lst_parents  = class_Recordset(1, $qry_parents, null, null, null);
$arr_parents  = $lst_parents['data'];

//Connections
$qry_connections  = "SELECT a.ConnectionId,a.Title FROM connections a WHERE a.Status = 1 ORDER BY a.Title ASC";
$lst_connections  = class_Recordset(1, $qry_connections, null, null, null);
$arr_connections  = $lst_connections['data'];

//Connections
$qry_pipelines  = "SELECT a.ReportsId,a.PipelinesId,a.Description, b.Title FROM pipelines a INNER JOIN reports b ON b.ReportsId = a.ReportsId WHERE a.Status = 1 ORDER BY b.Title ASC";
$lst_pipelines  = class_Recordset(1, $qry_pipelines, null, null, null);
$arr_pipelines  = $lst_pipelines['data'];

$arr_layoutgridclass = array(
  'Predeterminado' => "col",
  '25%' => "col-md-3",
  '50%' => "col-md-6",
  '100%' => "col-md-12",
  'Auto' => "col-auto",
);

$arr_maskingstatus = array(
  "Activo" => 1,
  "Inactivo" => 0,
);

$arr_totalaxisx = array(
  "Activo" => 1,
  "Inactivo" => 0,
);

$arr_totalaxisy = array(
  "Activo" => 1,
  "Inactivo" => 0,
);

$arr_status = array(
  "Activo" => 1,
  "Inactivo" => 0,
  "Mantenimiento" => 2,
);
?>
<button class="btn btn-subtle-warning w-100 w-md-auto mb-2" type="button" data-bs-toggle="modal" data-bs-target="#cruds_edit">
  <i class="fas fa-edit"></i> Editar
</button>
<div class="modal fade" id="cruds_edit" tabindex="-1" aria-labelledby="scrollingLongModalLabel2" style="display: none;" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="scrollingLongModalLabel2">Modificar Reporte No. <?php echo $row_reports_info['ReportsId']; ?></h5><button class="btn btn-close p-1" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-body-tertiary lh-lg mb-0">
          <form action="" method="POST" id="query_form" class="needs-validation" novalidate=""">
            <input type="hidden" name="forms_id" value="cruds_edit">
            <ul class="nav nav-underline fs-9" id="myTab" role="tablist">
              <li class="nav-item"><a class="nav-link active" id="home-tab" data-bs-toggle="tab" href="#tab-home" role="tab" aria-controls="tab-home" aria-selected="true">Básico</a></li>
              <li class="nav-item"><a class="nav-link" id="profile-tab" data-bs-toggle="tab" href="#tab-profile" role="tab" aria-controls="tab-profile" aria-selected="false">Avanzado</a></li>
            </ul>
            <div class="tab-content mt-3" id="myTabContent">
              <div class="tab-pane fade show active" id="tab-home" role="tabpanel" aria-labelledby="home-tab">
                <div class="row">

                  <!-- Título -->
                  <div class="col-md-12">
                    <label class="form-label" for="Title">Título</label>
                    <input class="form-control" id="Title" name="Title" type="text" value="<?php echo $row_reports_info['Title']; ?>" required="">
                  </div>

                  <!-- Descripción -->
                  <div class="mb-3">
                    <label class="form-label" for="Description">Próposito</label>
                    <textarea class="form-control" id="Description" name="Description" rows="3" placeholder="Próposito"><?php echo $row_reports_info['Description']; ?></textarea>
                  </div>

                  <!-- Categoría -->
                  <div class="col-md-6">
                    <label class="form-label" for="CategoryId">Categoría</label>
                    <select class="form-select" id="CategoryId" name="CategoryId" required="">
                      <option selected="" disabled="" value="">Sin Seleccionar...</option>
                      <?php foreach ($arr_categories as $key_categories => $row_categories) { ?>

                      <?php
                      //SubCategories
                      $qry_subcategories = "SELECT a.CategoryId,a.Title FROM category a WHERE a.Status = 1 AND a.ParentId = ".$row_categories['CategoryId']." ORDER BY a.Title ASC";
                      $lst_subcategories = class_Recordset(1, $qry_subcategories, null, null, null);
                      $arr_subcategories = $lst_subcategories['data'];
                      ?>

                      <!-- category -->
                      <option value="<?php echo $row_categories['CategoryId']; ?>" <?php if ($row_categories['CategoryId']==$row_reports_info['CategoryId']) { echo "selected"; } ?> ><?php echo $row_categories['Title']; ?></option>
                      
                      <!-- subcategory -->
                      <?php if(count($arr_subcategories)){ ?>
                      <?php foreach ($arr_subcategories as $key_subcategories => $row_subcategories) { ?>
                      <option value="<?php echo $row_subcategories['CategoryId']; ?>" <?php if ($row_subcategories['CategoryId']==$row_reports_info['CategoryId']) { echo "selected"; } ?> >- <?php echo $row_subcategories['Title']; ?></option>
                      <?php } ?>
                      <?php } ?>

                      <?php } ?>
                    </select>
                  </div>

                  <!-- Dueño -->
                  <div class="col-md-6">
                    <label class="form-label" for="UsersId">Dueño</label>
                    <select class="form-select" id="UsersId" name="UsersId" required="">
                      <option selected="" disabled="" value="">Sin Seleccionar...</option>
                      <?php foreach ($arr_owners as $key_owners => $row_owners) { ?>
                      <option value="<?php echo $row_owners['UsersId']; ?>" <?php if ($row_owners['UsersId']==$row_reports_info['UsersId']) { echo "selected"; } ?> ><?php echo $row_owners['Fullname']; ?></option>
                    <?php } ?>
                    </select>
                  </div>

                  <!-- Ordenar -->
                  <div class="col-md-6">
                    <label class="form-label" for="Order">Ordenar</label>
                    <input class="form-control" id="Order" name="Order" type="number" value="<?php echo $Order; ?>" required="">
                  </div>

                  <!-- Estado -->
                  <div class="col-md-6">
                    <label class="form-label" for="Status">Estado </label>
                    <select class="form-select" id="Status" name="Status">
                      <option disabled="" value="">Sin Seleccionar...</option>
                      <?php foreach ($arr_status as $key_status => $row_status) { ?>
                      <option value="<?php echo $row_status; ?>" <?php if ($row_status==$row_reports_info['Status']) { echo "selected"; } ?> ><?php echo $key_status; ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
              </div>
              <div class="tab-pane fade" id="tab-profile" role="tabpanel" aria-labelledby="profile-tab">
                <div class="row">

                  <!-- Tipo -->
                  <div class="col-md-6">
                    <label class="form-label" for="TypeId">Tipo</label>
                    <select class="form-select" name=" "TypeId" required="">
                      <option selected="" disabled="" value="">Sin Seleccionar...</option>
                      <?php foreach ($arr_types as $key_types => $row_types) { ?>
                      <option value="<?php echo $row_types['TypesId']; ?>" <?php if ($row_types['TypesId']==$row_reports_info['TypeId']) { echo "selected"; } ?> ><?php echo $row_types['Title']; ?></option>
                      <?php } ?>
                    </select>
                  </div>

                  <!-- Categoría Padre -->
                  <div class="col-md-6">
                    <label class="form-label" for="ParentId">Padre</label>
                    <select class="form-select" id="ParentId" name="ParentId">
                      <option selected="" disabled="" value="">Sin Seleccionar...</option>
                      <?php foreach ($arr_parents as $key_parents => $row_parents) { ?>
                      <option value="<?php echo $row_parents['ReportsId']; ?>" <?php if ($row_parents['ReportsId']==$row_reports_info['ParentId']) { echo "selected"; } ?> ><?php echo $row_parents['Title']; ?></option>
                      <?php } ?>
                    </select>
                  </div>

                  <!-- Conexión -->
                  <div class="col-md-6">
                    <label class="form-label" for="ConnectionId">Conexión</label>
                    <select class="form-select" id="ConnectionId" name="ConnectionId">
                      <option selected="" disabled="" value="">Sin Seleccionar...</option>
                      <?php foreach ($arr_connections as $key_connections => $row_connections) { ?>
                      <option value="<?php echo $row_connections['ConnectionId']; ?>" <?php if ($row_connections['ConnectionId']==$row_reports_info['ConnectionId']) { echo "selected"; } ?> ><?php echo $row_connections['Title']; ?></option>
                      <?php } ?>
                    </select>
                  </div>

                  <!-- Enmascaramiento -->
                  <div class="col-md-6">
                    <label class="form-label" for="MaskingStatus">Enmascaramiento</label>
                    <select class="form-select" id="MaskingStatus" name="MaskingStatus">
                      <option disabled="" value="">Sin Seleccionar...</option>
                      <?php foreach ($arr_maskingstatus as $key_maskingstatus => $row_maskingstatus) { ?>
                      <option value="<?php echo $row_maskingstatus; ?>" <?php if ($row_maskingstatus==$row_reports_info['MaskingStatus']) { echo "selected"; } ?> ><?php echo $key_maskingstatus; ?></option>
                      <?php } ?>
                    </select>
                  </div>

                  <!-- Pipelines -->
                  <div class="col-md-12">
                    <label class="form-label" for="PipelinesId">Pipelines</label>
                    <select class="form-select" id="PipelinesId" name="PipelinesId">
                      <option selected="" disabled="" value="">Sin Seleccionar...</option>
                      <?php foreach ($arr_pipelines as $key_pipelines => $row_pipelines) { ?>
                      <option value="<?php echo $row_pipelines['PipelinesId']; ?>" <?php if ($row_pipelines['PipelinesId']==$row_reports_info['PipelinesId']) { echo "selected"; } ?> ><?php echo $row_pipelines['ReportsId']; ?>. <?php echo $row_pipelines['Title']; ?></option>
                      <?php } ?>
                    </select>
                  </div>

                  <!-- Totalizador eje X -->
                  <div class="col-md-6">
                    <label class="form-label" for="TotalAxisX">Totalizar eje X</label>
                    <select class="form-select" id="TotalAxisX" name="TotalAxisX">
                      <option disabled="" value="">Sin Seleccionar...</option>
                      <?php foreach ($arr_totalaxisx as $key_totalaxisx => $row_totalaxisx) { ?>
                      <option value="<?php echo $row_totalaxisx; ?>" <?php if ($row_totalaxisx==$row_reports_info['TotalAxisX']) { echo "selected"; } ?> ><?php echo $key_totalaxisx; ?></option>
                      <?php } ?>
                    </select>
                  </div>

                  <!-- Totalizador eje Y -->
                  <div class="col-md-6">
                    <label class="form-label" for="TotalAxisY">Totalizar eje Y</label>
                    <select class="form-select" id="TotalAxisY" name="TotalAxisY">
                      <option disabled="" value="">Sin Seleccionar...</option>
                      <?php foreach ($arr_totalaxisy as $key_totalaxisy => $row_totalaxisy) { ?>
                      <option value="<?php echo $row_totalaxisy; ?>" <?php if ($row_totalaxisy==$row_reports_info['TotalAxisY']) { echo "selected"; } ?> ><?php echo $key_totalaxisy; ?></option>
                      <?php } ?>
                    </select>
                  </div>

                  <!-- Diseño -->
                  <div class="col-md-6">
                    <label class="form-label" for="LayoutGridClass">Diseño</label>
                    <select class="form-select" id="LayoutGridClass" name="LayoutGridClass">
                      <option disabled="" value="">Sin Seleccionar...</option>
                      <?php foreach ($arr_layoutgridclass as $key_layoutgridclass => $row_layoutgridclass) { ?>
                      <option value="<?php echo $row_layoutgridclass; ?>" <?php if ($row_layoutgridclass==$row_reports_info['LayoutGridClass']) { echo "selected"; } ?> ><?php echo $key_layoutgridclass; ?></option>
                      <?php } ?>
                    </select>
                  </div>

                </div>
              </div>
            </div>
            <div class="col-12 pt-3">
              <button class="btn btn-primary" type="submit" name="action" value="update">Actualizar</button>
              <button class="btn btn-outline-primary" type="button" data-bs-dismiss="modal">Cancelar</button>
            </div>
          </form>
        </p>
      </div>
    </div>
  </div>
</div>