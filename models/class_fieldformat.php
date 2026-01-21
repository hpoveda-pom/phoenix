<?php 
function fieldFormat($field, $value = 0) {

    $field_key  = $field;
    $field_type = "STRING";
    $field_value = $value;
    $field_class = null;
    $field_total = false;

    if ($field) {
        $qry_conventions = "SELECT a.DataType FROM conventions a WHERE a.Status = 1 AND a.FieldName = '".$field."'";
        $arr_conventions = class_Recordset(1, $qry_conventions, null, null, 1);

        if ($arr_conventions['data']) {
            $field_type = $arr_conventions['data'][0]['DataType'];
        }
    }

    //formater
    if ($field_type) {
        switch ($field_type) {
            case 'NUMBER':
                $field_key = $field;
                $field_value = number_format($value, 0);
                $field_class = "text-center";
                $field_total = true;
                break;

            case 'DECIMAL':
                $field_key = $field;
                $field_value = number_format($value, 2);
                $field_class = "text-end";
                $field_total = true;
                break;
            
            default:
               $field_key = $field;
               $field_value = $value;
               $field_class = "text-start";
               $field_total = false;
               break;
        }
    }

    $results = array(
        'field'  => $field_key,
        'type'  => $field_type,
        'value' => $field_value,
        'class' => $field_class,
        'total' => $field_total,
    );

    return $results;
}