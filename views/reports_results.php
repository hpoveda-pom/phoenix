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
                  <?php if (isset($query_execution_time_formatted)): ?>
                  <p><strong>Tiempo de ejecución:</strong> <span class="badge bg-primary"><?php echo $query_execution_time_formatted; ?></span></p>
                  <?php endif; ?>
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
              <i class="fas fa-bug me-2"></i> Debug
            </button>
          </h2>
          <div class="accordion-collapse collapse" id="collapseDebug" aria-labelledby="headingDebug" data-bs-parent="#accordionExample">
            <div class="accordion-body pt-0">
              <div class="container-fluid">
                <!-- Información del Reporte -->
                <div class="card mb-3">
                  <div class="card-header bg-body-tertiary">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Reporte</h6>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-6">
                        <p class="mb-1"><strong>ReportsId:</strong> <?php echo isset($row_reports_info['ReportsId']) ? htmlspecialchars($row_reports_info['ReportsId']) : 'N/A'; ?></p>
                        <p class="mb-1"><strong>ConnectionId:</strong> <?php echo isset($row_reports_info['ConnectionId']) ? htmlspecialchars($row_reports_info['ConnectionId']) : 'N/A'; ?></p>
                        <p class="mb-1"><strong>Conexión:</strong> <?php echo isset($row_reports_info['conn_title']) ? htmlspecialchars($row_reports_info['conn_title']) : 'N/A'; ?></p>
                        <p class="mb-1"><strong>Schema:</strong> <?php echo isset($row_reports_info['conn_schema']) ? htmlspecialchars($row_reports_info['conn_schema']) : 'N/A'; ?></p>
                      </div>
                      <div class="col-md-6">
                        <p class="mb-1"><strong>Total Rows:</strong> <?php echo isset($array_info['total_rows']) ? number_format($array_info['total_rows']) : 'N/A'; ?></p>
                        <p class="mb-1"><strong>Page Rows:</strong> <?php echo isset($array_info['page_rows']) ? number_format($array_info['page_rows']) : 'N/A'; ?></p>
                        <?php if (isset($array_reports['error']) && !empty($array_reports['error'])): ?>
                        <p class="mb-1"><strong class="text-danger">Error:</strong> <span class="text-danger"><?php echo htmlspecialchars($array_reports['error']); ?></span></p>
                        <?php endif; ?>
                        <?php if (isset($array_headers['error']) && !empty($array_headers['error'])): ?>
                        <p class="mb-1"><strong class="text-danger">Error (Headers):</strong> <span class="text-danger"><?php echo htmlspecialchars($array_headers['error']); ?></span></p>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Log de Debug -->
                <div class="card">
                  <div class="card-header bg-body-tertiary">
                    <h6 class="mb-0"><i class="fas fa-list me-2"></i>Log de Debug</h6>
                  </div>
                  <div class="card-body">
                    <?php 
                    // Obtener información de debug
                    $debug_messages = isset($debug_info) && is_array($debug_info) ? $debug_info : (isset($GLOBALS['debug_info']) && is_array($GLOBALS['debug_info']) ? $GLOBALS['debug_info'] : []);
                    
                    if (!empty($debug_messages)) {
                      echo '<div class="table-responsive">';
                      echo '<table class="table table-sm table-bordered mb-0">';
                      echo '<thead class="table-secondary">';
                      echo '<tr><th style="width: 50px;">#</th><th>Mensaje</th></tr>';
                      echo '</thead>';
                      echo '<tbody>';
                      foreach ($debug_messages as $index => $msg) {
                        $is_error = (stripos($msg, 'error') !== false || stripos($msg, 'exception') !== false || stripos($msg, 'fatal') !== false);
                        $row_class = $is_error ? 'table-danger' : '';
                        echo '<tr class="' . $row_class . '">';
                        echo '<td class="text-center">' . ($index + 1) . '</td>';
                        echo '<td><code style="font-size: 11px;">' . htmlspecialchars($msg) . '</code></td>';
                        echo '</tr>';
                      }
                      echo '</tbody>';
                      echo '</table>';
                      echo '</div>';
                    } else {
                      echo '<div class="alert alert-secondary mb-0">No hay información de debug disponible.</div>';
                    }
                    ?>
                  </div>
                </div>
                
                <!-- Información de DataTables (si hay error) -->
                <div id="datatables-debug" class="card mt-3" style="display: none;">
                  <div class="card-header bg-warning">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Error de DataTables</h6>
                  </div>
                  <div class="card-body">
                    <div id="datatables-error-message" class="text-danger"></div>
                    <pre id="datatables-error-details" class="mt-2" style="font-size: 11px; max-height: 300px; overflow-y: auto;"></pre>
                  </div>
                </div>
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

