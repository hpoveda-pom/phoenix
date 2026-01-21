<?php
function class_queryMysqli($ConnectionId, $Query, $ArrayFilter, $array_groupby, $Limit, $start = null, $length = null, $array_sumby = null) {
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
        $debug_info[] = "=== Procesando GroupBy ===";
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
                $debug_info[] = "Estructura esperada: array('key' => 'field', 'value' => 'nombre_campo')";
                continue;
            }
            
            // Validar que el nombre del campo no sea 'GroupBy' (nombre del input)
            if (empty($field_name) || $field_name === 'GroupBy' || strtolower($field_name) === 'groupby') {
                $debug_info[] = "ERROR: Nombre de campo inválido: '$field_name' (no puede ser 'GroupBy')";
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
        $debug_info[] = "=== Procesando SumBy ===";
        $debug_info[] = "array_sumby recibido: " . json_encode($array_sumby);
        
        // Procesar cada item de SumBy (similar a GroupBy)
        foreach ($array_sumby as $key_sumby => $row_sumby) {
            $debug_info[] = "Procesando item SumBy #$key_sumby: " . json_encode($row_sumby);
            
            // Validar que row_sumby tenga la estructura correcta
            if (!is_array($row_sumby)) {
                $debug_info[] = "ERROR: row_sumby no es un array";
                continue;
            }
            
            // Obtener el nombre del campo - SIEMPRE debe estar en 'value' (igual que GroupBy)
            $field_name = null;
            if (isset($row_sumby['value']) && !empty($row_sumby['value'])) {
                $field_name = trim($row_sumby['value']);
            } elseif (isset($row_sumby['SumBy']) && !empty($row_sumby['SumBy'])) {
                // Fallback: si viene directamente como 'SumBy'
                $field_name = trim($row_sumby['SumBy']);
            } else {
                $debug_info[] = "ERROR: No se pudo determinar el nombre del campo en: " . json_encode($row_sumby);
                $debug_info[] = "Estructura esperada: array('key' => 'field', 'value' => 'nombre_campo')";
                continue;
            }
            
            // Validar que el nombre del campo no sea 'SumBy' o 'field' (nombres de inputs)
            if (empty($field_name) || $field_name === 'SumBy' || $field_name === 'field' || strtolower($field_name) === 'sumby' || strtolower($field_name) === 'field') {
                $debug_info[] = "ERROR: Nombre de campo inválido: '$field_name' (no puede ser 'SumBy' o 'field')";
                continue;
            }
            
            $debug_info[] = "Campo SumBy determinado: '$field_name'";
            
            // Agregar el campo a la lista (permitir múltiples campos)
            if (!in_array($field_name, $sumby_fields)) {
                $sumby_fields[] = $field_name;
            }
        }
        
        // Construir el SELECT con SUM para cada campo
        if (!empty($sumby_fields)) {
            $sumby_selects = array();
            foreach ($sumby_fields as $sumby_field) {
                // Usar backticks para proteger el nombre del campo
                $sumby_field_escaped = "`" . $sumby_field . "`";
                // Hacer SUM del campo (similar a COUNT en GroupBy, pero sumando)
                // Usar backticks para el alias porque contiene espacios y paréntesis
                $sumby_alias = "`Suma (" . $sumby_field . ")`";
                $sumby_selects[] = "SUM(
                    CASE 
                        WHEN tb." . $sumby_field_escaped . " IS NULL OR tb." . $sumby_field_escaped . " = '' THEN 0
                        WHEN tb." . $sumby_field_escaped . " REGEXP '^[0-9]+\.?[0-9]*$' THEN CAST(tb." . $sumby_field_escaped . " AS DECIMAL(20,2))
                        ELSE 0
                    END
                ) AS " . $sumby_alias;
            }
            $select_SumBy = implode(', ', $sumby_selects);
            $debug_info[] = "SumBy SQL construido: '$select_SumBy'";
            $debug_info[] = "Campos SumBy procesados: " . implode(', ', $sumby_fields);
        }
    }

    // Records per page - Soporte para paginación de DataTables
    $query_limit = null;
    if ($start !== null && $length !== null) {
        // Paginación de DataTables (start y length)
        $query_limit = "LIMIT " . intval($start) . "," . intval($length);
    } elseif ($Limit) {
        // Paginación tradicional (solo límite)
        $query_limit = "LIMIT 0," . $Limit;
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

            // IN & NOT IN PARENTHESES AND COMMA SEPARATOR
            if ($row_filter['operator'] == 'in' || $row_filter['operator'] == 'not in') {
                $values_array = explode(',', $row_filter['value']);
                $values_with_quotes = array_map(function($value) {
                    return "'" . $value . "'";
                }, $values_array);

                $filter_value = implode(',', $values_with_quotes);
            }

            // BETWEEN AND / SEPARATOR
            if ($row_filter['operator'] == 'BETWEEN') {

                $values_array = preg_split('/[,\s\/\\\\]+/', $row_filter['value']);
                $values_with_quotes = array_map(function($value) {
                    return "'" . $value . "'";
                }, $values_array);

                $filter_value = implode(' AND ', $values_with_quotes);
            }

            // REG EXP
            if ($row_filter['operator'] == 'REGEXP_LIKE' || $row_filter['operator'] == 'NOT REGEXP_LIKE') {
                $values_array = explode(',', $row_filter['value']);
                $values_with_quotes = array_map(function($value) {
                    return $value;
                }, $values_array);

                $filter_value = implode('/', $values_with_quotes);
            }

            // Group filters by key
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
                    //$query_where .= " OR ";
                    $query_where .= " AND ";
                }

                switch ($filter['operator']) {
                    case 'is null':
                        $query_where .= "$key " . $filter['operator'];
                        break;
                    case 'in':
                        $query_where .= "$key " . $filter['operator'] . "(" . $filter['value'] . ")";
                        break;
                    case 'not in':
                        $query_where .= "$key " . $filter['operator'] . "(" . $filter['value'] . ")";
                        break;
                    case 'BETWEEN':
                        $query_where .= "$key " . $filter['operator'] . " " . $filter['value'] . "";
                        break;
                    case 'REGEXP_LIKE':
                        $query_where .= $filter['operator'] . "(" . $key . ", '" . $filter['value'] . "')";
                        break;
                    case 'NOT REGEXP_LIKE':
                        $query_where .= $filter['operator'] . "(" . $key . ", '" . $filter['value'] . "')";
                        break;
                    default:
                        $query_where .= "$key " . $filter['operator'] . " '" . $filter['value'] . "'";
                        break;
                }

                $j++;
            }
            $query_where .= ")";
            $i++;
        }
    }

    $query = "SELECT tb.* FROM (" . $Query . ")tb " . $query_where . " " . $query_limit;

    // Group By or Details
    if ($array_groupby && !empty($GroupBy) && !empty($select_GroupBy)) {
        // Remover la coma final de select_GroupBy
        $select_GroupBy = rtrim($select_GroupBy, ',');
        
        // Debug: Log de la consulta SQL antes de ejecutarla
        if (isset($GLOBALS['debug_info'])) {
            $debug_info = &$GLOBALS['debug_info'];
            $debug_info[] = "=== Construyendo consulta SQL con GroupBy ===";
            $debug_info[] = "select_GroupBy (limpio): '$select_GroupBy'";
            $debug_info[] = "GroupBy: '$GroupBy'";
            if (!empty($select_SumBy)) {
                $debug_info[] = "SumBy: '$select_SumBy'";
            }
        }
        
        // Construir SELECT con o sin SUM
        $select_fields = "tb." . $select_GroupBy . ", COUNT(1) AS Cantidad";
        if (!empty($select_SumBy)) {
            $select_fields .= ", " . $select_SumBy;
        }
        
        $query = "SELECT " . $select_fields . " FROM (" . $Query . ")tb " . $query_where . " " . $GroupBy . " ORDER BY Cantidad DESC" . " " . $query_limit;
        
        if (isset($GLOBALS['debug_info'])) {
            $debug_info[] = "Query SQL final: " . $query;
        }
    }

    // Connect to the database
    $conn = class_Connections($ConnectionId);

    // Validar que la conexión se haya establecido correctamente
    if (!$conn || !($conn instanceof mysqli)) {
        return [
            'info' => ['total_rows' => 0, 'page_rows' => 0, 'total_pages' => 0],
            'headers' => [],
            'data' => [],
            'error' => 'No se pudo establecer la conexión a la base de datos. Verifique la configuración de la conexión.'
        ];
    }

    // Initialize variables
    $info = [];
    $headers = [];
    $data = [];
    $msg_error = ''; // Variable to store error messages

    try {
        // Debug: Log de la consulta antes de ejecutarla
        if (isset($GLOBALS['debug_info'])) {
            $debug_info = &$GLOBALS['debug_info'];
            $debug_info[] = "=== Ejecutando consulta SQL ===";
            $debug_info[] = "Query: " . $query;
        }
        
        // Execute the main query
        $result = $conn->query($query);

        if ($result) {
            $headers_set = false;
            $row_count = 0;
            while ($row = $result->fetch_assoc()) {
                if (!$headers_set) {
                    $headers = array_keys($row);
                    $headers_set = true;
                    if (isset($GLOBALS['debug_info'])) {
                        $debug_info[] = "Headers obtenidos de resultado: " . implode(', ', $headers);
                        $debug_info[] = "Número de columnas: " . count($headers);
                    }
                }
                $data[] = $row;
                $row_count++;
            }
            $result->free();
            
            if (isset($GLOBALS['debug_info'])) {
                $debug_info[] = "Consulta ejecutada exitosamente. Filas obtenidas: " . count($data);
                if (empty($headers)) {
                    $debug_info[] = "ADVERTENCIA: No se obtuvieron headers de la consulta";
                    $debug_info[] = "Query ejecutada: " . $query;
                    // Intentar obtener headers de la estructura esperada si hay GroupBy
                    if (!empty($select_GroupBy) && !empty($GroupBy)) {
                        $debug_info[] = "Intentando obtener headers de la estructura esperada...";
                        $expected_headers = explode(',', $select_GroupBy);
                        $expected_headers = array_map('trim', $expected_headers);
                        $expected_headers[] = 'Cantidad';
                        if (!empty($select_SumBy)) {
                            // Extraer el nombre del campo SumBy del alias (puede ser `Suma (campo)` o Suma_campo)
                            // Buscar todos los aliases que empiecen con Suma
                            if (preg_match_all('/AS\s+[`"]?Suma\s*\(([^)]+)\)[`"]?/i', $select_SumBy, $matches)) {
                                foreach ($matches[0] as $match) {
                                    // Extraer el nombre completo del alias
                                    if (preg_match('/AS\s+[`"]?([^`"]+)[`"]?/i', $match, $alias_match)) {
                                        $expected_headers[] = $alias_match[1];
                                    }
                                }
                            } elseif (preg_match_all('/AS\s+(\w+Suma_\w+)/i', $select_SumBy, $matches)) {
                                // Fallback para formato antiguo Suma_campo
                                foreach ($matches[1] as $match) {
                                    $expected_headers[] = $match;
                                }
                            }
                            if (!empty($expected_headers)) {
                                $debug_info[] = "Campos SumBy detectados: " . implode(', ', array_slice($expected_headers, -count($matches[0])));
                            }
                        }
                        $headers = $expected_headers;
                        $debug_info[] = "Headers esperados (de estructura): " . implode(', ', $headers);
                    }
                } else {
                    $debug_info[] = "Headers finales: " . implode(', ', $headers);
                }
            }
        } else {
            // Si la consulta falla, capturar el error
            $msg_error = $conn->error;
            $data = [];
            
            if (isset($GLOBALS['debug_info'])) {
                $debug_info[] = "ERROR en consulta SQL: " . $msg_error;
                $debug_info[] = "Query que falló: " . $query;
                $debug_info[] = "Error número: " . $conn->errno;
                // Si el error es sobre un campo que no existe, intentar sin SUM
                if (strpos($msg_error, "Unknown column") !== false && !empty($select_SumBy)) {
                    $debug_info[] = "ADVERTENCIA: El campo para SUM puede no existir. Verifique el nombre del campo.";
                }
                // Intentar obtener headers de la estructura esperada aunque haya fallado
                if (empty($headers) && !empty($select_GroupBy)) {
                    $debug_info[] = "Intentando obtener headers de la estructura esperada (después de error)...";
                    $expected_headers = explode(',', $select_GroupBy);
                    $expected_headers = array_map('trim', $expected_headers);
                    $expected_headers[] = 'Cantidad';
                        if (!empty($select_SumBy)) {
                            // Extraer el nombre del campo SumBy del alias (formato: `Suma (campo)`)
                            // Buscar todos los aliases que empiecen con Suma
                            if (preg_match_all('/AS\s+[`"]?Suma\s*\(([^)]+)\)[`"]?/i', $select_SumBy, $matches)) {
                                foreach ($matches[0] as $match) {
                                    // Extraer el nombre completo del alias incluyendo paréntesis
                                    if (preg_match('/AS\s+[`"]?([^`"]+)[`"]?/i', $match, $alias_match)) {
                                        $expected_headers[] = $alias_match[1];
                                    }
                                }
                                $debug_info[] = "Campos SumBy detectados: " . implode(', ', array_slice($expected_headers, -count($matches[0])));
                            } elseif (preg_match_all('/AS\s+(\w+Suma_\w+)/i', $select_SumBy, $matches)) {
                                // Fallback para formato antiguo Suma_campo
                                foreach ($matches[1] as $match) {
                                    $expected_headers[] = $match;
                                }
                                $debug_info[] = "Campos SumBy detectados (formato antiguo): " . implode(', ', array_slice($expected_headers, -count($matches[1])));
                            }
                        }
                    $headers = $expected_headers;
                    $debug_info[] = "Headers esperados (de estructura): " . implode(', ', $headers);
                }
                // Intentar obtener headers de la estructura de la consulta aunque haya fallado
                if (empty($headers) && !empty($select_GroupBy)) {
                    $debug_info[] = "Intentando obtener headers de la estructura esperada...";
                    $expected_headers = explode(',', $select_GroupBy);
                    $expected_headers = array_map('trim', $expected_headers);
                    $expected_headers[] = 'Cantidad';
                    if (!empty($select_SumBy)) {
                        // Extraer el nombre del campo SumBy del alias
                        if (preg_match('/AS\s+(\w+)/i', $select_SumBy, $matches)) {
                            $expected_headers[] = $matches[1];
                        }
                    }
                    $headers = $expected_headers;
                    $debug_info[] = "Headers esperados (de estructura): " . implode(', ', $headers);
                }
            }
        }
    } catch (mysqli_sql_exception $e) {
        // Capture error and store it in the msg_error variable
        $msg_error = $e->getMessage();
        $data = []; // If there's an error, return empty data
        
        if (isset($GLOBALS['debug_info'])) {
            $debug_info = &$GLOBALS['debug_info'];
            $debug_info[] = "EXCEPCIÓN SQL: " . $msg_error;
            $debug_info[] = "Query que causó la excepción: " . $query;
        }
    } catch (Exception $e) {
        // Capturar cualquier otro error
        $msg_error = $e->getMessage();
        $data = [];
        
        if (isset($GLOBALS['debug_info'])) {
            $debug_info = &$GLOBALS['debug_info'];
            $debug_info[] = "EXCEPCIÓN: " . $msg_error;
        }
    }

    // Get total rows
    // Si hay GroupBy, contar los grupos únicos en lugar de todas las filas
    if ($array_groupby && !empty($GroupBy) && !empty($select_GroupBy)) {
        // Contar grupos únicos
        $select_GroupBy_clean = rtrim($select_GroupBy, ',');
        $query_totalrows = "SELECT COUNT(1) AS TOTAL_ROWS FROM (SELECT " . $select_GroupBy_clean . " FROM (" . $Query . ")tb " . $query_where . " " . $GroupBy . ") AS grouped";
        
        if (isset($GLOBALS['debug_info'])) {
            $debug_info = &$GLOBALS['debug_info'];
            $debug_info[] = "=== Contando grupos únicos ===";
            $debug_info[] = "Query de conteo: " . $query_totalrows;
        }
    } else {
        // Contar todas las filas (sin GroupBy)
        $query_totalrows = "SELECT COUNT(1) AS TOTAL_ROWS FROM (" . $Query . ") AS tb " . $query_where;
    }
    
    try {
        $countStmt = $conn->prepare($query_totalrows);
        if ($countStmt === false) {
            // If prepare failed, capture the error
            $msg_error = $conn->error;
            $total_rows = 0;
            
            if (isset($GLOBALS['debug_info'])) {
                $debug_info = &$GLOBALS['debug_info'];
                $debug_info[] = "ERROR al preparar consulta de conteo: " . $msg_error;
            }
        } else {
            $countStmt->execute();
            $result = $countStmt->get_result();

            $totalRowsResult = $result->fetch_assoc();
            $total_rows = $totalRowsResult['TOTAL_ROWS'];
            $countStmt->close();
            
            if (isset($GLOBALS['debug_info'])) {
                $debug_info = &$GLOBALS['debug_info'];
                $debug_info[] = "Total de " . ($array_groupby && !empty($GroupBy) ? "grupos" : "filas") . " encontrados: " . $total_rows;
            }
        }
    } catch (mysqli_sql_exception $e) {
        // Capture error and store it in the msg_error variable
        $msg_error = $e->getMessage();
        $total_rows = 0; // If there's an error, set total_rows to 0
        
        if (isset($GLOBALS['debug_info'])) {
            $debug_info = &$GLOBALS['debug_info'];
            $debug_info[] = "EXCEPCIÓN en conteo: " . $msg_error;
        }
    }

    // Calculate total pages
    $page_rows = count($data);
    $total_pages = 0; // Default value if $page_rows is 0
    if ($page_rows > 0) {
        $total_pages = $total_rows / $page_rows;
    }

    $info = array(
        'page_rows' => $page_rows,
        'total_pages' => $total_pages,
        'total_rows' => $total_rows
    );

    $conn->close();

    // Return array with data, headers, info, and error message
    return array(
        'info' => $info,
        'headers' => $headers,
        'data' => $data,
        'error' => $msg_error // Include error message in the return array
    );
}
?>
