<?php
$host = 'localhost';
$user = 'u639443685_zabdi'; // Reemplaza con tu usuario de Hostinger
$pass = '6781LCvZ&'; // Reemplaza con tu contraseña
$db   = 'u639443685_simtlec'; // Reemplaza con tu base de datos

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
?>