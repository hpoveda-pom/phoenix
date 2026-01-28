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
//echo "<pre>";print_r($array_reports['data']);exit;

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
              <?php 
              // Para reportes tipo 1, siempre mostrar la tabla (DataTables obtendrá los datos desde data.php)
              // Ya no verificamos si hay datos porque DataTables los obtendrá dinámicamente
              if (isset($row_reports_info['TypeId']) && $row_reports_info['TypeId'] == 1) { 
                // Verificar si hay error primero
                if (isset($array_reports['error']) && $array_reports['error']) { ?>
                  <div class="alert alert-subtle-danger d-flex align-items-center" role="alert">
                    <span class="fas fa-times-circle text-danger fs-5 me-3"></span>
                    <b>Query Error: </b>
                    <p class="mb-0 flex-1"> <?php echo $array_reports['error']; ?></p>
                    <?php class_cruds('rollback'); ?>
                  </div>
                <?php } else { ?>
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
                <?php } ?>
              <?php } elseif(isset($array_reports['error']) && $array_reports['error']){ ?>
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
        <!-- DEBUG Accordion - Solo se muestra si el modo debug está activado Y el usuario es administrador -->
        <?php if (isset($_SESSION['debug_mode']) && $_SESSION['debug_mode'] && isset($UsersType) && $UsersType == 1): ?>
        <div class="accordion-item">
          <h2 class="accordion-header" id="headingDebug">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDebug" aria-expanded="false" aria-controls="collapseDebug">
              DEBUG
            </button>
          </h2>
          <div class="accordion-collapse collapse" id="collapseDebug" aria-labelledby="headingDebug" data-bs-parent="#accordionExample">
            <div class="accordion-body pt-0">
              <div class="container mt-3 p-0">
                <div class="card g-3 pt-2 pb-1 w-100">
                  <div class="card-header">
                    Subquery SQL Ejecutado
                  </div>
                  <div class="card-body p-0">
                    <?php 
                    // Obtener la consulta de debug si está disponible
                    $debug_query = null;
                    $debug_query_with_where = null;
                    $debug_filters = [];
                    
                    // Debug: verificar qué contiene array_reports
                    // var_dump(array_keys($array_reports ?? [])); // Descomentar para debug
                    
                    if (isset($array_reports['debug_query']) && !empty($array_reports['debug_query'])) {
                      $debug_query = $array_reports['debug_query'];
                    }
                    if (isset($array_reports['debug_query_with_where']) && !empty($array_reports['debug_query_with_where'])) {
                      $debug_query_with_where = $array_reports['debug_query_with_where'];
                    }
                    if (isset($array_reports['debug_filters']) && is_array($array_reports['debug_filters']) && !empty($array_reports['debug_filters'])) {
                      $debug_filters = $array_reports['debug_filters'];
                    }
                    ?>
                    
                    <?php if ($Query): ?>
                    <div class="p-3">
                      <h6 class="mb-2"><strong>1. Query Original (sin filtros):</strong></h6>
                      <pre class="bg-light p-3 border rounded" style="max-height: 200px; overflow-y: auto; font-size: 11px; white-space: pre-wrap; word-wrap: break-word;"><code><?php echo htmlspecialchars($Query); ?></code></pre>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($debug_query_with_where): ?>
                    <div class="p-3 border-top">
                      <h6 class="mb-2"><strong>2. Subquery con Filtros Aplicados (query_with_where):</strong></h6>
                      <p class="text-muted small mb-2">Esta es la subconsulta que se inserta dentro del SELECT tb.* FROM (...)</p>
                      <pre class="bg-light p-3 border rounded" style="max-height: 300px; overflow-y: auto; font-size: 11px; white-space: pre-wrap; word-wrap: break-word;"><code><?php echo htmlspecialchars($debug_query_with_where); ?></code></pre>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($debug_query): ?>
                    <div class="p-3 border-top">
                      <h6 class="mb-2"><strong>3. Query Final Completo Ejecutado (nivel superior):</strong></h6>
                      <p class="text-muted small mb-2">Esta es la query completa que se ejecuta, incluyendo el SELECT tb.* FROM (subquery) AS tb</p>
                      <pre class="bg-primary bg-opacity-10 p-3 border border-primary rounded" style="max-height: 400px; overflow-y: auto; font-size: 11px; white-space: pre-wrap; word-wrap: break-word;"><code><?php echo htmlspecialchars($debug_query); ?></code></pre>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($filter_results) && !empty($filter_results)): ?>
                    <div class="p-3 border-top">
                      <h6 class="mb-2">Filtros Aplicados (filter_results):</h6>
                      <pre class="bg-light p-3 border rounded" style="max-height: 200px; overflow-y: auto; font-size: 11px; white-space: pre-wrap; word-wrap: break-word;"><code><?php echo htmlspecialchars(print_r($filter_results, true)); ?></code></pre>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($debug_filters)): ?>
                    <div class="p-3 border-top">
                      <h6 class="mb-2">Información de Debug de Filtros:</h6>
                      <ul class="list-group">
                        <?php foreach ($debug_filters as $debug_msg): ?>
                        <li class="list-group-item" style="font-size: 11px;"><?php echo htmlspecialchars($debug_msg); ?></li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!$debug_query && !$debug_query_with_where): ?>
                    <div class="p-3 border-top">
                      <h6 class="mb-2"><strong>Diagnóstico:</strong></h6>
                      <div class="alert alert-warning">
                        <p class="mb-1"><strong>Las variables de debug no están disponibles.</strong></p>
                        <p class="mb-0 small">
                          <?php if (isset($array_reports)): ?>
                            Variables disponibles en array_reports: <?php echo implode(', ', array_keys($array_reports)); ?><br>
                            ¿Tiene debug_query? <?php echo isset($array_reports['debug_query']) ? 'Sí' : 'No'; ?><br>
                            ¿Tiene debug_query_with_where? <?php echo isset($array_reports['debug_query_with_where']) ? 'Sí' : 'No'; ?><br>
                            ¿Tiene debug_filters? <?php echo isset($array_reports['debug_filters']) ? 'Sí' : 'No'; ?>
                          <?php else: ?>
                            array_reports no está definido.
                          <?php endif; ?>
                        </p>
                      </div>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
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
<?php 
// Usar headers de array_reports o array_headers como respaldo
$datatable_headers = null;
if (isset($array_reports['headers']) && is_array($array_reports['headers']) && !empty($array_reports['headers'])) {
    $datatable_headers = $array_reports['headers'];
} elseif (isset($array_headers['headers']) && is_array($array_headers['headers']) && !empty($array_headers['headers'])) {
    $datatable_headers = $array_headers['headers'];
}

