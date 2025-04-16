<?php
// update_registrador.php
session_start();
define('BASE_URL', '/');

if (!isset($_SESSION['rpe']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/app/config/database.php';

header('Content-Type: application/json'); // Establecer encabezado JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rpe = $_POST['rpe'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $correo = !empty($_POST['correo']) ? $_POST['correo'] : null;
    $tipo_usuario = $_POST['tipo_usuario'] ?? '';

    // Validación básica
    if (empty($rpe) || empty($nombre) || empty($tipo_usuario)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Todos los campos requeridos deben estar completos.']);
        exit();
    }

    // Validar tipo_usuario
    if (!in_array($tipo_usuario, ['administrador', 'usuario'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tipo de usuario inválido.']);
        exit();
    }

    $stmt = $conexion->prepare("UPDATE registradores SET nombre = ?, correo = ?, tipo_usuario = ? WHERE rpe = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error preparando la consulta: ' . $conexion->error]);
        exit();
    }

    $stmt->bind_param("ssss", $nombre, $correo, $tipo_usuario, $rpe);
    if ($stmt->execute()) {
        // Devolver los datos actualizados
        echo json_encode([
            'success' => true,
            'message' => 'Registrador actualizado correctamente',
            'data' => [
                'rpe' => $rpe,
                'nombre' => $nombre,
                'correo' => $correo ?? 'N/A',
                'tipo_usuario' => $tipo_usuario
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