<?php
function class_exportExcel($array_headers, $array_data, $title, $tmp_path, $download_redirect) {

    $date_file = date("YmdHis");
    $report_file  = "reporte - ".$title." - ".$date_file;

    // Crear nuevo objeto PHPExcel
    $objPHPExcel = new PHPExcel();
    $sheet = $objPHPExcel->getActiveSheet();
    $sheet->setTitle("reporte");

    // Título en la celda A1
    $sheet->setCellValue('A1', $title);
    $titleStyle = array(
        'font' => array('bold' => true, 'size' => 20)
    );
    $sheet->getStyle('A1')->applyFromArray($titleStyle);

    // Fecha y hora de generación en la celda A2
    $generated = "Generado el ".date("Y-m-d H:i:s");
    $sheet->setCellValue('A2', $generated);

    // Verificar si hay datos
    if (empty($array_data)) {
        // Si no hay datos, mostrar mensaje en A4
        $msg_data = "No hay registros para este reporte en este periodo";
        $sheet->setCellValue('A4', $msg_data);
        $messageStyle = array(
            'font' => array('bold' => true, 'color' => array('rgb' => 'FF0000')),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            )
        );
        $sheet->getStyle('A4')->applyFromArray($messageStyle);
    } else {
        // Insertar encabezados en la fila 4
        $sheet->fromArray(array($array_headers), NULL, 'A4');

        // Aplicar estilo a las cabeceras
        $headerColumnCount = count($array_headers);
        $highestColumn = PHPExcel_Cell::stringFromColumnIndex($headerColumnCount - 1);
        $headerRange = 'A4:' . $highestColumn . '4';

        $keysStyle = array(
            'font' => array('bold' => true),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            )
        );
        $sheet->getStyle($headerRange)->applyFromArray($keysStyle);

        // Insertar datos en la fila 5
        $rowIndex = 5;
        foreach ($array_data as $row) {
            $colIndex = 0;
            foreach ($row as $cellValue) {
                $sheet->setCellValueExplicitByColumnAndRow(
                    $colIndex, 
                    $rowIndex, 
                    (string)$cellValue, 
                    PHPExcel_Cell_DataType::TYPE_STRING
                );
                $colIndex++;
            }
            $rowIndex++;
        }
    }

    // Ajustar automáticamente el ancho de las columnas
    foreach (range('A', $sheet->getHighestColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Guardar el archivo en formato Excel 2007
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $ruta = $tmp_path . $report_file . ".xlsx";
    $objWriter->save($ruta);

    // Redirigir o devolver información del archivo
    if ($download_redirect) {
        header("Location: " . $ruta);
    } else {
        return array(
            'title' => $title,
            'filename' => $report_file . ".xlsx",
            'path' => $tmp_path,
            'filepath' => $ruta
        );
    }
}
?>
