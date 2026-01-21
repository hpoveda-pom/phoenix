        <table id="reportsTable" class="table table-striped table-sm fs-9 mb-0">
          <thead>
            <tr>
              <?php foreach ($array_reports['headers'] as $key_headers => $row_headers) { ?>
                <th class="sort ps-3 text-center">

                  <?php
                  $ReportsId        = $row_reports_info['ReportsId'];
                  $header_title     = getFieldAlias($row_headers);
                  $header_tooltips  = getFieldTooltips($row_headers, $ReportsId);
                  if ($header_tooltips['comments']) {
                    ?>
                    <button class="btn p-0 m-1 d-md-block" 
                    data-bs-toggle="tooltip" 
                    data-bs-placement="top" 
                    title='<div class="text-start"><b><?php echo $header_title; ?> [<?php echo $row_headers; ?>]</b>: <?php echo $header_tooltips['comments']; ?></div>' 
                    data-bs-html="true">
                    <?php echo $header_title; ?>
                  </button>
                  <?php
                }else{
                  ?>
                  <button class="btn p-0 m-1 d-none d-md-block" data-bs-html="true" title='<div class="text-start"><b><?php echo $header_title; ?> [<?php echo $row_headers; ?>]</b>: Sin Documentación</div>' data-bs-toggle="tooltip">
                    <?php
                    echo $header_title;
                  }
                  ?>
                </button>
              </th>
            <?php } ?>
          </tr>
        </thead>
        <tbody>
          <!-- Los datos se cargarán dinámicamente mediante DataTables -->
        </tbody>
      </table>