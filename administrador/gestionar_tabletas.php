<?php
// gestionar_tabletas.php
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

// Configuración de paginación
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Contar el total de tabletas
    $total_query = "SELECT COUNT(*) as total FROM tabletas";
    $bind_params = [];
    if (!empty($search)) {
        $total_query .= " WHERE activo LIKE ? OR marca LIKE ? OR modelo LIKE ? OR numero_serie LIKE ? OR rpe_trabajador LIKE ?";
        $search_param = "%$search%";
        $bind_params = [$search_param, $search_param, $search_param, $search_param, $search_param];
    }

    $stmt = $conexion->prepare($total_query);
    if (!$stmt) {
        throw new Exception("Error preparando consulta de conteo: " . $conexion->error);
    }

    if (!empty($bind_params)) {
        $stmt->bind_param(str_repeat("s", count($bind_params)), ...$bind_params);
    }

    if (!$stmt->execute()) {
        throw new Exception("Error ejecutando consulta de conteo: " . $stmt->error);
    }

    $total_result = $stmt->get_result();
    $total_row = $total_result->fetch_assoc();
    $total_tabletas = $total_row['total'];
    $total_pages = ceil($total_tabletas / $limit);
    $stmt->close();

    // Obtener tabletas
    $query = "SELECT * FROM tabletas";
    $bind_params = [];
    if (!empty($search)) {
        $query .= " WHERE activo LIKE ? OR marca LIKE ? OR modelo LIKE ? OR numero_serie LIKE ? OR rpe_trabajador LIKE ?";
        $search_param = "%$search%";
        $bind_params = [$search_param, $search_param, $search_param, $search_param, $search_param];
    }
    $query .= " LIMIT ? OFFSET ?";
    $bind_params[] = $limit;
    $bind_params[] = $offset;

    $stmt = $conexion->prepare($query);
    if (!$stmt) {
        throw new Exception("Error preparando consulta de datos: " . $conexion->error);
    }

    $types = str_repeat("s", count($bind_params) - 2) . "ii";
    $stmt->bind_param($types, ...$bind_params);

    if (!$stmt->execute()) {
        throw new Exception("Error ejecutando consulta de datos: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $tabletas = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error en la consulta: " . htmlspecialchars($e->getMessage()) . "</div>";
    $tabletas = [];
    $total_tabletas = 0;
    $total_pages = 1;
}

// Manejar eliminación
if (isset($_POST['delete_activo'])) {
    try {
        $activo = $_POST['delete_activo'];

        // Iniciar transacción
        $conexion->begin_transaction();

        // 1. Obtener IDs y RPEs de historial_asignaciones para firmas
        $stmt = $conexion->prepare("SELECT id, rpe_trabajador FROM historial_asignaciones WHERE activo = ?");
        if (!$stmt) {
            throw new Exception("Error preparando consulta de historial_asignaciones: " . $conexion->error);
        }
        $stmt->bind_param("s", $activo);
        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando consulta de historial_asignaciones: " . $stmt->error);
        }
        $historial_ids = [];
        $rpes = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $historial_ids[] = $row['id'];
            if (!empty($row['rpe_trabajador'])) {
                $rpes[] = $row['rpe_trabajador'];
            }
        }
        $stmt->close();

        // 2. Eliminar de fallas_historial
        if (!empty($historial_ids)) {
            $placeholders = implode(',', array_fill(0, count($historial_ids), '?'));
            $stmt = $conexion->prepare("DELETE FROM fallas_historial WHERE historial_id IN ($placeholders)");
            if (!$stmt) {
                throw new Exception("Error preparando eliminación de fallas_historial: " . $conexion->error);
            }
            $stmt->bind_param(str_repeat("i", count($historial_ids)), ...$historial_ids);
            if (!$stmt->execute()) {
                throw new Exception("Error ejecutando eliminación de fallas_historial: " . $stmt->error);
            }
            $stmt->close();
        }

        // 3. Obtener y eliminar fotos físicas
        $stmt = $conexion->prepare("SELECT ruta_foto FROM fotos WHERE tableta_id = ?");
        if (!$stmt) {
            throw new Exception("Error preparando consulta de fotos: " . $conexion->error);
        }
        $stmt->bind_param("s", $activo);
        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando consulta de fotos: " . $stmt->error);
        }
        $fotos = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $fotos[] = $row['ruta_foto'];
        }
        $stmt->close();

        // Borrar fotos del servidor
        $upload_dir_fotos = $_SERVER['DOCUMENT_ROOT'] . '/cfe-api/uploads/tabletas/';
        foreach ($fotos as $foto) {
            $file_path = $upload_dir_fotos . basename($foto);
            if (file_exists($file_path)) {
                if (!unlink($file_path)) {
                    throw new Exception("Error al eliminar el archivo: $file_path");
                }
            }
        }

        // 4. Eliminar de fotos
        $stmt = $conexion->prepare("DELETE FROM fotos WHERE tableta_id = ?");
        if (!$stmt) {
            throw new Exception("Error preparando eliminación de fotos: " . $conexion->error);
        }
        $stmt->bind_param("s", $activo);
        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando eliminación de fotos: " . $stmt->error);
        }
        $stmt->close();

        // 5. Borrar firmas del servidor
        $upload_dir_firmas = $_SERVER['DOCUMENT_ROOT'] . '/cfe-api/uploads/firmas/';
        foreach ($rpes as $rpe) {
            $firma_filename = $activo . '_' . $rpe . '.png';
            $file_path = $upload_dir_firmas . $firma_filename;
            if (file_exists($file_path)) {
                if (!unlink($file_path)) {
                    throw new Exception("Error al eliminar la firma: $file_path");
                }
            }
        }

        // 6. Eliminar de historial_asignaciones
        $stmt = $conexion->prepare("DELETE FROM historial_asignaciones WHERE activo = ?");
        if (!$stmt) {
            throw new Exception("Error preparando eliminación de historial_asignaciones: " . $conexion->error);
        }
        $stmt->bind_param("s", $activo);
        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando eliminación de historial_asignaciones: " . $stmt->error);
        }
        $stmt->close();

        // 7. Eliminar de tabletas
        $stmt = $conexion->prepare("DELETE FROM tabletas WHERE activo = ?");
        if (!$stmt) {
            throw new Exception("Error preparando eliminación de tabletas: " . $conexion->error);
        }
        $stmt->bind_param("s", $activo);
        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando eliminación de tabletas: " . $stmt->error);
        }
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        if ($affected_rows === 0) {
            throw new Exception("No se encontró la tableta con activo '$activo'.");
        }

        // Confirmar transacción
        $conexion->commit();

        // Redirigir con mensaje de éxito
        header("Location: " . BASE_URL . "administrador/gestionar_tabletas.php?page=$page" . (!empty($search) ? "&search=" . urlencode($search) : "") . "&success=" . urlencode("Tableta y datos relacionados eliminados correctamente"));
        exit();
    } catch (Exception $e) {
        // Revertir transacción
        $conexion->rollback();

        // Log del error
        error_log("Error al eliminar tableta '$activo': " . $e->getMessage());

        // Redirigir con mensaje de error
        header("Location: " . BASE_URL . "administrador/gestionar_tabletas.php?page=$page" . (!empty($search) ? "&search=" . urlencode($search) : "") . "&error=" . urlencode("Error al eliminar: " . $e->getMessage()));
        exit();
    }
}

