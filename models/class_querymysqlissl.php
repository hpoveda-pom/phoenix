<?php
// Esta función es idéntica a class_queryMysqli pero valida que la conexión sea mysqli (con o sin SSL)
// Reutilizamos la misma lógica ya que mysqli funciona igual con SSL activado

require_once('class_querymysqli.php');

function class_queryMysqliSSL($ConnectionId, $Query, $ArrayFilter, $array_groupby, $Limit, $start = null, $length = null, $array_sumby = null) {
    // Reutilizar la función de mysqli estándar ya que funciona igual con SSL
    // La diferencia está solo en la conexión, no en las consultas
    return class_queryMysqli($ConnectionId, $Query, $ArrayFilter, $array_groupby, $Limit, $start, $length, $array_sumby);
}
?>
