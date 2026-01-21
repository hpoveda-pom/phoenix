<?php
$Query = $row_reports_info['Query'];
if (isset($_POST['Query'])) {
  $Query = $_POST['Query'];
}

$ReportsId = $row_reports_info['ReportsId'];
if (isset($_POST['ReportsId'])) {
  $ReportsId = $_POST['ReportsId'];
}

$UserUpdated = null;
if (isset($_SESSION['UsersId'])) {
  $UserUpdated = $_SESSION['UsersId'];
}

//CRUD - Query Editor
if ($action == "update" && $form_id == 'editor_query') {
  $qry_upd_reports = 'UPDATE reports a SET a.Query = "'.$Query.'", a.UserUpdated = '.$UserUpdated.' WHERE a.ReportsId = '.$ReportsId;
  $upd_reports = class_queryMysqliExe(1, $qry_upd_reports);

  $lastURL = $_SERVER['REQUEST_URI'];
  header("Location: $lastURL");
  exit();
}
?>
<div class="card pt-1 pb-4">
    <div class="tab-content" id="myTabContent">
      <?php if($cod_error == 1){ ?>

      <div class="alert alert-outline-danger d-flex align-items-center mt-5" role="alert">
        <span class="fas fa-times-circle text-danger fs-5 me-3"></span>
        <p class="mb-0 flex-1"><?php echo $msg_error; ?></p>
        <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>

      <?php }else{ ?>
      <div class="accordion" id="accordionExample">
        <!-- Resultados Accordion -->
        <div class="accordion-item">
          <h2 class="accordion-header" id="headingOne">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
              Resultados
            </button>
          </h2>
          <div class="accordion-collapse collapse show" id="collapseOne" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
            <div class="accordion-body pt-0">
              <!-- Table of Results -->
              <?php if(isset($array_reports['data']) && is_array($array_reports['data']) && !empty($array_reports['data'])){ ?>
              <div id="tableExample">
                <div class="table-responsive">
                  <?php if ($row_reports_info['Status'] == 0) { ?>
                  <div class="col-md-12">
                    <ul class="list-group">
                      <li class="list-group-item list-group-item-default py-3">
                        <i class="fas fa-ban text-danger"></i> 
                        Este reporte está inactivo...
                      </li>
                    </ul>
                  </div>

                  <?php }elseif ($row_reports_info['SyncStatus'] == 3) { ?>
                    <div class="col-md-6">
                      <ul class="list-group">
                        <li class="list-group-item list-group-item-success py-3">
                          <div class="spinner-grow spinner-grow-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                          </div>
                          Sincronizando, vuelva en unos minutos...
                        </li>
                      </ul>
                    </div>
                  <?php } else {
                  require_once('views/reports_tables.php');
                  }
                  ?>
                </div>
                <!-- Pagination -->
                <div class="d-flex justify-content-between mt-3">
                  <span class="d-sm-inline-block" data-list-info="data-list-info">
                    Registros <b>1</b> al <b><?php echo $array_info['page_rows']; ?></b> 
                    <span class="text-body-tertiary">Total</span><b><?php echo number_format($array_info['total_rows']); ?></b>
                  </span>
                  <div class="d-flex">
                    <button class="btn btn-sm btn-primary" type="button" data-list-pagination="prev" disabled><span>Previous</span></button>
                    <button class="btn btn-sm btn-primary px-4 ms-2" type="button" data-list-pagination="next" disabled><span>Next</span></button>
                  </div>
                </div>
            </div>
          <?php }elseif(isset($array_reports['error']) && $array_reports['error']){ ?>
          <div class="alert alert-subtle-danger d-flex align-items-center" role="alert">
            <span class="fas fa-times-circle text-danger fs-5 me-3"></span>
            <b>Query Error: </b>
            <p class="mb-0 flex-1"> <?php echo $array_reports['error']; ?></p>
            <?php class_cruds('rollback'); ?>
          </div>
          <?php }else{ ?>
            No hay registros
          <?php } ?>
          </div>
        </div>
        </div>
        <!-- Detalles Accordion -->
        <div class="accordion-item">
          <h2 class="accordion-header" id="headingTwo">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
              Detalles
            </button>
          </h2>
          <div class="accordion-collapse collapse" id="collapseTwo" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
            <div class="accordion-body pt-0">
              <div class="row">
                <div class="col-md-4">
                  <p><strong>Título:</strong> <?php echo $row_reports_info['Title']; ?></p>
                  <p><strong>Versión:</strong> <?php echo $row_reports_info['Version']; ?></p>
                  <p><strong>Propósito:</strong> <?php echo $row_reports_info['Description']; ?></p>
                  <p><strong>Categoría:</strong> <?php echo $row_reports_info['Category']; ?></p>
                </div>
                <div class="col-md-4">
                  <p><strong>Dueño:</strong> <?php echo $row_reports_info['FullName']; ?></p>
                  <p><strong>Fecha Creación:</strong> <?php echo $row_reports_info['CreatedDate']; ?></p>
                  <p><strong>Modificado por:</strong> <?php echo $row_reports_info['UserUpdatedName']; ?></p>
                  <p><strong>Última modificación:</strong> <?php echo $row_reports_info['LastUpdated']; ?></p>
                </div>
                <div class="col-md-4">
                  <p><strong>Conexión:</strong> <?php echo $row_reports_info['conn_title']; ?></p>
                  <p><strong>Conector:</strong> <?php echo $row_reports_info['conn_connector']; ?></p>
                  <p><strong>Schema:</strong> <?php echo $row_reports_info['conn_schema']; ?></p>
                  <p><strong>Última ejecución:</strong> <?php echo $row_reports_info['LastExecution']; ?></p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Configuración Accordion -->
        <?php if ($UsersType != 3) { ?>
        <div class="accordion-item">
          <h2 class="accordion-header" id="headingThree">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
              Configuración
            </button>
          </h2>
          <div class="accordion-collapse collapse" id="collapseThree" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
            <div class="accordion-body pt-0">
              <div class="container mt-5 p-0">
                <!-- Formulario para actualizar -->
                  <div class="card g-3 pt-2 pb-1 w-100">
                    <div class="card-header">
                      Código SQL
                    </div>
                    <div class="card-body p-0">
                      <div id="editor_query" style="height: 400px; width: 100%;"><?php echo $Query; ?></div>
                    </div>
                    <div class="card-footer">
                      <!-- Botones -->
                      <div class="col-12 col-md-10 col-lg-8">
                        <div class="btn-group w-100 d-flex flex-wrap">
                          <?php if($row_reports_info['UsersId'] == $UsersId || $UsersId == 1){ ?>
                          <form action="" method="POST" id="query_form">
                            <input type="hidden" name="form_id" value="editor_query">
                            <button class="btn btn-subtle-primary w-100 w-md-auto mb-2" type="submit" name="action" value="update">
                              <i class="fas fa-sync-alt"></i> Actualizar
                            </button>
                            <input type="hidden" name="Query" id="query_input">
                          </form>
                          <?php
                          class_cruds('edit');
                          class_cruds('copy');
                          class_cruds('delete');
                          ?>
                        <?php } ?>

                          <?php
                          
                          if (0) {
                            class_cruds('rollback');
                            class_cruds('share');
                            class_cruds('add');
                          }
                          ?>
                        </div>
                      </div>
                    </div>
                  </div>
              </div>
            </div>
          </div>
        </div>
        <?php } ?>
        <!-- Debug Accordion -->
        <div class="accordion-item">
          <h2 class="accordion-header" id="headingDebug">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDebug" aria-expanded="false" aria-controls="collapseDebug">
              Debug
            </button>
          </h2>
          <div class="accordion-collapse collapse" id="collapseDebug" aria-labelledby="headingDebug" data-bs-parent="#accordionExample">
            <div class="accordion-body pt-0">
              <div class="container-fluid">
                <h6 class="mb-3">Información de Debug</h6>
                <?php 
                // Obtener información de debug
                $debug_messages = isset($debug_info) && is_array($debug_info) ? $debug_info : (isset($GLOBALS['debug_info']) && is_array($GLOBALS['debug_info']) ? $GLOBALS['debug_info'] : []);
                
                if (!empty($debug_messages)) {
                  echo '<div class="alert alert-info">';
                  echo '<pre style="white-space: pre-wrap; word-wrap: break-word; font-size: 12px; margin: 0;">';
                  foreach ($debug_messages as $msg) {
                    echo htmlspecialchars($msg) . "\n";
                  }
                  echo '</pre>';
                  echo '</div>';
                } else {
                  echo '<div class="alert alert-secondary">No hay información de debug disponible.</div>';
                }
                
                // Información adicional
                echo '<div class="mt-3">';
                echo '<h6>Información del Reporte:</h6>';
                echo '<ul class="list-unstyled">';
                if (isset($row_reports_info['ConnectionId'])) {
                  echo '<li><strong>ConnectionId:</strong> ' . htmlspecialchars($row_reports_info['ConnectionId']) . '</li>';
                }
                if (isset($row_reports_info['conn_title'])) {
                  echo '<li><strong>Conexión:</strong> ' . htmlspecialchars($row_reports_info['conn_title']) . '</li>';
                }
                if (isset($row_reports_info['conn_schema'])) {
                  echo '<li><strong>Schema:</strong> ' . htmlspecialchars($row_reports_info['conn_schema']) . '</li>';
                }
                if (isset($array_reports['error'])) {
                  echo '<li><strong>Error:</strong> <span class="text-danger">' . htmlspecialchars($array_reports['error']) . '</span></li>';
                }
                if (isset($array_headers['error'])) {
                  echo '<li><strong>Error (Headers):</strong> <span class="text-danger">' . htmlspecialchars($array_headers['error']) . '</span></li>';
                }
                echo '</ul>';
                echo '</div>';
                ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php } ?>
    </div>
  </div>


<script type="text/javascript">
// Inicializar el editor específico
  var editor_query = configureAceEditor("editor_query", {
    theme: "ace/theme/default",
    mode: "ace/mode/sql",
    fontSize: "10pt",
    wrap: false,
    dynamicHeight: true,
  });

document.getElementById("query_form").addEventListener("submit", function (event) {
    var query = editor_query.getValue();
    document.getElementById("query_input").value = query;
});
</script>