// Definir columnas
$columns = [
    ['id' => 'activo', 'label' => 'Activo', 'field' => 'activo'],
    ['id' => 'marca', 'label' => 'Marca', 'field' => 'marca'],
    ['id' => 'modelo', 'label' => 'Modelo', 'field' => 'modelo'],
    ['id' => 'inventario', 'label' => 'Inventario', 'field' => 'inventario'],
    ['id' => 'numero_serie', 'label' => 'Número de Serie', 'field' => 'numero_serie'],
    ['id' => 'version_android', 'label' => 'Versión Android', 'field' => 'version_android'],
    ['id' => 'anio_adquisicion', 'label' => 'Año Adquisición', 'field' => 'anio_adquisicion'],
    ['id' => 'agencia', 'label' => 'Agencia', 'field' => 'agencia'],
    ['id' => 'proceso', 'label' => 'Proceso', 'field' => 'proceso'],
    ['id' => 'rpe_trabajador', 'label' => 'RPE Trabajador', 'field' => 'rpe_trabajador'],
    ['id' => 'ubicacion_registro', 'label' => 'Ubicación Registro', 'field' => 'ubicacion_registro'],
    ['id' => 'fecha_registro', 'label' => 'Fecha Registro', 'field' => 'fecha_registro'],
    ['id' => 'marca_chip', 'label' => 'Marca Chip', 'field' => 'marca_chip'],
    ['id' => 'numero_serie_chip', 'label' => 'Número Serie Chip', 'field' => 'numero_serie_chip'],
];
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/views/layouts/header-admin.php'; ?>

