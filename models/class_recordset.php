<?php
require_once('class_queryclickhouse.php');

function class_Recordset($ConnectionId, $Query, $Filter, $GroupBy, $Limit, $start = null, $length = null, $SumBy = null, $OrderBy = null){
	$results = null;
	
	// Obtener el tipo de conector desde la base de datos
	global $conn_phoenix, $row_config;
	$connector_type = null;
	
	// Intentar obtener el conector desde la tabla connections
	if (isset($conn_phoenix) && $conn_phoenix instanceof mysqli && !$conn_phoenix->connect_error) {
		$stmt = $conn_phoenix->prepare("SELECT Connector FROM connections WHERE ConnectionId = ? AND Status = 1");
		if ($stmt) {
			$stmt->bind_param('i', $ConnectionId);
			$stmt->execute();
			$result = $stmt->get_result();
			if ($result && $row = $result->fetch_assoc()) {
				$connector_type = isset($row['Connector']) ? strtolower(trim($row['Connector'])) : null;
			}
			$stmt->close();
		}
	}
	
	// Si no se pudo obtener el conector, usar valores por defecto según ConnectionId
	if (!$connector_type) {
		switch ($ConnectionId) {
			case '1': //phoenix
			case 1:
			case '2': //phoenix DW
			case 2:
				$connector_type = 'mysqli';
				break;
			case '3':
			case 3:
				$connector_type = 'oci';
				break;
			default:
				$connector_type = 'mysqli'; // Por defecto MySQLi
				break;
		}
	}

	// Seleccionar la función de consulta según el tipo de conector
	switch ($connector_type) {
		case 'mysqli':
		case 'mysql':
		case 'mariadb':
			$results = class_queryMysqli($ConnectionId, $Query, $Filter, $GroupBy, $Limit, $start, $length, $SumBy, $OrderBy);
			break;
		case 'mysqlissl':
			require_once('class_querymysqlissl.php');
			$results = class_queryMysqliSSL($ConnectionId, $Query, $Filter, $GroupBy, $Limit, $start, $length, $SumBy, $OrderBy);
			break;
		case 'oci':
		case 'oracle':
			$results = class_queryOci($ConnectionId, $Query, $Filter, $GroupBy, $Limit);
			break;
		case 'clickhouse':
			$results = class_queryClickHouse($ConnectionId, $Query, $Filter, $GroupBy, $Limit, $start, $length, $SumBy, $OrderBy);
			break;
		case 'sqlserver':
		case 'mssql':
			require_once('class_querysqlserver.php');
			$results = class_querySqlServer($ConnectionId, $Query, $Filter, $GroupBy, $Limit, $start, $length, $SumBy, $OrderBy);
			break;
		default:
			// Por defecto intentar con MySQLi
			$results = class_queryMysqli($ConnectionId, $Query, $Filter, $GroupBy, $Limit, $start, $length, $SumBy, $OrderBy);
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