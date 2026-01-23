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
                
                <!-- Debug Específico de Resultados del Reporte -->
                <div class="card mb-3">
                  <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Consulta SQL del Reporte</h6>
                  </div>
                  <div class="card-body">
                    <?php 
                    // Obtener la consulta SQL del reporte
                    $report_query = isset($Query) ? trim($Query) : '';
                    $reports_id = isset($row_reports_info['ReportsId']) ? $row_reports_info['ReportsId'] : 0;
                    $connection_id = isset($row_reports_info['ConnectionId']) ? $row_reports_info['ConnectionId'] : 0;
                    
                    // Obtener información de debug específica de filtros
                    $debug_filters = isset($GLOBALS['debug_filters']) && is_array($GLOBALS['debug_filters']) ? $GLOBALS['debug_filters'] : [];
                    
                    // Filtrar solo mensajes relacionados con la consulta SQL del reporte
                    $filtered_debug = [];
                    
                    if (empty($report_query)) {
                      echo '<div class="alert alert-warning mb-0">No hay consulta SQL del reporte disponible.</div>';
                    } else {
                      // Normalizar la consulta del reporte para comparación
                      // Extraer la parte central de la consulta (lo que está dentro del SELECT ... FROM)
                      $report_query_normalized = preg_replace('/\s+/', ' ', strtolower(trim($report_query)));
                      
                      // Buscar palabras clave únicas de la consulta del reporte (primeras 50 palabras)
                      $report_keywords = [];
                      $words = explode(' ', $report_query_normalized);
                      foreach ($words as $word) {
                        $word = trim($word);
                        if (strlen($word) > 3 && !in_array(strtolower($word), ['select', 'from', 'where', 'and', 'or', 'join', 'inner', 'left', 'right', 'outer', 'on', 'as', 'tb', 'a', 'b', 'c', 'd', 'e', 'f', 'g'])) {
                          $report_keywords[] = $word;
                          if (count($report_keywords) >= 10) break; // Solo las primeras 10 palabras clave
                        }
                      }
                      
                      // Buscar el bloque de mensajes que contiene la consulta del reporte
                      $found_report_block = false;
                      $report_block_start = -1;
                      $report_block_end = -1;
                      
                      // Primero, encontrar dónde empieza el bloque del reporte
                      foreach ($debug_filters as $index => $msg) {
                        if (stripos($msg, 'SQL EJECUTADO') !== false) {
                          // Normalizar el mensaje para comparación
                          $msg_normalized = preg_replace('/\s+/', ' ', strtolower($msg));
                          
                          // Verificar si este mensaje contiene la consulta del reporte
                          // Buscar si contiene suficientes palabras clave del reporte
                          $matches = 0;
                          foreach ($report_keywords as $keyword) {
                            if (stripos($msg_normalized, $keyword) !== false) {
                              $matches++;
                            }
                          }
                          
                          // Si tiene al menos 3 palabras clave o contiene la consulta completa, es el reporte
                          if ($matches >= 3 || stripos($msg_normalized, $report_query_normalized) !== false) {
                            // Verificar que NO sea una consulta del sistema
                            $is_system = false;
                            $system_patterns = [
                              '/\bFROM\s+[`\']?users[`\']?\s/i',
                              '/\bFROM\s+[`\']?category[`\']?\s/i',
                              '/\bFROM\s+[`\']?connections[`\']?\s/i',
                              '/\bFROM\s+[`\']?reports[`\']?\s/i',
                              '/\bFROM\s+[`\']?types[`\']?\s/i',
                              '/\bFROM\s+[`\']?pipelines[`\']?\s/i',
                              '/WHERE\s+a\.ReportsId\s*=\s*\d+/i', // Consultas que buscan por ReportsId (del sistema)
                              '/WHERE\s+a\.UsersId\s*=\s*\d+/i',   // Consultas que buscan por UsersId (del sistema)
                            ];
                            
                            foreach ($system_patterns as $pattern) {
                              if (preg_match($pattern, $msg)) {
                                $is_system = true;
                                break;
                              }
                            }
                            
                            if (!$is_system) {
                              $found_report_block = true;
                              $report_block_start = $index;
                              break;
                            }
                          }
                        }
                      }
                      
                      // Si encontramos el bloque, extraer todos los mensajes relacionados
                      if ($found_report_block && $report_block_start >= 0) {
                        // Buscar hasta encontrar el siguiente SQL EJECUTADO o hasta el final
                        for ($i = $report_block_start; $i < count($debug_filters); $i++) {
                          $msg = $debug_filters[$i];
                          
                          // Si encontramos otro SQL EJECUTADO que no es del reporte, parar
                          if ($i > $report_block_start && stripos($msg, 'SQL EJECUTADO') !== false) {
                            // Verificar si es del sistema
                            $is_system = false;
                            foreach (['users', 'category', 'connections', 'reports', 'types', 'pipelines'] as $table) {
                              if (preg_match('/\bFROM\s+[`\']?' . preg_quote($table, '/') . '[`\']?\s/i', $msg)) {
                                $is_system = true;
                                break;
                              }
                            }
                            if (!$is_system) {
                              // Es otra consulta del reporte, continuar
                              continue;
                            } else {
                              // Es del sistema, parar aquí
                              break;
                            }
                          }
                          
                          $filtered_debug[] = $msg;
                        }
                      }
                      
                      // Si no encontramos en debug_filters, mostrar la consulta directamente
                      if (empty($filtered_debug) && !empty($report_query)) {
                        $filtered_debug[] = 'SQL EJECUTADO: ' . $report_query;
                        if (isset($array_reports['info']['total_rows'])) {
                          $filtered_debug[] = 'TOTAL: ' . number_format($array_reports['info']['total_rows']) . ' filas';
                        }
                        if (isset($array_reports['info']['page_rows'])) {
                          $filtered_debug[] = 'RESULTADO: ' . number_format($array_reports['info']['page_rows']) . ' filas obtenidas';
                        }
                        if (isset($array_reports['error']) && !empty($array_reports['error'])) {
                          $filtered_debug[] = 'ERROR: ' . $array_reports['error'];
                        }
                      }
                    }
                    
                    if (!empty($filtered_debug)) {
                      echo '<div class="table-responsive">';
                      echo '<table class="table table-sm table-bordered mb-0">';
                      echo '<thead class="table-secondary">';
                      echo '<tr><th style="width: 50px;">#</th><th>Consulta SQL del Reporte</th></tr>';
                      echo '</thead>';
                      echo '<tbody>';
                      foreach ($filtered_debug as $index => $msg) {
                        $is_sql = (stripos($msg, 'SQL') !== false);
                        $is_error = (stripos($msg, 'error') !== false || stripos($msg, 'exception') !== false || stripos($msg, 'ERROR') !== false);
                        $is_result = (stripos($msg, 'RESULTADO') !== false || stripos($msg, 'TOTAL') !== false || stripos($msg, 'filas') !== false);
                        $is_filter = (stripos($msg, 'FILTROS') !== false);
                        $row_class = '';
                        if ($is_error) {
                          $row_class = 'table-danger';
                        } elseif ($is_sql) {
                          $row_class = 'table-info';
                        } elseif ($is_result) {
                          $row_class = 'table-success';
                        } elseif ($is_filter) {
                          $row_class = 'table-warning';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td class="text-center">' . ($index + 1) . '</td>';
                        if ($is_sql) {
                          // Para queries SQL, mostrar en un formato más legible
                          echo '<td><pre style="font-size: 11px; margin: 0; white-space: pre-wrap; word-wrap: break-word; background: #f8f9fa; padding: 8px; border-radius: 4px;">' . htmlspecialchars($msg) . '</pre></td>';
                        } else {
                          echo '<td><code style="font-size: 11px;">' . htmlspecialchars($msg) . '</code></td>';
                        }
                        echo '</tr>';
                      }
                      echo '</tbody>';
                      echo '</table>';
                      echo '</div>';
                    } else {
                      echo '<div class="alert alert-info mb-0">No hay información de debug disponible. El reporte se ejecutó correctamente.</div>';
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
        
        // Si la columna es "Cantidad", centrarla y ajustar ancho, y permitir HTML
        if (headers[i] && headers[i].toLowerCase() === 'cantidad') {
            columnConfig.className = "text-center";
            columnConfig.width = "auto";
            columnConfig.render = function(data, type, row) {
                // Si el dato contiene HTML (span con cantidad-clickable), devolverlo tal cual
                if (type === 'display' && typeof data === 'string' && data.indexOf('<span') !== -1) {
                    return data;
                }
                return data;
            };
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
            "dataSrc": function(json) {
                // Mostrar debug de filtros en consola sin interferir
                if (json.debug_filters) {
                    console.log("=== DEBUG FILTROS (AJAX) ===");
                    json.debug_filters.forEach(function(msg) {
                        console.log(msg);
                    });
                    
                    // Mostrar alerta si hay discrepancia
                    if (json.recordsTotal > 0 && json.recordsTotal > 10) {
                        console.warn("⚠️ ADVERTENCIA: DataTables recibió " + json.recordsTotal + " registros. ¿Los filtros se aplicaron correctamente?");
                    }
                }
                // Retornar los datos para DataTables
                return json.data;
            },
            "error": function(xhr, error, thrown) {
                console.error("Error en DataTables:", error, thrown);
                console.error("Response:", xhr.responseText);
                
                // Mostrar debug de filtros si está disponible
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.debug_filters) {
                        console.log("=== DEBUG FILTROS (AJAX) ===");
                        response.debug_filters.forEach(function(msg) {
                            console.log(msg);
                        });
                    }
                } catch(e) {}
                
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
            var columnDefs = [];
            
            // Encontrar índices de columnas especiales
            var cantidadIndex = -1;
            var sumaIndices = [];
            
            for (var i = 0; i < headers.length; i++) {
                var header = headers[i] ? headers[i].toLowerCase() : '';
                
                // Columna "Cantidad" - centrada
                if (header === 'cantidad') {
                    cantidadIndex = i;
                }
                
                // Columnas de montos (Suma (*) o campos con monto/precio/total/importe) - alineadas a la derecha
                if (header.indexOf('suma (') === 0 || 
                    header.indexOf('suma_') === 0 ||
                    header.indexOf('monto') !== -1 || 
                    header.indexOf('precio') !== -1 ||
                    header.indexOf('total') !== -1 ||
                    header.indexOf('importe') !== -1) {
                    sumaIndices.push(i);
                }
            }
            
            // Aplicar configuración a "Cantidad"
            if (cantidadIndex >= 0) {
                columnDefs.push({
                    "targets": [cantidadIndex],
                    "width": "auto",
                    "className": "text-center"
                });
            }
            
            // Aplicar configuración a columnas de montos
            if (sumaIndices.length > 0) {
                columnDefs.push({
                    "targets": sumaIndices,
                    "className": "text-end"
                });
            }
            
            return columnDefs;
        })()
    });
    
    // Manejar clic en valores de "Cantidad" para drill-down
    $(document).on('click', '.cantidad-clickable', function(e) {
        e.preventDefault();
        
        // Obtener los valores de agrupación del atributo data-group-values
        var groupValuesAttr = $(this).attr('data-group-values');
        if (!groupValuesAttr) {
            console.error('No se encontró data-group-values en el elemento clickeado');
            return;
        }
        
        try {
            // Parsear el JSON de los valores de agrupación
            var groupValues = JSON.parse(groupValuesAttr);
            console.log('Valores de agrupación:', groupValues);
            
            // Construir la URL base
            var baseUrl = 'reports.php';
            var urlParams = new URLSearchParams();
            urlParams.set('Id', <?php echo $row_reports_info['ReportsId']; ?>);
            
            // Agregar filtros basados en los valores de agrupación
            // Cada campo agrupado se convierte en un filtro con operador "="
            var filterIndex = 0;
            for (var field in groupValues) {
                if (groupValues.hasOwnProperty(field)) {
                    urlParams.append('Filter[' + filterIndex + '][field]', field);
                    urlParams.append('Filter[' + filterIndex + '][operator]', '=');
                    urlParams.append('Filter[' + filterIndex + '][keyword]', groupValues[field]);
                    filterIndex++;
                }
            }
            
            // Mantener otros parámetros existentes si es necesario
            <?php if(isset($Limit) && $Limit > 0): ?>
            urlParams.set('Limit', <?php echo $Limit; ?>);
            <?php endif; ?>
            
            // Construir la URL final
            var finalUrl = baseUrl + '?' + urlParams.toString();
            console.log('Navegando a:', finalUrl);
            
            // Redirigir a la nueva URL
            window.location.href = finalUrl;
        } catch (error) {
            console.error('Error al parsear data-group-values:', error);
            console.error('Valor recibido:', groupValuesAttr);
            alert('Error al procesar el clic. Por favor, intente nuevamente.');
        }
    });
});
<?php endif; ?>
</script>
