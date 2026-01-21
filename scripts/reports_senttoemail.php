<?php
require_once('../config.php');
require_once('../functions.php');
require_once('../models/class_connections.php');
require_once('../models/class_connmysqli.php');
require_once('../models/class_connoci.php');
require_once('../models/class_querymysqli.php');
require_once('../models/class_querymysqliexe.php');
require_once('../models/class_queryoci.php');
require_once('../models/class_recordset.php');
require_once('../models/class_exportexcel.php');
require_once('../models/class_masking.php');
require_once('../models/class_fieldalias.php');
require_once('../models/class_fieldformat.php');
require_once('../models/class_sentmail.php');
require_once('../views/template_senttoemail.php');

//time zone
date_default_timezone_set('America/Costa_Rica');

$Limit = null;
if (isset($_GET['Limit'])) {
  $Limit = $_GET['Limit'];
}

$Sleep = 5;
if (isset($_GET['Sleep'])) {
  $Sleep = $_GET['Sleep'];
}

$status_email = 1;
if (isset($_GET['status_email'])) {
  $status_email = $_GET['status_email'];
}

$status_excel = 1;
if (isset($_GET['status_excel'])) {
  $status_excel = $_GET['status_excel'];
}

$ReportsId = null;
if (isset($_GET['ReportsId'])) {
  $ReportsId = $_GET['ReportsId'];
}

$UsersId = null;
if (isset($_GET['UsersId'])) {
  $UsersId = $_GET['UsersId'];
}

$date_now = date("Y-m-d H:i:s");
if (isset($_GET['date_now'])) {
  $date_now = $_GET['date_now'];
}

/* query conditions */

$sql_where_reportsid = null;
if($ReportsId){
 $sql_where_reportsid = "AND a.ReportsId = ".$ReportsId;
}

$sql_where_usersid = null;
if($UsersId){
 $sql_where_usersid = "AND c.UsersId = ".$UsersId;
}

$sql_limit = null;
if($Limit){
 $sql_limit = "LIMIT 0, ".$Limit;
}


$time_weekday   = date("H:i:s");
$day_weekday    = date("D");
$today_weekday  = date("Y-m-d");
$sql_weekday    = "AND ".$day_weekday." <= '".$time_weekday."'";
$sql_weekday   .= " AND (b.LastSend IS NULL OR DATE(b.LastSend) != '".$today_weekday."')";

//GET REPORTS SCHEDULED LIST

//tracking sent tasks
if ($ReportsId && $UsersId) {
$query_reports = "SELECT a.TypeId, NULL AS TasksId, a.ReportsId,a.Title,a.Description,a.`Query`,a.ConnectionId,a.`Order`,
                    a.Version, c.UsersId,NULL AS Mon,NULL AS Tue,NULL AS Wed,NULL AS Thu,NULL AS Fri,NULL AS Sat,NULL AS Sun,c.Email, c.FullName, NOW() AS LastSend
                    FROM reports a
                    LEFT JOIN users c ON c.UsersId = $UsersId
                    WHERE a.Status = 1 AND c.`Status` = 1".
                    " ".$sql_where_reportsid.
                    " ".$sql_where_usersid.
                    " ".$sql_limit;
}else{
$query_reports = "SELECT a.TypeId, b.TasksId, a.ReportsId,a.Title,a.Description,a.`Query`,a.ConnectionId,a.`Order`,
                    a.Version, b.UsersId,b.Mon,b.Tue,b.Wed,b.Thu,b.Fri,b.Sat,b.Sun,c.Email, c.FullName,b.LastSend
                    FROM reports a INNER JOIN tasks b ON b.ReportsId = a.ReportsId
                    INNER JOIN users c ON c.UsersId = b.UsersId
                    WHERE a.Status = 1 AND b.`Status` = 1 AND c.`Status` = 1".
                    " ".$sql_weekday.
                    " ".$sql_where_reportsid.
                    " ".$sql_where_usersid.
                    " ".$sql_limit;
}



$reports = class_Recordset(1, $query_reports, null, null, null);

