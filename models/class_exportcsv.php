<?php
function class_exportCSV($array_headers, $array_data, $title, $tmp_path, $download_redirect, $date_status = true, $prefix_status = true, $head = true) {

    $date_file = null;
    if ($date_status) {
       $date_file = " - ".date("YmdHis");
    }

    $prefix_title = null;
    if ($prefix_status) {
       $prefix_title = "reporte - ";
    }
    
    $report_file  = $prefix_title.$title.$date_file.".csv";
    $ruta = $tmp_path . $report_file;

    // Verificar si el directorio existe, si no, crearlo
    $tmp_path;
    if (!file_exists($tmp_path)) {
        mkdir($tmp_path, 0777, true);
    }

    // Abrir archivo en modo escritura
    $file = fopen($ruta, 'w');

    // Verificar si fopen fue exitoso
    if ($file === false) {
        die("Error: No se pudo abrir el archivo para escritura.");
    }

    // Título y fecha
    if ($head) {
        fputcsv($file, [$title]);
        fputcsv($file, ["Generado el " . date("Y-m-d H:i:s")]);
        fputcsv($file, []); // Línea vacía para separación
    }

    // Escribir cabeceras
    fputcsv($file, $array_headers);

    // Escribir datos
    foreach ($array_data as $row) {
        fputcsv($file, $row);
    }

    // Cerrar archivo
    fclose($file);

    // Redirigir al archivo generado
    if ($download_redirect) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . basename($ruta) . '"');
        readfile($ruta);
        exit;
    } else {
        return [
            'title' => $title,
            'filename' => $report_file,
            'path' => $tmp_path,
            'filepath' => $ruta
        ];
    }
}
?>
