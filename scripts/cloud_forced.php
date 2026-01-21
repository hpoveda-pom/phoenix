<?php
set_time_limit(30);

require_once('../config.php');
require_once('../models/class_querymysqli.php');
require_once('../models/class_querymysqliexe.php');
require_once('../models/class_connections.php');
require_once('../models/class_connmysqli.php');
require_once('../models/class_curl.php');

$action = null;
if (isset($_REQUEST['action'])) {
	$action = $_REQUEST['action'];
}

$sleep = 5;
if (isset($_REQUEST['sleep'])) {
	$sleep = $_REQUEST['sleep'];
}

$limit = 5;
if (isset($_REQUEST['limit'])) {
	$limit = $_REQUEST['limit'];
}

$limit_rand = 5;
if (isset($_REQUEST['limit_rand'])) {
	$limit_rand = $_REQUEST['limit_rand'];
}

$date_start = null;
if (isset($_REQUEST['date_start'])) {
	$date_start = $_REQUEST['date_start'];
}

$date_end = null;
if (isset($_REQUEST['date_end'])) {
	$date_end = $_REQUEST['date_end'];
}

$date = null;
if (isset($_REQUEST['date'])) {
	$date = $_REQUEST['date'];
}

if ($action == "log") {
	$dummyArray = range(1, 1);
}else{
	$dummyArray = range(1, $limit);
}

$query_date = null;
if ($date) {
	$query_date = "AND DATE(created_at) = '$date'";
}

