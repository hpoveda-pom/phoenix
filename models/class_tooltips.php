<?php 
function getFieldTooltips($FieldName, $ReportsId){


	$qry = "
	SELECT a.Comments
	FROM conventions a
	
	WHERE a.Status = 1 AND a.FieldName = '".$FieldName."'";

	$array = class_Recordset(1, $qry, null, null, null);

	$comments = null;
	if ($array['data']) {
		$comments .= "<br><br>";
		$comments .= "<ul>";
		foreach ($array['data'] as $key => $row) {
			$comments .= "<li>".$row['Comments']."</li>";
		}
		$comments .= "</ul>";
	}

	$array_results = array(
		'comments' => $comments,
	);

	return $array_results;
}