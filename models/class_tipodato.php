<?php
function class_tipoDato($variable){
  if (is_numeric($variable)) {
      if (ctype_digit($variable) || is_int($variable + 0)) {
          $result = "entero";
      } elseif (is_float($variable + 0)) {
          $result = "decimal";
      } else {
          $result = "número";
      }
  } elseif (is_string($variable)) {
      $result = "texto";
  } elseif (is_bool($variable)) {
      $result = "booleano.";
  } elseif (is_array($variable)) {
      $result = "array.";
  } elseif (is_object($variable)) {
      $result = "objeto.";
  } elseif (is_null($variable)) {
      $result = "null.";
  } elseif (is_resource($variable)) {
      $result = "recurso.";
  } elseif (is_callable($variable)) {
      $result = "callable.";
  } else {
      $result = "desconocido";
  }

  return $result;
}