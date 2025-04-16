<?php
// registradores.php
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

// Contar el total de registradores
$total_query = "SELECT COUNT(*) as total FROM registradores";
$bind_params = [];
if (!empty($search)) {
    $total_query .= " WHERE rpe LIKE ? OR nombre LIKE ? OR correo LIKE ?";
    $search_param = "%$search%";
    $bind_params = [$search_param, $search_param, $search_param];
}

$stmt = $conexion->prepare($total_query);
if (!$stmt) {
    die("Error preparando consulta de conteo: " . $conexion->error);
}

if (!empty($bind_params)) {
    $stmt->bind_param(str_repeat("s", count($bind_params)), ...$bind_params);
}

$stmt->execute();
$total_result = $stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_registradores = $total_row['total'];
$total_pages = ceil($total_registradores / $limit);
$stmt->close();

// Obtener registradores
$query = "SELECT * FROM registradores";
$bind_params = [];
if (!empty($search)) {
    $query .= " WHERE rpe LIKE ? OR nombre LIKE ? OR correo LIKE ?";
    $search_param = "%$search%";
    $bind_params = [$search_param, $search_param, $search_param];
}
$query .= " ORDER BY rpe ASC LIMIT ? OFFSET ?";
$bind_params[] = $limit;
$bind_params[] = $offset;

$stmt = $conexion->prepare($query);
if (!$stmt) {
    die("Error preparando consulta de datos: " . $conexion->error);
}

$types = (!empty($search) ? str_repeat("s", 3) : "") . "ii";
$stmt->bind_param($types, ...$bind_params);
$stmt->execute();
$result = $stmt->get_result();
$registradores = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Manejar eliminación
if (isset($_POST['delete_rpe'])) {
    $rpe = $_POST['delete_rpe'];
    $stmt = $conexion->prepare("DELETE FROM registradores WHERE rpe = ?");
    $stmt->bind_param("s", $rpe);
    $stmt->execute();
    header("Location: " . BASE_URL . "administrador/registradores.php?page=$page" . (!empty($search) ? "&search=" . urlencode($search) : ""));
    exit();
}

// Definir columnas
$columns = [
    ['id' => 'rpe', 'label' => 'RPE', 'field' => 'rpe'],
    ['id' => 'nombre', 'label' => 'Nombre', 'field' => 'nombre'],
    ['id' => 'correo', 'label' => 'Correo', 'field' => 'correo'],
    ['id' => 'tipo_usuario', 'label' => 'Tipo Usuario', 'field' => 'tipo_usuario'],
    ['id' => 'fecha_registro', 'label' => 'Fecha Registro', 'field' => 'fecha_registro'],
    ['id' => 'foto', 'label' => 'Foto', 'field' => 'foto_perfil'],
];
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/views/layouts/header-admin.php'; ?>