<div class="container mt-5">
    <!-- Contenedor para el Toast -->
    <div class="position-fixed start-50 translate-middle-x p-3" style="z-index: 1055; top: 70px; width: 100%; max-width: 400px;">        <div id="notificationToast" class="toast shadow-lg border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="4000">
            <div class="toast-body d-flex align-items-center gap-3 p-3">
                <i id="toastIcon" class="bi" style="font-size: 1.5rem;"></i>
                <div>
                    <strong id="toastTitle" class="d-block mb-1"></strong>
                    <span id="toastBody"></span>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <h1 class="mb-4" style="color: #003d2a; font-weight: 700;">Gestión de Tabletas</h1>

    <!-- Barra de búsqueda -->
    <div class="mb-4 d-flex align-items-center gap-3">
        <form method="GET" action="<?= BASE_URL ?>administrador/gestionar_tabletas.php" class="d-flex" style="max-width: 400px; flex-grow: 1;">
            <input type="text" name="search" id="searchInput" class="form-control" placeholder="Buscar por Activo, Marca, Modelo, Número de Serie, RPE..." value="<?= htmlspecialchars($search) ?>" style="border-radius: 8px 0 0 8px; border-color: #003d2a;">
            <button type="submit" class="btn" style="background-color: #003d2a; color: #fff; border-radius: 0 8px 8px 0;"><i class="bi bi-search"></i></button>
        </form>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#columnModal" style="border-color: #003d2a; color: #003d2a;">
            Configurar Columnas
        </button>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reorderModal" style="border-color: #003d2a; color: #003d2a;">
            Reordenar Columnas
        </button>
    </div>

    <!-- Tabla de tabletas -->
    <div class="table-responsive shadow-sm" style="border-radius: 10px; overflow-x: auto;">
        <table class="table table-bordered" id="tabletasTable">
            <thead style="background-color: #003d2a; color: #fff; font-weight: 600;">
                <tr>
                    <?php foreach ($columns as $col): ?>
                        <th data-col-id="<?= $col['id'] ?>"><?= $col['label'] ?></th>
                    <?php endforeach; ?>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tabletas)): ?>
                    <tr>
                        <td colspan="<?= count($columns) + 1 ?>" style="text-align: center;">No hay tabletas disponibles.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tabletas as $index => $tableta): ?>
                        <tr data-activo="<?= htmlspecialchars($tableta['activo']) ?>" class="<?= $index % 2 === 0 ? 'row-dark' : 'row-light' ?>">
                            <?php foreach ($columns as $col): ?>
                                <td data-col-id="<?= $col['id'] ?>">
                                    <?= htmlspecialchars($tableta[$col['field']] ?? 'N/A') ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="d-flex justify-content-center gap-2">
                                <button class="btn btn-edit" data-bs-toggle="modal" data-bs-target="#editModal" style="background-color: #003d2a; color: #fff; border: none; padding: 6px; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Seguro que quieres eliminar la tableta <?= htmlspecialchars($tableta['activo']) ?>?');">
                                    <input type="hidden" name="delete_activo" value="<?= htmlspecialchars($tableta['activo']) ?>">
                                    <button type="submit" class="btn btn-delete" style="background-color: #dc3545; color: #fff; border: none; padding: 6px; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Controles de paginación -->
    <div class="d-flex justify-content-between mt-4">
        <a href="<?= BASE_URL ?>administrador/gestionar_tabletas.php?page=<?= max(1, $page - 1) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="btn btn-primary <?= $page <= 1 ? 'disabled' : '' ?>" style="background-color: #003d2a; border-color: #003d2a; padding: 10px 20px; border-radius: 8px;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <span style="color: #003d2a; font-weight: 600; align-self: center;">Página <?= $page ?> de <?= $total_pages ?></span>
        <a href="<?= BASE_URL ?>administrador/gestionar_tabletas.php?page=<?= min($total_pages, $page + 1) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="btn btn-primary <?= $page >= $total_pages ? 'disabled' : '' ?>" style="background-color: #003d2a; border-color: #003d2a; padding: 10px 20px; border-radius: 8px;">
            <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</div>

