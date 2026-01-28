<?php if ($error): ?>
  <div class="alert alert-subtle-danger d-flex align-items-center mt-5" role="alert">
    <span class="fas fa-times-circle text-danger fs-5 me-3"></span>
    <p class="mb-0 flex-1"><?php echo htmlspecialchars($error_message); ?></p>
  </div>
<?php else: ?>
  <?php require_once('views/dashboard_breadcrumb.php'); ?>
  
  <?php if ($array_parent['data']) { ?>
    <!-- Contenedor arrastrable para widgets -->
    <div id="dashboard-widgets-container" class="row" data-dashboard-id="<?php echo $row_reports_info['ReportsId']; ?>">
    <?php
    $Limit = null;
    foreach ($array_parent['data'] as $key_dashbboard => $row_dashbboard) {

      $array_reports  = class_Recordset($row_dashbboard['ConnectionId'], $row_dashbboard['Query'], $filter_results, $groupby_results, $Limit);
      $array_info     = $array_reports['info'];
      $row_sync       = class_getLastExecution($row_dashbboard['LastExecution']);

      $LayoutGridClass = "col";
      if ($row_dashbboard['LayoutGridClass']) {
        $LayoutGridClass = $row_dashbboard['LayoutGridClass'];
      }

      // Variables dinámicas en títulos
      $mes_num = date('n');
      $anio = date('Y');

      if ($mes_num == 1) {
          $mes_anterior_num = 12;
          $anio_anterior = $anio - 1;
      } else {
          $mes_anterior_num = $mes_num - 1;
          $anio_anterior = $anio;
      }

      $meses = [
          1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
          5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
          9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
      ];

      $mes_anterior = $meses[$mes_anterior_num];

      // Función de reemplazo de variables en títulos
      $title = preg_replace_callback('/\{(\w+)\}/', function ($matches) {
          $varName = $matches[1];
          if (isset($GLOBALS[$varName])) {
              return $GLOBALS[$varName];
          }
          return '';
      }, $row_dashbboard['Title']);

      ?>
      <div class="<?php echo $LayoutGridClass; ?> dashboard-widget" data-widget-id="<?php echo $row_dashbboard['ReportsId']; ?>" id="widget-<?php echo $row_dashbboard['ReportsId']; ?>">
        <div class="d-flex flex-wrap justify-content-between align-items-center dashboard-widget-header" style="cursor: move;">
          <h6 class="mb-0">
            <i class="fas fa-grip-vertical text-muted me-2"></i>
            <?php echo $row_dashbboard['ReportsId']; ?>. <?php echo $title; ?>
          </h6>
          <button class="btn btn-sm btn-outline-secondary widget-refresh-btn" 
                  data-widget-id="<?php echo $row_dashbboard['ReportsId']; ?>"
                  data-dashboard-id="<?php echo $row_reports_info['ReportsId']; ?>"
                  title="Refrescar datos">
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>
        <div class="card p-2 mt-3 shadow-sm">
          <?php if ($array_parent['data']) { ?>
            <div class="tab-content" id="TabParentsContent-<?php echo $row_dashbboard['ReportsId']; ?>">
              <div id="tableParents">
                <div class="table-responsive">
                  <?php if ($row_dashbboard['SyncStatus']==3) { ?>
                    <div class="h-100">
                      <ul class="list-group">
                        <li class="list-group-item list-group-item-default py-3">
                          <div class="spinner-grow spinner-grow-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                          </div>
                          Sincronizando, vuelva en unos minutos...
                        </li>
                      </ul>
                    </div>
                  <?php }else{ ?>

                    <div class="widget-content" id="widget-content-<?php echo $row_dashbboard['ReportsId']; ?>">
                      <table class="table table-sm fs-9">
                        <thead>
                          <tr>
                            <?php foreach ($array_reports['headers'] as $key_headers => $row_headers) { ?>
                              <?php $field_format = fieldFormat($row_headers); ?>
                              <?php $header_title = getFieldAlias($row_headers); ?>
                              <th class="<?php echo $field_format['class']; ?>"><?php echo $header_title; ?></th>
                            <?php } ?>
                          </tr>
                        </thead>
                        <tbody>
                          <?php $total_values = []; ?>
                          <?php foreach ($array_reports['data'] as $key => $row) { ?>
                            <tr>
                              <?php foreach ($array_reports['headers'] as $key_headers => $row_headers) { ?>
                                <?php
                                if (!isset($total_values[$row_headers])) {
                                  $total_values[$row_headers] = 0;
                                }
                                $valor_dato = $row[$row_headers];
                                $field_format = fieldFormat($row_headers, $valor_dato);
                                if ($field_format['total']) {
                                  $total_values[$row_headers] += $valor_dato;
                                }
                                $valor_dato = $field_format['value'];
                                $valor_dato = maskedData($row_headers, $valor_dato, $row_dashbboard['UsersId'], $row_dashbboard['ReportsId']);
                                ?>
                                <td class="align-middle ps-3 <?php echo $field_format['class']; ?>" style="text-align: center;">
                                  <?php echo $valor_dato; ?>
                                </td>
                              <?php } ?>
                            </tr>
                          <?php } ?>
                        </tbody>
                        <?php if($row_dashbboard['TotalAxisX']){ ?>
                        <tfoot>
                          <tr>
                            <?php foreach ($array_reports['headers'] as $key_headers => $row_headers) { ?>
                            <?php $field_format = fieldFormat($row_headers); ?>
                            <?php if($field_format['total']){ ?>
                            <?php $field_format = fieldFormat($row_headers,$total_values[$row_headers]); ?>
                            <td class="<?php echo $field_format['class']; ?>">
                              <strong><?php echo $field_format['value']; ?></strong>
                            </td>
                            <?php }else{ ?>
                            <td></td>
                            <?php } ?>
                            <?php } ?>
                          </tr>
                        </tfoot>
                        <?php } ?>
                      </table>
                    </div>
                  <?php } ?>
                </div>
              <?php }else{ ?>
                No hay resultados
              <?php } ?>
            </div>
          </div>
          <div class="row align-items-center">
            <div class="col-auto">
              <?php if ($row_dashbboard['SyncStatus']==2) { ?>
                <button class="btn p-0 m-1 d-md-block" 
                data-bs-toggle="tooltip" 
                data-bs-placement="top" 
                title="<b>Sincronizando: </b> Los datos del reporte están siendo actualizados. Los nuevos resultados estarán disponibles en breve. Los datos mostrados actualmente son confiables."
                data-bs-html="true">
                <i class="fas fa-sync fa-spin text-secondary-light fs-7"></i>
                </button>
              <?php } elseif ($row_dashbboard['Status']==2) { ?>
                <button class="btn p-0 m-1 d-md-block" 
                data-bs-toggle="tooltip" 
                data-bs-placement="top" 
                title="<b>Adevertencia: </b> Este reporte está actualmente en mantenimiento, por lo que los datos mostrados podrían no ser precisos. Por favor, espere a que el mantenimiento finalice antes de tomar decisiones basadas en esta información." 
                data-bs-html="true">
                <i class="fas fa-exclamation-triangle text-warning fs-7"></i>
                </button>
              <?php } elseif ($row_dashbboard['Description']) { ?>
                <button class="btn p-0 m-1 d-md-block" 
                data-bs-toggle="tooltip" 
                data-bs-placement="top" 
                title="<b>Propósito</b>: <?php echo $row_dashbboard['Description']; ?>" 
                data-bs-html="true">
                <i class="fas fa-info-circle text-secondary-light fs-7"></i>
                </button>
              <?php } ?>
            </div>
            <div class="col text-end">
              <small class="widget-last-execution" id="widget-last-execution-<?php echo $row_dashbboard['ReportsId']; ?>"><?php echo $row_sync['LastExecution']; ?></small>
            </div>
          </div>
        </div>
      </div>
    <?php } ?>
    </div>
  <?php } else { ?>
    <div class="alert alert-info mt-3">
      <i class="fas fa-info-circle me-2"></i>
      Este dashboard no tiene widgets configurados aún.
    </div>
  <?php } ?>

  <!-- SortableJS para drag and drop -->
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('dashboard-widgets-container');
    if (!container) return;
    
    const dashboardId = container.getAttribute('data-dashboard-id');
    
    // Inicializar Sortable
    const sortable = new Sortable(container, {
      animation: 150,
      handle: '.dashboard-widget-header',
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      dragClass: 'sortable-drag',
      onEnd: function(evt) {
        const widgets = [];
        container.querySelectorAll('.dashboard-widget').forEach(function(widget) {
          widgets.push(widget.getAttribute('data-widget-id'));
        });
        
        fetch('controllers/dashboard_reorder.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            dashboard_id: dashboardId,
            widgets: widgets
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // No recargar la página, solo mostrar mensaje sutil
            console.log('Orden actualizado correctamente');
          } else {
            console.error('Error al actualizar el orden:', data.message);
            // Solo recargar si hay error crítico
            if (data.message && data.message.includes('crítico')) {
              location.reload();
            }
          }
        })
        .catch(error => {
          console.error('Error:', error);
        });
      }
    });
    
    // Manejar botones de refresh
    document.querySelectorAll('.widget-refresh-btn').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.stopPropagation(); // Evitar que active el drag
        const widgetId = this.getAttribute('data-widget-id');
        const dashboardId = this.getAttribute('data-dashboard-id');
        refreshWidget(widgetId, dashboardId, this);
      });
    });
    
    function refreshWidget(widgetId, dashboardId, button) {
      const widgetContent = document.getElementById('widget-content-' + widgetId);
      const lastExecution = document.getElementById('widget-last-execution-' + widgetId);
      const icon = button.querySelector('i');
      
      // Deshabilitar botón y mostrar animación
      button.disabled = true;
      icon.classList.add('fa-spin');
      
      // Agregar overlay de carga
      const overlay = document.createElement('div');
      overlay.className = 'widget-loading-overlay';
      overlay.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>';
      widgetContent.style.position = 'relative';
      widgetContent.appendChild(overlay);
      
      fetch('controllers/widget_refresh.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          widget_id: widgetId,
          dashboard_id: dashboardId
        })
      })
      .then(response => response.json())
      .then(data => {
        // Remover overlay
        const overlay = widgetContent.querySelector('.widget-loading-overlay');
        if (overlay) overlay.remove();
        
        // Restaurar botón
        button.disabled = false;
        icon.classList.remove('fa-spin');
        
        if (data.success) {
          // Actualizar contenido de la tabla
          updateWidgetContent(widgetId, data.data);
          
          // Actualizar última ejecución
          if (lastExecution && data.lastExecution) {
            lastExecution.textContent = data.lastExecution;
          }
        } else {
          // Mostrar error
          if (data.message) {
            alert('Error: ' + data.message);
          }
          // Si hay cooldown, volver a intentar después
          if (data.cooldown) {
            setTimeout(function() {
              button.disabled = false;
            }, data.cooldown * 1000);
          }
        }
      })
      .catch(error => {
        // Remover overlay
        const overlay = widgetContent.querySelector('.widget-loading-overlay');
        if (overlay) overlay.remove();
        
        // Restaurar botón
        button.disabled = false;
        icon.classList.remove('fa-spin');
        
        console.error('Error:', error);
        alert('Error al refrescar el widget. Por favor, intenta nuevamente.');
      });
    }
    
    function updateWidgetContent(widgetId, data) {
      const widgetContent = document.getElementById('widget-content-' + widgetId);
      const table = widgetContent.querySelector('table');
      
      if (!table || !data) return;
      
      // Actualizar tbody
      const tbody = table.querySelector('tbody');
      if (tbody && data.rows) {
        tbody.innerHTML = '';
        data.rows.forEach(function(row) {
          const tr = document.createElement('tr');
          row.forEach(function(cell) {
            const td = document.createElement('td');
            td.className = 'align-middle ps-3' + (cell.format.class ? ' ' + cell.format.class : '');
            td.style.textAlign = 'center';
            td.textContent = cell.value;
            tr.appendChild(td);
          });
          tbody.appendChild(tr);
        });
      }
      
      // Actualizar tfoot si existe
      const tfoot = table.querySelector('tfoot');
      if (tfoot && data.totals && data.totals.length > 0) {
        tfoot.innerHTML = '';
        const tr = document.createElement('tr');
        data.totals.forEach(function(total) {
          const td = document.createElement('td');
          if (total) {
            td.className = total.format.class || '';
            const strong = document.createElement('strong');
            strong.textContent = total.value;
            td.appendChild(strong);
          }
          tr.appendChild(td);
        });
        tfoot.appendChild(tr);
      }
    }
  });
  </script>

  <style>
  .sortable-ghost {
    opacity: 0.4;
    background: #f0f0f0;
  }

  .sortable-chosen {
    cursor: grabbing;
  }

  .sortable-drag {
    opacity: 0.8;
  }

  .dashboard-widget {
    transition: transform 0.2s ease;
  }

  .dashboard-widget-header:hover {
    background-color: rgba(0,0,0,0.02);
    border-radius: 4px;
  }

  .dashboard-widget-header .fa-grip-vertical {
    opacity: 0.5;
    transition: opacity 0.2s ease;
  }

  .dashboard-widget-header:hover .fa-grip-vertical {
    opacity: 1;
  }

  .sortable-ghost .dashboard-widget {
    opacity: 0.4;
  }

  .sortable-chosen .dashboard-widget-header {
    cursor: grabbing !important;
  }
  </style>
<?php endif; ?>
