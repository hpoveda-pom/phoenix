<?php
$lastUpdated = null;
$currentTimestamp = time(); // Obtener el timestamp actual
if ($row_reports_info['LastUpdated']) {
  $lastUpdated = strtotime($row_reports_info['LastUpdated']); // Convertir a timestamp
}
?>
<div class="d-flex flex-wrap align-items-center">
  <nav aria-label="breadcrumb" class="w-100 w-md-auto">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="#">Reportes</a></li>
      <li class="breadcrumb-item"><a href="#"><?php echo $row_reports_info['Category']; ?></a></li>
      <li class="breadcrumb-item active" aria-current="page">
        <?php echo $row_reports_info['ReportsId']; ?>. <?php echo $row_reports_info['Title']; ?>
      </li>
    </ol>
  </nav>

  <?php if ($row_reports_info['SyncStatus']==2) { ?>
          <button class="btn p-0 m-1 d-md-block" 
          data-bs-toggle="tooltip" 
          data-bs-placement="top" 
          title="<b>Sincronizando: </b> Los datos del reporte están siendo actualizados. Los nuevos resultados estarán disponibles en breve. Los datos mostrados actualmente son confiables."
          data-bs-html="true">
          <i class="fas fa-sync fa-spin text-secondary-light fs-7"></i>
        </button>
  <?php }elseif (($currentTimestamp - $lastUpdated) <= 900) { // muestra advertencia si el reporte ha sido modificado recientemente?>

    <button class="btn p-0 m-1 d-md-block" 
    data-bs-toggle="tooltip" 
    data-bs-placement="top" 
    title="<b>Adevertencia: </b> Este reporte está actualmente en mantenimiento, por lo que los datos mostrados podrían no ser precisos. Por favor, espere a que el mantenimiento finalice antes de tomar decisiones basadas en esta información." 
    data-bs-html="true">
    <i class="fas fa-exclamation-triangle text-warning fs-7"></i>
  </button>

  <?php }elseif ($row_reports_info['LastUpdated'] == 2) { ?>

    <button class="btn p-0 m-1 d-md-block" 
    data-bs-toggle="tooltip" 
    data-bs-placement="top" 
    title="<b>Adevertencia: </b> Este reporte está actualmente en mantenimiento, por lo que los datos mostrados podrían no ser precisos. Por favor, espere a que el mantenimiento finalice antes de tomar decisiones basadas en esta información." 
    data-bs-html="true">
    <i class="fas fa-exclamation-triangle text-warning fs-7"></i>
  </button>

<?php }elseif($row_reports_info['Description']){ ?>

  <button class="btn p-0 m-1 d-md-block" 
  data-bs-toggle="tooltip" 
  data-bs-placement="top" 
  title="<b>Porpósito</b>: <?php echo $row_reports_info['Description']; ?> <b>Dueño: </b><?php echo $row_reports_info['FullName']; ?>" 
  data-bs-html="true">
  <i class="fas fa-info-circle text-primary fs-7"></i>
  </button>

<?php } ?>

</div>