<div class="container mt-5">
    <!-- Contenedor para el Toast -->
    <div class="position-fixed start-50 translate-middle-x p-3" style="z-index: 1055; top: 70px; width: 100%; max-width: 400px;">
        <div id="notificationToast" class="toast shadow-lg border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="2000">
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

    <h1 class="mb-4" style="color: #003d2a; font-weight: 700;">Gestión de Registradores</h1>

    <!-- Barra de búsqueda y botones -->
    <div class="mb-4 d-flex align-items-center gap-3">
        <form method="GET" action="<?= BASE_URL ?>administrador/registradores.php" class="d-flex" style="max-width: 400px; flex-grow: 1;">
            <input type="text" name="search" id="searchInput" class="form-control" placeholder="Buscar por RPE, nombre, correo..." value="<?= htmlspecialchars($search) ?>" style="border-radius: 8px 0 0 8px; border-color: #003d2a;">
            <button type="submit" class="btn" style="background-color: #003d2a; color: #fff; border-radius: 0 8px 8px 0;"><i class="bi bi-search"></i></button>
        </form>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#columnModal" style="border-color: #003d2a; color: #003d2a;">
            Configurar Columnas
        </button>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reorderModal" style="border-color: #003d2a; color: #003d2a;">
            Reordenar Columnas
        </button>
    </div>

    <!-- Tabla de registradores -->
    <div class="table-responsive shadow-sm" style="border-radius: 10px; overflow-x: auto;">
        <table class="table table-bordered" id="registradoresTable">
            <thead style="background-color: #003d2a; color: #000; font-weight: 600;">
                <tr>
                    <?php foreach ($columns as $col): ?>
                        <th data-col-id="<?= $col['id'] ?>">
                            <?= $col['label'] ?>
                        </th>
                    <?php endforeach; ?>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registradores)): ?>
                    <tr>
                        <td colspan="<?= count($columns) + 1 ?>" style="text-align: center;">No hay registradores disponibles.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registradores as $index => $registrador): ?>
                        <tr data-rpe="<?= htmlspecialchars($registrador['rpe']) ?>" class="<?= $index % 2 === 0 ? 'row-dark' : 'row-light' ?>">
                            <?php foreach ($columns as $col): ?>
                                <td data-col-id="<?= $col['id'] ?>">
                                    <?php if ($col['id'] === 'foto'): ?>
                                        <img src="<?= BASE_URL ?>cfe-api/uploads/perfiles/<?= htmlspecialchars($registrador['foto_perfil']) ?>" alt="Foto" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 2px solid #003d2a;" onerror="this.src='<?= BASE_URL ?>cfe-api/uploads/perfiles/default.jpg';">
                                    <?php else: ?>
                                        <?= htmlspecialchars($registrador[$col['field']] ?? 'N/A') ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="d-flex justify-content-center gap-2">
                                <button class="btn btn-edit" data-bs-toggle="modal" data-bs-target="#editModal" style="background-color: #003d2a; color: #fff; border: none; padding: 6px; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Seguro que quieres eliminar a <?= htmlspecialchars($registrador['nombre']) ?>?');">
                                    <input type="hidden" name="delete_rpe" value="<?= htmlspecialchars($registrador['rpe']) ?>">
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
        <a href="<?= BASE_URL ?>administrador/registradores.php?page=<?= max(1, $page - 1) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="btn btn-primary <?= $page <= 1 ? 'disabled' : '' ?>" style="background-color: #003d2a; border-color: #003d2a; padding: 10px 20px; border-radius: 8px;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <span style="color: #003d2a; font-weight: 600; align-self: center;">Página <?= $page ?> de <?= $total_pages ?></span>
        <a href="<?= BASE_URL ?>administrador/registradores.php?page=<?= min($total_pages, $page + 1) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="btn btn-primary <?= $page >= $total_pages ? 'disabled' : '' ?>" style="background-color: #003d2a; border-color: #003d2a; padding: 10px 20px; border-radius: 8px;">
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-sm" style="border-radius: 10px; border: none;">
            <div class="modal-header" style="background-color: #003d2a; color: #fff; border-radius: 10px 10px 0 0;">
                <h5 class="modal-title" id="editModalLabel">Editar Registrador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateForm" method="POST" action="<?= BASE_URL ?>administrador/update_registrador.php">
                    <input type="hidden" name="rpe" id="editRpe">
                    <div class="mb-3">
                        <label for="editRpeDisplay" class="form-label">RPE</label>
                        <input type="text" class="form-control readonly-field" id="editRpeDisplay" readonly style="border-radius: 8px;">
                    </div>
                    <div class="mb-3">
                        <label for="editNombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="editNombre" name="nombre" required style="border-radius: 8px;">
                    </div>
                    <div class="mb-3">
                        <label for="editCorreo" class="form-label">Correo</label>
                        <input type="email" class="form-control" id="editCorreo" name="correo" style="border-radius: 8px;">
                    </div>
                    <div class="mb-3">
                        <label for="editTipoUsuario" class="form-label">Tipo de Usuario</label>
                        <select class="form-control" id="editTipoUsuario" name="tipo_usuario" required style="border-radius: 8px;">
                            <option value="administrador">Administrador</option>
                            <option value="usuario">Usuario</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editFechaRegistro" class="form-label">Fecha de Registro</label>
                        <input type="text" class="form-control readonly-field" id="editFechaRegistro" readonly style="border-radius: 8px;">
                    </div>
                    <div class="mb-3">
                        <label for="editFoto" class="form-label">Foto</label>
                        <div class="text-center">
                            <img id="editFoto" src="" alt="Foto de perfil" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; border: 2px solid #003d2a;" onerror="this.src='<?= BASE_URL ?>cfe-api/uploads/perfiles/default.jpg';">
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

