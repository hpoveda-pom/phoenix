<?php
// Función para eliminar el elemento específico
function class_filterRemove(&$array, $keyToRemove, $valueToRemove) {
    if (isset($array['filter_selected'])) {
        foreach ($array['filter_selected'] as &$filter) {
            if (isset($filter['filter'][$keyToRemove]) && $filter['filter'][$keyToRemove] == $valueToRemove) {
                unset($filter['filter'][$keyToRemove]);
            }
        }
    }
    if (isset($array['groupby_selected'])) {
        foreach ($array['groupby_selected'] as &$groupby) {
            if (isset($groupby['GroupBy']) && $groupby['GroupBy'] == $valueToRemove) {
                unset($groupby['GroupBy']);
            }
        }
    }
    if (isset($array['sumby_selected'])) {
        foreach ($array['sumby_selected'] as $key => &$sumby) {
            if (is_array($sumby)) {
                // Verificar si tiene 'SumBy'
                if (isset($sumby['SumBy']) && $sumby['SumBy'] == $valueToRemove) {
                    unset($array['sumby_selected'][$key]);
                    continue;
                }
                // Verificar si viene como 'field' => 'value'
                if (isset($sumby['field']) && $sumby['field'] == $valueToRemove) {
                    unset($array['sumby_selected'][$key]);
                    continue;
                }
                // Verificar si el valor está en cualquier parte del array (para compatibilidad)
                foreach ($sumby as $sumby_key => $sumby_value) {
                    if ($sumby_value == $valueToRemove && $sumby_key !== 'SumBy') {
                        unset($array['sumby_selected'][$key]);
                        break;
                    }
                }
            }
        }
        // Reindexar el array después de eliminar elementos y filtrar arrays vacíos
        $array['sumby_selected'] = array_values(array_filter($array['sumby_selected'], function($item) {
            return !empty($item) && is_array($item) && !empty(array_filter($item));
        }));
    }
}