<!-- Modal para seleccionar columnas -->
<div class="modal fade" id="columnModal" tabindex="-1" aria-labelledby="columnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #003d2a; color: #fff;">
                <h5 class="modal-title" id="columnModalLabel">Seleccionar Columnas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="columnSelectForm">
                    <?php foreach ($columns as $col): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="col-<?= $col['id'] ?>" data-col-id="<?= $col['id'] ?>">
                            <label class="form-check-label" for="col-<?= $col['id'] ?>">
                                <?= $col['label'] ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="applyColumns" style="background-color: #003d2a; border: none;">Aplicar</button>
                <button type="button" class="btn btn-warning" id="resetColumns">Resetear</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para reordenar columnas -->
<div class="modal fade" id="reorderModal" tabindex="-1" aria-labelledby="reorderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #003d2a; color: #fff;">
                <h5 class="modal-title" id="reorderModalLabel">Reordenar Columnas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul id="columnOrderList" class="list-group">
                    <?php foreach ($columns as $col): ?>
                        <li class="list-group-item" data-col-id="<?= $col['id'] ?>" draggable="true">
                            <?= $col['label'] ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="applyOrder" style="background-color: #003d2a; border: none;">Aplicar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de edición -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-sm" style="border-radius: 10px; border: none;">
            <div class="modal-header" style="background-color: #003d2a; color: #fff; border-radius: 10px 10px 0 0;">
                <h5 class="modal-title" id="editModalLabel">Editar Tableta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateForm" method="POST" action="<?= BASE_URL ?>administrador/update_tablet.php">
                    <input type="hidden" name="activo" id="editActivo">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editMarca" class="form-label">Marca</label>
                            <input type="text" class="form-control" id="editMarca" name="marca" readonly style="border-radius: 8px; background-color: #e9ecef;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editModelo" class="form-label">Modelo</label>
                            <input type="text" class="form-control" id="editModelo" name="modelo" readonly style="border-radius: 8px; background-color: #e9ecef;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editInventario" class="form-label">Inventario</label>
                            <input type="text" class="form-control" id="editInventario" name="inventario" style="border-radius: 8px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editNumeroSerie" class="form-label">Número de Serie</label>
                            <input type="text" class="form-control" id="editNumeroSerie" name="numero_serie" required style="border-radius: 8px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editVersionAndroid" class="form-label">Versión Android</label>
                            <input type="text" class="form-control" id="editVersionAndroid" name="version_android" style="border-radius: 8px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editAnioAdquisicion" class="form-label">Año Adquisición</label>
                            <input type="number" class="form-control" id="editAnioAdquisicion" name="anio_adquisicion" style="border-radius: 8px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editAgencia" class="form-label">Agencia</label>
                            <input type="text" class="form-control" id="editAgencia" name="agencia" style="border-radius: 8px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editProceso" class="form-label">Proceso</label>
                            <input type="text" class="form-control" id="editProceso" name="proceso" style="border-radius: 8px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editRpeTrabajador" class="form-label">RPE Trabajador</label>
                            <input type="text" class="form-control" id="editRpeTrabajador" name="rpe_trabajador" required style="border-radius: 8px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editUbicacionRegistro" class="form-label">Ubicación Registro</label>
                            <input type="text" class="form-control" id="editUbicacionRegistro" name="ubicacion_registro" readonly style="border-radius: 8px; background-color: #e9ecef;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editFechaRegistro" class="form-label">Fecha Registro</label>
                            <input type="datetime-local" class="form-control" id="editFechaRegistro" name="fecha_registro" readonly style="border-radius: 8px; background-color: #e9ecef;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editMarcaChip" class="form-label">Marca Chip</label>
                            <input type="text" class="form-control" id="editMarcaChip" name="marca_chip" readonly style="border-radius: 8px; background-color: #e9ecef;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editNumeroSerieChip" class="form-label">Número Serie Chip</label>
                            <input type="text" class="form-control" id="editNumeroSerieChip" name="numero_serie_chip" style="border-radius: 8px;">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal" style="padding: 8px 16px; border-radius: 8px;">Cancelar</button>
                        <button type="submit" class="btn btn-primary" style="background-color: #003d2a; border: none; padding: 8px 16px; border-radius: 8px;">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/views/layouts/footer-admin.php'; ?>

