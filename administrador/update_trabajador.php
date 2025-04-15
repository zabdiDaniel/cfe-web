<?php
session_start();
define('BASE_URL', '/');

if (!isset($_SESSION['rpe']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

require_once __DIR__ . '/../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rpe = $_POST['rpe'];
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $estatus = $_POST['estatus'];
    $centroTrabajo = !empty($_POST['centroTrabajo']) ? $_POST['centroTrabajo'] : null;
    $seccionSindical = !empty($_POST['seccionSindical']) ? $_POST['seccionSindical'] : null;
    $categoria = !empty($_POST['categoria']) ? $_POST['categoria'] : null;

    $stmt = $conexion->prepare("UPDATE trabajadores SET nombre = ?, correo = ?, estatus = ?, centroTrabajo = ?, seccionSindical = ?, categoria = ? WHERE rpe = ?");
    $stmt->bind_param("ssissss", $nombre, $correo, $estatus, $centroTrabajo, $seccionSindical, $categoria, $rpe);
    $stmt->execute();

    echo "Trabajador actualizado exitosamente";
}
?>