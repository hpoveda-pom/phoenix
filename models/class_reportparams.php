<?php
/**
 * Clase para procesar y normalizar parámetros de reportes
 * Centraliza la lógica compartida entre reports.php y data.php
 */
class ReportParams {
    
    /**
     * Procesa los filtros desde $_GET['Filter'] y filter_selected
     * @param array|null $Filter Parámetro Filter de $_GET
     * @param array $filter_selected Filtros ya seleccionados
     * @return array Array de filtros normalizados
     */
    public static function processFilters($Filter = null, $filter_selected = array()) {
        $array_filters = $filter_selected;
        
        // Si viene como string JSON (desde DataTables AJAX), decodificarlo
        if (is_string($array_filters)) {
            $decoded = json_decode($array_filters, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $array_filters = $decoded;
            }
        }
        
        // Convertir filter_selected del formato procesado (key, operator, value) al formato original
        if (!empty($array_filters) && is_array($array_filters)) {
            $first_item = reset($array_filters);
            if (is_array($first_item) && isset($first_item['key']) && isset($first_item['operator']) && isset($first_item['value'])) {
                // Es formato procesado (desde DataTables), convertir al formato original
                $grouped_filters = array();
                foreach ($array_filters as $filter_item) {
                    if (isset($filter_item['key']) && isset($filter_item['operator']) && isset($filter_item['value'])) {
                        $key = $filter_item['key'];
                        if (!isset($grouped_filters[$key])) {
                            $grouped_filters[$key] = array();
                        }
                        $grouped_filters[$key][] = array(
                            'operator' => $filter_item['operator'],
                            'value' => $filter_item['value']
                        );
                    }
                }
                // Convertir a formato original
                $array_filters = array();
                foreach ($grouped_filters as $key => $filters) {
                    foreach ($filters as $filter) {
                        $array_filters[] = array(
                            'filter' => array($key => $filter['value']),
                            'operator' => $filter['operator']
                        );
                    }
                }
            }
        }
        
        // Procesar Filter de la URL
        if (is_array($Filter)) {
            if (isset($Filter[0]) && is_array($Filter[0])) {
                // Array indexado: Filter[0][field], Filter[1][field], etc.
                foreach ($Filter as $filter_index => $filter_item) {
                    if (is_array($filter_item) && isset($filter_item['field']) && !empty(trim($filter_item['field']))) {
                        $filter_keyword = isset($filter_item['keyword']) ? trim($filter_item['keyword']) : '';
                        if (!empty($filter_keyword) || (isset($filter_item['operator']) && in_array(strtolower($filter_item['operator']), ['is null', 'is not null']))) {
                            $array_filters[] = array(
                                'filter' => array($filter_item['field'] => $filter_keyword),
                                'operator' => isset($filter_item['operator']) && !empty($filter_item['operator']) ? $filter_item['operator'] : '='
                            );
                        }
                    }
                }
            } elseif (isset($Filter['field']) && !empty(trim($Filter['field']))) {
                // Formato antiguo: Filter[field]
                $filter_keyword = isset($Filter['keyword']) ? trim($Filter['keyword']) : '';
                $filter_operator = isset($Filter['operator']) && !empty($Filter['operator']) ? $Filter['operator'] : '=';
                
                if (!empty($filter_keyword) || in_array(strtolower($filter_operator), ['is null', 'is not null'])) {
                    $array_filters[] = array(
                        'filter' => array($Filter['field'] => $filter_keyword),
                        'operator' => $filter_operator
                    );
                }
            }
        }
        
        // Convertir a formato filter_results
        $filter_results = array();
        if (is_array($array_filters) && !empty($array_filters)) {
            foreach ($array_filters as $key_filters => $row_filters) {
                if (is_array($row_filters)) {
                    foreach ($row_filters['filter'] as $filter_key => $filter_value) {
                        $filter_results[] = array(
                            'key' => $filter_key,
                            'operator' => $row_filters['operator'],
                            'value' => $filter_value,
                        );
                    }
                }
            }
        }
        
        return $filter_results;
    }
    
