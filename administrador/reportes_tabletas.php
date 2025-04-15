<?php
// reportes_tabletas.php
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
        $total_query .= " WHERE activo LIKE ? OR marca LIKE ? OR modelo LIKE ? OR numero_serie LIKE ? OR rpe_trabajador LIKE ? OR inventario LIKE ? OR version_android LIKE ? OR anio_adquisicion LIKE ? OR agencia LIKE ? OR proceso LIKE ? OR ubicacion_registro LIKE ? OR fecha_registro LIKE ? OR marca_chip LIKE ? OR numero_serie_chip LIKE ?";
        $search_param = "%$search%";
        $bind_params = array_fill(0, 14, $search_param);
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
        $query .= " WHERE activo LIKE ? OR marca LIKE ? OR modelo LIKE ? OR numero_serie LIKE ? OR rpe_trabajador LIKE ? OR inventario LIKE ? OR version_android LIKE ? OR anio_adquisicion LIKE ? OR agencia LIKE ? OR proceso LIKE ? OR ubicacion_registro LIKE ? OR fecha_registro LIKE ? OR marca_chip LIKE ? OR numero_serie_chip LIKE ?";
        $search_param = "%$search%";
        $bind_params = array_fill(0, 14, $search_param);
    }
    $query .= " ORDER BY activo ASC LIMIT ? OFFSET ?";
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
    <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1055; width: 100%; max-width: 400px;">
        <div id="notificationToast" class="toast shadow-lg border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="4000">
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

    <h1 class="mb-4" style="color: #003d2a; font-weight: 700;">Reportes de Tabletas</h1>

    <!-- Barra de búsqueda -->
    <div class="mb-4 d-flex align-items-center gap-3">
        <form method="GET" action="<?= BASE_URL ?>administrador/reportes_tabletas.php" class="d-flex" style="max-width: 400px; flex-grow: 1;">
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
                                <button class="btn btn-preview" title="Visualizar PDF" style="background-color: #6c757d; color: #fff; border: none; padding: 6px; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                                <button class="btn btn-download" title="Descargar PDF" style="background-color: #003d2a; color: #fff; border: none; padding: 6px; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Controles de paginación -->
    <div class="d-flex justify-content-between mt-4">
        <a href="<?= BASE_URL ?>administrador/reportes_tabletas.php?page=<?= max(1, $page - 1) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="btn btn-primary <?= $page <= 1 ? 'disabled' : '' ?>" style="background-color: #003d2a; border-color: #003d2a; padding: 10px 20px; border-radius: 8px;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <span style="color: #003d2a; font-weight: 600; align-self: center;">Página <?= $page ?> de <?= $total_pages ?></span>
        <a href="<?= BASE_URL ?>administrador/reportes_tabletas.php?page=<?= min($total_pages, $page + 1) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="btn btn-primary <?= $page >= $total_pages ? 'disabled' : '' ?>" style="background-color: #003d2a; border-color: #003d2a; padding: 10px 20px; border-radius: 8px;">
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

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/views/layouts/footer-admin.php'; ?>

