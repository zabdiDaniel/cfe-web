<?php
session_start();
define('BASE_URL', '/');

if (!isset($_SESSION['rpe']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rpe = $_POST['rpe'];
    $nombre = $_POST['nombre'];
    $correo = !empty($_POST['correo']) ? $_POST['correo'] : null;
    $tipo_usuario = $_POST['tipo_usuario'];

    $stmt = $conexion->prepare("UPDATE registradores SET nombre = ?, correo = ?, tipo_usuario = ? WHERE rpe = ?");
    $stmt->bind_param("ssss", $nombre, $correo, $tipo_usuario, $rpe);
    $stmt->execute();

    echo "Registro actualizado";
}
?>