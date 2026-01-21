        <table class="table table-striped table-sm fs-9 mb-0">
          <thead>
            <tr>
              <?php foreach ($array_reports['headers'] as $key_headers => $row_headers) { ?>
                <th class="sort ps-3 text-center" data-sort="name">

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
        <tbody class="list">
          <?php foreach ($array_reports['data'] as $key => $row) { ?>
            <tr>
              <?php foreach ($array_reports['headers'] as $key_headers => $row_headers) { ?>
                <?php
                $tipo_dato = class_tipoDato($row[$row_headers]);
                $text_aling = null;
                switch ($tipo_dato) {
                  case 'decimal':
                  $valor_dato = number_format($row[$row_headers],2);
                    //$text_aling = "text-end";
                  break;
                  case 'entero':
                  $valor_dato = $row[$row_headers];
                    //$text_aling = "text-center";
                  break;

                  default:
                  $valor_dato = $row[$row_headers];
                  $text_aling = null;
                  break;
                }
                if ($row_reports_info['MaskingStatus']) {
                  $valor_dato = maskedData($row_headers,$valor_dato,$row_reports_info['UsersId'],$row_reports_info['ReportsId']);
                }
                ?>
                <td class="align-middle ps-3 <?php echo $text_aling; ?>">
                  <?php echo $valor_dato; ?>
                </td>
              <?php } ?>
            </tr>
          <?php } ?>
        </tbody>
      </table>