// Inicializar DataTables con paginación del lado del servidor
<?php if(isset($array_reports['headers']) && is_array($array_reports['headers']) && !empty($array_reports['headers']) && isset($row_reports_info['ReportsId'])): ?>
$(document).ready(function() {
    var columnCount = <?php echo count($array_reports['headers']); ?>;
    var headers = <?php echo json_encode($array_reports['headers']); ?>;
    var columns = [];
    for (var i = 0; i < columnCount; i++) {
        var columnConfig = {
            "data": i,
            "orderable": true,
            "defaultContent": ""
        };
        
        // Si la columna es "Cantidad", centrarla y ajustar ancho
        if (headers[i] && headers[i].toLowerCase() === 'cantidad') {
            columnConfig.className = "text-center";
            columnConfig.width = "auto";
        }
        
        columns.push(columnConfig);
    }
    
    var reportsTable = $('#reportsTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "controllers/data.php",
            "type": "GET",
            "data": function(d) {
                d.action = "datatables";
                d.Id = <?php echo $row_reports_info['ReportsId']; ?>;
                // Pasar filtros y agrupaciones si existen
                <?php if(isset($filter_results) && !empty($filter_results)): ?>
                d.filter_selected = <?php echo json_encode($filter_results); ?>;
                <?php endif; ?>
                <?php if(isset($groupby_results) && !empty($groupby_results)): ?>
                d.groupby_selected = <?php echo json_encode($groupby_results); ?>;
                <?php endif; ?>
                <?php if(isset($sumby_results) && !empty($sumby_results)): ?>
                d.sumby_selected = <?php echo json_encode($sumby_results); ?>;
                <?php endif; ?>
            },
            "error": function(xhr, error, thrown) {
                console.error("Error en DataTables:", error, thrown);
                console.error("Response:", xhr.responseText);
                
                // Mostrar error en el accordion de debug
                var errorDiv = document.getElementById('datatables-debug');
                var errorMsg = document.getElementById('datatables-error-message');
                var errorDetails = document.getElementById('datatables-error-details');
                
                if (errorDiv && errorMsg && errorDetails) {
                    errorDiv.style.display = 'block';
                    errorMsg.textContent = "Error: " + error + " - " + thrown;
                    
                    var responseText = xhr.responseText || 'Sin respuesta del servidor';
                    try {
                        var jsonResponse = JSON.parse(responseText);
                        errorDetails.textContent = JSON.stringify(jsonResponse, null, 2);
                        if (jsonResponse.debug && Array.isArray(jsonResponse.debug)) {
                            errorDetails.textContent += "\n\nDebug Info:\n" + jsonResponse.debug.join("\n");
                        }
                    } catch (e) {
                        errorDetails.textContent = responseText.substring(0, 2000);
                    }
                    
                    // Expandir el accordion de debug
                    var debugButton = document.querySelector('[data-bs-target="#collapseDebug"]');
                    if (debugButton && !debugButton.classList.contains('collapsed')) {
                        debugButton.click();
                    }
                }
                
                alert("Error al cargar los datos: " + error + "\n\nRevisa el accordion de Debug para más detalles.");
            }
        },
        "pageLength": <?php echo isset($Limit) && $Limit > 0 ? $Limit : 10; ?>,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        "searching": false,
        "language": {
            "decimal": ",",
            "emptyTable": "No hay datos disponibles en la tabla",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "infoPostFix": "",
            "thousands": ".",
            "lengthMenu": "Mostrar _MENU_ registros",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros coincidentes",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "aria": {
                "sortAscending": ": activar para ordenar la columna de forma ascendente",
                "sortDescending": ": activar para ordenar la columna de forma descendente"
            }
        },
        "order": [],
        "responsive": false,
        "scrollX": false,
        "autoWidth": true,
        "dom": 'rtip',
        "columns": columns,
        "columnDefs": (function() {
            // Encontrar el índice de la columna "Cantidad"
            var cantidadIndex = -1;
            for (var i = 0; i < headers.length; i++) {
                if (headers[i] && headers[i].toLowerCase() === 'cantidad') {
                    cantidadIndex = i;
                    break;
                }
            }
            
            if (cantidadIndex >= 0) {
                return [
                    {
                        "targets": [cantidadIndex],
                        "width": "auto",
                        "className": "text-center"
                    }
                ];
            }
            return [];
        })()
    });
});
<?php endif; ?>
</script>
