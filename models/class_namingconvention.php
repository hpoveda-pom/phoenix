<?php 
function class_namingConvention($title, $convention = 'snake_case') {

    // Eliminar espacios en blanco al inicio y al final
    $title = trim($title);
    
    // Modificar el patrón para buscar el ID opcional al inicio del título
    preg_match('/^(\d+)?\.?\s*(.*)$/', $title, $matches);
    
    // Obtener el ID (si existe) y el título limpio
    $id = isset($matches[1]) ? $matches[1] : '';
    $cleanTitle = isset($matches[2]) ? $matches[2] : $title;

    // Eliminar puntos, tildes y caracteres especiales
    $cleanTitle = preg_replace('/[^\w\s]/u', '', $cleanTitle); // Eliminar caracteres no alfanuméricos y espacios
    $cleanTitle = preg_replace('/\s+/', ' ', $cleanTitle); // Reemplazar múltiples espacios por uno solo
    $cleanTitle = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'], 
        ['a', 'e', 'i', 'o', 'u', 'n', 'A', 'E', 'I', 'O', 'U', 'N'], 
        $cleanTitle
    ); // Reemplazar tildes y ñ

    // Convertir a minúsculas y reemplazar espacios según la convención
    switch ($convention) {
        case 'snake_case':
            // Solo añadir el ID si está presente
            return strtolower(($id ? $id . '_' : '') . str_replace(' ', '_', $cleanTitle));
        case 'kebab-case':
            return strtolower(($id ? $id . '-' : '') . str_replace(' ', '-', $cleanTitle));
        case 'PascalCase':
            return $id . str_replace(' ', '', ucwords($cleanTitle));
        case 'camelCase':
            $words = explode(' ', $cleanTitle);
            return $id . strtolower($words[0]) . str_replace(' ', '', ucwords(implode(' ', array_slice($words, 1))));
        default:
            throw new InvalidArgumentException("Convención no soportada. Elige entre 'snake_case', 'kebab-case', 'PascalCase' o 'camelCase'.");
    }
}