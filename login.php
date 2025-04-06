<?php
session_start();
require __DIR__ . '/app/config/database.php';

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rpe = $_POST['rpe'];
    $contrasena = $_POST['contrasena'];

    $sql = "SELECT * FROM registradores WHERE rpe = '$rpe' AND contrasena = '$contrasena'";
    $resultado = $conexion->query($sql);

    if ($resultado->num_rows > 0) {
        $_SESSION['rpe'] = $rpe;
        header("Location: /cfe-web/dashboard.php");
        exit;
    } else {
        $_SESSION['error'] = "Credenciales incorrectas";
    }
}

// Mostrar vista
require __DIR__ . '/views/layouts/header.php';
require __DIR__ . '/views/auth/login.php';
require __DIR__ . '/views/layouts/footer.php';
?>