<style>
    .table th,
    .table td {
        vertical-align: middle;
        text-align: center;
        padding: 4px;
        font-size: 12px;
        min-width: 100px;
        white-space: nowrap;
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        max-width: 100%;
        border: 1px solid #dee2e6;
        position: relative;
    }

    .table {
        width: auto;
        min-width: 100%;
    }

    .table-responsive::-webkit-scrollbar {
        height: 12px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background-color: #003d2a;
        border-radius: 6px;
    }

    .btn-edit,
    .btn-delete {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .btn-edit:hover {
        background-color: #00261d;
        transform: scale(1.1);
        box-shadow: 0 3px 6px rgba(0, 61, 42, 0.3);
    }

    .btn-delete:hover {
        background-color: #c82333;
        transform: scale(1.1);
        box-shadow: 0 3px 6px rgba(220, 53, 69, 0.3);
    }

    .form-control:focus {
        border-color: #003d2a;
        box-shadow: 0 0 5px rgba(0, 61, 42, 0.5);
    }

    tr.row-dark {
        background-color: #E2EFDA !important;
    }

    tr.row-dark td {
        background-color: #E2EFDA !important;
        color: #333333 !important;
    }

    tr.row-light {
        background-color: #FFFFFF !important;
    }

    tr.row-light td {
        background-color: #FFFFFF !important;
        color: #333333 !important;
    }

    td.d-flex {
        padding: 4px !important;
        gap: 4px;
    }

    .hidden {
        display: none !important;
    }

    #columnOrderList .list-group-item {
        cursor: move;
        margin-bottom: 5px;
        padding: 10px;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
    }

    #columnOrderList .list-group-item:hover {
        background-color: #e9ecef;
    }

    .form-check-input {
        display: inline-block !important;
        visibility: visible !important;
    }

    .form-control[readonly] {
        cursor: not-allowed;
        opacity: 0.7;
    }

    /* Estilos para el toast */
    .toast {
        background-color: #fff;
        border-radius: 8px;
        overflow: hidden;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }

    .toast.show {
        transform: translateY(0);
        opacity: 1;
    }

    .toast:not(.show) {
        transform: translateY(-20px);
        opacity: 0;
    }

    .toast.text-bg-success {
        background-color: #E2EFDA;
        color: #003d2a;
    }

    .toast.text-bg-success #toastIcon::before {
        content: "\f26e";
        /* Bootstrap Icons: check-circle-fill */
        color: #003d2a;
    }

    .toast.text-bg-danger {
        background-color: #f8d7da;
        color: #721c24;
    }

    .toast.text-bg-danger #toastIcon::before {
        content: "\f333";
        /* Bootstrap Icons: exclamation-triangle-fill */
        color: #721c24;
    }

    .toast-body {
        font-size: 0.95rem;
        line-height: 1.4;
    }

    #toastTitle {
        font-size: 1rem;
        font-weight: 600;
    }

    #toastBody {
        font-weight: 400;
    }

    .btn-close {
        filter: brightness(0.5);
    }

    .btn-close:hover {
        filter: brightness(0.3);
    }

    @media (max-width: 576px) {
        .toast {
            max-width: 90%;
            margin: 0 auto;
        }
    }
