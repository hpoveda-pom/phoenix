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
      
      // Verificar si hay error en la consulta
      $has_error = isset($array_reports['msg_error']) && !empty($array_reports['msg_error']);
      $error_message = $has_error ? $array_reports['msg_error'] : '';
      
      // Verificar si hay datos
      $has_data = isset($array_reports['data']) && is_array($array_reports['data']) && count($array_reports['data']) > 0;
      
      $array_info     = isset($array_reports['info']) ? $array_reports['info'] : ['total_rows' => 0];
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
        <div class="d-flex flex-nowrap justify-content-between align-items-center dashboard-widget-header" style="cursor: move;">
          <h6 class="mb-0 dashboard-widget-title" title="<?php echo htmlspecialchars($row_dashbboard['ReportsId'] . '. ' . $title); ?>">
            <i class="fas fa-grip-vertical text-muted me-2"></i>
            <span class="widget-title-text"><?php echo $row_dashbboard['ReportsId']; ?>. <?php echo htmlspecialchars($title); ?></span>
          </h6>
          <?php if ($is_admin): ?>
          <button class="btn btn-sm btn-link text-muted p-1 widget-edit-btn" 
                  data-widget-id="<?php echo $row_dashbboard['ReportsId']; ?>"
                  data-bs-toggle="modal" 
                  data-bs-target="#widgetEditModal"
                  title="Editar widget"
                  style="opacity: 0.5; transition: opacity 0.2s;"
                  onclick="editWidget(<?php echo htmlspecialchars(json_encode([
                    'ReportsId' => $row_dashbboard['ReportsId'],
                    'Title' => $row_dashbboard['Title'],
                    'Description' => $row_dashbboard['Description'] ?? '',
                    'Query' => $row_dashbboard['Query'] ?? '',
                    'LayoutGridClass' => $row_dashbboard['LayoutGridClass'] ?? 'col',
                    'Order' => $row_dashbboard['Order'] ?? 0,
                    'Status' => $row_dashbboard['Status'] ?? 1,
                    'ConnectionId' => $row_dashbboard['ConnectionId'] ?? 0
                  ])); ?>)">
            <i class="fas fa-edit"></i>
          </button>
          <?php endif; ?>
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
                  <?php }elseif ($has_error) { ?>
                    <!-- Mostrar error en el widget -->
                    <div class="widget-content-wrapper" id="widget-content-wrapper-<?php echo $row_dashbboard['ReportsId']; ?>">
                      <div class="widget-content d-flex align-items-center justify-content-center" id="widget-content-<?php echo $row_dashbboard['ReportsId']; ?>" style="min-height: 150px;">
                        <div class="text-center text-muted">
                          <i class="fas fa-exclamation-circle mb-2" style="font-size: 1.5rem; opacity: 0.5;"></i>
                          <p class="mb-0 small"><?php echo htmlspecialchars($error_message); ?></p>
                        </div>
                      </div>
                    </div>
                  <?php }elseif (!$has_data) { ?>
                    <!-- Mostrar mensaje cuando no hay resultados -->
                    <div class="widget-content-wrapper" id="widget-content-wrapper-<?php echo $row_dashbboard['ReportsId']; ?>">
                      <div class="widget-content d-flex align-items-center justify-content-center" id="widget-content-<?php echo $row_dashbboard['ReportsId']; ?>" style="min-height: 150px;">
                        <div class="text-center text-muted">
                          <i class="fas fa-inbox mb-2" style="font-size: 1.5rem; opacity: 0.5;"></i>
                          <p class="mb-0 small">No hay resultados</p>
                        </div>
                      </div>
                    </div>
                  <?php }else{ ?>
                    <?php 
                    $row_count = count($array_reports['data']);
                    $has_many_rows = $row_count > 5;
                    ?>
                    <div class="widget-content-wrapper <?php echo $has_many_rows ? 'has-many-rows' : ''; ?>" id="widget-content-wrapper-<?php echo $row_dashbboard['ReportsId']; ?>">
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
                      <?php if ($has_many_rows): ?>
                      <div class="widget-fade-overlay" id="widget-fade-<?php echo $row_dashbboard['ReportsId']; ?>"></div>
                      <?php endif; ?>
                    </div>
                    <?php 
                    if ($has_many_rows): 
                    ?>
                    <div class="widget-expand-control text-center mt-2">
                      <button class="btn btn-sm btn-link text-muted widget-expand-btn" 
                              data-widget-id="<?php echo $row_dashbboard['ReportsId']; ?>"
                              style="font-size: 0.75rem; padding: 0.25rem 0.5rem; text-decoration: none;">
                        <i class="fas fa-chevron-down me-1"></i>
                        <span class="expand-text">Ver más (<?php echo $row_count - 5; ?> más)</span>
                        <span class="collapse-text d-none">Ver menos</span>
                      </button>
                    </div>
                    <?php endif; ?>
                  <?php } ?>
                </div>
              <?php }else{ ?>
                No hay resultados
              <?php } ?>
            </div>
          </div>

          <?php
          // Debugger por widget (solo admin + modo debug activo)
          $widget_debug_enabled = $is_admin && isset($_SESSION['debug_mode']) && $_SESSION['debug_mode'];
          if ($widget_debug_enabled):
            $debug_query = $array_reports['debug_query'] ?? null;
            $debug_query_with_where = $array_reports['debug_query_with_where'] ?? null;
            $debug_filters = $array_reports['debug_filters'] ?? [];
            $debug_total_rows = $array_info['total_rows'] ?? (isset($array_reports['info']['total_rows']) ? $array_reports['info']['total_rows'] : null);
          ?>
          <div class="px-2 pb-2">
            <button class="btn btn-xs btn-outline-secondary text-muted widget-debug-toggle"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#widgetDebug-<?php echo $row_dashbboard['ReportsId']; ?>"
                    aria-expanded="false"
                    aria-controls="widgetDebug-<?php echo $row_dashbboard['ReportsId']; ?>"
                    style="font-size: 0.7rem; padding: 0.15rem 0.4rem;">
              <i class="fas fa-bug me-1"></i>Debug
            </button>
            <div class="collapse mt-2 small" id="widgetDebug-<?php echo $row_dashbboard['ReportsId']; ?>">
              <div class="border rounded p-2 bg-light-subtle">
                <div class="mb-1">
                  <strong>ID:</strong> <?php echo $row_dashbboard['ReportsId']; ?>,
                  <strong>Filas:</strong> <?php echo $debug_total_rows !== null ? intval($debug_total_rows) : 'N/D'; ?>,
                  <strong>Error:</strong> <?php echo $has_error ? 'Sí' : 'No'; ?>
                </div>
                <?php if ($has_error && !empty($error_message)): ?>
                  <div class="text-danger mb-1" style="font-size: 0.75rem;">
                    <?php echo htmlspecialchars($error_message); ?>
                  </div>
                <?php endif; ?>
                <?php if ($debug_query_with_where): ?>
                  <details class="mb-1">
                    <summary class="cursor-pointer">Subquery con filtros</summary>
                    <pre class="mt-1 mb-0 p-2 bg-body border rounded" style="max-height: 200px; overflow-y: auto; font-size: 0.7rem; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($debug_query_with_where); ?></pre>
                  </details>
                <?php endif; ?>
                <?php if ($debug_query): ?>
                  <details class="mb-1">
                    <summary class="cursor-pointer">Query final ejecutada</summary>
                    <pre class="mt-1 mb-0 p-2 bg-body border rounded" style="max-height: 200px; overflow-y: auto; font-size: 0.7rem; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($debug_query); ?></pre>
                  </details>
                <?php endif; ?>
                <?php if (!empty($debug_filters) && is_array($debug_filters)): ?>
                  <details class="mb-0">
                    <summary class="cursor-pointer">Mensajes de debug (<?php echo count($debug_filters); ?>)</summary>
                    <ul class="mt-1 mb-0 ps-3" style="font-size: 0.7rem; max-height: 150px; overflow-y: auto;">
                      <?php foreach ($debug_filters as $debug_msg): ?>
                        <li><?php echo htmlspecialchars($debug_msg); ?></li>
                      <?php endforeach; ?>
                    </ul>
                  </details>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endif; ?>

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

    // Manejar expandir/colapsar widgets
    document.querySelectorAll('.widget-expand-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const widgetId = this.getAttribute('data-widget-id');
        const wrapper = document.getElementById('widget-content-wrapper-' + widgetId);
        const expandText = this.querySelector('.expand-text');
        const collapseText = this.querySelector('.collapse-text');
        
        if (wrapper.classList.contains('expanded')) {
          wrapper.classList.remove('expanded');
          expandText.classList.remove('d-none');
          collapseText.classList.add('d-none');
        } else {
          wrapper.classList.add('expanded');
          expandText.classList.add('d-none');
          collapseText.classList.remove('d-none');
        }
      });
     });
   });

   // Función para editar widget
   function editWidget(widgetData) {
     document.getElementById('edit_widget_id').value = widgetData.ReportsId;
     document.getElementById('edit_widget_title').value = widgetData.Title || '';
     document.getElementById('edit_widget_description').value = widgetData.Description || '';
     document.getElementById('edit_widget_query').value = widgetData.Query || '';
     document.getElementById('edit_widget_layout').value = widgetData.LayoutGridClass || 'col';
     document.getElementById('edit_widget_order').value = widgetData.Order || 0;
     document.getElementById('edit_widget_status').value = widgetData.Status || 1;
     document.getElementById('edit_widget_connection').value = widgetData.ConnectionId || 0;
   }

   // Manejar envío del formulario de edición
   document.getElementById('widgetEditForm').addEventListener('submit', function(e) {
     e.preventDefault();
     
     const formData = new FormData(this);
     formData.append('action', 'update_widget');
     
     const submitBtn = this.querySelector('button[type="submit"]');
     const originalText = submitBtn.innerHTML;
     submitBtn.disabled = true;
     submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
     
     fetch('controllers/widget_update.php', {
       method: 'POST',
       body: formData
     })
     .then(response => response.json())
     .then(data => {
       if (data.success) {
         // Cerrar modal
         const modal = bootstrap.Modal.getInstance(document.getElementById('widgetEditModal'));
         modal.hide();
         
         // Recargar la página para ver los cambios
         location.reload();
       } else {
         alert('Error: ' + (data.message || 'No se pudo actualizar el widget'));
         submitBtn.disabled = false;
         submitBtn.innerHTML = originalText;
       }
     })
     .catch(error => {
       console.error('Error:', error);
       alert('Error al guardar los cambios');
       submitBtn.disabled = false;
       submitBtn.innerHTML = originalText;
     });
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
    flex-wrap: nowrap;
    gap: 0.25rem;
  }

  .dashboard-widget-title {
    display: flex;
    align-items: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
    min-width: 0;
  }

  .widget-title-text {
    display: inline-block;
    min-width: 0;
    flex: 1;
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

  /* Widget height control */
  .widget-content-wrapper {
    position: relative;
    overflow-x: hidden;
    transition: max-height 0.3s ease;
    display: flex;
    flex-direction: column;
  }

  .widget-content-wrapper table tbody tr {
    height: auto;
  }

  /* Solo aplicar altura máxima y scroll cuando hay más de 5 filas */
  /* Aproximadamente 5 filas completas: header (40px) + 5 filas (30px cada una) + espacio extra = ~220px */
  /* El difuminado empieza después de la fila 5, no sobre ella */
  .widget-content-wrapper.has-many-rows:not(.expanded) {
    max-height: 220px;
    overflow-y: auto;
  }

  .widget-content-wrapper.expanded {
    max-height: none;
    overflow: visible;
  }

  .widget-content {
    position: relative;
  }

  .widget-fade-overlay {
    position: sticky;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
    height: 60px;
    pointer-events: none;
    transition: opacity 0.3s ease;
    z-index: 10;
    margin-top: -60px;
    flex-shrink: 0;
    /* Light mode - gradiente blanco */
    background: linear-gradient(to bottom, 
      transparent, 
      rgba(255, 255, 255, 0.7), 
      rgba(255, 255, 255, 0.95), 
      rgba(255, 255, 255, 1)
    );
  }

  /* Dark mode - gradiente oscuro usando #141824 */
  [data-bs-theme="dark"] .widget-fade-overlay,
  [data-theme="dark"] .widget-fade-overlay,
  .dark-mode .widget-fade-overlay,
  body.dark .widget-fade-overlay,
  .dark .widget-fade-overlay {
    background: linear-gradient(to bottom, 
      transparent, 
      rgba(20, 24, 36, 0.7), 
      rgba(20, 24, 36, 0.95), 
      rgba(20, 24, 36, 1)
    );
  }

  /* Si el card tiene fondo oscuro específico o usa la variable CSS */
  .card.bg-dark .widget-fade-overlay,
  .card.bg-secondary .widget-fade-overlay,
  .card[style*="background-color: #141824"] .widget-fade-overlay,
  .card[style*="background-color: rgb(20, 24, 36)"] .widget-fade-overlay {
    background: linear-gradient(to bottom, 
      transparent, 
      rgba(20, 24, 36, 0.7), 
      rgba(20, 24, 36, 0.95), 
      rgba(20, 24, 36, 1)
    );
  }

  /* Usar variable CSS si está disponible */
  .card .widget-fade-overlay {
    background: linear-gradient(to bottom, 
      transparent, 
      rgba(var(--phoenix-card-bg-rgb, 255, 255, 255), 0.7), 
      rgba(var(--phoenix-card-bg-rgb, 255, 255, 255), 0.95), 
      rgba(var(--phoenix-card-bg-rgb, 255, 255, 255), 1)
    );
  }

  /* Dark mode con variable CSS */
  [data-bs-theme="dark"] .card .widget-fade-overlay,
  .dark-mode .card .widget-fade-overlay {
    background: linear-gradient(to bottom, 
      transparent, 
      rgba(20, 24, 36, 0.7), 
      rgba(20, 24, 36, 0.95), 
      rgba(20, 24, 36, 1)
    );
  }

  .widget-content-wrapper.expanded .widget-fade-overlay {
    opacity: 0;
    display: none;
  }

  .widget-expand-control {
    margin-top: 0.5rem;
    padding: 0.25rem 0;
  }

  .widget-expand-btn {
    transition: all 0.2s ease;
    border: none;
    background: none;
  }

  .widget-expand-btn:hover {
    text-decoration: underline !important;
  }

  .widget-expand-btn i {
    transition: transform 0.3s ease;
  }

  .widget-content-wrapper.expanded ~ .widget-expand-control .widget-expand-btn i {
    transform: rotate(180deg);
  }

  /* Ensure all widgets have consistent minimum height */
  .dashboard-widget .card {
    min-height: 300px;
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  /* El contenido principal debe ocupar el espacio disponible */
  .dashboard-widget .card .tab-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
  }

  .dashboard-widget .card .tab-content #tableParents {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
  }

  .dashboard-widget .card .table-responsive {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
  }

  .widget-content-wrapper {
    flex: 1;
    min-height: 0;
  }

  /* El footer siempre queda al final */
  .widget-footer {
    margin-top: auto;
  }

  /* Custom scrollbar for widget content */
  .widget-content-wrapper::-webkit-scrollbar {
    width: 6px;
  }

  .widget-content-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
  }

  .widget-content-wrapper::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
  }

  .widget-content-wrapper::-webkit-scrollbar-thumb:hover {
    background: #555;
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

  .widget-edit-btn {
    opacity: 0.3;
    transition: opacity 0.2s ease;
  }

  .dashboard-widget-header:hover .widget-edit-btn {
    opacity: 0.7;
  }

  .widget-edit-btn:hover {
    opacity: 1 !important;
  }
  </style>

  <!-- Modal para editar widget -->
  <?php if (isset($is_admin) && $is_admin): ?>
  <div class="modal fade" id="widgetEditModal" tabindex="-1" aria-labelledby="widgetEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="widgetEditModalLabel">
            <i class="fas fa-edit me-2"></i>Editar Widget
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="widgetEditForm">
          <div class="modal-body">
            <input type="hidden" id="edit_widget_id" name="widget_id" value="">
            
            <div class="mb-3">
              <label for="edit_widget_title" class="form-label">Título <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit_widget_title" name="title" required>
            </div>

            <div class="mb-3">
              <label for="edit_widget_description" class="form-label">Descripción</label>
              <textarea class="form-control" id="edit_widget_description" name="description" rows="2"></textarea>
            </div>

            <div class="mb-3">
              <label for="edit_widget_connection" class="form-label">Conexión</label>
              <select class="form-select" id="edit_widget_connection" name="connection_id">
                <option value="0">Seleccionar conexión...</option>
                <?php
                // Obtener conexiones disponibles
                $query_connections = "SELECT ConnectionId, Title FROM connections WHERE Status = 1 ORDER BY Title ASC";
                $connections_result = class_Recordset(1, $query_connections, null, null, null);
                $connections = $connections_result['data'] ?? [];
                foreach ($connections as $conn):
                ?>
                <option value="<?php echo $conn['ConnectionId']; ?>"><?php echo htmlspecialchars($conn['Title']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="edit_widget_query" class="form-label">SQL Query <span class="text-danger">*</span></label>
              <textarea class="form-control font-monospace" id="edit_widget_query" name="query" rows="6" required style="font-size: 0.875rem;"></textarea>
              <small class="form-text text-muted">Consulta SQL que se ejecutará para obtener los datos del widget.</small>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="edit_widget_layout" class="form-label">Diseño (Layout)</label>
                <select class="form-select" id="edit_widget_layout" name="layout_grid_class">
                  <option value="col">col (Automático)</option>
                  <option value="col-12">col-12 (Ancho completo)</option>
                  <option value="col-6">col-6 (Mitad)</option>
                  <option value="col-4">col-4 (Un tercio)</option>
                  <option value="col-3">col-3 (Un cuarto)</option>
                  <option value="col-md-6">col-md-6 (Mitad en desktop)</option>
                  <option value="col-md-4">col-md-4 (Un tercio en desktop)</option>
                  <option value="col-md-3">col-md-3 (Un cuarto en desktop)</option>
                </select>
              </div>

              <div class="col-md-3 mb-3">
                <label for="edit_widget_order" class="form-label">Orden</label>
                <input type="number" class="form-control" id="edit_widget_order" name="order" value="0" min="0">
              </div>

              <div class="col-md-3 mb-3">
                <label for="edit_widget_status" class="form-label">Estado</label>
                <select class="form-select" id="edit_widget_status" name="status">
                  <option value="1">Activo</option>
                  <option value="0">Inactivo</option>
                  <option value="2">Mantenimiento</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save me-2"></i>Guardar Cambios
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
<?php endif; ?>
