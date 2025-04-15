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

// Configurar la respuesta como JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Recoger datos del formulario
        $activo = $_POST['activo'] ?? '';
        $inventario = $_POST['inventario'] ?? null;
        $numero_serie = $_POST['numero_serie'] ?? '';
        $version_android = $_POST['version_android'] ?? null;
        $anio_adquisicion = $_POST['anio_adquisicion'] ?? null;
        $agencia = $_POST['agencia'] ?? null;
        $proceso = $_POST['proceso'] ?? null;
        $rpe_trabajador = $_POST['rpe_trabajador'] ?? '';
        $numero_serie_chip = $_POST['numero_serie_chip'] ?? null;

        // Log para depurar datos recibidos
        error_log('Datos recibidos: ' . json_encode($_POST));

        // Validar campos requeridos
        if (empty($activo) || empty($numero_serie) || empty($rpe_trabajador)) {
            throw new Exception("Los campos requeridos (activo, número de serie, RPE trabajador) no pueden estar vacíos.");
        }

        // Preparar la consulta de actualización, excluyendo campos no editables
        $query = "UPDATE tabletas SET 
            inventario = ?, 
            numero_serie = ?, 
            version_android = ?, 
            anio_adquisicion = ?, 
            agencia = ?, 
            proceso = ?, 
            rpe_trabajador = ?, 
            numero_serie_chip = ?
            WHERE activo = ?";

        $stmt = $conexion->prepare($query);
        if (!$stmt) {
            throw new Exception("Error preparando la consulta: " . $conexion->error);
        }

        // Vincular parámetros con tipos correctos
        $stmt->bind_param(
            "sssisssss",
            $inventario,
            $numero_serie,
            $version_android,
            $anio_adquisicion,
            $agencia,
            $proceso,
            $rpe_trabajador,
            $numero_serie_chip,
            $activo
        );

        // Ejecutar la consulta
        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando la actualización: " . $stmt->error);
        }

        // Log para depurar resultado
        $affected_rows = $stmt->affected_rows;
        error_log("Filas afectadas: $affected_rows");

        $stmt->close();

        if ($affected_rows === 0) {
            throw new Exception("No se actualizó ninguna fila. Verifique que el activo '$activo' exista.");
        }
        
        // Responder con éxito
        echo json_encode([
            'success' => true,
            'message' => 'Tableta actualizada correctamente'
        ]);
        exit();

    } catch (Exception $e) {
        // Log del error
        error_log('Error en update_tablet.php: ' . $e->getMessage());
        
        // Responder con error
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit();
    }
} else {
    // Si no es POST, devolver error
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}
?>