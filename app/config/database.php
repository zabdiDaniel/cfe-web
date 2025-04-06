<?php
$host = '192.168.1.83';
$user = 'zabdi';
$pass = 'pogonyuto';
$db   = 'inventario_tabletas';

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
?>