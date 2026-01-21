<?php
function class_Recordset($ConnectionId, $Query, $Filter, $GroupBy, $Limit){
	$results = null;

	switch ($ConnectionId) {
		case '1': //phoenix
		case 1:
			$results = class_queryMysqli($ConnectionId, $Query, $Filter, $GroupBy, $Limit);
			break;
		case '2': //phoenix DW
		case 2:
			$results = class_queryMysqli($ConnectionId, $Query, $Filter, $GroupBy, $Limit);
			break;
		case '3':
		case 3:
			$results = class_queryOci($ConnectionId, $Query, $Filter, $GroupBy, $Limit);
			break;
		default:
			// Si no coincide con ningún caso, intentar con MySQLi por defecto
			$results = class_queryMysqli($ConnectionId, $Query, $Filter, $GroupBy, $Limit);
			break;
	}

	// Si results es null o false, devolver estructura vacía
	if (!$results || !is_array($results)) {
		$results = [
			'info' => ['total_rows' => 0],
			'headers' => [],
			'data' => [],
			'msg_error' => isset($results['msg_error']) ? $results['msg_error'] : 'Error al ejecutar la consulta'
		];
	}

	//array output
	return $results;

}