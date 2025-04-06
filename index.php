<?php
// Configuración básica
session_start();
$basePath = '/cfe-web'; // Ajusta esto si tu proyecto está en otra ruta

// Redirigir a login por defecto
header("Location: {$basePath}/login.php");
exit;
?>