$response = [];
if (count($reports['data'])) {
  foreach ($reports['data'] as $key_reports => $row_reports) {

    //dashboard
    $arr_children = [];

    if ($row_reports['TypeId']==2) {
        $sql_children = "SELECT a.* FROM reports a WHERE a.Status = 1 AND ParentId = ".$row_reports['ReportsId'];
        $children = class_Recordset(1, $sql_children, null, null, null);

        //reports info
        $reports_data['info'] = $children['info'];

        foreach ($children['data'] as $key_children => $row_children) {

            //excel - get reports data
            $children_title  = $row_children['ReportsId'].". ".$row_children['Title'];
            $children_data   = class_Recordset($row_children['ConnectionId'], $row_children['Query'], null, null, null);

            //excel - set headers
            $new_headers = [];
            foreach ($children_data['headers'] as $key_headers => $row_headers) {
                $new_headers[] = getFieldAlias($row_headers);
            }

            //children output
            $arr_children[] = array(
                'ReportsId' => $row_children['ReportsId'],
                'Title'     => $row_children['Title'],
                'Records'   => $children_data['info']['total_rows']
            );

        }

        //excel - get reports data
        $reports_title  = $row_reports['ReportsId'].". ".$row_reports['Title'];

        //echo "<pre>";print_r($children_data);


    }else{

        //excel - get reports data
        $reports_title  = $row_reports['ReportsId'].". ".$row_reports['Title'];
        $reports_data   = class_Recordset($row_reports['ConnectionId'], $row_reports['Query'], null, null, null);

        //excel - set headers
        $new_headers = [];
        foreach ($reports_data['headers'] as $key_headers => $row_headers) {
            $new_headers[] = getFieldAlias($row_headers);
        }

    }

    //generate excel
    $excel['filepath'] = null;
    if ($status_excel) {
        $tmp_path = "../tmp/"; // temporaly save files
        $download_redirect = false;
        $excel = class_exportExcel($new_headers, $reports_data['data'], $reports_title, $tmp_path, $download_redirect);

        if ($excel['filepath']) {
            $fault_code = 2;
            $fault_msg = "Error al generar el excel";
        }
    }

    //sent mail
    if ($status_email) {
        if ($row_reports['Email']) {

            $to         = $row_reports['Email'];
            $cc         = null;
            $bcc        = null;
            $subject    = "reporte - ".$reports_title;
            $body       = class_templateSentoemail();
            $attachment = $excel['filepath'];
            $sentmail   = class_sentMail($to, $cc, $bcc, $subject, $body, $attachment);

            if ($sentmail) {
                $fault_code = 0;
                $fault_msg = "Reporte se ha enviado con éxito";

                //set last sent
                if ($row_reports['TasksId']) {
                    $sql_update_tasks = "UPDATE tasks SET LastSend = '".$date_now."' WHERE TasksId = ".$row_reports['TasksId'];
                    $update_tasks = class_queryMysqliExe(1, $sql_update_tasks);
                }

            }else{
                $fault_code = 3;
                $fault_msg = "Error al enviar el correo: ".$sentmail;
            }

            //pause every n seoonds
            sleep($Sleep);

        }else{
            $fault_code = 5;
            $fault_msg = "No hay un destinatario definido";
        }
    }else{
        $fault_code = 7;
        $fault_msg = "Envío de correo inactivo";
    }

    //scheduled output
    $arr_sheduled = array(
        'Mon' => $row_reports['Mon'],
        'Tue' => $row_reports['Tue'],
        'Wed' => $row_reports['Wed'],
        'Thu' => $row_reports['Thu'],
        'Fri' => $row_reports['Fri'],
        'Sat' => $row_reports['Sat'],
        'Sun' => $row_reports['Sun'],
    );

    //array output
    $response[$row_reports['TasksId']] = array(
        'ReportsId'     => $row_reports['ReportsId'],
        'Title'         => $row_reports['Title'],
        'User'          => $row_reports['FullName'],
        'Email'         => $row_reports['Email'],
        'File_Path'     => @$excel['filepath'],
        'Fault_Code'    => $fault_code,
        'Fault_Msg'     => $fault_msg,
        'Records'       => $reports_data['info']['total_rows'],
        'sheduled'      => $arr_sheduled,
        'LastSend'      => $row_reports['LastSend'],
        'Children'        => $arr_children
    );

  } //end foreach


}else{

    //array output
    $response = null;
    /*
    $response[] = array(
        'Fault_Code'    => 1,
        'Fault_Msg'     => "No hay reportes encontrados",
        'ReportsId'     => 0,
        'Title'         => 0,
        'User'          => 0,
        'Email'         => 0,
        'File_Path'     => 0,
        'Records'       => 0,
        'sheduled'      => 0,
        'LastSend'      => null,
        'Children'      => 0,
    );
    */
}

//output
$msg = null;
if ($response) {
    foreach ($response as $key_response => $row_response) {

        $LastSend = date("Y-m-d H:i:s");
        if ($row_response['LastSend']) {
            $LastSend = $row_response['LastSend'];
        }
        echo "[".$LastSend."][".$row_response['Fault_Code']."][".$row_response['Fault_Msg']."][".$row_response['ReportsId']."][".$row_response['Email']."][".$row_response['Records']."]"."\n";
    }
}
