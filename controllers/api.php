<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

set_time_limit(0);
date_default_timezone_set($row_config['time_zone']);
ini_set('memory_limit', $row_config['memory_limit']);

require_once('config.php');
require_once('functions.php');
require_once('models/class_recordset.php');
require_once('models/class_connections.php');
require_once('models/class_exportjson.php');
require_once('models/class_queryoci.php');
require_once('models/class_querymysqli.php');
require_once('models/class_connoci.php');
require_once('models/class_connmysqli.php');
require_once('models/class_tipodato.php');
require_once('models/class_namingconvention.php');
require_once('models/class_ociformat.php');



header('Content-Type: application/json');

// Datos dummy para simular la respuesta
$datos = [
    [
        "COD_PERIODO" => "202401",
        "COD_MATERIA" => "MAT101",
        "COD_PROGRAMA" => "PRG001",
        "NOM_PROGRAMA" => "Ingeniería en Sistemas",
        "NUM_IDENTIFICACION" => "12345678",
        "NOM_ESTUDIANTE" => "Juan Pérez",
        "EST_ESTUDIANTE" => "Activo",
        "TIP_ESTUDIANTE" => "Regular"
    ],
    [
        "COD_PERIODO" => "202401",
        "COD_MATERIA" => "MAT102",
        "COD_PROGRAMA" => "PRG002",
        "NOM_PROGRAMA" => "Administración de Empresas",
        "NUM_IDENTIFICACION" => "87654321",
        "NOM_ESTUDIANTE" => "María López",
        "EST_ESTUDIANTE" => "Inactivo",
        "TIP_ESTUDIANTE" => "Becado"
    ],
    [
        "COD_PERIODO" => "202402",
        "COD_MATERIA" => "MAT103",
        "COD_PROGRAMA" => "PRG003",
        "NOM_PROGRAMA" => "Contaduría Pública",
        "NUM_IDENTIFICACION" => "11223344",
        "NOM_ESTUDIANTE" => "Carlos Rodríguez",
        "EST_ESTUDIANTE" => "Activo",
        "TIP_ESTUDIANTE" => "Especial"
    ]
];

// Devuelve los datos en formato JSON
echo json_encode($datos, JSON_PRETTY_PRINT);
?>
