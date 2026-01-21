<?php 
function getFieldAlias($field) {

    if ($field) {
        // Translation map from technical field names to user-friendly aliases
        $qry_conventions = "SELECT a.FieldAlias FROM conventions a WHERE a.Status = 1 AND a.FieldName = '".$field."'";
        $arr_conventions = class_Recordset(1, $qry_conventions, null, null, 1);

        if ($arr_conventions['data']) {
            $field = $arr_conventions['data'][0]['FieldAlias'];
        }
    }

    // If an alias is available, return it; otherwise, return a friendly version
    return ($field);
}