// Inicializar DataTables si hay ReportsId (siempre inicializar, incluso sin headers)
if (isset($row_reports_info['ReportsId'])): ?>
$(document).ready(function() {
    console.log('Inicializando DataTables...');
    
    // Verificar que la tabla exista
    if ($('#reportsTable').length === 0) {
        console.error('Error: La tabla #reportsTable no existe en el DOM');
        return;
    }
    
    var columnCount = <?php echo $datatable_headers ? count($datatable_headers) : 0; ?>;
    var headers = <?php echo $datatable_headers ? json_encode($datatable_headers) : '[]'; ?>;
    var columns = [];
    console.log('Headers iniciales:', headers, 'Count:', columnCount);
    
    // Configurar columnas si hay headers
    if (columnCount > 0 && headers.length > 0) {
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
    }
    // Si no hay headers, DataTables los detectará desde la primera respuesta
    // pero necesitamos al menos una columna para inicializar
    if (columns.length === 0) {
        columns = [{
            "data": 0,
            "orderable": true,
            "defaultContent": ""
        }];
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
                // Retornar los datos para DataTables
                return json.data;
            },
            "error": function(xhr, error, thrown) {
                console.error("Error en DataTables:", error, thrown);
                alert("Error al cargar los datos: " + error);
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
        "columns": columns.length > 0 ? columns : undefined, // Si no hay columnas, DataTables las detectará automáticamente
        "columnDefs": (function() {
            var columnDefs = [];
            
            // Solo aplicar columnDefs si hay headers
            if (headers.length > 0) {
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
