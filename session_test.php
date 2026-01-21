<?php 
session_start();

if (!isset($_SESSION['contador'])) {
    $_SESSION['contador'] = 1;
    echo "Sesión iniciada. ID: " . session_id() . "<br>";
} else {
    $_SESSION['contador']++;
    echo "Sesión cargada. ID: " . session_id() . "<br>";
}

echo "Contador de visitas en sesión: " . $_SESSION['contador'];