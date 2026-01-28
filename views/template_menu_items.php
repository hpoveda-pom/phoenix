<?php function views_getMenuItems($array_reports, $row_category){ ?>
<?php 
            $filtered_reports = array_filter($array_reports, function($report) use ($row_category) {
              return $report['CategoryId'] == $row_category['CategoryId'];
            });
            ?>
            <?php foreach ($filtered_reports as $key_reports => $row_reports) { ?>
              <!-- REPORTS ITEM START  -->
              <?php
              $link_active = null;
              if (isset($row_reports['ReportsId']) && isset($row_reports_info['ReportsId']) && $row_reports['ReportsId'] == $row_reports_info['ReportsId']) {
                $link_active = "active";
              }
              ?>
              <li class="nav-item">
                <?php
                switch ($row_reports['TypeId']) {
                  case 1:
                  $script_name = 'reports.php';
                  break;
                  case 2:
                  $script_name = 'dashboards.php';
                  break;
                  case 3:
                  $script_name = 'tools.php';
                  break;
                  default:
                  $script_name = 'reports.php';
                  break;
                }
                ?>
                  <?php
                  $lastUpdated = null;
                  $currentTimestamp = time();
                  $CreatedDate = strtotime($row_reports['CreatedDate']);

                  if ($row_reports['LastUpdated']) {
                    $lastUpdated = strtotime($row_reports['LastUpdated']);
                  }
                  $status_new = false;
                  if (($currentTimestamp - $CreatedDate) <= 259200) {
                      $status_new = true;
                  }

                  $status_modified = false;
                  if (($currentTimestamp - $lastUpdated) <= 900) {
                      $status_modified = true;
                  }
                  ?>
                <a class="nav-link <?php echo $link_active ?>" href="<?php echo $script_name; ?>?Id=<?php echo $row_reports['ReportsId']; ?>">
                  <div class="d-flex align-items-center position-relative">
                    <!-- Botón de advertencia con tooltip -->
                    <?php if ($status_new) { ?>
                    <button class="btn p-0 d-md-block position-absolute" 
                      style="left: -25px;" 
                      data-bs-toggle="tooltip" 
                      data-bs-placement="top" 
                      title="<b>Nuevo reporte: </b> Este reporte se ha creado recientemente." 
                      data-bs-html="true">
                      <span class="badge text-bg-primary">new</span>
                    </button>
                  <?php }elseif($status_modified){ ?>
                    <button class="btn p-0 d-md-block position-absolute" 
                      style="left: -25px;" 
                      data-bs-toggle="tooltip" 
                      data-bs-placement="top" 
                      title="<b>Adevertencia: </b> Este reporte está actualmente en mantenimiento, por lo que los datos mostrados podrían no ser precisos. Por favor, espere a que el mantenimiento finalice antes de tomar decisiones basadas en esta información." 
                      data-bs-html="true">
                      <i class="fas fa-exclamation-triangle text-warning fs-7"></i>
                    </button>
                <?php } ?>
                    <!-- Texto del enlace -->
                    <span class="nav-link-text">
                      <?php echo $row_reports['ReportsId']; ?>. <?php echo $row_reports['Title']; ?>
                    </span>
                  </div>
                </a>
              </li>
            <?php } ?>
            <!-- REPORTS ITEM END  -->

<?php } ?>