</style>

<script>
    // Función para ajustar el ancho de la tabla según columnas visibles
    function adjustTableWidth() {
        const table = document.getElementById('tabletasTable');
        const visibleColumns = document.querySelectorAll('#tabletasTable th:not(.hidden)');
        if (visibleColumns.length <= 1) { // Solo "Acciones" está visible
            table.style.display = 'none';
        } else {
            table.style.display = '';
        }
    }

    // Inicializar columnas al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
        const savedColumns = JSON.parse(localStorage.getItem('gestionarTabletasColumns')) || {};
        const checkboxes = document.querySelectorAll('#columnSelectForm .form-check-input');

        checkboxes.forEach(checkbox => {
            const colId = checkbox.dataset.colId;
            const isVisible = savedColumns[colId] !== false;
            checkbox.checked = isVisible;
            checkbox.style.display = 'inline-block';
            document.querySelectorAll(`[data-col-id="${colId}"]`).forEach(el => {
                el.classList.toggle('hidden', !isVisible);
            });
        });

        adjustTableWidth();

        // Orden de columnas
        const savedOrder = JSON.parse(localStorage.getItem('gestionarTabletasColumnOrder')) || [];
        if (savedOrder.length === <?php echo count($columns); ?>) {
            const thead = document.querySelector('#tabletasTable thead tr');
            const tbodyRows = document.querySelectorAll('#tabletasTable tbody tr');
            const newThead = document.createElement('tr');

            savedOrder.forEach(colId => {
                const th = document.querySelector(`th[data-col-id="${colId}"]`);
                if (th) newThead.appendChild(th.cloneNode(true));
            });
            newThead.appendChild(thead.lastElementChild.cloneNode(true)); // Acciones
            thead.innerHTML = newThead.innerHTML;

            tbodyRows.forEach(row => {
                const newRow = document.createElement('tr');
                newRow.className = row.className;
                newRow.dataset.activo = row.dataset.activo;
                savedOrder.forEach(colId => {
                    const td = row.querySelector(`td[data-col-id="${colId}"]`);
                    if (td) newRow.appendChild(td.cloneNode(true));
                });
                newRow.appendChild(row.lastElementChild.cloneNode(true)); // Acciones
                row.innerHTML = newRow.innerHTML;
            });
        }
    });

    // Actualizar checkboxes al abrir el modal
    document.getElementById('columnModal').addEventListener('show.bs.modal', () => {
        const savedColumns = JSON.parse(localStorage.getItem('gestionarTabletasColumns')) || {};
        const checkboxes = document.querySelectorAll('#columnSelectForm .form-check-input');

        checkboxes.forEach(checkbox => {
            const colId = checkbox.dataset.colId;
            checkbox.checked = savedColumns[colId] !== false;
            checkbox.style.display = 'inline-block';
            checkbox.style.visibility = 'visible';
        });
    });

    // Aplicar cambios de columnas
    document.getElementById('applyColumns').addEventListener('click', () => {
        const visibleColumns = {};
        const checkboxes = document.querySelectorAll('#columnSelectForm .form-check-input');
        checkboxes.forEach(checkbox => {
            const colId = checkbox.dataset.colId;
            visibleColumns[colId] = checkbox.checked;
            document.querySelectorAll(`[data-col-id="${colId}"]`).forEach(el => {
                el.classList.toggle('hidden', !checkbox.checked);
            });
        });
        localStorage.setItem('gestionarTabletasColumns', JSON.stringify(visibleColumns));
        adjustTableWidth();
        bootstrap.Modal.getInstance(document.getElementById('columnModal')).hide();
    });

    // Resetear configuración de columnas
    document.getElementById('resetColumns').addEventListener('click', () => {
        localStorage.removeItem('gestionarTabletasColumns');
        const checkboxes = document.querySelectorAll('#columnSelectForm .form-check-input');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
            checkbox.style.display = 'inline-block';
            checkbox.style.visibility = 'visible';
            document.querySelectorAll(`[data-col-id="${checkbox.dataset.colId}"]`).forEach(el => {
                el.classList.remove('hidden');
            });
        });
        localStorage.setItem('gestionarTabletasColumns', JSON.stringify({}));
        adjustTableWidth();
        bootstrap.Modal.getInstance(document.getElementById('columnModal')).hide();
    });

    // Reordenar columnas
    const orderList = document.getElementById('columnOrderList');
    if (orderList) {
        orderList.addEventListener('dragstart', e => {
            if (e.target.classList.contains('list-group-item')) {
                e.target.classList.add('dragging');
                e.dataTransfer.setData('text/plain', e.target.dataset.colId);
            }
        });

        orderList.addEventListener('dragend', e => {
            if (e.target.classList.contains('list-group-item')) {
                e.target.classList.remove('dragging');
            }
        });

        orderList.addEventListener('dragover', e => {
            e.preventDefault();
            const afterElement = getDragAfterElement(orderList, e.clientY);
            const draggable = document.querySelector('.dragging');
            if (afterElement == null) {
                orderList.appendChild(draggable);
            } else {
                orderList.insertBefore(draggable, afterElement);
            }
        });

        document.getElementById('applyOrder').addEventListener('click', () => {
            const newOrder = Array.from(orderList.children).map(item => item.dataset.colId);
            localStorage.setItem('gestionarTabletasColumnOrder', JSON.stringify(newOrder));

            const thead = document.querySelector('#tabletasTable thead tr');
            const tbodyRows = document.querySelectorAll('#tabletasTable tbody tr');
            const newThead = document.createElement('tr');

            newOrder.forEach(colId => {
                const th = document.querySelector(`th[data-col-id="${colId}"]`);
                if (th) newThead.appendChild(th.cloneNode(true));
            });
            newThead.appendChild(thead.lastElementChild.cloneNode(true)); // Acciones
            thead.innerHTML = newThead.innerHTML;

            tbodyRows.forEach(row => {
                const newRow = document.createElement('tr');
                newRow.className = row.className;
                newRow.dataset.activo = row.dataset.activo;
                newOrder.forEach(colId => {
                    const td = row.querySelector(`td[data-col-id="${colId}"]`);
                    if (td) newRow.appendChild(td.cloneNode(true));
                });
                newRow.appendChild(row.lastElementChild.cloneNode(true)); // Acciones
                row.innerHTML = newRow.innerHTML;
            });

            bootstrap.Modal.getInstance(document.getElementById('reorderModal')).hide();
        });

        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.list-group-item:not(.dragging)')];
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return {
                        offset: offset,
                        element: child
                    };
                }
                return closest;
            }, {
                offset: Number.NEGATIVE_INFINITY
            }).element;
        }
    }

    // Editar tableta
    document.addEventListener('click', function(event) {
        const button = event.target.closest('.btn-edit');
        if (!button) return;

        const row = button.closest('tr');
        if (!row) return;

        // Mapa de correspondencia entre colId y nombre del input
        const inputMap = {
            activo: 'editActivo',
            marca: 'editMarca',
            modelo: 'editModelo',
            inventario: 'editInventario',
            numero_serie: 'editNumeroSerie',
            version_android: 'editVersionAndroid',
            anio_adquisicion: 'editAnioAdquisicion',
            agencia: 'editAgencia',
            proceso: 'editProceso',
            rpe_trabajador: 'editRpeTrabajador',
            ubicacion_registro: 'editUbicacionRegistro',
            fecha_registro: 'editFechaRegistro',
            marca_chip: 'editMarcaChip',
            numero_serie_chip: 'editNumeroSerieChip'
        };

        // Iterar sobre todas las columnas definidas
        <?php echo json_encode(array_column($columns, 'id')); ?>.forEach(colId => {
            // Buscar el <td>, incluso si está oculto
            const td = row.querySelector(`td[data-col-id="${colId}"]`);
            const value = td ? td.textContent.trim() : '';
            const inputId = inputMap[colId];
            const input = document.getElementById(inputId);

            if (input) {
                if (colId === 'fecha_registro' && value && value !== 'N/A') {
                    try {
                        // Convertir la fecha al formato YYYY-MM-DDTHH:mm
                        const date = new Date(value);
                        if (!isNaN(date)) {
                            const year = date.getFullYear();
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const day = String(date.getDate()).padStart(2, '0');
                            const hours = String(date.getHours()).padStart(2, '0');
                            const minutes = String(date.getMinutes()).padStart(2, '0');
                            input.value = `${year}-${month}-${day}T${hours}:${minutes}`;
                        } else {
                            input.value = '';
                        }
                    } catch (e) {
                        console.warn(`Error parseando fecha_registro para ${colId}:`, value, e);
                        input.value = '';
                    }
                } else {
                    input.value = value === 'N/A' ? '' : value;
                }
            }
        });
    });

    // Enviar actualización
    document.getElementById('updateForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);

        // Log para depurar datos enviados
        const formDataObject = {};
        for (const [key, value] of formData.entries()) {
            formDataObject[key] = value;
        }
        console.log('Datos enviados en el formulario:', formDataObject);

        fetch('<?= BASE_URL ?>administrador/update_tablet.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Respuesta del servidor:', response.status, response.statusText);
                return response.json();
            })
            .then(data => {
                console.log('Contenido de la respuesta:', data);
                const toastEl = document.getElementById('notificationToast');
                const toastTitle = document.getElementById('toastTitle');
                const toastBody = document.getElementById('toastBody');
                const toast = bootstrap.Toast.getOrCreateInstance(toastEl);

                if (data.success) {
                    // Configurar toast para éxito
                    toastTitle.textContent = 'Éxito';
                    toastBody.textContent = data.message;
                    toastEl.classList.remove('text-bg-danger');
                    toastEl.classList.add('text-bg-success');
                    toast.show();

                    // Cerrar el modal de edición
                    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();

                    // Recargar la página después de un breve retraso para ver el toast
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Configurar toast para error
                    toastTitle.textContent = 'Error';
                    toastBody.textContent = data.message || 'Error desconocido';
                    toastEl.classList.remove('text-bg-success');
                    toastEl.classList.add('text-bg-danger');
                    toast.show();
                }
            })
            .catch(error => {
                console.error('Error en la solicitud:', error);
                const toastEl = document.getElementById('notificationToast');
                const toastTitle = document.getElementById('toastTitle');
                const toastBody = document.getElementById('toastBody');
                const toast = bootstrap.Toast.getOrCreateInstance(toastEl);

                toastTitle.textContent = 'Error';
                toastBody.textContent = error.message || 'Error al actualizar la tableta';
                toastEl.classList.remove('text-bg-success');
                toastEl.classList.add('text-bg-danger');
                toast.show();
            });
    });
</script>