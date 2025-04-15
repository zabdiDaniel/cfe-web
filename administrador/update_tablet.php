<?php
// update_tablet.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
define('BASE_URL', '/');

if (!isset($_SESSION['rpe']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Recoger datos del formulario
        $activo = $_POST['activo'] ?? '';
        $marca = $_POST['marca'] ?? '';
        $modelo = $_POST['modelo'] ?? '';
        $inventario = $_POST['inventario'] ?? null;
        $numero_serie = $_POST['numero_serie'] ?? '';
        $version_android = $_POST['version_android'] ?? null;
        $anio_adquisicion = $_POST['anio_adquisicion'] ?? null;
        $agencia = $_POST['agencia'] ?? null;
        $proceso = $_POST['proceso'] ?? null;
        $rpe_trabajador = $_POST['rpe_trabajador'] ?? '';
        $ubicacion_registro = $_POST['ubicacion_registro'] ?? null;
        $fecha_registro = $_POST['fecha_registro'] ?? null;
        $marca_chip = $_POST['marca_chip'] ?? null;
        $numero_serie_chip = $_POST['numero_serie_chip'] ?? null;

        // Validar campos requeridos
        if (empty($activo) || empty($marca) || empty($modelo) || empty($numero_serie) || empty($rpe_trabajador)) {
            throw new Exception("Los campos requeridos no pueden estar vacíos.");
        }

        // Preparar la consulta de actualización
        $query = "UPDATE tabletas SET 
            marca = ?, 
            modelo = ?, 
            inventario = ?, 
            numero_serie = ?, 
            version_android = ?, 
            anio_adquisicion = ?, 
            agencia = ?, 
            proceso = ?, 
            rpe_trabajador = ?, 
            ubicacion_registro = ?, 
            fecha_registro = ?, 
            marca_chip = ?, 
            numero_serie_chip = ?
            WHERE activo = ?";

        $stmt = $conexion->prepare($query);
        if (!$stmt) {
            throw new Exception("Error preparando la consulta: " . $conexion->error);
        }

        // Vincular parámetros (null para campos opcionales)
        $stmt->bind_param(
            "sssssisssssss",
            $marca,
            $modelo,
            $inventario,
            $numero_serie,
            $version_android,
            $anio_adquisicion,
            $agencia,
            $proceso,
            $rpe_trabajador,
            $ubicacion_registro,
            $fecha_registro,
            $marca_chip,
            $numero_serie_chip,
            $activo
        );

        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando la actualización: " . $stmt->error);
        }

        $stmt->close();
        
        // Redirigir con mensaje de éxito
        header("Location: " . BASE_URL . "administrador/gestionar_tabletas.php?success=Tableta actualizada correctamente");
        exit();

    } catch (Exception $e) {
        // Redirigir con mensaje de error
        header("Location: " . BASE_URL . "administrador/gestionar_tabletas.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si no es POST, redirigir
    header("Location: " . BASE_URL . "administrador/gestionar_tabletas.php");
    exit();
}
?>