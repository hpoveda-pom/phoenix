<?php 
function class_dbConvertFieldType($data_type, $data_length) {
    $data_type = strtoupper($data_type);
    
    $conversionMap = [
        'INT'       => ['type' => 'INT', 'max_length' => 11],
        'VARCHAR2'  => ['type' => 'MEDIUMTEXT', 'max_length' => null],
        'NUMBER'    => ['type' => 'DECIMAL', 'max_length' => 65],
        'CHAR'      => ['type' => 'CHAR', 'max_length' => 255],
        'CLOB'      => ['type' => 'TEXT', 'max_length' => null],
        'NCLOB'     => ['type' => 'TEXT', 'max_length' => null],
        'BLOB'      => ['type' => 'LONGBLOB', 'max_length' => null],
        'DATE'      => ['type' => 'DATETIME', 'max_length' => null],
        'TIMESTAMP' => ['type' => 'DATETIME', 'max_length' => null],
        'LONG'      => ['type' => 'TEXT', 'max_length' => null]
    ];
    
    if (!isset($conversionMap[$data_type])) {
        return ['DATA_TYPE' => $data_type, 'DATA_LENGTH' => $data_length]; //default
    }
    
    $mysqlType = $conversionMap[$data_type]['type'];
    $maxLength = $conversionMap[$data_type]['max_length'];
    
    if ($maxLength !== null) {
        $mysqlLength = min($data_length, $maxLength);
    } else {
        $mysqlLength = null;
    }
    
    return [
        'DATA_TYPE' => $mysqlType,
        'DATA_LENGTH' => $mysqlLength
    ];
}