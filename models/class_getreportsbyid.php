<?php
function class_getReportsById($Id, $array_filter, $array_groupby, $limit){

	if ($Id) {

		//get info by id
		$conn_id 	= 1;
		$query 		= "SELECT a.*, b.Title AS Category FROM reports a INNER JOIN category b ON b.CategoryId = a.CategoryId WHERE a.Status = 1 AND b.Status = 1 AND a.ReportsId = ".$Id;
		$recordset 	= class_Recordset($conn_id, $query, null, null, 1);
		$row_info 	= $recordset['data'][0];

		//get data from info
		$array_headers = class_Recordset($row_info['ConnectionId'], $row_info['Query'], null, null, 1);
		$array_reports = class_Recordset($row_info['ConnectionId'], $row_info['Query'], $array_filter, $array_groupby, $limit);

		$array_results = array(
			'cod_fault' => 0, 'fault' => null,
			'keys' 		=> $array_headers['headers'],
			'headers' 	=> $array_reports['headers'],
			'data' 		=> $array_reports['data']
		);

	}else{
		$array_results = array(
			'cod_fault' =>1, 'fault' => 'Reporte Inválido',
			'keys' 		=> null,
			'headers' 	=> null,
			'data' 		=> null
		);
	}

	return $array_results;
}