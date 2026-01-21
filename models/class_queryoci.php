<?php
function class_queryOci($ConnectionId, $Query, $ArrayFilter, $array_groupby, $Limit){

	$i = 0;
	$msg_error = ''; // Variable para almacenar el mensaje de error

	// Generación de cláusulas GROUP BY si es necesario
	if ($array_groupby) {
		$total_groupby = count($array_groupby);
		$GroupBy = null;
		$select_GroupBy = null;
		foreach ($array_groupby as $key_groupby => $row_groupby) {

			// group by
			if ($i==0) {
				$GroupBy .= "GROUP BY ".$row_groupby['value'];
			}else{
				$GroupBy .= ",".$row_groupby['value'];
			}

			// select
			$select_GroupBy .= $row_groupby['value'].",";

			$i++;
		}
	}

	// Limitar los registros si es necesario
	$query_limit = null;
	if ($Limit) {
		$query_limit = "WHERE ROWNUM <= ".$Limit;
	}

	// Filtros
	$query_where = null;
	if ($ArrayFilter) {

		$query_limit = null;
		if ($Limit) {
			$query_limit = "AND ROWNUM <= ".$Limit;
		}

		$grouped_filters = [];
		foreach ($ArrayFilter as $key_filter => $row_filter) {

		    $filter_value = $row_filter['value'];

		    // Like y not like con comodín %
		    if ($row_filter['operator'] == 'like' || $row_filter['operator'] == 'not like') {
		        $filter_value = "%" . $row_filter['value'] . "%";
		    }

			// IN y NOT IN con separador de comas
			if ($row_filter['operator'] == 'in' || $row_filter['operator'] == 'not in') {
			    $values_array = explode(',', $row_filter['value']);
			    $values_with_quotes = array_map(function($value) {
			        return "'" . $value . "'";
			    }, $values_array);
			    $filter_value = implode(',', $values_with_quotes);
			}

			// BETWEEN con AND
			if ($row_filter['operator'] == 'BETWEEN') {
			    $values_array = preg_split('/[,\s\/\\\\]+/', $row_filter['value']);
			    $values_with_quotes = array_map(function($value) {
			        return "'" . $value . "'";
			    }, $values_array);
			    $filter_value = implode(' AND ', $values_with_quotes);
			}

			// REGEXP_LIKE
			if ($row_filter['operator'] == 'REGEXP_LIKE' || $row_filter['operator'] == 'NOT REGEXP_LIKE') {
			    $values_array = explode(',', $row_filter['value']);
			    $values_with_quotes = array_map(function($value) {
			        return $value;
			    }, $values_array);
			    $filter_value = implode('|', $values_with_quotes);
			}

		    // Agrupar los filtros por clave
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
		            	$query_where .= $filter['operator'] . "(".$key.", '" . $filter['value'] . "')";
		        		break;
		        	case 'NOT REGEXP_LIKE':
		            	$query_where .= $filter['operator'] . "(".$key.", '" . $filter['value'] . "')";
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

	// Construcción de la consulta
	$query = "SELECT tb.* FROM (".$Query.")tb".$query_where." ".$query_limit;
	if($array_groupby){
		$query = "SELECT tb2.* FROM(SELECT tb.".$select_GroupBy." COUNT(1) AS Cantidad FROM (".$Query.")tb".$query_where." ".$GroupBy." ORDER BY Cantidad DESC)tb2 WHERE ROWNUM <= ".$Limit;
	}

	// Conexión y ejecución de la consulta
	$conn = class_Connections($ConnectionId);

	// Establecer el formato de fecha en la sesión
	$alterSession = oci_parse($conn, "ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
	oci_execute($alterSession);
	oci_free_statement($alterSession);

	$stmt = oci_parse($conn, $query);

	if ($conn) {
		$stmt = oci_parse($conn, $query);

		// Manejo de errores en la ejecución
		try {
		    if (!oci_execute($stmt)) {
		        $e = oci_error($stmt);
		        throw new Exception("OCI Execute Error: " . $e['message']);
		    }

		    // Definir los resultados
		    $data = [];
		    $headers = [];
		    while ($results = oci_fetch_assoc($stmt)) {
		        if ($results) {
		            $headers = array_keys($results);
		            $data[] = $results;
		        }
		    }

		} catch (Exception $e) {
		    $msg_error = $e->getMessage(); // Captura el mensaje de error
		    $data = [];
		    $headers = [];
		}

		// Total de registros
		$query_totalrows = "SELECT COUNT(1) AS TOTAL_ROWS FROM (" . $Query . ") tb" . $query_where;
		$countStmt = oci_parse($conn, $query_totalrows);
		oci_execute($countStmt);
		$totalRowsResult = oci_fetch_assoc($countStmt);

		$total_rows = $totalRowsResult['TOTAL_ROWS'];
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

		oci_free_statement($countStmt);
		oci_close($conn);

	}else{
		$info = null;
		$headers = null;
		$data = null;
		$msg_error = "Error de conexión OCI";
	}

	// Array de salida con el error si ocurre
	return array(
		'info'      => $info,
		'headers'   => $headers,
		'data'      => $data,
		'error'     => $msg_error // Agrega el mensaje de error si existe
	);

}