<?php 
//Category Recordset
$array_cattools = array();
$query_cattools = "SELECT * FROM category WHERE IdType = 2 AND Status = 1 ORDER BY `Order` ASC";
if ($results_cattools = $conn_phoenix->query($query_cattools)) {
  if ($results_cattools->num_rows > 0) {
    while ($row_cattools = $results_cattools->fetch_assoc()) {
      $array_cattools[] = $row_cattools;
    }
  }
}

//Reports Menu Recordset
$query_tools = "SELECT a.* FROM reports a INNER JOIN category b ON b.CategoryId = a.CategoryId WHERE a.Status = 1 AND b.IdType = 2 AND b.Status = 1 ORDER BY `Order` ASC";
if ($results_tools = $conn_phoenix->query($query_tools)) {
  if ($results_tools->num_rows > 0) {
    while ($row_tools = $results_tools->fetch_assoc()) {
      $array_tools[] = $row_tools;
    }
  }
}
?>

<li class="nav-item">
          <!-- label-->
          <p class="navbar-vertical-label">TOOLS</p>
          <hr class="navbar-vertical-line">
          <?php if ($array_cattools) { ?>
          <?php foreach ($array_cattools as $key_cattools => $row_cattools) { 
            // Generar un ID único para cada categoría
            $categoryId = "nv-ECharts-" . $row_cattools['CategoryId'];
            ?>
            <?php
            $collapse_show = null;
            if (isset($row_cattools['CategoryId']) && isset($row_tools_info['CategoryId']) && $row_cattools['CategoryId'] == $row_tools_info['CategoryId']) {
              $collapse_show = "show";
            }
            ?>
            <!-- Reportes operativos -->
            <div class="nav-item-wrapper">
              <a class="nav-link dropdown-indicator label-<?php echo $row_cattools['CategoryId']; ?>" href="#<?php echo $categoryId; ?>" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="<?php echo $categoryId; ?>">
                <div class="d-flex align-items-center">
                  <div class="dropdown-indicator-icon">
                    <svg class="svg-inline--fa fa-caret-right" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="caret-right" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512" data-fa-i2svg="">
                      <path fill="currentColor" d="M118.6 105.4l128 127.1C252.9 239.6 256 247.8 256 255.1s-3.125 16.38-9.375 22.63l-128 127.1c-9.156 9.156-22.91 11.9-34.88 6.943S64 396.9 64 383.1V128c0-12.94 7.781-24.62 19.75-29.58S109.5 96.23 118.6 105.4z"></path>
                    </svg>
                  </div>
                  <span class="nav-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-tool">
                      <line x1="18" y1="20" x2="18" y2="10"></line>
                      <line x1="12" y1="20" x2="12" y2="4"></line>
                      <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                  </span>
                  <span class="nav-link-text"><?php echo $row_cattools['Title']; ?></span>
                </div>
              </a>
              <div class="parent-wrapper label-<?php echo $row_cattools['CategoryId']; ?>">
                <ul class="nav collapse <?php echo $collapse_show; ?> parent" data-bs-parent="#navbarVerticalNav" id="<?php echo $categoryId; ?>">
                  <li class="collapsed-nav-item-title d-none"><?php echo $row_cattools['Title']; ?></li>
                  <?php 
                  $filtered_tools = array_filter($array_tools, function($tools) use ($row_cattools) {
                    return $tools['CategoryId'] == $row_cattools['CategoryId'];
                  });
                  ?>
                  <?php foreach ($filtered_tools as $key_tools => $row_tools) { ?>
                    <?php
                    $link_active = null;
                    if (isset($row_tools['ReportsId']) && isset($row_tools_info['ReportsId']) && $row_tools['ReportsId'] == $row_tools_info['ReportsId']) {
                      $link_active = "active";
                    }
                    ?>
                    <li class="nav-item">
                      <a class="nav-link <?php echo $link_active ?>" href="reports.php?Id=<?php echo $row_tools['ReportsId']; ?>">
                        <div class="d-flex align-items-center"><span class="nav-link-text"> <?php echo $row_tools['ReportsId']; ?>. <?php echo $row_tools['Title']; ?></span></div>
                      </a>
                    </li>
                  <?php } ?>
                </ul>
              </div>
            </div>
          <?php } ?>
          <?php } ?>
        </li>