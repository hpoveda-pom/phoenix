<?php
require_once('models/class_recordset.php');
require_once('models/class_connections.php');
require_once('models/class_exportexcel.php');
require_once('models/class_queryoci.php');
require_once('models/class_querymysqli.php');
require_once('models/class_connoci.php');
require_once('models/class_connmysqli.php');
require_once('models/class_filterremove.php');
require_once('models/class_tipodato.php');
require_once('models/class_accesslog.php');
require_once('models/class_querymysqliexe.php');

//global vars
$action = null;
if (isset($_GET['action'])) {
  $action = $_GET['action'];
}

$Id = null;
if (isset($_GET['Id'])) {
  $Id = $_GET['Id'];
}

$GroupBy = null;
if (isset($_GET['GroupBy'])) {
  $GroupBy = $_GET['GroupBy'];
}

$OrderBy = null;
if (isset($_GET['OrderBy'])) {
  $OrderBy = $_GET['OrderBy'];
}

$Filter = null;
if (isset($_GET['Filter'])) {
  $Filter = $_GET['Filter'];
}

$Limit = 10;
if (isset($_GET['Limit'])) {
  $Limit = $_GET['Limit'];
}

if (isset($_GET['unset'])) {
  foreach ($_GET['unset'] as $key_unset => $row_unset) {
    class_filterRemove($_GET, $key_unset, $row_unset);
  }
}

$filter_selected = array();
if (isset($_GET['filter_selected'])) {
  $filter_selected = $_GET['filter_selected'];
}

$array_filters = $filter_selected;
if (is_array($Filter)) {
  if ($Filter['field']) {
    $array_filters[] = array(
      'filter' => array($Filter['field'] => $Filter['keyword']),
      'operator' => $Filter['operator']
    );
  }
}

$filter_results = array();
if (is_array($array_filters) && !empty($array_filters)) {
  foreach ($array_filters as $key_filters => $row_filters) {
    if (is_array($row_filters)) {
      foreach ($row_filters['filter'] as $filter_key => $filter_value) {
        $filter_results[] = array(
          'key' => $filter_key,
          'operator' => $row_filters['operator'],
          'value' => $filter_value,
        );
      }
    }
  }
}

//Group by selected
$groupby_selected = array();
if (isset($_GET['groupby_selected'])) {
  $groupby_selected = $_GET['groupby_selected'];
}

$array_groupby = $groupby_selected;
if (is_array($GroupBy)) {
  if ($GroupBy['field']) {
    $array_groupby[] = array(
      'GroupBy' => $GroupBy['field'],
    );
  }
}

$groupby_results = array();
if (is_array($array_groupby) && !empty($array_groupby)) {
  foreach ($array_groupby as $key_groupby => $row_groupby) {
    if (is_array($row_groupby)) {
      foreach ($row_groupby as $groupby_key => $groupby_value) {
        $groupby_results[] = array(
          'key' => $groupby_key,
          'value' => $groupby_value,
        );
      }
    }
  }
}

//OrderBy by selected
$orderby_selected = array();
if (isset($_GET['orderby_selected'])) {
  $orderby_selected = $_GET['orderby_selected'];
}

$array_orderby = $orderby_selected;
if (is_array($OrderBy)) {
  if ($OrderBy['field']) {
    $array_orderby[] = array(
      'OrderBy' => array($OrderBy['field'] => $OrderBy['operator']),
    );
  }
}

$orderby_results = array();
if (is_array($array_orderby) && !empty($array_orderby)) {
  foreach ($array_orderby as $key_orderby => $row_orderby) {
    if (is_array($row_orderby)) {
      foreach ($row_orderby['OrderBy'] as $orderby_key => $orderby_value) {
        $orderby_results[] = array(
          'key' => $orderby_key,
          'value' => $orderby_value,
        );
      }
    }
  }
}

//limit list
$array_limit = array(
  '10'    => 10,
  '25'    => 25,
  '50'    => 50,
  '100'   => 100,
  '200'   => 200,
  '500'   => 500,
  '1000'  => 1000,
  '2000'  => 2000,
  '5000'  => 5000,
  '10000' => 10000
);

//Order By list
$array_orderby = array(
  'ASC'    => 'Ascendente',
  'DESC' => 'Descendente'
);



if ($Id) {
  $query_reports_info = "SELECT a.*, b.Title AS Category, c.FullName, d.Connector AS conn_connector, d.Schema AS conn_schema, d.Title AS conn_title FROM reports a INNER JOIN category b ON b.CategoryId = a.CategoryId INNER JOIN users c ON c.UsersId = a.UsersId INNER JOIN connections d ON d.ConnectionId = a.ConnectionId WHERE a.Status = 1 AND b.Status = 1 AND a.ReportsId = ".$Id." ORDER BY `Order` ASC";
  $reports_info = class_Recordset(1, $query_reports_info, null, null, 1);
  $row_reports_info = $reports_info['data'][0];
}else{
  echo '<div class="alert alert-subtle-danger" role="alert">Error, no ha seleccionado un reporte válido!</div>';
  exit;
}

//Export to Excel
$LogType = 'Reporte';
if ($action == "excel") {
  $excel_title  = $row_reports_info['ReportsId'].". ".$row_reports_info['Title'];
  $excel_data   = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], $filter_results, $groupby_results, null);
  class_exportExcel($excel_data['headers'], $excel_data['data'], $excel_title);
  $LogType = 'Excel';
}

//reports recordset
if ($row_reports_info['TypeId']==1) {
  $array_headers  = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], null, null, 1);
  $array_reports  = class_Recordset($row_reports_info['ConnectionId'], $row_reports_info['Query'], $filter_results, $groupby_results, $Limit);
  $array_info     = $array_reports['info']; 
}

//parent recordsets
if ($row_reports_info['TypeId']==2) {
  $query_parent = "SELECT a.* FROM reports a WHERE a.ParentId = ".$row_reports_info['ReportsId']." AND a.Status = 1 ORDER BY a.Order ASC";
  $array_parent = class_Recordset(1, $query_parent, null, null, NULL);
  $array_info = $array_parent['info']; 
}

//parent recordsets
if ($row_reports_info['TypeId']==3) {
  $query_parent = "SELECT a.* FROM reports a WHERE a.ParentId = ".$row_reports_info['ReportsId']." AND a.Status = 1 ORDER BY a.Order ASC";
  $array_parent = class_Recordset(1, $query_parent, null, null, NULL);
  $array_info = $array_parent['info']; 
}


//Report
if ($row_reports_info['TypeId']==1) {
  require_once('views/reports_filtersbox.php');
  require_once('views/reports_breadcrumb.php');
  require_once('views/reports_results.php');
}

if ($row_reports_info['TypeId']==3) {
  require_once('views/reports_breadcrumb.php');
  require_once('views/reports_charts.php');
}

  $Response = "OK;ROWS:".$array_info['total_rows'];
  class_accessLog($Id, $UsersId, $LogType, $exec_timestart, $Response);
?>