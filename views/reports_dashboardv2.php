<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>


<?php if ($array_parent['data']) { ?>
  <div class="row">
    <?php foreach ($array_parent['data'] as $key_dashbboard => $row_dashbboard) {
      $array_reports = class_Recordset($row_dashbboard['ConnectionId'], $row_dashbboard['Query'], $filter_results, $groupby_results, $Limit);
      $array_info = $array_reports['info'];
      $row_sync = class_getLastExecution($row_dashbboard['LastExecution']);
      ?>
      <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 col-12">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
          <h6 class="mb-0">
            <?php echo $row_dashbboard['ReportsId']; ?>. <?php echo $row_dashbboard['Title']; ?>
          </h6>
        </div>
        <div class="card p-2 mt-3 shadow-sm">
          <?php if ($array_parent['data']) { ?>
            <div class="tab-content" id="TabParentsContent-<?php echo $row_dashbboard['ReportsId']; ?>">
              <div id="tableParents">
                <div class="table-responsive scrollbar">
                  <?php if ($row_dashbboard['SyncStatus'] == 2) { ?>
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
                  <?php } else { ?>
                    <!-- Tabla con un ID único por cada Report -->
                    <table class="table table-sm fs-9" id="table-<?php echo $row_dashbboard['ReportsId']; ?>" data-report-id="<?php echo $row_dashbboard['ReportsId']; ?>">
                      <thead>
                        <tr>
                          <?php foreach ($array_reports['headers'] as $key_headers => $row_headers) { ?>
                            <th><?php echo $row_headers; ?></th>
                          <?php } ?>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($array_reports['data'] as $key => $row) { ?>
                          <tr>
                            <?php foreach ($array_reports['headers'] as $key_headers => $row_headers) { ?>
                              <td class="align-middle ps-3" style="text-align: center;">
                                <?php
                                $valor_dato = $row[$row_headers];
                                $valor_dato = maskedData($row_headers, $valor_dato, $row_dashbboard['UsersId'], $row_dashbboard['ReportsId']);
                                echo $valor_dato;
                                ?>
                              </td>
                            <?php } ?>
                          </tr>
                        <?php } ?>
                      </tbody>
                    </table>
                  <?php } ?>
                </div>
                <div class="row align-items-center py-1">
                  <div class="pagination d-none"></div>
                  <div class="col d-flex fs-9">
                    <p><small><?php echo $row_sync['LastExecution']; ?></small></p>
                  </div>
<div class="col-auto d-flex">
  <button class="btn btn-link px-1 me-1" type="button" title="Previous" data-list-pagination="prev" disabled>Previous</button>
  <button class="btn btn-link px-1 ms-1" type="button" title="Next" data-list-pagination="next">Next</button>
</div>


                </div>
              </div>
            </div>
          <?php } else { ?>
            No hay resultados
          <?php } ?>
        </div>
      </div>
    <?php } ?>
  </div>
<?php } ?>


<script>
$(document).ready(function() {
  // Inicializa todas las tablas después de que el DOM esté listo
  $('table[data-report-id]').each(function() {
    var tableId = $(this).attr('id');
    
    // Inicializar DataTables para cada tabla con un ID único
    var table = $('#' + tableId).DataTable({
      "order": [[0, 'asc']],    // Ordena por la primera columna (puedes cambiar el índice)
      "paging": true,           // Habilitar paginación
      "pageLength": 5,          // Limitar el número de filas por página a 5
      "lengthChange": false,    // Elimina el selector "Show entries"
      "info": false,            // Deshabilita el texto de "Showing X to Y of Z entries"
      "searching": false,       // Deshabilitar búsqueda
      "dom": 'lrtp',            // Personaliza la estructura de DataTables (sin el selector, solo la tabla y los botones de paginación)
    });

    // Asignar los botones de "Previous" y "Next"
    var prevButton = $(this).closest('.card').find('.pagination button[data-list-pagination="prev"]');
    var nextButton = $(this).closest('.card').find('.pagination button[data-list-pagination="next"]');

    // Controlar los botones de "Previous" y "Next" manualmente
    prevButton.on('click', function() {
      table.page('previous').draw('page'); // Navegar hacia la página anterior
    });

    nextButton.on('click', function() {
      table.page('next').draw('page'); // Navegar hacia la siguiente página
    });

    // Desactivar los botones si no hay más páginas
    table.on('draw', function() {
      var info = table.page.info();  // Obtener la información de la página actual
      prevButton.prop('disabled', info.page() === 0);  // Desactiva el botón "Previous" si estamos en la primera página
      nextButton.prop('disabled', info.page() === info.pages() - 1);  // Desactiva el botón "Next" si estamos en la última página
    });

    // Actualizar la paginación al cargar la tabla
    var info = table.page.info();
    prevButton.prop('disabled', info.page() === 0);  // Desactivar el botón de "Previous" si estamos en la primera página
    nextButton.prop('disabled', info.page() === info.pages() - 1);  // Desactivar el botón de "Next" si estamos en la última página
  });
});
</script>





