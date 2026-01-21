<?php 
function fieldFormat($field) {

    $field_type = "STRING";
    $field_value = $value;
    $field_class = null;

    if ($field) {
        $qry_conventions = "SELECT a.DataType FROM conventions a WHERE a.Status = 1 AND a.FieldName = '".$field."'";
        $arr_conventions = class_Recordset(1, $qry_conventions, null, null, 1);

        if ($arr_conventions['data']) {
            $field_type = $arr_conventions['data'][0]['DataType'];
        }
    }

    //formater
    switch ($field_type) {
        case 'NUMBER':
            $field_value = number_format($value, 0);
            $field_class = "text-center";
            break;

        case 'DECIMAL':
            $field_value = number_format($value, 2);
            $field_class = "text-end";
            break;
        
        default:
           $field_value = $value;
           $field_class = null;
           break;
    }

    $results = array(
        'type' => $field_type,
        'value' => $field_value,
        'class' => $field_class,
    );

    return $results;
}