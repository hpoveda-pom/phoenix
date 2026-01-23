<?php
require_once('class_connclickhouse.php');

function class_queryClickHouse($ConnectionId, $Query, $ArrayFilter, $array_groupby, $Limit, $start = null, $length = null, $array_sumby = null) {
    // Validar que la Query no esté vacía
    if (empty(trim($Query))) {
        return [
            'info' => ['total_rows' => 0],
            'headers' => [],
            'data' => [],
            'msg_error' => 'La consulta SQL está vacía'
        ];
    }

    $i = 0;
    if ($array_groupby) {
        $total_groupby = count($array_groupby);
        $GroupBy = '';
        $select_GroupBy = '';
        
        // Debug: Log del array_groupby recibido
        if (!isset($GLOBALS['debug_info'])) {
            $GLOBALS['debug_info'] = [];
        }
        $debug_info = &$GLOBALS['debug_info'];
        $debug_info[] = "=== Procesando GroupBy (ClickHouse) ===";
        $debug_info[] = "array_groupby recibido: " . json_encode($array_groupby);
        
        foreach ($array_groupby as $key_groupby => $row_groupby) {
            $debug_info[] = "Procesando item GroupBy #$key_groupby: " . json_encode($row_groupby);
            
            // Validar que row_groupby tenga la estructura correcta
            if (!is_array($row_groupby)) {
                $debug_info[] = "ERROR: row_groupby no es un array";
                continue;
            }
            
            // Obtener el nombre del campo - SIEMPRE debe estar en 'value'
            $field_name = null;
            if (isset($row_groupby['value']) && !empty($row_groupby['value'])) {
                $field_name = trim($row_groupby['value']);
            } elseif (isset($row_groupby['GroupBy']) && !empty($row_groupby['GroupBy'])) {
                // Fallback: si viene directamente como 'GroupBy'
                $field_name = trim($row_groupby['GroupBy']);
            } else {
                $debug_info[] = "ERROR: No se pudo determinar el nombre del campo en: " . json_encode($row_groupby);
                continue;
            }
            
            // Validar que el nombre del campo no sea 'GroupBy'
            if (empty($field_name) || $field_name === 'GroupBy' || strtolower($field_name) === 'groupby') {
                $debug_info[] = "ERROR: Nombre de campo inválido: '$field_name'";
                continue;
            }
            
            $debug_info[] = "Campo GroupBy determinado: '$field_name'";
            
            // group by
            if ($i == 0) {
                $GroupBy = "GROUP BY " . $field_name;
            } else {
                $GroupBy .= "," . $field_name;
            }

            // select
            $select_GroupBy .= $field_name . ",";

            $i++;
        }
        
        $debug_info[] = "GroupBy SQL construido: '$GroupBy'";
        $debug_info[] = "select_GroupBy construido: '$select_GroupBy'";
    }

    // Procesar SumBy (similar a GroupBy, permitiendo múltiples campos)
    $select_SumBy = '';
    $sumby_fields = array();
    if ($array_sumby && !empty($array_sumby)) {
        if (!isset($GLOBALS['debug_info'])) {
            $GLOBALS['debug_info'] = [];
        }
        $debug_info = &$GLOBALS['debug_info'];
        $debug_info[] = "=== Procesando SumBy (ClickHouse) ===";
        $debug_info[] = "array_sumby recibido: " . json_encode($array_sumby);
        
        foreach ($array_sumby as $key_sumby => $row_sumby) {
            $debug_info[] = "Procesando item SumBy #$key_sumby: " . json_encode($row_sumby);
            
            if (!is_array($row_sumby)) {
                $debug_info[] = "ERROR: row_sumby no es un array";
                continue;
            }
            
            $field_name = null;
            if (isset($row_sumby['value']) && !empty($row_sumby['value'])) {
                $field_name = trim($row_sumby['value']);
            } elseif (isset($row_sumby['SumBy']) && !empty($row_sumby['SumBy'])) {
                $field_name = trim($row_sumby['SumBy']);
            } else {
                $debug_info[] = "ERROR: No se pudo determinar el nombre del campo en: " . json_encode($row_sumby);
                continue;
            }
            
            if (empty($field_name) || $field_name === 'SumBy' || $field_name === 'field' || strtolower($field_name) === 'sumby' || strtolower($field_name) === 'field') {
                $debug_info[] = "ERROR: Nombre de campo inválido: '$field_name'";
                continue;
            }
            
            $debug_info[] = "Campo SumBy determinado: '$field_name'";
            
            if (!in_array($field_name, $sumby_fields)) {
                $sumby_fields[] = $field_name;
            }
        }
        
        // Construir el SELECT con SUM para cada campo (ClickHouse usa sintaxis similar)
        if (!empty($sumby_fields)) {
            $sumby_selects = array();
            foreach ($sumby_fields as $sumby_field) {
                // ClickHouse: usar toFloat64OrZero para convertir strings a números
                $sumby_field_escaped = "`" . $sumby_field . "`";
                $sumby_alias = "`Suma (" . $sumby_field . ")`";
                $sumby_selects[] = "sum(toFloat64OrZero(tb." . $sumby_field_escaped . ")) AS " . $sumby_alias;
            }
            $select_SumBy = implode(', ', $sumby_selects);
            $debug_info[] = "SumBy SQL construido: '$select_SumBy'";
        }
    }

    // Records per page - Soporte para paginación
    $query_limit = null;
    if ($start !== null && $length !== null) {
        $query_limit = "LIMIT " . intval($length) . " OFFSET " . intval($start);
    } elseif ($Limit) {
        $query_limit = "LIMIT " . intval($Limit);
    }

    // Filters
    $query_where = null;
    if ($ArrayFilter) {
        $grouped_filters = [];
        foreach ($ArrayFilter as $key_filter => $row_filter) {
            $filter_value = $row_filter['value'];

            // Like and not like adding wildcard %
            if ($row_filter['operator'] == 'like' || $row_filter['operator'] == 'not like') {
                $filter_value = "%" . $row_filter['value'] . "%";
            }

            // IN & NOT IN
            if ($row_filter['operator'] == 'in' || $row_filter['operator'] == 'not in') {
                $values_array = explode(',', $row_filter['value']);
                $values_with_quotes = array_map(function($value) {
                    return "'" . addslashes(trim($value)) . "'";
                }, $values_array);
                $filter_value = "(" . implode(',', $values_with_quotes) . ")";
            }

            // BETWEEN AND
            if ($row_filter['operator'] == 'BETWEEN') {
                $values_array = preg_split('/[,\s\/\\\\]+/', $row_filter['value']);
                $values_with_quotes = array_map(function($value) {
                    return "'" . addslashes(trim($value)) . "'";
                }, $values_array);
                if (count($values_with_quotes) >= 2) {
                    $filter_value = $values_with_quotes[0] . " AND " . $values_with_quotes[1];
                } else {
                    continue; // Skip si no hay suficientes valores
                }
            }

            // REGEXP_LIKE -> ClickHouse usa match() o like()
            if ($row_filter['operator'] == 'REGEXP_LIKE' || $row_filter['operator'] == 'NOT REGEXP_LIKE') {
                // ClickHouse no tiene REGEXP_LIKE, usar match() para regex o like() para patrones simples
                $values_array = explode(',', $row_filter['value']);
                $filter_value = "'" . addslashes(trim($values_array[0])) . "'";
            }

            $grouped_filters[$row_filter['key']][] = [
                'operator' => $row_filter['operator'],
                'value' => $filter_value
            ];
        }

        $query_where = " WHERE ";
        $i = 0;
        foreach ($grouped_filters as $key => $filters) {
            if ($i > 0) {
                $query_where .= " AND ";
            }

            $query_where .= "(";
            $j = 0;
            foreach ($filters as $filter) {
                if ($j > 0) {
                    $query_where .= " AND ";
                }

                switch ($filter['operator']) {
                    case 'is null':
                        $query_where .= "$key IS NULL";
                        break;
                    case 'in':
                        $query_where .= "$key IN " . $filter['value'];
                        break;
                    case 'not in':
                        $query_where .= "$key NOT IN " . $filter['value'];
                        break;
                    case 'BETWEEN':
                        $query_where .= "$key BETWEEN " . $filter['value'];
                        break;
                    case 'REGEXP_LIKE':
                        // ClickHouse: usar match() para regex
                        $query_where .= "match(" . $key . ", " . $filter['value'] . ")";
                        break;
                    case 'NOT REGEXP_LIKE':
                        $query_where .= "NOT match(" . $key . ", " . $filter['value'] . ")";
                        break;
                    default:
                        // Escapar comillas simples en el valor
                        $escaped_value = addslashes($filter['value']);
                        $query_where .= "$key " . $filter['operator'] . " '" . $escaped_value . "'";
                        break;
                }

                $j++;
            }
            $query_where .= ")";
            $i++;
        }
    }

    $query = "SELECT tb.* FROM (" . $Query . ") AS tb " . $query_where . " " . $query_limit;

    // Group By or Details
    if ($array_groupby && !empty($GroupBy) && !empty($select_GroupBy)) {
        $select_GroupBy = rtrim($select_GroupBy, ',');
        
        if (isset($GLOBALS['debug_info'])) {
            $debug_info = &$GLOBALS['debug_info'];
            $debug_info[] = "=== Construyendo consulta SQL con GroupBy (ClickHouse) ===";
            $debug_info[] = "select_GroupBy (limpio): '$select_GroupBy'";
            $debug_info[] = "GroupBy: '$GroupBy'";
            if (!empty($select_SumBy)) {
                $debug_info[] = "SumBy: '$select_SumBy'";
            }
        }
        
        $select_fields = "tb." . str_replace(',', ', tb.', $select_GroupBy) . ", count(*) AS Cantidad";
        if (!empty($select_SumBy)) {
            $select_fields .= ", " . $select_SumBy;
        }
        
        $query = "SELECT " . $select_fields . " FROM (" . $Query . ") AS tb " . $query_where . " " . $GroupBy . " ORDER BY Cantidad DESC" . " " . $query_limit;
        
        if (isset($GLOBALS['debug_info'])) {
            $debug_info[] = "Query SQL final: " . $query;
        }
    }

    // Connect to the database
    $conn = class_Connections($ConnectionId);

    // Validar que la conexión se haya establecido correctamente
    if (!$conn || !isset($conn->type) || $conn->type !== 'clickhouse') {
        $error_msg = 'No se pudo establecer la conexión a ClickHouse. Verifique la configuración de la conexión.';
        
        // Agregar información de debug si está disponible
        if (isset($GLOBALS['debug_info']) && is_array($GLOBALS['debug_info'])) {
            $clickhouse_errors = array_filter($GLOBALS['debug_info'], function($msg) {
                return stripos($msg, 'clickhouse') !== false && 
                       (stripos($msg, 'error') !== false || stripos($msg, '✗') !== false);
            });
            if (!empty($clickhouse_errors)) {
                $last_error = end($clickhouse_errors);
                $error_msg .= ' Detalle: ' . $last_error;
            }
        }
        
        // Agregar al debug de filtros para que aparezca en el debug del reporte
        if (isset($GLOBALS['debug_filters'])) {
            $GLOBALS['debug_filters'][] = "ERROR: " . $error_msg;
        }
        
        return [
            'info' => ['total_rows' => 0, 'page_rows' => 0, 'total_pages' => 0],
            'headers' => [],
            'data' => [],
            'error' => $error_msg
        ];
    }

    // Initialize variables
    $info = [];
    $headers = [];
    $data = [];
    $msg_error = '';

    try {
        // Debug breve: solo query SQL y filtros
        if (!isset($GLOBALS['debug_filters'])) {
            $GLOBALS['debug_filters'] = [];
        }
        $GLOBALS['debug_filters'][] = "SQL EJECUTADO (ClickHouse): " . $query;
        if ($ArrayFilter && !empty($ArrayFilter)) {
            $GLOBALS['debug_filters'][] = "FILTROS APLICADOS: " . json_encode($ArrayFilter);
        } else {
            $GLOBALS['debug_filters'][] = "FILTROS APLICADOS: (NINGUNO)";
        }
        
        // Execute the main query
        $error_info = null;
        $result_data = class_clickhouse_execute($conn, $query, $error_info);

        if ($result_data !== false && is_array($result_data)) {
            // ClickHouse retorna array de arrays asociativos cuando se usa formato JSON
            if (!empty($result_data)) {
                // Obtener headers de la primera fila
                $first_row = $result_data[0];
                if (is_array($first_row)) {
                    $headers = array_keys($first_row);
                }
                
                // Convertir a formato esperado (array de arrays asociativos)
                foreach ($result_data as $row) {
                    $data[] = $row;
                }
            }
            
            $GLOBALS['debug_filters'][] = "RESULTADO: " . count($data) . " filas obtenidas";
        } else {
            $msg_error = "Error ejecutando consulta en ClickHouse";
            if ($error_info) {
                $msg_error .= ": " . $error_info;
            }
            $data = [];
            
            if (isset($GLOBALS['debug_info'])) {
                $debug_info[] = "ERROR en consulta ClickHouse: " . $msg_error;
                $debug_info[] = "Query que falló: " . $query;
            }
            
            // Agregar al debug de filtros para que aparezca en el debug del reporte
            if (isset($GLOBALS['debug_filters'])) {
                $GLOBALS['debug_filters'][] = "ERROR: " . $msg_error;
            }
        }
    } catch (Exception $e) {
        $msg_error = $e->getMessage();
        $data = [];
        
        if (isset($GLOBALS['debug_info'])) {
            $debug_info = &$GLOBALS['debug_info'];
            $debug_info[] = "EXCEPCIÓN: " . $msg_error;
        }
    }

    // Get total rows
    $total_rows = 0;
    if ($array_groupby && !empty($GroupBy) && !empty($select_GroupBy)) {
        $select_GroupBy_clean = rtrim($select_GroupBy, ',');
        $query_totalrows = "SELECT count(*) AS TOTAL_ROWS FROM (SELECT " . $select_GroupBy_clean . " FROM (" . $Query . ") AS tb " . $query_where . " " . $GroupBy . ") AS grouped";
    } else {
        $query_totalrows = "SELECT count(*) AS TOTAL_ROWS FROM (" . $Query . ") AS tb " . $query_where;
    }
    
    $GLOBALS['debug_filters'][] = "SQL COUNT (ClickHouse): " . $query_totalrows;
    
    try {
        $count_error_info = null;
        $count_result = class_clickhouse_execute($conn, $query_totalrows, $count_error_info);
        
        if ($count_result !== false && is_array($count_result) && !empty($count_result)) {
            $total_rows = isset($count_result[0]['TOTAL_ROWS']) ? intval($count_result[0]['TOTAL_ROWS']) : 0;
            $GLOBALS['debug_filters'][] = "TOTAL: " . $total_rows . " filas";
        } elseif ($count_error_info) {
            $msg_error = "Error en conteo: " . $count_error_info;
            $total_rows = 0;
            if (isset($GLOBALS['debug_filters'])) {
                $GLOBALS['debug_filters'][] = "ERROR: " . $msg_error;
            }
        }
    } catch (Exception $e) {
        $msg_error = $e->getMessage();
        $total_rows = 0;
        
        if (isset($GLOBALS['debug_info'])) {
            $debug_info[] = "EXCEPCIÓN en conteo: " . $msg_error;
        }
        if (isset($GLOBALS['debug_filters'])) {
            $GLOBALS['debug_filters'][] = "ERROR: " . $msg_error;
        }
    }

    // Calculate total pages
    $page_rows = count($data);
    $total_pages = 0;
    if ($page_rows > 0) {
        $total_pages = $total_rows / $page_rows;
    }

    $info = array(
        'page_rows' => $page_rows,
        'total_pages' => $total_pages,
        'total_rows' => $total_rows
    );

    // Return array with data, headers, info, and error message
    return array(
        'info' => $info,
        'headers' => $headers,
        'data' => $data,
        'error' => $msg_error
    );
}
?>