    /**
     * Procesa los parámetros de GroupBy
     * @param array|null $GroupBy Parámetro GroupBy de $_GET
     * @param array $groupby_selected GroupBy ya seleccionados
     * @return array Array de GroupBy normalizados
     */
    public static function processGroupBy($GroupBy = null, $groupby_selected = array()) {
        $array_groupby = $groupby_selected;
        
        if (is_array($GroupBy)) {
            if (isset($GroupBy['field']) && !empty($GroupBy['field'])) {
                $array_groupby[] = array(
                    'GroupBy' => $GroupBy['field'],
                );
            }
        }
        
        $groupby_results = array();
        if (is_array($array_groupby) && !empty($array_groupby)) {
            foreach ($array_groupby as $key_groupby => $row_groupby) {
                if (is_array($row_groupby)) {
                    if (isset($row_groupby['GroupBy']) && !empty($row_groupby['GroupBy'])) {
                        // Validar que no sea 'field' literalmente
                        $field_name = trim($row_groupby['GroupBy']);
                        if ($field_name !== 'field' && $field_name !== 'GroupBy') {
                            $groupby_results[] = array(
                                'key' => 'field',  // Esto es solo metadata, no se usa en SQL
                                'value' => $field_name,  // Este es el nombre real del campo que se usa en SQL
                            );
                        }
                    } else {
                        foreach ($row_groupby as $groupby_key => $groupby_value) {
                            // Solo agregar si no es 'GroupBy' o si el valor no está vacío
                            // Y asegurarse de que el valor no sea 'field' literalmente
                            if ($groupby_key !== 'GroupBy' && !empty($groupby_value) && $groupby_value !== 'field') {
                                $groupby_results[] = array(
                                    'key' => $groupby_key,
                                    'value' => $groupby_value,
                                );
                            }
                        }
                    }
                }
            }
        }
        
        return $groupby_results;
    }
    
    /**
     * Procesa los parámetros de SumBy
     * @param array|null $SumBy Parámetro SumBy de $_GET
     * @param array $sumby_selected SumBy ya seleccionados
     * @return array Array de SumBy normalizados
     */
    public static function processSumBy($SumBy = null, $sumby_selected = array()) {
        $array_sumby = $sumby_selected;
        
        if (is_array($SumBy)) {
            if (isset($SumBy['field']) && !empty($SumBy['field'])) {
                $array_sumby[] = array(
                    'SumBy' => $SumBy['field'],
                );
            }
        }
        
        $sumby_results = array();
        if (is_array($array_sumby) && !empty($array_sumby)) {
            foreach ($array_sumby as $key_sumby => $row_sumby) {
                if (is_array($row_sumby)) {
                    if (isset($row_sumby['SumBy']) && !empty($row_sumby['SumBy'])) {
                        $sumby_results[] = array(
                            'key' => 'field',
                            'value' => $row_sumby['SumBy'],
                        );
                    } else {
                        foreach ($row_sumby as $sumby_key => $sumby_value) {
                            if ($sumby_key !== 'SumBy' || !empty($sumby_value)) {
                                $sumby_results[] = array(
                                    'key' => $sumby_key,
                                    'value' => $sumby_value,
                                );
                            }
                        }
                    }
                }
            }
        }
        
        return $sumby_results;
    }
    
    /**
     * Procesa los parámetros de OrderBy
     * @param array|null $OrderBy Parámetro OrderBy de $_GET
     * @param array $orderby_selected OrderBy ya seleccionados
     * @return array Array de OrderBy normalizados
     */
    public static function processOrderBy($OrderBy = null, $orderby_selected = array()) {
        $array_orderby = $orderby_selected;
        
        if (is_array($OrderBy)) {
            if (isset($OrderBy['field']) && !empty($OrderBy['field'])) {
                $array_orderby[] = array(
                    'OrderBy' => array($OrderBy['field'] => $OrderBy['operator']),
                );
            }
        }
        
        $orderby_results = array();
        if (is_array($array_orderby) && !empty($array_orderby)) {
            foreach ($array_orderby as $key_orderby => $row_orderby) {
                if (is_array($row_orderby)) {
                    foreach ($row_orderby['OrderBy'] as $orderby_key => $orderby_value) {
                        $orderby_results[] = array(
                            'key' => $orderby_key,
                            'value' => $orderby_value,
                        );
                    }
                }
            }
        }
        
        return $orderby_results;
    }
    
