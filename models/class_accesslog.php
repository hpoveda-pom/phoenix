<?php 
function getRealBrowser() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    if (preg_match('/Chrome\/([\d.]+)/', $userAgent, $matches) && !preg_match('/Edg/', $userAgent)) {
        return 'Google Chrome ' . $matches[1];
    } elseif (preg_match('/Edg\/([\d.]+)/', $userAgent, $matches)) {
        return 'Microsoft Edge ' . $matches[1];
    } elseif (preg_match('/Firefox\/([\d.]+)/', $userAgent, $matches)) {
        return 'Mozilla Firefox ' . $matches[1];
    } elseif (preg_match('/Safari\/([\d.]+)/', $userAgent, $matches) && !preg_match('/Chrome/', $userAgent)) {
        return 'Apple Safari ' . $matches[1];
    } elseif (preg_match('/Opera\/([\d.]+)/', $userAgent, $matches) || preg_match('/OPR\/([\d.]+)/', $userAgent, $matches)) {
        return 'Opera ' . $matches[1];
    } elseif (preg_match('/MSIE ([\d.]+)/', $userAgent, $matches) || preg_match('/Trident\/.*rv:([\d.]+)/', $userAgent, $matches)) {
        return 'Internet Explorer ' . $matches[1];
    }

    return 'Desconocido';
}

function class_accessLog($ReportsId, $UsersId, $LogType, $exec_timestart, $Response){


    // Si el UsersId es igual a 1, sal de la función y no ejecutes nada más.
    if($UsersId == 1){
        return false; // Devuelve false o null para indicar que no se hizo nada
    }

    $exec_timeend = microtime(true);
    $ExecTime = $exec_timeend - $exec_timestart;
    $ExecTime = number_format($ExecTime, 2);

    $full_url = $_SERVER['REQUEST_URI'];
    $path = parse_url($full_url, PHP_URL_PATH);
    $filename = basename($path);
    $query_string = $_SERVER['QUERY_STRING'];
    $final_result = $filename . '?' . $query_string;

    $Browser = getRealBrowser();

    $QueryDate = date("Y-m-d H:i:s");

    $query = "INSERT INTO access_log (ReportsId, UsersId, QueryDate, ExecTime, Type, Request, Response, Browser) 
              VALUES ($ReportsId, $UsersId, '$QueryDate', $ExecTime, '$LogType', '$final_result', '$Response', '$Browser')";

    $result = class_queryMysqliExe(1, $query);

    return $result;
}
?>
