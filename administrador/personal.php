<?php
// Mostrar errores para depuración
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
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Contar el total de trabajadores (con búsqueda si aplica)
$total_query = "SELECT COUNT(*) as total FROM trabajadores";
if (!empty($search)) {
    $total_query .= " WHERE rpe LIKE ? OR nombre LIKE ? OR correo LIKE ?";
}
$stmt = $conexion->prepare($total_query);
if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_trabajadores = $total_row['total'];
$total_pages = ceil($total_trabajadores / $limit);

// Obtener trabajadores para la página actual (con búsqueda si aplica)
$query = "SELECT * FROM trabajadores";
if (!empty($search)) {
    $query .= " WHERE rpe LIKE ? OR nombre LIKE ? OR correo LIKE ?";
}
$query .= " LIMIT ? OFFSET ?";
$stmt = $conexion->prepare($query);
if (!empty($search)) {
    $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$trabajadores = $result->fetch_all(MYSQLI_ASSOC);

// Manejar eliminación
if (isset($_POST['delete_rpe'])) {
    $rpe = $_POST['delete_rpe'];
    $stmt = $conexion->prepare("DELETE FROM trabajadores WHERE rpe = ?");
    $stmt->bind_param("s", $rpe);
    $stmt->execute();
    header("Location: " . BASE_URL . "administrador/personal.php?page=$page" . (!empty($search) ? "&search=$search" : ""));
    exit();
}
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/views/layouts/header-admin.php'; ?>

<div class="container mt-5">
    <h1 class="mb-4" style="color: #003d2a; font-weight: 700;">Gestión de Personal</h1>

    <!-- Barra de búsqueda -->
    <div class="mb-4">
        <form method="GET" action="<?= BASE_URL ?>administrador/personal.php" class="d-flex" style="max-width: 400px;">
            <input type="text" name="search" id="searchInput" class="form-control" placeholder="Buscar por RPE, nombre, correo..." value="<?= htmlspecialchars($search) ?>" style="border-radius: 8px 0 0 8px; border-color: #003d2a;">
            <button type="submit" class="btn" style="background-color: #003d2a; color: #fff; border-radius: 0 8px 8px 0;"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <!-- Tabla de trabajadores -->
    <div class="table-responsive shadow-sm" style="border-radius: 10px; overflow: hidden;">
        <table class="table table-bordered" id="trabajadoresTable">
            <thead style="background-color: #003d2a; color: #fff; font-weight: 600;">
                <tr>
                    <th>RPE</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Estatus</th>
                    <th>Fecha Registro</th>
                    <th>Centro Trabajo</th>
                    <th>Sección Sindical</th>
                    <th>Categoría</th>
                    <th>Foto</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($trabajadores)): ?>
                    <tr><td colspan="10" style="text-align: center;">No hay trabajadores disponibles.</td></tr>
                <?php else: ?>
                    <?php foreach ($trabajadores as $index => $trabajador): ?>
                    <tr data-rpe="<?= htmlspecialchars($trabajador['rpe']) ?>" class="<?= $index % 2 === 0 ? 'row-dark' : 'row-light' ?>">
                        <td><?= htmlspecialchars($trabajador['rpe']) ?></td>
                        <td><?= htmlspecialchars($trabajador['nombre']) ?></td>
                        <td><?= htmlspecialchars($trabajador['correo']) ?></td>
                        <td><?= $trabajador['estatus'] ? 'Activo' : 'Inactivo' ?></td>
                        <td><?= htmlspecialchars($trabajador['fechaRegistro']) ?></td>
                        <td><?= htmlspecialchars($trabajador['centroTrabajo'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($trabajador['seccionSindical'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($trabajador['categoria'] ?? 'N/A') ?></td>
                        <td>
                            <img src="<?= BASE_URL ?>cfe-api/uploads/perfiles/<?= htmlspecialchars($trabajador['foto_perfil']) ?>" alt="Foto" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 2px solid #003d2a;" onerror="this.src='<?= BASE_URL ?>cfe-api/uploads/perfiles/default.jpg';">
                        </td>
                        <td class="d-flex justify-content-center gap-2">
                            <button class="btn btn-edit" data-bs-toggle="modal" data-bs-target="#editModal" style="background-color: #003d2a; color: #fff; border: none; padding: 6px; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Seguro que quieres eliminar a <?= htmlspecialchars($trabajador['nombre']) ?>?');">
                                <input type="hidden" name="delete_rpe" value="<?= htmlspecialchars($trabajador['rpe']) ?>">
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
        <a href="<?= BASE_URL ?>administrador/personal.php?page=<?= max(1, $page - 1) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="btn btn-primary <?= $page <= 1 ? 'disabled' : '' ?>" style="background-color: #003d2a; border-color: #003d2a; padding: 10px 20px; border-radius: 8px;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <span style="color: #003d2a; font-weight: 600; align-self: center;">Página <?= $page ?> de <?= $total_pages ?></span>
        <a href="<?= BASE_URL ?>administrador/personal.php?page=<?= min($total_pages, $page + 1) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="btn btn-primary <?= $page >= $total_pages ? 'disabled' : '' ?>" style="background-color: #003d2a; border-color: #003d2a; padding: 10px 20px; border-radius: 8px;">
            <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</div>

<!-- Modal de edición -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-sm" style="border-radius: 10px; border: none;">
            <div class="modal-header" style="background-color: #003d2a; color: #fff; border-radius: 10px 10px 0 0;">
                <h5 class="modal-title" id="editModalLabel">Editar Trabajador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateForm" method="POST" action="<?= BASE_URL ?>administrador/update_trabajador.php">
                    <input type="hidden" name="rpe" id="editRpe">
                    <div class="mb-3">
                        <label for="editNombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="editNombre" name="nombre" required style="border-radius: 8px;">
                    </div>
                    <div class="mb-3">
                        <label for="editCorreo" class="form-label">Correo</label>
                        <input type="email" class="form-control" id="editCorreo" name="correo" required style="border-radius: 8px;">
                    </div>
                    <div class="mb-3">
                        <label for="editEstatus" class="form-label">Estatus</label>
                        <select class="form-control" id="editEstatus" name="estatus" style="border-radius: 8px;">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editCentroTrabajo" class="form-label">Centro de Trabajo</label>
                        <input type="text" class="form-control" id="editCentroTrabajo" name="centroTrabajo" style="border-radius: 8px;">
                    </div>
                    <div class="mb-3">
                        <label for="editSeccionSindical" class="form-label">Sección Sindical</label>
                        <input type="text" class="form-control" id="editSeccionSindical" name="seccionSindical" style="border-radius: 8px;">
                    </div>
                    <div class="mb-3">
                        <label for="editCategoria" class="form-label">Categoría</label>
                        <input type="text" class="form-control" id="editCategoria" name="categoria" style="border-radius: 8px;">
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
    .table th, .table td {
        vertical-align: middle;
        text-align: center;
    }
    .btn-edit, .btn-delete {
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
    /* Ajuste para la columna Acciones */
    td.d-flex {
        padding: 4px !important; /* Reducir padding para minimizar "hueco" */
        gap: 4px; /* Reducir el gap entre botones */
    }
</style>

<script>
    // Editar trabajador (llenar el modal)
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const rpe = row.dataset.rpe;
            document.getElementById('editRpe').value = rpe;
            document.getElementById('editNombre').value = row.cells[1].textContent;
            document.getElementById('editCorreo').value = row.cells[2].textContent;
            document.getElementById('editEstatus').value = row.cells[3].textContent === 'Activo' ? 1 : 0;
            document.getElementById('editCentroTrabajo').value = row.cells[5].textContent === 'N/A' ? '' : row.cells[5].textContent;
            document.getElementById('editSeccionSindical').value = row.cells[6].textContent === 'N/A' ? '' : row.cells[6].textContent;
            document.getElementById('editCategoria').value = row.cells[7].textContent === 'N/A' ? '' : row.cells[7].textContent;
        });
    });

    // Enviar actualización desde el modal
    document.getElementById('updateForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        fetch('<?= BASE_URL ?>administrador/update_trabajador.php', {
            method: 'POST',
            body: formData
        }).then(response => response.text())
          .then(() => location.reload())
          .catch(error => console.error('Error:', error));
    });
</script>