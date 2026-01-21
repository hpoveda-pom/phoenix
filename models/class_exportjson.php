<?php 
function class_exportJSON($array_data, $title, $output){

    $json_results = json_encode($array_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $file_path = 'data/'.$title.'.json';

    file_put_contents($file_path, $json_results);

    if (file_exists($file_path)) {

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $base_url = $protocol . '://' . $host;


       $array_fault = array(
        'fault_code'    => 0,
        'date_time'     => date("Y-m-d H:i:s"),
        'fault_msg'     => "El archivo JSON se ha generado y guardado en el servidor en la ruta: " . $base_url.'/'.$file_path
        );
   } else {
       $array_fault = array(
        'fault_code'    => 1,
        'date_time'     => date("Y-m-d H:i:s"),
        'fault_msg'     => "Error al generar el archivo JSON."
        );
   }

   //output
    $results = $array_fault;
    if ($output) {
        header('Content-Type: application/json');
        $results = json_encode($array_data, JSON_PRETTY_PRINT);
        echo $results;
        exit;
    }else{
        echo "[".$array_fault['date_time']."]"."[".$array_fault['fault_code']."]"."[".$array_fault['fault_msg']."]"."\n";
    }
}
?>
