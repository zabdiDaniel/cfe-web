<?php
// update_trabajador.php
session_start();
define('BASE_URL', '/');

if (!isset($_SESSION['rpe']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/app/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rpe = $_POST['rpe'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $estatus = isset($_POST['estatus']) ? (int)$_POST['estatus'] : null;
    $centroTrabajo = !empty($_POST['centroTrabajo']) ? $_POST['centroTrabajo'] : null;
    $seccionSindical = !empty($_POST['seccionSindical']) ? $_POST['seccionSindical'] : null;
    $categoria = !empty($_POST['categoria']) ? $_POST['categoria'] : null;

    // Validación básica
    if (empty($rpe) || empty($nombre) || empty($correo) || !in_array($estatus, [0, 1], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'RPE, nombre, correo y estatus son requeridos.']);
        exit();
    }

    $stmt = $conexion->prepare("UPDATE trabajadores SET nombre = ?, correo = ?, estatus = ?, centroTrabajo = ?, seccionSindical = ?, categoria = ? WHERE rpe = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error preparando la consulta: ' . $conexion->error]);
        exit();
    }

    $stmt->bind_param("ssissss", $nombre, $correo, $estatus, $centroTrabajo, $seccionSindical, $categoria, $rpe);
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Trabajador actualizado correctamente',
            'data' => [
                'rpe' => $rpe,
                'nombre' => $nombre,
                'correo' => $correo,
                'estatus' => $estatus,
                'centroTrabajo' => $centroTrabajo,
                'seccionSindical' => $seccionSindical,
                'categoria' => $categoria
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error actualizando el registro: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>