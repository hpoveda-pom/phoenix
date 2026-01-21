<?php
function is_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function class_initExecution($ReportsId){

    $QueryDate = date("Y-m-d H:i:s");
    $update_sql = "UPDATE pipelines a SET SyncStatus = 2, LastExecution = '".$QueryDate."' WHERE a.ReportsId = ".$ReportsId;
    $result = class_queryMysqliExe(1, $update_sql);

    return $result;
}

function class_lastExecution($ReportsId){

    $QueryDate = date("Y-m-d H:i:s");
    $update_sql = "UPDATE pipelines a SET SyncStatus = 1, LastExecution = '".$QueryDate."' WHERE a.ReportsId = ".$ReportsId;
    $result = class_queryMysqliExe(1, $update_sql);

    return $result;
}

function class_getLastExecution($datetime) {
    // Inicializamos la variable $msg
    $msg = "";

    if ($datetime) {
        // Definimos las fechas
        $now = new DateTime(); // Fecha actual
        $givenDate = new DateTime($datetime); // Fecha recibida
        $interval = $now->diff($givenDate); // Diferencia entre las dos fechas

        $days = $interval->days;
        $hours = $interval->h;
        $minutes = $interval->i;
        $seconds = $interval->s;

        // Si la fecha está en el futuro
        if ($now < $givenDate) {
            $msg = "En el futuro";
        }

        // Si es hoy
        elseif ($days === 0) {
            if ($hours > 0) {
                $msg = "Se actualizó hace $hours " . ($hours === 1 ? "hora" : "horas");
            } elseif ($minutes > 0) {
                $msg = "Se actualizó hace $minutes " . ($minutes === 1 ? "minuto" : "minutos");
            } else {
                $msg = "Se actualizó hace $seconds " . ($seconds === 1 ? "segundo" : "segundos");
            }
        }

        // Si es ayer
        elseif ($days === 1) {
            $msg = "Se actualizó ayer a las " . $givenDate->format('h:i A');
        }

        // Si hace menos de una semana
        elseif ($days <= 7) {
            $msg = "Se actualizó hace $days " . ($days === 1 ? "día" : "días");
        }

        // Si hace menos de un mes
        elseif ($days <= 30) {
            $weeks = floor($days / 7);
            $msg = "Se actualizó hace $weeks " . ($weeks === 1 ? "semana" : "semanas");
        }

        // Si hace menos de un año
        elseif ($days <= 365) {
            $months = floor($days / 30);
            $msg = "Se actualizó hace $months " . ($months === 1 ? "mes" : "meses");
        }

        // Si hace más de un año
        else {
            $years = floor($days / 365);
            $msg = "Se actualizó hace $years " . ($years === 1 ? "año" : "años");
        }
    } else {
        // Si no se recibe fecha
        $msg = "Reporte en tiempo real";
    }

    $array_results = array(
        'LastExecution' => $msg,
        'LastDatetime'  => $datetime
    );

    // Devolvemos la variable $msg con el resultado
    return $array_results;
}


?>
