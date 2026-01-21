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
}