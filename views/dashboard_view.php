<?php if ($error): ?>
  <div class="alert alert-subtle-danger d-flex align-items-center mt-5" role="alert">
    <span class="fas fa-times-circle text-danger fs-5 me-3"></span>
    <p class="mb-0 flex-1"><?php echo htmlspecialchars($error_message); ?></p>
  </div>
<?php else: ?>
  <?php require_once('views/dashboard_breadcrumb.php'); ?>
  
  <?php if ($array_parent['data']) { ?>
    <!-- Contenedor arrastrable para widgets -->
    <div id="dashboard-widgets-container" class="row g-3" data-dashboard-id="<?php echo $row_reports_info['ReportsId']; ?>">
    <?php
    $Limit = null;
    foreach ($array_parent['data'] as $key_dashbboard => $row_dashbboard) {

      // Capturar tiempo de ejecución del widget
      $widget_start = microtime(true);
      $array_reports  = class_Recordset($row_dashbboard['ConnectionId'], $row_dashbboard['Query'], $filter_results, $groupby_results, $Limit);
      $widget_end = microtime(true);
      $widget_execution_time = $widget_end - $widget_start;
      
      $array_info     = $array_reports['info'];
      $array_info['execution_time'] = $widget_execution_time;
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
          <h6 class="mb-0 dashboard-widget-title" title="<?php echo htmlspecialchars($row_dashbboard['ReportsId'] . '. ' . $title); ?>">
            <i class="fas fa-grip-vertical text-muted me-2"></i>
            <span class="widget-title-text"><?php echo $row_dashbboard['ReportsId']; ?>. <?php echo htmlspecialchars($title); ?></span>
          </h6>
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
          <div class="widget-footer" id="widget-footer-<?php echo $row_dashbboard['ReportsId']; ?>">
            <div class="d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center" style="gap: 0.75rem;">
                <?php 
                // Calcular tiempo de ejecución
                $execution_time = isset($array_info['execution_time']) ? $array_info['execution_time'] : null;
                ?>
                <?php if ($execution_time): ?>
                  <small class="text-muted widget-execution-time" id="widget-execution-time-<?php echo $row_dashbboard['ReportsId']; ?>">
                    <i class="fas fa-clock me-1"></i>
                    <span><?php echo number_format($execution_time, 3); ?>s</span>
                  </small>
                <?php endif; ?>
                <small class="text-muted">
                  <i class="fas fa-circle me-1" style="font-size: 0.4rem; vertical-align: middle;"></i>
                  Reporte en tiempo real
                </small>
              </div>
              <button class="btn p-0 border-0 bg-transparent widget-refresh-btn" 
                      data-widget-id="<?php echo $row_dashbboard['ReportsId']; ?>"
                      data-dashboard-id="<?php echo $row_reports_info['ReportsId']; ?>"
                      title="Refrescar datos">
                <i class="fas fa-sync-alt text-muted"></i>
              </button>
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
          
          // Actualizar última ejecución y tiempo de ejecución
          if (lastExecution && data.lastExecution) {
            const lastExecText = lastExecution.querySelector('.last-execution-text');
            if (lastExecText) {
              lastExecText.textContent = data.lastExecution;
              lastExecText.setAttribute('data-timestamp', data.lastExecutionTimestamp || Math.floor(Date.now() / 1000));
            }
            updateTimeAgo(widgetId);
          }
          
          // Actualizar tiempo de ejecución
          const execTimeEl = document.getElementById('widget-execution-time-' + widgetId);
          if (execTimeEl && data.executionTime !== undefined) {
            const timeSpan = execTimeEl.querySelector('span');
            if (timeSpan) {
              timeSpan.textContent = parseFloat(data.executionTime).toFixed(3) + 's';
            } else {
              // Si no existe el elemento, crearlo
              execTimeEl.innerHTML = '<i class="fas fa-clock me-1"></i><span>' + parseFloat(data.executionTime).toFixed(3) + 's</span>';
            }
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
    
    // Función para actualizar "hace X tiempo"
    function updateTimeAgo(widgetId) {
      const lastExecText = document.querySelector('#widget-last-execution-' + widgetId + ' .last-execution-text');
      const timeAgoEl = document.getElementById('widget-time-ago-' + widgetId);
      
      if (!lastExecText || !timeAgoEl) return;
      
      const timestamp = parseInt(lastExecText.getAttribute('data-timestamp'));
      if (!timestamp) return;
      
      const now = Math.floor(Date.now() / 1000);
      const diff = now - timestamp;
      
      let timeAgo = '';
      if (diff < 60) {
        timeAgo = 'hace ' + diff + 's';
      } else if (diff < 3600) {
        const minutes = Math.floor(diff / 60);
        timeAgo = 'hace ' + minutes + 'm';
      } else if (diff < 86400) {
        const hours = Math.floor(diff / 3600);
        timeAgo = 'hace ' + hours + 'h';
      } else {
        const days = Math.floor(diff / 86400);
        timeAgo = 'hace ' + days + 'd';
      }
      
      timeAgoEl.textContent = '(' + timeAgo + ')';
    }
    
    // Actualizar "hace X tiempo" para todos los widgets al cargar
    document.querySelectorAll('.widget-last-execution').forEach(function(el) {
      const widgetId = el.id.replace('widget-last-execution-', '');
      updateTimeAgo(widgetId);
    });
    
    // Actualizar cada minuto
    setInterval(function() {
      document.querySelectorAll('.widget-last-execution').forEach(function(el) {
        const widgetId = el.id.replace('widget-last-execution-', '');
        updateTimeAgo(widgetId);
      });
    }, 60000); // Cada minuto
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

  .dashboard-widget-header {
    overflow: hidden;
  }

  .dashboard-widget-title {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
    min-width: 0;
  }

  .widget-title-text {
    display: inline-block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
  }

  .dashboard-widget-header:hover {
    background-color: rgba(0,0,0,0.02);
    border-radius: 4px;
  }

  .dashboard-widget-header .fa-grip-vertical {
    opacity: 0.5;
    transition: opacity 0.2s ease;
    flex-shrink: 0;
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
  
  
  .widget-footer {
    padding-top: 0.5rem !important;
    padding-bottom: 0.25rem !important;
    padding-left: 0.5rem !important;
    padding-right: 0.5rem !important;
    border-top: 1px solid rgba(0,0,0,0.1) !important;
    margin-top: 0.5rem !important;
  }
  
  .widget-footer small {
    font-size: 0.75rem !important;
    color: #6c757d !important;
  }
  
  .widget-footer .widget-refresh-btn {
    padding: 0 !important;
    opacity: 0.6 !important;
    transition: opacity 0.2s ease !important;
  }
  
  .widget-footer .widget-refresh-btn:hover:not(:disabled) {
    opacity: 1 !important;
  }
  
  .widget-footer .widget-refresh-btn i {
    font-size: 0.75rem !important;
  }
  
  .widget-footer-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .widget-footer-actions {
    opacity: 0.4;
    transition: opacity 0.2s ease;
  }
  
  .dashboard-widget:hover .widget-footer-actions {
    opacity: 0.7;
  }
  
  .widget-refresh-btn {
    padding: 0.15rem 0.3rem;
    transition: all 0.2s ease;
  }
  
  .widget-refresh-btn:hover:not(:disabled) {
    opacity: 1 !important;
    transform: rotate(90deg);
  }
  
  .widget-refresh-btn:disabled {
    opacity: 0.3 !important;
    cursor: not-allowed;
  }
  
  .widget-refresh-btn i.fa-spin {
    animation: fa-spin 1s infinite linear;
  }
  
  .widget-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    border-radius: 0.25rem;
  }
  
  .widget-content {
    position: relative;
  }
  </style>
<?php endif; ?>