    /**
     * Obtiene la información del reporte desde la base de datos
     * @param int $ReportsId ID del reporte
     * @param bool $include_pipeline Si incluir información de pipelines
     * @return array|null Información del reporte o null si no existe
     */
    public static function getReportInfo($ReportsId, $include_pipeline = false) {
        if (!$ReportsId) {
            return null;
        }
        
        // Cache en sesión para evitar consultas duplicadas
        $cache_key = 'report_info_' . $ReportsId . '_' . ($include_pipeline ? '1' : '0');
        
        if (isset($_SESSION[$cache_key])) {
            return $_SESSION[$cache_key];
        }
        
        $query_reports_info = "
        SELECT a.*, b.Title AS Category, c.FullName, d.Connector AS conn_connector, d.Schema AS conn_schema, d.Title AS conn_title";
        
        if ($include_pipeline) {
            $query_reports_info .= ", e.ConnSource, e.TableSource, e.Status AS PipelineStatus, e.SchemaCreate, e.TableCreate, e.TableTruncate, e.TimeStamp, e.RecordsAlert";
        } else {
            $query_reports_info .= ",
            CASE
            WHEN e.LastExecution IS NOT NULL THEN e.LastExecution
            WHEN f.LastExecution IS NOT NULL THEN f.LastExecution
            ELSE NULL
            END AS LastExecution,
            CASE
            WHEN e.SyncStatus IS NOT NULL THEN e.SyncStatus
            WHEN f.SyncStatus IS NOT NULL THEN f.SyncStatus
            ELSE NULL
            END AS SyncStatus,
            g.FullName AS UserUpdatedName";
        }
        
        $query_reports_info .= "
        FROM reports a
        INNER JOIN category b ON b.CategoryId = a.CategoryId 
        INNER JOIN users c ON c.UsersId = a.UsersId 
        INNER JOIN connections d ON d.ConnectionId = a.ConnectionId";
        
        if ($include_pipeline) {
            $query_reports_info .= "
            LEFT JOIN pipelines e ON e.ReportsId = a.ReportsId";
        } else {
            $query_reports_info .= "
            LEFT JOIN pipelines e ON e.ReportsId = a.ReportsId
            LEFT JOIN pipelines f ON f.PipelinesId = a.PipelinesId
            LEFT JOIN users g ON g.UsersId = a.UserUpdated";
        }
        
        $query_reports_info .= "
        WHERE a.ReportsId = " . intval($ReportsId);
        
        if ($include_pipeline) {
            $query_reports_info .= " AND a.Status = 1 AND b.Status = 1";
        }
        
        $query_reports_info .= " ORDER BY `Order` ASC";
        
        $reports_info = class_Recordset(1, $query_reports_info, null, null, 1);
        
        if (isset($reports_info['error']) && !empty($reports_info['error'])) {
            return array('error' => $reports_info['error']);
        }
        
        if (isset($reports_info['info']['total_rows']) && $reports_info['info']['total_rows'] > 0 && isset($reports_info['data'][0])) {
            $result = $reports_info['data'][0];
            // Guardar en cache
            $_SESSION[$cache_key] = $result;
            return $result;
        }
        
        return null;
    }
    
    /**
     * Procesa todos los parámetros de una vez
     * @param array $params Array con Filter, GroupBy, SumBy, OrderBy, etc.
     * @return array Array con todos los parámetros procesados
     */
    public static function processAll($params = array()) {
        $Filter = isset($params['Filter']) ? $params['Filter'] : null;
        $filter_selected = isset($params['filter_selected']) ? $params['filter_selected'] : array();
        $GroupBy = isset($params['GroupBy']) ? $params['GroupBy'] : null;
        $groupby_selected = isset($params['groupby_selected']) ? $params['groupby_selected'] : array();
        $SumBy = isset($params['SumBy']) ? $params['SumBy'] : null;
        $sumby_selected = isset($params['sumby_selected']) ? $params['sumby_selected'] : array();
        $OrderBy = isset($params['OrderBy']) ? $params['OrderBy'] : null;
        $orderby_selected = isset($params['orderby_selected']) ? $params['orderby_selected'] : array();
        
        return array(
            'filter_results' => self::processFilters($Filter, $filter_selected),
            'groupby_results' => self::processGroupBy($GroupBy, $groupby_selected),
            'sumby_results' => self::processSumBy($SumBy, $sumby_selected),
            'orderby_results' => self::processOrderBy($OrderBy, $orderby_selected)
        );
    }
    
    /**
     * Limpia el cache de información de reportes
     * Útil cuando se actualiza un reporte
     */
    public static function clearCache($ReportsId = null) {
        if ($ReportsId) {
            unset($_SESSION['report_info_' . $ReportsId . '_0']);
            unset($_SESSION['report_info_' . $ReportsId . '_1']);
        } else {
            // Limpiar todo el cache de reportes
            foreach ($_SESSION as $key => $value) {
                if (strpos($key, 'report_info_') === 0) {
                    unset($_SESSION[$key]);
                }
            }
        }
    }
}
?>
