<?php
require_once('config.php');
require_once('restrict.php');
require_once('functions.php');
require_once('views/template_header.php');

require_once('conn/phoenix.php');

require_once('models/class_recordset.php');
require_once('models/class_connections.php');
require_once('models/class_querymysqli.php');
require_once('models/class_connmysqli.php');

//php ini settings
set_time_limit($row_config['set_time_limit']);
date_default_timezone_set($row_config['time_zone']);
ini_set('memory_limit', $row_config['memory_limit']);

$exec_timestart = microtime(true);

$Id = null;
if (isset($_GET['Id'])) {
  $Id = $_GET['Id'];
}

$UsersId = null;
if (isset($_SESSION['UsersId'])) {
  $UsersId = $_SESSION['UsersId'];
}
$UsersType = 1;

if ($UsersId) {
  $query_users_info = "SELECT a.UsersId, a.UsersType,a.FullName,a.`Status` FROM users a WHERE a.UsersId = ".$UsersId;
  $users_info = class_Recordset(1, $query_users_info, null, null, 1);

//echo "<pre>";print_r($users_info);

  if (isset($users_info['info']['total_rows']) && $users_info['info']['total_rows'] > 0 && isset($users_info['data'][0])) {
    $row_users_info = $users_info['data'][0];
    $UsersType = $row_users_info['UsersType'];
  }
}
?>

<body>
  <main class="main" id="top">
    <nav class="navbar navbar-vertical navbar-expand-lg">
      <script>
        var navbarStyle = window.config.config.phoenixNavbarStyle;
        if (navbarStyle && navbarStyle !== 'transparent') {
          document.querySelector('body').classList.add(`navbar-${navbarStyle}`);
        }
      </script>
      <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
        <!-- scrollbar removed-->
        <div class="navbar-vertical-content">
          <ul class="navbar-nav flex-column" id="navbarVerticalNav">

            <?php
            //require_once("views/template_menu_favorites.php")
            //require_once("views/template_menu_tools.php")
            
            if ($UsersType==3) {
              require_once("views/template_menu_reports.php");
            }else{
              require_once("views/template_menu_reports.php");
              require_once("views/template_menu_explorer.php");
            }
            ?>

          </ul>
        </div>
      </div>
      <div class="navbar-vertical-footer">
        <button class="btn navbar-vertical-toggle border-0 fw-semibold w-100 white-space-nowrap d-flex align-items-center">
          <span class="fas fa-arrow-left fs-8"></span>
          <span class="fas fa-arrow-right fs-8"></span>
          <span class="navbar-vertical-footer-text ms-2">Collapsed View</span>
        </button>
      </div>
    </nav>
    <?php require_once('views/template_navbar.php'); ?>
    <div class="content">
      <div class="row g-5 pb-4">