<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rpe = $_POST['rpe'];
    $contrasena = $_POST['contrasena'];

    // Consulta directa temporal (luego lo moveremos al modelo)
    $sql = "SELECT * FROM registradores WHERE rpe = '$rpe' AND contrasena = '$contrasena'";
    $resultado = $conexion->query($sql);

    if ($resultado->num_rows > 0) {
        $_SESSION['rpe'] = $rpe;
        header("Location: /cfe-web/dashboard.php"); // Redirigir al dashboard (lo crearemos después)
        exit;
    } else {
        $_SESSION['error'] = "Credenciales incorrectas";
        header("Location: /cfe-web/login.php");
        exit;
    }
}
?>