<script>
    // Función para mostrar el toast
    function showToast(title, message, isSuccess) {
        const toastEl = document.getElementById('notificationToast');
        const toastTitle = document.getElementById('toastTitle');
        const toastBody = document.getElementById('toastBody');
        const toastIcon = document.getElementById('toastIcon');
        const toast = bootstrap.Toast.getOrCreateInstance(toastEl);

        toastTitle.textContent = title;
        toastBody.textContent = message;
        toastEl.classList.remove('text-bg-success', 'text-bg-danger');
        toastEl.classList.add(isSuccess ? 'text-bg-success' : 'text-bg-danger');
        toastIcon.className = 'bi ' + (isSuccess ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill');
        toast.show();
    }

    // Función para ajustar el ancho de la tabla según columnas visibles
    function adjustTableWidth() {
        const table = document.getElementById('registradoresTable');
        const visibleColumns = document.querySelectorAll('#registradoresTable th:not(.hidden)');
        if (visibleColumns.length <= 1) { // Solo "Acciones" está visible
            table.style.display = 'none';
        } else {
            table.style.display = '';
        }
    }

    // Inicializar eventos
    document.addEventListener('DOMContentLoaded', () => {
        // Botones de acción
        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', function() {
                const row = this.closest('tr');
                const rpe = row.dataset.rpe;
                const nombreCell = row.querySelector('td[data-col-id="nombre"]');
                const correoCell = row.querySelector('td[data-col-id="correo"]');
                const tipoUsuarioCell = row.querySelector('td[data-col-id="tipo_usuario"]');
                const fechaRegistroCell = row.querySelector('td[data-col-id="fecha_registro"]');
                const fotoCell = row.querySelector('td[data-col-id="foto"] img');

                document.getElementById('editRpe').value = rpe;
                document.getElementById('editRpeDisplay').value = rpe;
                document.getElementById('editNombre').value = nombreCell ? nombreCell.textContent.trim() : '';
                document.getElementById('editCorreo').value = correoCell && correoCell.textContent !== 'N/A' ? correoCell.textContent.trim() : '';
                document.getElementById('editFechaRegistro').value = fechaRegistroCell ? fechaRegistroCell.textContent.trim() : 'N/A';
                document.getElementById('editFoto').src = fotoCell ? fotoCell.src : '<?= BASE_URL ?>cfe-api/uploads/perfiles/default.jpg';

                const tipoUsuarioValue = tipoUsuarioCell ? tipoUsuarioCell.textContent.trim().toLowerCase() : '';
                const tipoUsuarioSelect = document.getElementById('editTipoUsuario');
                tipoUsuarioSelect.value = tipoUsuarioValue === 'administrador' || tipoUsuarioValue === 'usuario' ? tipoUsuarioValue : 'usuario';
            });
        });

        // Inicializar columnas
        const savedColumns = JSON.parse(localStorage.getItem('registradoresColumns')) || {};
        const checkboxes = document.querySelectorAll('#columnSelectForm .form-check-input');

        checkboxes.forEach(checkbox => {
            const colId = checkbox.dataset.colId;
            const isVisible = savedColumns[colId] !== false;
            checkbox.checked = isVisible;
            document.querySelectorAll(`[data-col-id="${colId}"]`).forEach(el => {
                el.classList.toggle('hidden', !isVisible);
            });
        });

        adjustTableWidth();

        // Orden de columnas
        const savedOrder = JSON.parse(localStorage.getItem('registradoresColumnOrder')) || [];
        if (savedOrder.length === <?php echo count($columns); ?>) {
            const thead = document.querySelector('#registradoresTable thead tr');
            const tbodyRows = document.querySelectorAll('#registradoresTable tbody tr');
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
                newRow.dataset.rpe = row.dataset.rpe;
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
        const savedColumns = JSON.parse(localStorage.getItem('registradoresColumns')) || {};
        document.querySelectorAll('#columnSelectForm .form-check-input').forEach(checkbox => {
            const colId = checkbox.dataset.colId;
            checkbox.checked = savedColumns[colId] !== false;
        });
    });

    // Aplicar cambios de columnas
    document.getElementById('applyColumns').addEventListener('click', () => {
        const visibleColumns = {};
        document.querySelectorAll('#columnSelectForm .form-check-input').forEach(checkbox => {
            const colId = checkbox.dataset.colId;
            visibleColumns[colId] = checkbox.checked;
            document.querySelectorAll(`[data-col-id="${colId}"]`).forEach(el => {
                el.classList.toggle('hidden', !checkbox.checked);
            });
        });
        localStorage.setItem('registradoresColumns', JSON.stringify(visibleColumns));
        adjustTableWidth();
        bootstrap.Modal.getInstance(document.getElementById('columnModal')).hide();
    });

    // Resetear configuración de columnas
    document.getElementById('resetColumns').addEventListener('click', () => {
        localStorage.removeItem('registradoresColumns');
        document.querySelectorAll('#columnSelectForm .form-check-input').forEach(checkbox => {
            checkbox.checked = true;
            document.querySelectorAll(`[data-col-id="${checkbox.dataset.colId}"]`).forEach(el => {
                el.classList.remove('hidden');
            });
        });
        localStorage.setItem('registradoresColumns', JSON.stringify({}));
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
            localStorage.setItem('registradoresColumnOrder', JSON.stringify(newOrder));

            const thead = document.querySelector('#registradoresTable thead tr');
            const tbodyRows = document.querySelectorAll('#registradoresTable tbody tr');
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
                newRow.dataset.rpe = row.dataset.rpe;
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
            const draggableElements = [...container.querySelectorAll('.list-group-itemELEReadonly-field:not(.dragging)')];
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

    // Enviar actualización desde el modal
    document.getElementById('updateForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));

        fetch('<?= BASE_URL ?>administrador/update_registrador.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    modal.hide();
                    showToast('Éxito', data.message, true);

                    // Actualizar la fila en la tabla
                    const row = document.querySelector(`tr[data-rpe="${data.data.rpe}"]`);
                    if (row) {
                        const cells = {
                            'rpe': row.querySelector('td[data-col-id="rpe"]'),
                            'nombre': row.querySelector('td[data-col-id="nombre"]'),
                            'correo': row.querySelector('td[data-col-id="correo"]'),
                            'tipo_usuario': row.querySelector('td[data-col-id="tipo_usuario"]')
                        };

                        if (cells.rpe) cells.rpe.textContent = data.data.rpe;
                        if (cells.nombre) cells.nombre.textContent = data.data.nombre;
                        if (cells.correo) cells.correo.textContent = data.data.correo;
                        if (cells.tipo_usuario) cells.tipo_usuario.textContent = data.data.tipo_usuario;

                        // Resaltar la fila actualizada
                        row.classList.add('highlight');
                        setTimeout(() => {
                            row.classList.remove('highlight');
                        }, 1000);
                    }
                } else {
                    showToast('Error', data.message, false);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error', 'No se pudo actualizar el registrador', false);
            });
    });
</script>

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

    .toast.text-bg-danger {
        background-color: #f8d7da;
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

    /* Estilos para campos de solo lectura */
    .readonly-field {
        background-color: #f8f9fa;
        border-color: #dee2e6;
        color: #495057;
        cursor: not-allowed;
    }

    .readonly-field:focus {
        box-shadow: none;
        border-color: #dee2e6;
    }

    /* Animación para resaltar fila actualizada */
    tr.highlight {
        background-color: #d4edda !important;
        transition: background-color 0.5s ease;
    }

    tr.highlight td {
        background-color: #d4edda !important;
    }

    @media (max-width: 576px) {
        .toast {
            max-width: 90%;
            margin: 0 auto;
        }

        #editFoto {
            width: 60px;
            height: 60px;
        }
    }
</style>