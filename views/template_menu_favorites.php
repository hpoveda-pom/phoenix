<?php
//Category Recordset
$array_category = [];
$query_category = "
SELECT a.*
FROM category a
INNER JOIN reports b ON b.CategoryId = a.CategoryId
INNER JOIN favorites c ON c.ReportsId = b.ReportsId
WHERE a.IdType = 1 AND a.Status = 1 AND b.Status = 1 AND c.UsersId = $UsersId
AND a.ParentId IS NULL
GROUP BY a.CategoryId
ORDER BY c.Order ASC
";
if ($results_category = $conn_phoenix->query($query_category)) {
  if ($results_category->num_rows > 0) {
    while ($row_category = $results_category->fetch_assoc()) {
      $array_category[] = $row_category;
    }
  }
}

//Reports Menu Recordset
$array_reports = [];
$query_reports = "
SELECT a.* FROM reports a 
INNER JOIN category b ON b.CategoryId = a.CategoryId AND a.ParentId IS NULL
INNER JOIN favorites c ON c.ReportsId = a.ReportsId
WHERE a.Status = 1 AND b.IdType = 1 AND b.Status = 1 AND c.UsersId = ".$UsersId." 
ORDER BY c.Order ASC";
if ($results_reports = $conn_phoenix->query($query_reports)) {
  if ($results_reports->num_rows > 0) {
    while ($row_reports = $results_reports->fetch_assoc()) {
      $array_reports[] = $row_reports;
    }
  }
}

//Reports info Recordset
$row_reports_info = array();
if ($Id) {
  $query_reports_info = "SELECT a.* FROM reports a WHERE a.ReportsId = ".$Id;
  $reports_info = class_Recordset(1, $query_reports_info, null, null, 1);
  $row_reports_info = $reports_info['data'][0];
}
?>
<?php if(count($array_category)){ ?>
<li class="nav-item">
          <!-- label-->
          <p class="navbar-vertical-label">Favoritos</p>
          <hr class="navbar-vertical-line">
          <?php if(count($array_category)){ ?>
          <?php foreach ($array_category as $key_category => $row_category) {
            // Generar un ID único para cada categoría
            $categoryId = "nv-ECharts-" . $row_category['CategoryId'];
            ?>
            <?php
            $collapse_show = null;
            if (isset($row_category['CategoryId']) && isset($row_reports_info['CategoryId']) && $row_category['CategoryId'] == $row_reports_info['CategoryId']) {
              $collapse_show = "show";
            }
            ?>
            <!-- Reportes operativos -->
            <div class="nav-item-wrapper">
              <a class="nav-link dropdown-indicator label-<?php echo $row_category['CategoryId']; ?>" href="#<?php echo $categoryId; ?>" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="<?php echo $categoryId; ?>">
                <div class="d-flex align-items-center">
                  <div class="dropdown-indicator-icon">
                    <svg class="svg-inline--fa fa-caret-right" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="caret-right" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512" data-fa-i2svg="">
                      <path fill="currentColor" d="M118.6 105.4l128 127.1C252.9 239.6 256 247.8 256 255.1s-3.125 16.38-9.375 22.63l-128 127.1c-9.156 9.156-22.91 11.9-34.88 6.943S64 396.9 64 383.1V128c0-12.94 7.781-24.62 19.75-29.58S109.5 96.23 118.6 105.4z"></path>
                    </svg>
                  </div>
                  <span class="nav-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bar-chart-2">
                      <line x1="18" y1="20" x2="18" y2="10"></line>
                      <line x1="12" y1="20" x2="12" y2="4"></line>
                      <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                  </span>
                  <span class="nav-link-text"><?php echo $row_category['Title']; ?></span>
                </div>
              </a>
              <div class="parent-wrapper label-<?php echo $row_category['CategoryId']; ?>">
                <ul class="nav collapse <?php echo $collapse_show; ?> parent" data-bs-parent="#navbarVerticalNav" id="<?php echo $categoryId; ?>">
                  <li class="collapsed-nav-item-title d-none"><?php echo $row_category['Title']; ?></li>
                  <?php 
                  $filtered_reports = array_filter($array_reports, function($report) use ($row_category) {
                    return $report['CategoryId'] == $row_category['CategoryId'];
                  });
                  ?>
                  <?php foreach ($filtered_reports as $key_reports => $row_reports) { ?>
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
                          $script_name = 'reports.php';
                          break;
                        case 3:
                          $script_name = 'tools.php';
                          break;
                        default:
                          $script_name = 'reports.php';
                          break;
                      }
                      ?>

                      <a class="nav-link <?php echo $link_active ?>" href="<?php echo $script_name; ?>?Id=<?php echo $row_reports['ReportsId']; ?>">


                        <div class="d-flex align-items-center"><span class="nav-link-text"> <?php echo $row_reports['ReportsId']; ?>. <?php echo $row_reports['Title']; ?></span></div>
                      </a>
                    </li>
                  <?php } ?>
                </ul>
              </div>
            </div>
          <?php } ?>
          <?php } ?>
        </li>
<?php } ?>