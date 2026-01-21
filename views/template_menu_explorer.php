<?php
//Category Recordset
$array_category = [];
$query_category = "

SELECT a.*
FROM category a
LEFT JOIN category c ON c.ParentId = a.CategoryId
INNER JOIN reports b ON b.CategoryId = a.CategoryId OR b.CategoryId = c.CategoryId
WHERE a.IdType = 1 AND a.Status = 1 AND b.Status = 1 AND b.UsersId != $UsersId
AND a.ParentId IS NULL
GROUP BY a.CategoryId
ORDER BY a.Title ASC


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
$query_reports = "SELECT a.* FROM reports a INNER JOIN category b ON b.CategoryId = a.CategoryId AND (a.ParentId = 0 OR a.ParentId IS NULL) WHERE a.Status = 1 AND b.IdType = 1 AND b.Status = 1 AND a.UsersId != ".$UsersId." ORDER BY `Order` ASC";
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
  if (isset($reports_info['data'][0])) {
    $row_reports_info = $reports_info['data'][0];
  }
}
?>
<?php if(count($array_category)){?>
<li class="nav-item">
  <!-- label-->
  <p class="navbar-vertical-label">Explorador</p>
  <hr class="navbar-vertical-line">
    <?php foreach ($array_category as $key_category => $row_category) { 
            // Generar un ID único para cada categoría
      $categoryId = "nv-ECharts-" . $row_category['CategoryId'];

      $collapse_show = null;
      if (isset($row_category['CategoryId']) && isset($row_reports_info['CategoryId']) && $row_category['CategoryId'] == $row_reports_info['CategoryId']) {
        $collapse_show = "show";
      }

        //SubCategory Recordset
        $array_subcategory = [];
        $query_subcategory = "
        SELECT a.*
        FROM category a
        LEFT JOIN category c ON c.ParentId = a.CategoryId
        INNER JOIN reports b ON b.CategoryId = a.CategoryId OR b.CategoryId = c.CategoryId
        WHERE a.IdType = 1 AND a.Status = 1 AND b.Status = 1 AND b.UsersId != $UsersId
        AND a.ParentId = ".$row_category['CategoryId']."
        GROUP BY a.CategoryId
        ORDER BY a.`Order` ASC
        ";
        $results_subcategory = $conn_phoenix->query($query_subcategory);
        if ($results_subcategory->num_rows > 0) {
          while ($row_subcategory = $results_subcategory->fetch_assoc()) {
            $array_subcategory[] = $row_subcategory;
          }
        }

        //echo "<pre>";print_r($array_subcategory);
      ?>
      <!-- Reportes operativos -->
      <div class="nav-item-wrapper">
        <a class="nav-link dropdown-indicator explorer-<?php echo $row_category['CategoryId']; ?>" href="#explorer-<?php echo $categoryId; ?>" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="explorer-<?php echo $categoryId; ?>">
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
        <div class="parent-wrapper explorer-<?php echo $row_category['CategoryId']; ?>">
          <ul class="nav collapse <?php echo $collapse_show; ?> parent" data-bs-parent="#navbarVerticalNav" id="explorer-<?php echo $categoryId; ?>">
            <li class="collapsed-nav-item-title d-none"><?php echo $row_category['Title']; ?></li>

                      <!-- SUB-CATEGORIA START  -->
                      <?php if (count($array_subcategory)) { ?>
                      <?php foreach ($array_subcategory as $key_subcategory => $row_subcategory) { ?>
                      <?php 
                      $subcategory_collapse_show = null;
                        if (isset($row_subcategory['CategoryId']) && isset($row_reports_info['CategoryId']) && $row_subcategory['CategoryId'] == $row_reports_info['CategoryId']) {
                          $subcategory_collapse_show = "show";
                        }
                      ?>
                      <li class="nav-item">
                        <a class="nav-link dropdown-indicator" href="#nv-explorer-<?php echo $row_subcategory['CategoryId']; ?>" data-bs-toggle="collapse" aria-expanded="true" aria-controls="nv-explorer-<?php echo $row_subcategory['CategoryId']; ?>">
                          <div class="d-flex align-items-center">
                            <div class="dropdown-indicator-icon-wrapper"><svg class="svg-inline--fa fa-caret-right dropdown-indicator-icon" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="caret-right" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512" data-fa-i2svg=""><path fill="currentColor" d="M246.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-128-128c-9.2-9.2-22.9-11.9-34.9-6.9s-19.8 16.6-19.8 29.6l0 256c0 12.9 7.8 24.6 19.8 29.6s25.7 2.2 34.9-6.9l128-128z"></path></svg><!-- <span class="fas fa-caret-right dropdown-indicator-icon"></span> Font Awesome fontawesome.com --></div>
                            <span class="nav-link-text"><?php echo $row_subcategory['Title']; ?></span>
                          </div>
                        </a>
                        <div class="parent-wrapper">
                          <ul class="nav collapse parent <?php echo $subcategory_collapse_show; ?>" data-bs-parent="#e-commerce" id="nv-explorer-<?php echo $row_subcategory['CategoryId']; ?>">
                            <!-- REPORTS ITEM START  -->
                            <?php
                            //get reports items from subcategories
                            views_getMenuItems($array_reports, $row_subcategory);
                            ?>
                            <!-- REPORTS ITEM END  -->
                          </ul>
                        </div>
                      </li>
                      <?php } // END FOR EACH ?>
                  <?php } ?>
            <?php
            //get reports items from categories
            views_getMenuItems($array_reports, $row_category);
            ?>
          </ul>
        </div>
      </div>
    <?php } ?>
</li>
<?php } ?>