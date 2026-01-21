<?php
function class_queryMysqli($ConnectionId, $Query, $ArrayFilter, $array_groupby, $Limit, $start = null, $length = null) {
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
        $GroupBy = null;
        $select_GroupBy = null;
        foreach ($array_groupby as $key_groupby => $row_groupby) {

            // group by
            if ($i == 0) {
                $GroupBy .= "GROUP BY " . $row_groupby['value'];
            } else {
                $GroupBy .= "," . $row_groupby['value'];
            }

            // select
            $select_GroupBy .= $row_groupby['value'] . ",";

            $i++;
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
    if ($array_groupby) {
        $query = "SELECT tb." . $select_GroupBy . " COUNT(1) AS Cantidad FROM (" . $Query . ")tb " . $query_where . " " . $GroupBy . " ORDER BY Cantidad DESC" . " " . $query_limit;
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
        // Execute the main query
        $result = $conn->query($query);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $headers = array_keys($row);
                $data[] = $row;
            }
            $result->free();
        } else {
            // Si la consulta falla, capturar el error
            $msg_error = $conn->error;
            $data = [];
        }
    } catch (mysqli_sql_exception $e) {
        // Capture error and store it in the msg_error variable
        $msg_error = $e->getMessage();
        $data = []; // If there's an error, return empty data
    } catch (Exception $e) {
        // Capturar cualquier otro error
        $msg_error = $e->getMessage();
        $data = [];
    }

    // Get total rows
    $query_totalrows = "SELECT COUNT(1) AS TOTAL_ROWS FROM (" . $Query . ") AS tb " . $query_where;
    try {
        $countStmt = $conn->prepare($query_totalrows);
        if ($countStmt === false) {
            // If prepare failed, capture the error
            $msg_error = $conn->error;
            $total_rows = 0;
        } else {
            $countStmt->execute();
            $result = $countStmt->get_result();

            $totalRowsResult = $result->fetch_assoc();
            $total_rows = $totalRowsResult['TOTAL_ROWS'];
            $countStmt->close();
        }
    } catch (mysqli_sql_exception $e) {
        // Capture error and store it in the msg_error variable
        $msg_error = $e->getMessage();
        $total_rows = 0; // If there's an error, set total_rows to 0
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