<!-- Incluir jsPDF desde CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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
        const table = document.getElementById('tabletasTable');
        const visibleColumns = document.querySelectorAll('#tabletasTable th:not(.hidden)');
        if (visibleColumns.length <= 1) { // Solo "Acciones" está visible
            table.style.display = 'none';
        } else {
            table.style.display = '';
        }
    }

    // Generar PDF (común para descargar y visualizar)
    function generatePDF(activo, isPreview) {
        fetch('<?= BASE_URL ?>administrador/get_tablet_data.php?activo=' + encodeURIComponent(activo), {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al obtener datos de la tableta');
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Error desconocido');
                }

                const {
                    jsPDF
                } = window.jspdf;
                const doc = new jsPDF({
                    unit: 'mm',
                    format: 'letter',
                    orientation: 'portrait'
                });

                // Configuración inicial
                const pageWidth = doc.internal.pageSize.getWidth();
                const margin = 10;
                let yPosition = margin;

                // Logos (placeholders)
                doc.setFillColor(224, 224, 224);
                doc.rect(margin, yPosition, 30, 30, 'F'); // Logo izquierda
                doc.rect(pageWidth - margin - 30, yPosition, 30, 30, 'F'); // Logo derecha

                // Título
                yPosition += 15;
                doc.setFontSize(16);
                doc.text('Reporte de Tableta', pageWidth / 2, yPosition, {
                    align: 'center'
                });
                yPosition +=40

                // Datos de la tableta
                doc.setFontSize(11);
                const lineHeight = 6;
                const colWidth = (pageWidth - 3 * margin) / 2;

                const dataLeft = [
                    ['Activo:', data.tableta.activo],
                    ['Marca:', data.tableta.marca],
                    ['Modelo:', data.tableta.modelo],
                    ['Inventario:', data.tableta.inventario || 'N/A'],
                    ['Número de Serie:', data.tableta.numero_serie],
                    ['Versión Android:', data.tableta.version_android || 'N/A'],
                    ['Año de Adquisición:', data.tableta.anio_adquisicion || 'N/A']
                ];

                const dataRight = [
                    ['Agencia:', data.tableta.agencia || 'N/A'],
                    ['Proceso:', data.tableta.proceso || 'N/A'],
                    ['RPE Trabajador:', data.tableta.rpe_trabajador],
                    ['Ubicación Registro:', data.tableta.ubicacion_registro || 'N/A'],
                    ['Fecha Registro:', data.tableta.fecha_registro || 'N/A'],
                    ['Marca Chip:', data.tableta.marca_chip || 'N/A'],
                    ['Número Serie Chip:', data.tableta.numero_serie_chip || 'N/A']
                ];

                // Imprimir datos
                const printData = (dataArray, x, yStart) => {
                    dataArray.forEach(([label, value], i) => {
                        const y = yStart + i * lineHeight;
                        doc.setFont('helvetica', 'bold');
                        doc.text(label, x, y);
                        const labelWidth = doc.getTextWidth(label) + 1;
                        doc.setFont('helvetica', 'normal');
                        doc.text(String(value), x + labelWidth, y);
                    });
                };

                let yData = yPosition;
                printData(dataLeft, margin, yData);
                printData(dataRight, margin + colWidth + margin, yData);

                // Fotos
                yPosition = yData + 50;
                doc.setFontSize(12);
                doc.text('Fotos de la Tableta:', pageWidth / 2, yPosition, {
                    align: 'center'
                });
                yPosition += 10;

                const fotoSize = 55;
                const espacioEntreFotos = 10;
                const fotosPorFila = 2;
                let xStart = (pageWidth - ((fotoSize * fotosPorFila) + espacioEntreFotos)) / 2;
                let x = xStart;
                let y = yPosition;

                // Generar un timestamp para evitar caché
                const cacheBuster = Date.now();

                // Cargar fotos dinámicamente
                let fotosCargadas = 0;
                const totalFotos = data.fotos.length || 1;

                function finalizarPDF() {
                    let yPosition = y + (data.fotos.length ? fotoSize + 20 : 20);
                    doc.setFontSize(12);
                    const firmaLabel = 'Firma:';
                    const firmaBoxWidth = 60;
                    const firmaBoxHeight = 15;
                    const firmaX = (pageWidth - firmaBoxWidth) / 2;
                    doc.text(firmaLabel, pageWidth / 2, yPosition, {
                        align: 'center'
                    });
                    yPosition += 5;
                    doc.rect(firmaX, yPosition, firmaBoxWidth, firmaBoxHeight);

                    if (isPreview) {
                        const pdfBlob = doc.output('bloburl');
                        window.open(pdfBlob, '_blank');
                        showToast('Éxito', 'Reporte previsualizado correctamente', true);
                    } else {
                        doc.save(`reporte_tableta_${activo}.pdf`);
                        showToast('Éxito', 'Reporte generado correctamente', true);
                    }
                }

                if (data.fotos.length) {
                    data.fotos.forEach((foto, index) => {
                        console.log('Cargando foto:', foto); // Log para depuración
                        const img = new Image();
                        img.crossOrigin = 'Anonymous';
                        // Añadir cache buster a la URL
                        img.src = '<?= BASE_URL ?>cfe-api/uploads/tabletas/' + foto + '?v=' + cacheBuster;
                        img.onload = () => {
                            console.log('Foto cargada correctamente:', foto);
                            const canvas = document.createElement('canvas');
                            canvas.width = img.width;
                            canvas.height = img.height;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(img, 0, 0);
                            const imgData = canvas.toDataURL('image/jpeg');
                            doc.addImage(imgData, 'JPEG', x, y, fotoSize, fotoSize);
                            if ((index + 1) % fotosPorFila === 0) {
                                y += fotoSize + espacioEntreFotos;
                                x = xStart;
                            } else {
                                x += fotoSize + espacioEntreFotos;
                            }
                            fotosCargadas++;
                            if (fotosCargadas === data.fotos.length) {
                                finalizarPDF();
                            }
                        };
                        img.onerror = () => {
                            console.error('Error al cargar foto:', foto);
                            fotosCargadas++;
                            if (fotosCargadas === data.fotos.length) {
                                finalizarPDF();
                            }
                        };
                    });
                } else {
                    console.log('No hay fotos para cargar');
                    finalizarPDF();
                }
            })
            .catch(error => {
                console.error('Error generando PDF:', error);
                showToast('Error', error.message || 'Error al generar el reporte', false);
            });
    }

    // Inicializar eventos
    document.addEventListener('DOMContentLoaded', () => {
        // Manejar botones de acción
        document.querySelectorAll('.btn-download').forEach(button => {
            button.addEventListener('click', function() {
                const activo = this.closest('tr').dataset.activo;
                generatePDF(activo, false);
            });
        });

        document.querySelectorAll('.btn-preview').forEach(button => {
            button.addEventListener('click', function() {
                const activo = this.closest('tr').dataset.activo;
                generatePDF(activo, true);
            });
        });

        // Inicializar columnas
        const savedColumns = JSON.parse(localStorage.getItem('reportesTabletasColumns')) || {};
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
        const savedOrder = JSON.parse(localStorage.getItem('reportesTabletasColumnOrder')) || [];
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
        const savedColumns = JSON.parse(localStorage.getItem('reportesTabletasColumns')) || {};
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
        localStorage.setItem('reportesTabletasColumns', JSON.stringify(visibleColumns));
        adjustTableWidth();
        bootstrap.Modal.getInstance(document.getElementById('columnModal')).hide();
    });

    // Resetear configuración de columnas
    document.getElementById('resetColumns').addEventListener('click', () => {
        localStorage.removeItem('reportesTabletasColumns');
        const checkboxes = document.querySelectorAll('#columnSelectForm .form-check-input');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
            checkbox.style.display = 'inline-block';
            checkbox.style.visibility = 'visible';
            document.querySelectorAll(`[data-col-id="${checkbox.dataset.colId}"]`).forEach(el => {
                el.classList.remove('hidden');
            });
        });
        localStorage.setItem('reportesTabletasColumns', JSON.stringify({}));
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
            localStorage.setItem('reportesTabletasColumnOrder', JSON.stringify(newOrder));

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

    .btn-preview,
    .btn-download {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .btn-preview:hover {
        background-color: #5a6268;
        transform: scale(1.1);
        box-shadow: 0 3px 6px rgba(108, 117, 125, 0.3);
    }

    .btn-download:hover {
        background-color: #00261d;
        transform: scale(1.1);
        box-shadow: 0 3px 6px rgba(0, 61, 42, 0.3);
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