foreach ($dummyArray as $i) {

	//Nuevos - Prepara los nuevos datos para ser procesado en el próximo lote, omite las ordenes inválidas	
	$query_update_0 = "UPDATE cloudcam_integraciones.tivenos_no_sincronizados a INNER JOIN cloudcam_integraciones.tivenos_orden_pago b ON a.numero_referencia = b.numero_referencia SET a.actualizado = 2, a.updated_at = NULL, a.created_at = NOW(), observacion = 'inserted by cloudforced.php' WHERE id_recibo = 0 AND actualizado IS NULL";

	//Encontrado - Cuando una orden de pago se le asigna un recibo
	$query_update_1 = "UPDATE cloudcam_integraciones.tivenos_no_sincronizados SET actualizado = 1, observacion = 'success by cloudforced.php' WHERE id_recibo > 0 AND actualizado = 4";

	//No encontrado - Aisla los errores
	$query_update_2 = "UPDATE cloudcam_integraciones.tivenos_no_sincronizados SET actualizado = 3, observacion = 'errors found by cloudforced.php' WHERE id_recibo = 0 AND actualizado = 4";

	//Procesando - Determina cuales ordenes de pago se va a procesar por el API de SIS con base al date, limite y otros filtros
	$query_update_3 = "UPDATE cloudcam_integraciones.tivenos_no_sincronizados a SET a.actualizado = 4, a.tipo_documento = 'recibo', a.updated_at = NULL, observacion = 'Queue by cloudforced.php' WHERE a.id IN (SELECT id FROM (SELECT id FROM cloudcam_integraciones.tivenos_no_sincronizados WHERE id_recibo = 0 AND actualizado = 2 $query_date ORDER BY id ASC LIMIT $limit_rand) AS temp_table)";

	if ($action == "log") {
		$update_0 = 'no exec';
		$update_1 = 'no exec';
		$update_2 = 'no exec';
		$update_3 = 'no exec';
	}else{
		$update_0 = class_queryMysqliExe(3, $query_update_0);
		$update_1 = class_queryMysqliExe(3, $query_update_1);
		$update_2 = class_queryMysqliExe(3, $query_update_2);
		$update_3 = class_queryMysqliExe(3, $query_update_3);
	}

	//Reset - Cuando llega al fin para reprocesar nuevamente las ordenes que no se encontraron o dieron error.
	$update_4 = "reset no";
	if ($update_3 == "Query executed successfully. Affected rows: 0") {
		$query_update_4 = "UPDATE cloudcam_integraciones.tivenos_no_sincronizados SET actualizado = 2, tipo_documento = 'recibo', updated_at = NULL, observacion = 'reprocessing by cloudforced.php' WHERE id_recibo = 0";
		$update_4 = class_queryMysqliExe(3, $query_update_4);
	}

	//Agrega nuevas ordenes de pago para ser procesadas desde matriculas
	$update_5 = "No add new records";
	if ($update_3 == "Query executed successfully. Affected rows: 0") {
		$query_update_5 = "
		INSERT INTO cloudcam_integraciones.tivenos_no_sincronizados
		SELECT DISTINCT
			    NULL id,
			    0 id_recibo,
			    a.REF_SIS AS numero_referencia,
			    'recibo' AS tipo_documento,
			    NOW() AS created_at,
			    NULL AS updated_at,
			    NULL actualizado,
			    NULL encontrado,
			    NULL id_carrito,
			    NULL periodo,
			    NULL id_periodo,
			    'inserted by cloudforced.php' observacion
			FROM TEST_MATRICULAS a
			INNER JOIN TEST_PAGOS b ON b.ID_FACTURA = a.ID_FACTURA
			LEFT JOIN cloudcam_integraciones.tivenos_no_sincronizados e ON e.numero_referencia = a.REF_SIS
			WHERE e.numero_referencia IS NULL AND a.REF_SIS >0
      	ORDER BY b.FECHA DESC
	    LIMIT 0,100
		";
		$update_5 = class_queryMysqliExe(3, $query_update_5);
	}

	//Agrega nuevas ordenes de pago para ser procesadas desde PAGOS_SIS
	$update_6 = "No add new records";
	if ($update_3 == "Query executed successfully. Affected rows: 0") {
		$query_update_6 = "
		INSERT INTO cloudcam_integraciones.tivenos_no_sincronizados
		SELECT DISTINCT
			    NULL id,
			    0 id_recibo,
			    a.ref_orden_pago_sis AS numero_referencia,
			    'recibo' AS tipo_documento,
			    NOW() AS created_at,
			    NULL AS updated_at,
			    NULL actualizado,
			    NULL encontrado,
			    NULL id_carrito,
			    NULL periodo,
			    NULL id_periodo,
			    'inserted by cloudforced.php' observacion
			FROM TEST_PAGOS_SIS a
			LEFT JOIN cloudcam_integraciones.tivenos_no_sincronizados e ON e.numero_referencia = a.ref_orden_pago_sis
			WHERE e.numero_referencia IS NULL AND a.ref_orden_pago_sis >0
      	ORDER BY a.fecha_recibo DESC
	    LIMIT 0,100
		";
		$update_6 = class_queryMysqliExe(3, $query_update_6);
	}


	//Checker - Monitoreo / Progreso
	$query_checker = "SELECT MIN(b.fecha_recibo) AS last_payment, MAX(IF(a.updated_at,a.updated_at,a.created_at)) AS last_update, CASE a.actualizado WHEN NULL THEN 'nuevo' WHEN 4 THEN 'procesando' WHEN 5 THEN 'no encontrado' WHEN 1 THEN 'encontrado' WHEN 2 THEN 'pendiente' WHEN 3 THEN 'no encontrado' ELSE CONCAT('desconocido (',a.actualizado,')') END as estado, COUNT(1) as cantidad FROM cloudcam_integraciones.tivenos_no_sincronizados a LEFT JOIN TEST_PAGOS_SIS b ON b.ref_orden_pago_sis = a.numero_referencia WHERE 1=1 $query_date GROUP BY a.actualizado";
	$array_checker = class_queryMysqli(3, $query_checker, null, null, null);

	//Log - Debug, no procesa datos solo visualiza
	if ($action == "log") {
		$curl = "no exec";
	}else{
		$curl_url = "https://cloudcampuspro.com/modules/xxxx/masivo_sis_forced.php";
		$curl_response = class_curl($curl_url);
	}

	//Output
	$results = array(
		'date' => $date,
		'line' => $i,
		'sleep' => $sleep."s",
		//'curl - exec' => $curl_response,
		'query0 - news' => $update_0,
		'query1 - found' => $update_1,
		'query2 - updated' => $update_2,
		'query3 - rand' => $update_3,
		'query4 - reset' => $update_4,
		'query5 - add' => $update_5,
		'query6 - add' => $update_6,
		'checker' => $array_checker['data']
	);

	echo "<pre>";
	print_r($results);

} //end for each
