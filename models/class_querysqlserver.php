<?php
require_once('class_connsqlserver.php');

function class_querySqlServer($ConnectionId, $Query, $ArrayFilter, $array_groupby, $Limit, $start = null, $length = null, $array_sumby = null, $OrderBy = null) {
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
        
        foreach ($array_groupby as $key_groupby => $row_groupby) {
            if (!is_array($row_groupby)) {
                continue;
            }
            
            $field_name = null;
            if (isset($row_groupby['value']) && !empty($row_groupby['value'])) {
                $field_name = trim($row_groupby['value']);
            } elseif (isset($row_groupby['GroupBy']) && !empty($row_groupby['GroupBy'])) {
                $field_name = trim($row_groupby['GroupBy']);
            } else {
                continue;
            }
            
            // Validar que el nombre del campo no sea 'GroupBy', 'field', o vacío
            // 'field' es el valor de 'key' (metadata), NO debe usarse como nombre de campo
            if (empty($field_name) || 
                $field_name === 'GroupBy' || 
                strtolower($field_name) === 'groupby' ||
                $field_name === 'field' ||
                strtolower($field_name) === 'field') {
                continue;
            }
            
            if ($i == 0) {
                $GroupBy = "GROUP BY " . $field_name;
            } else {
                $GroupBy .= "," . $field_name;
            }

            $select_GroupBy .= $field_name . ",";
            $i++;
        }
    }

    // Procesar SumBy
    $select_SumBy = '';
    $sumby_fields = array();
    if ($array_sumby && !empty($array_sumby)) {
        foreach ($array_sumby as $key_sumby => $row_sumby) {
            if (!is_array($row_sumby)) {
                continue;
            }
            
            $field_name = null;
            if (isset($row_sumby['value']) && !empty($row_sumby['value'])) {
                $field_name = trim($row_sumby['value']);
            } elseif (isset($row_sumby['SumBy']) && !empty($row_sumby['SumBy'])) {
                $field_name = trim($row_sumby['SumBy']);
            } else {
                continue;
            }
            
            if (empty($field_name) || $field_name === 'SumBy' || $field_name === 'field' || strtolower($field_name) === 'sumby' || strtolower($field_name) === 'field') {
                continue;
            }
            
            if (!in_array($field_name, $sumby_fields)) {
                $sumby_fields[] = $field_name;
            }
        }
        
        if (!empty($sumby_fields)) {
            $sumby_selects = array();
            foreach ($sumby_fields as $sumby_field) {
                $sumby_field_escaped = "[" . $sumby_field . "]";
                $sumby_alias = "[Suma (" . $sumby_field . ")]";
                $sumby_selects[] = "SUM(CAST(tb." . $sumby_field_escaped . " AS FLOAT)) AS " . $sumby_alias;
            }
            $select_SumBy = implode(', ', $sumby_selects);
        }
    }

    // Records per page - SQL Server usa TOP y OFFSET/FETCH
    $query_limit = '';
    if ($start !== null && $length !== null) {
        // SQL Server 2012+ usa OFFSET/FETCH
        $query_limit = "OFFSET " . intval($start) . " ROWS FETCH NEXT " . intval($length) . " ROWS ONLY";
    } elseif ($Limit) {
        $query_limit = "TOP " . intval($Limit);
    }

    // Filters
    $query_where = '';
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
                    return "'" . str_replace("'", "''", trim($value)) . "'";
                }, $values_array);
                $filter_value = "(" . implode(',', $values_with_quotes) . ")";
            }

            // BETWEEN AND
            if ($row_filter['operator'] == 'BETWEEN') {
                $values_array = preg_split('/[,\s\/\\\\]+/', $row_filter['value']);
                $values_with_quotes = array_map(function($value) {
                    return "'" . str_replace("'", "''", trim($value)) . "'";
                }, $values_array);
                if (count($values_with_quotes) >= 2) {
                    $filter_value = $values_with_quotes[0] . " AND " . $values_with_quotes[1];
                } else {
                    continue;
                }
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
                        $query_where .= "[$key] IS NULL";
                        break;
                    case 'in':
                        $query_where .= "[$key] IN " . $filter['value'];
                        break;
                    case 'not in':
                        $query_where .= "[$key] NOT IN " . $filter['value'];
                        break;
                    case 'BETWEEN':
                        $query_where .= "[$key] BETWEEN " . $filter['value'];
                        break;
                    case 'like':
                        $query_where .= "[$key] LIKE '" . str_replace("'", "''", $filter['value']) . "'";
                        break;
                    case 'not like':
                        $query_where .= "[$key] NOT LIKE '" . str_replace("'", "''", $filter['value']) . "'";
                        break;
                    default:
                        $escaped_value = str_replace("'", "''", $filter['value']);
                        $query_where .= "[$key] " . $filter['operator'] . " '" . $escaped_value . "'";
                        break;
                }

                $j++;
            }
            $query_where .= ")";
            $i++;
        }
    }

    // Construir ORDER BY
    $query_order_by = '';
    if (!empty($OrderBy)) {
        // OrderBy ya viene formateado con corchetes desde data.php para SQL Server
        $query_order_by = " ORDER BY " . $OrderBy;
    }
    
    // Helpers para manejar CTE y ORDER BY de alto nivel en SQL Server
    $splitSqlServerCte = function($sql) {
        $trimmed = ltrim($sql);
        if (!preg_match('/^;?\s*WITH\b/i', $trimmed)) {
            return null;
        }
        
        $len = strlen($trimmed);
        $depth = 0;
        $in_string = false;
        $string_char = '';
        
        for ($i = 0; $i < $len; $i++) {
            $ch = $trimmed[$i];
            
            if ($in_string) {
                if ($ch === $string_char) {
                    // Escapar comillas dobles en strings tipo ''
                    if ($string_char === "'" && $i + 1 < $len && $trimmed[$i + 1] === "'") {
                        $i++;
                        continue;
                    }
                    $in_string = false;
                }
                continue;
            }
            
            if ($ch === "'" || $ch === '"') {
                $in_string = true;
                $string_char = $ch;
                continue;
            }
            
            if ($ch === '(') {
                $depth++;
                continue;
            }
            if ($ch === ')') {
                if ($depth > 0) {
                    $depth--;
                }
                continue;
            }
            
            if ($depth === 0) {
                $rest = substr($trimmed, $i);
                if (preg_match('/^SELECT\b/i', $rest)) {
                    $cte = rtrim(substr($trimmed, 0, $i));
                    $main = ltrim(substr($trimmed, $i));
                    return [$cte, $main];
                }
            }
        }
        
        return null;
    };
    
    $stripTopLevelOrderBy = function($sql) {
        $len = strlen($sql);
        $depth = 0;
        $in_string = false;
        $string_char = '';
        $order_pos = null;
        
        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            
            if ($in_string) {
                if ($ch === $string_char) {
                    if ($string_char === "'" && $i + 1 < $len && $sql[$i + 1] === "'") {
                        $i++;
                        continue;
                    }
                    $in_string = false;
                }
                continue;
            }
            
            if ($ch === "'" || $ch === '"') {
                $in_string = true;
                $string_char = $ch;
                continue;
            }
            
            if ($ch === '(') {
                $depth++;
                continue;
            }
            if ($ch === ')') {
                if ($depth > 0) {
                    $depth--;
                }
                continue;
            }
            
            if ($depth === 0) {
                $rest = substr($sql, $i);
                if (preg_match('/^ORDER\s+BY\b/i', $rest)) {
                    $order_pos = $i;
                }
            }
        }
        
        if ($order_pos !== null) {
            return rtrim(substr($sql, 0, $order_pos));
        }
        
        return rtrim($sql);
    };
    
    $cte_parts = $splitSqlServerCte($Query);
    $cte_prefix = '';
    $main_query = $Query;
    if ($cte_parts) {
        $cte_prefix = rtrim($cte_parts[0]) . " ";
        $main_query = $cte_parts[1];
    }
    
    // Remover ORDER BY de alto nivel en subconsultas para SQL Server
    $main_query = $stripTopLevelOrderBy($main_query);
    
    // Construir query base
    $base_query = $cte_prefix . "SELECT tb.* FROM (" . $main_query . ") AS tb " . $query_where . $query_order_by;

    // Group By or Details
    if ($array_groupby && !empty($GroupBy) && !empty($select_GroupBy)) {
        $select_GroupBy = rtrim($select_GroupBy, ',');
        
        $select_fields = "tb." . str_replace(',', ', tb.', $select_GroupBy) . ", COUNT(1) AS Cantidad";
        if (!empty($select_SumBy)) {
            $select_fields .= ", " . $select_SumBy;
        }
        
        // Si hay OrderBy personalizado, usarlo; si no, usar el orden por defecto (Cantidad DESC)
        // OrderBy ya viene formateado con corchetes desde data.php para SQL Server
        $groupby_order_by = !empty($OrderBy) ? " ORDER BY " . $OrderBy : " ORDER BY Cantidad DESC";
        
        $query = $cte_prefix . "SELECT " . $select_fields . " FROM (" . $main_query . ") AS tb " . $query_where . " " . $GroupBy . $groupby_order_by;
        
        // Agregar paginación si es necesario
        if ($start !== null && $length !== null) {
            $query .= " " . $query_limit;
        } elseif ($Limit && empty($query_limit)) {
            // Si hay Limit pero no start/length, usar TOP
            $query = $cte_prefix . "SELECT TOP " . intval($Limit) . " " . $select_fields . " FROM (" . $main_query . ") AS tb " . $query_where . " " . $GroupBy . $groupby_order_by;
        }
    } else {
        // Query sin GroupBy
        if ($Limit && $start === null && $length === null) {
            // Si solo hay Limit, usar TOP
            $query = $cte_prefix . "SELECT TOP " . intval($Limit) . " tb.* FROM (" . $main_query . ") AS tb " . $query_where . $query_order_by;
        } else {
            $query = $base_query;
            if ($start !== null && $length !== null) {
                // Si no hay OrderBy, necesitamos uno para usar OFFSET/FETCH
                if (empty($query_order_by)) {
                    $query .= " ORDER BY (SELECT NULL)";
                }
                $query .= " " . $query_limit;
            }
        }
    }

    // Connect to the database
    $conn = class_Connections($ConnectionId);

    // Validar que la conexión se haya establecido correctamente
    if (!$conn || !($conn instanceof PDO)) {
        return [
            'info' => ['total_rows' => 0, 'page_rows' => 0, 'total_pages' => 0],
            'headers' => [],
            'data' => [],
            'error' => 'No se pudo establecer la conexión a SQL Server. Verifique la configuración de la conexión.'
        ];
    }

    // Initialize variables
    $info = [];
    $headers = [];
    $data = [];
    $msg_error = '';

    try {
        // Execute the main query
        $stmt = $conn->query($query);
        
        if ($stmt) {
            $headers_set = false;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!$headers_set) {
                    $headers = array_keys($row);
                    $headers_set = true;
                }
                $data[] = $row;
            }
        } else {
            $error_info = $conn->errorInfo();
            $msg_error = "Error ejecutando consulta: " . ($error_info[2] ?? 'Error desconocido');
            $data = [];
        }
    } catch (PDOException $e) {
        $msg_error = $e->getMessage();
        $data = [];
    }

    // Get total rows
    $total_rows = 0;
    if ($array_groupby && !empty($GroupBy) && !empty($select_GroupBy)) {
        $select_GroupBy_clean = rtrim($select_GroupBy, ',');
        $query_totalrows = $cte_prefix . "SELECT COUNT(*) AS TOTAL_ROWS FROM (SELECT " . $select_GroupBy_clean . " FROM (" . $main_query . ") AS tb " . $query_where . " " . $GroupBy . ") AS grouped";
    } else {
        $query_totalrows = $cte_prefix . "SELECT COUNT(*) AS TOTAL_ROWS FROM (" . $main_query . ") AS tb " . $query_where;
    }
    
    try {
        $count_stmt = $conn->query($query_totalrows);
        if ($count_stmt) {
            $count_row = $count_stmt->fetch(PDO::FETCH_ASSOC);
            $total_rows = isset($count_row['TOTAL_ROWS']) ? intval($count_row['TOTAL_ROWS']) : 0;
        }
    } catch (PDOException $e) {
        $msg_error = "Error en conteo: " . $e->getMessage();
        $total_rows = 0;
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

    // Asegurar que headers siempre sea un array
    if (!is_array($headers)) {
        $headers = [];
    }
    
    // Si no hay headers pero hay datos, obtenerlos del primer registro
    if (empty($headers) && !empty($data) && is_array($data[0])) {
        $headers = array_keys($data[0]);
    }

    // Return array with data, headers, info, and error message
    return array(
        'info' => $info,
        'headers' => $headers,
        'data' => $data,
        'error' => $msg_error
    );
}
?>
