<?php 
function maskedData($key, $value, $OwnerId, $ReportsId) {

    // Validar la sesión antes de usarla
    if (!isset($_SESSION['UsersId'])) {
        throw new Exception('Usuario no autenticado.');
    }

    $UsersId = $_SESSION['UsersId'];
    $result = 'Sin permisos'; // Valor predeterminado si no tiene permisos

    // Si el valor es nulo, retorna el mensaje "Sin permisos"
    if ($value === null) {
        return $value;
    }

    // Verificar si el usuario es el propietario o tiene acceso global
    if ($OwnerId == $UsersId || $UsersId == 1) {
        return $value;
    }

    // Obtener nivel de enmascaramiento desde la base de datos
    $qry_conventions = "
        SELECT a.MaskingLevel 
        FROM conventions a 
        WHERE a.Status = 1 AND a.FieldName = '".addslashes($key)."'
    ";
    $arr_conventions = class_Recordset(1, $qry_conventions, null, null, 1);

    // Validar si se encontró el nivel
    $level = $arr_conventions['data'][0]['MaskingLevel'] ?? null;

    if (!$level) {
        // Si no hay nivel definido, permitir el acceso al valor
        return $value;
    }

    /*
    * Validar permisos de enmascaramiento según el nivel.
    * Nivel: 1=Public, 2=Personal, 3=Sensitive, 4=Confidential
    */
    $qry_masking = "
        SELECT a.MaskingId 
        FROM masking a 
        WHERE 
            a.ReportsId = ".intval($ReportsId)." AND 
            a.Level > ".intval($level)." AND 
            a.Status = 1 AND 
            a.UsersId = ".intval($UsersId)."
    ";
    $arr_masking = class_Recordset(1, $qry_masking, null, null, 1);

    // Si tiene permisos, devolver el valor
    if (!empty($arr_masking['data'])) {
        $result = $value;
    }

    return $result;
}
