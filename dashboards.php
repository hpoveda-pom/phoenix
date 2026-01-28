<?php
require_once('header.php');
require_once('controllers/dashboards.php');

// Determinar qué vista mostrar según la acción
switch ($action) {
    case 'edit':
        require_once('views/dashboards_form.php');
        break;
    
    case 'widgets':
        require_once('views/dashboards_widgets.php');
        break;
    
    case 'edit_widget':
        require_once('views/dashboards_widgets.php');
        break;
    
    case 'list':
    default:
        require_once('views/dashboards_list.php');
        break;
}

require_once('footer.php');
?>
