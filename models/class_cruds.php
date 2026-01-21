<?php

function class_cruds($action){

	global $row_reports_info;

	switch ($action) {
		case 'add':
			require_once('views/cruds_add.php');
			break;
		case 'edit':
			require_once('views/cruds_edit.php');
			break;
		case 'delete':
			require_once('views/cruds_delete.php');
			break;
		case 'copy':
			require_once('views/cruds_copy.php');
			break;
		case 'share':
			require_once('views/cruds_share.php');
			break;
		case 'rollback':
			require_once('views/cruds_rollback.php');
			break;
		default:
			echo "No hay CRUDS seleccionados";
			break;
	}

}