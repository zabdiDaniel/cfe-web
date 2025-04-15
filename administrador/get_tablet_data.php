<?php
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/app/config/database.php';

if (!isset($_GET['activo']) || empty($_GET['activo'])) {
    echo json_encode(['success' => false, 'message' => 'Activo no proporcionado']);
    exit();
}

$activo = $_GET['activo'];

try {
    // Obtener datos de la tableta
    $stmt = $conexion->prepare("SELECT * FROM tabletas WHERE activo = ?");
    $stmt->bind_param("s", $activo);
    $stmt->execute();
    $result = $stmt->get_result();
    $tableta = $result->fetch_assoc();
    $stmt->close();

    if (!$tableta) {
        echo json_encode(['success' => false, 'message' => 'Tableta no encontrada']);
        exit();
    }

    // Obtener fotos
    $fotos = [];
    for ($i = 1; $i <= 4; $i++) {
        $foto_path = $_SERVER['DOCUMENT_ROOT'] . "/cfe-api/uploads/tabletas/{$activo}_{$i}.jpg";
        if (file_exists($foto_path)) {
            $fotos[] = "{$activo}_{$i}.jpg";
        }
    }

    echo json_encode([
        'success' => true,
        'tableta' => $tableta,
        'fotos' => $fotos
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}