<?php
require_once('header.php');

// Si hay un Id, mostrar el dashboard
if (isset($_GET['Id']) && !isset($_GET['action'])) {
    require_once('controllers/dashboard_view.php');
    require_once('views/dashboard_view.php');
} else {
    // Si no hay Id, usar el CRUD de dashboards
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
}

require_once('footer.php');
?>
