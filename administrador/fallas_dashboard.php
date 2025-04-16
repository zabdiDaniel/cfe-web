<?php
// fallas_dashboard.php
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

// Filtros
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01', strtotime('-1 year'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
$activo = isset($_GET['activo']) ? trim($_GET['activo']) : '';

// Consulta: Total fallas
$fallas_query = "SELECT COUNT(*) as total 
FROM fallas_historial fh
JOIN historial_asignaciones ha ON fh.historial_id = ha.id
WHERE ha.fecha_inicio BETWEEN ? AND ?";
$bind_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
$types = "ss";
if ($categoria) {
    $fallas_query .= " AND fh.categoria = ?";
    $bind_params[] = $categoria;
    $types .= "s";
}
if ($activo) {
    $fallas_query .= " AND ha.activo = ?";
    $bind_params[] = $activo;
    $types .= "s";
}
$stmt = $conexion->prepare($fallas_query);
$stmt->bind_param($types, ...$bind_params);
$stmt->execute();
$total_fallas = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Consulta: Categorías más comunes
$categorias_query = "SELECT fh.categoria, COUNT(*) as total
FROM fallas_historial fh
JOIN historial_asignaciones ha ON fh.historial_id = ha.id
WHERE ha.fecha_inicio BETWEEN ? AND ?
GROUP BY fh.categoria
ORDER BY total DESC
LIMIT 3";
$bind_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
$types = "ss";
if ($activo) {
    $categorias_query .= " AND ha.activo = ?";
    $bind_params[] = $activo;
    $types .= "s";
}
$stmt = $conexion->prepare($categorias_query);
$stmt->bind_param($types, ...$bind_params);
$stmt->execute();
$categorias_top = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Consulta: Tabletas con más fallas
$tabletas_fallas_query = "SELECT ha.activo, COUNT(*) as total
FROM fallas_historial fh
JOIN historial_asignaciones ha ON fh.historial_id = ha.id
WHERE ha.fecha_inicio BETWEEN ? AND ?
GROUP BY ha.activo
ORDER BY total DESC
LIMIT 3";
$bind_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
$types = "ss";
if ($categoria) {
    $tabletas_fallas_query .= " AND fh.categoria = ?";
    $bind_params[] = $categoria;
    $types .= "s";
}
$stmt = $conexion->prepare($tabletas_fallas_query);
$stmt->bind_param($types, ...$bind_params);
$stmt->execute();
$tabletas_top = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Consulta: Gráfico de categorías
$categorias_chart_query = "SELECT fh.categoria, COUNT(*) as total
FROM fallas_historial fh
JOIN historial_asignaciones ha ON fh.historial_id = ha.id
WHERE ha.fecha_inicio BETWEEN ? AND ?
GROUP BY fh.categoria";
$bind_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
$types = "ss";
if ($activo) {
    $categorias_chart_query .= " AND ha.activo = ?";
    $bind_params[] = $activo;
    $types .= "s";
}
$stmt = $conexion->prepare($categorias_chart_query);
$stmt->bind_param($types, ...$bind_params);
$stmt->execute();
$categorias_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$chart_labels = array_column($categorias_data, 'categoria');
$chart_data = array_column($categorias_data, 'total');
$stmt->close();

// Consulta: Gráfico de fallas por mes
$fallas_por_mes = [];
$labels_mes = [];
$start = new DateTime($start_date);
$end = new DateTime($end_date);
$interval = new DateInterval('P1M');
$period = new DatePeriod($start, $interval, $end->modify('+1 day'));
foreach ($period as $date) {
    $month_start = $date->format('Y-m-01');
    $month_end = $date->format('Y-m-t');
    $labels_mes[] = $date->format('M Y');
    
    $query = "SELECT COUNT(*) as total 
    FROM fallas_historial fh
    JOIN historial_asignaciones ha ON fh.historial_id = ha.id
    WHERE ha.fecha_inicio BETWEEN ? AND ?";
    $bind_params = [$month_start . ' 00:00:00', $month_end . ' 23:59:59'];
    $types = "ss";
    if ($categoria) {
        $query .= " AND fh.categoria = ?";
        $bind_params[] = $categoria;
        $types .= "s";
    }
    if ($activo) {
        $query .= " AND ha.activo = ?";
        $bind_params[] = $activo;
        $types .= "s";
    }
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param($types, ...$bind_params);
    $stmt->execute();
    $fallas_por_mes[] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}

// Consulta: Tabletas con fallas
$fallas_table_query = "SELECT ha.activo, fh.categoria, fh.falla, MAX(ha.fecha_inicio) as ultima_falla
FROM fallas_historial fh
JOIN historial_asignaciones ha ON fh.historial_id = ha.id
WHERE ha.fecha_inicio BETWEEN ? AND ?
GROUP BY ha.activo, fh.categoria, fh.falla
ORDER BY ultima_falla DESC
LIMIT 10";
$bind_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
$types = "ss";
if ($categoria) {
    $fallas_table_query .= " AND fh.categoria = ?";
    $bind_params[] = $categoria;
    $types .= "s";
}
if ($activo) {
    $fallas_table_query .= " AND ha.activo = ?";
    $bind_params[] = $activo;
    $types .= "s";
}
$stmt = $conexion->prepare($fallas_table_query);
$stmt->bind_param($types, ...$bind_params);
$stmt->execute();
$fallas_table = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Opciones de filtros
$categorias_filtro = $conexion->query("SELECT DISTINCT categoria FROM fallas_historial ORDER BY categoria")->fetch_all(MYSQLI_ASSOC);
$activos_filtro = $conexion->query("SELECT DISTINCT activo FROM tabletas ORDER BY activo")->fetch_all(MYSQLI_ASSOC);
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/views/layouts/header-admin.php'; ?>

<div class="container mt-3">
    <!-- Toast -->
    <div class="position-fixed start-50 translate-middle-x p-3" style="z-index: 1055; top: 70px; width: 100%; max-width: 400px;">
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

    <h1 class="mb-4" style="color: #003d2a; font-weight: 700;">Dashboard de Fallas</h1>

    <!-- Filtros -->
    <div class="mb-4 d-flex align-items-center gap-3 flex-wrap">
        <form method="GET" action="<?= BASE_URL ?>administrador/fallas_dashboard.php" class="d-flex gap-2 flex-wrap" style="max-width: 800px;">
            <div>
                <label for="start_date" class="form-label">Fecha Inicio</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>" style="border-radius: 8px; border-color: #003d2a;">
            </div>
            <div>
                <label for="end_date" class="form-label">Fecha Fin</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>" style="border-radius: 8px; border-color: #003d2a;">
            </div>
            <div>
                <label for="categoria" class="form-label">Categoría</label>
                <select name="categoria" id="categoria" class="form-control" style="border-radius: 8px; border-color: #003d2a;">
                    <option value="">Todas</option>
                    <?php foreach ($categorias_filtro as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['categoria']) ?>" <?= $categoria === $cat['categoria'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['categoria']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="activo" class="form-label">Tableta</label>
                <select name="activo" id="activo" class="form-control" style="border-radius: 8px; border-color: #003d2a;">
                    <option value="">Todas</option>
                    <?php foreach ($activos_filtro as $act): ?>
                        <option value="<?= htmlspecialchars($act['activo']) ?>" <?= $activo === $act['activo'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($act['activo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="align-self-end">
                <button type="submit" class="btn" style="background-color: #003d2a; color: #fff; border-radius: 8px;">Filtrar</button>
            </div>
        </form>
    </div>

    <!-- Cards de Resumen -->
    <div class="row mb-4 g-3">
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm dashboard-card animate__animated animate__fadeIn" style="border-radius: 10px; border: none;">
                <div class="card-body text-center" style="background-color: #E2EFDA;">
                    <h5 class="card-title" style="color: #003d2a;">Total Fallas</h5>
                    <p class="card-text display-6" style="color: #003d2a;"><?= $total_fallas ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm dashboard-card animate__animated animate__fadeIn" style="border-radius: 10px; border: none;">
                <div class="card-body" style="background-color: #FFFFFF;">
                    <h5 class="card-title" style="color: #003d2a;">Categorías Comunes</h5>
                    <ul class="list-unstyled">
                        <?php foreach ($categorias_top as $cat): ?>
                            <li style="color: #003d2a;"><?= htmlspecialchars($cat['categoria']) ?>: <?= $cat['total'] ?></li>
                        <?php endforeach; ?>
                        <?php if (empty($categorias_top)): ?>
                            <li style="color: #003d2a;">Sin datos</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm dashboard-card animate__animated animate__fadeIn" style="border-radius: 10px; border: none;">
                <div class="card-body" style="background-color: #E2EFDA;">
                    <h5 class="card-title" style="color: #003d2a;">Tabletas con Más Fallas</h5>
                    <ul class="list-unstyled">
                        <?php foreach ($tabletas_top as $tab): ?>
                            <li style="color: #003d2a;"><?= htmlspecialchars($tab['activo']) ?>: <?= $tab['total'] ?></li>
                        <?php endforeach; ?>
                        <?php if (empty($tabletas_top)): ?>
                            <li style="color: #003d2a;">Sin datos</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mb-4 g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm animate__animated animate__fadeIn" style="border-radius: 10px; border: none;">
                <div class="card-body">
                    <h5 class="card-title" style="color: #003d2a;">Fallas por Categoría</h5>
                    <canvas id="categoriasChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm animate__animated animate__fadeIn" style="border-radius: 10px; border: none;">
                <div class="card-body">
                    <h5 class="card-title" style="color: #003d2a;">Fallas por Mes</h5>
                    <canvas id="fallasMesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Fallas -->
    <div class="card shadow-sm animate__animated animate__fadeIn" style="border-radius: 10px; border: none;">
        <div class="card-body">
            <h5 class="card-title" style="color: #003d2a;">Tabletas con Fallas</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead style="background-color: #003d2a; color: #fff;">
                        <tr>
                            <th>Activo</th>
                            <th>Categoría</th>
                            <th>Falla</th>
                            <th>Última Falla</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fallas_table)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No hay fallas registradas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fallas_table as $index => $falla): ?>
                                <tr class="<?= $index % 2 === 0 ? 'row-dark' : 'row-light' ?>">
                                    <td><?= htmlspecialchars($falla['activo']) ?></td>
                                    <td><?= htmlspecialchars($falla['categoria']) ?></td>
                                    <td><?= htmlspecialchars($falla['falla']) ?></td>
                                    <td><?= htmlspecialchars($falla['ultima_falla'] ?? 'N/A') ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>administrador/gestionar_tabletas.php?search=<?= urlencode($falla['activo']) ?>" class="btn btn-sm" style="background-color: #003d2a; color: #fff;">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/views/layouts/footer-admin.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- Animate.css -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

<script>
// Toast
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

// Toggler
document.addEventListener('DOMContentLoaded', () => {
    const toggler = document.querySelector('.navbar-toggler');
    if (toggler) {
        toggler.addEventListener('click', (e) => {
            console.log('Toggler clicked at:', e.clientX, e.clientY);
        });
        toggler.addEventListener('touchstart', (e) => {
            console.log('Toggler touched at:', e.touches[0].clientX, e.touches[0].clientY);
        });
    }

    // Gráfico de categorías
    const categoriasCtx = document.getElementById('categoriasChart').getContext('2d');
    new Chart(categoriasCtx, {
        type: 'pie',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                data: <?= json_encode($chart_data) ?>,
                backgroundColor: ['#003d2a', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6c757d'],
                borderColor: '#FFFFFF',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#003d2a'
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutQuad'
            }
        }
    });

    // Gráfico de fallas por mes
    const fallasMesCtx = document.getElementById('fallasMesChart').getContext('2d');
    new Chart(fallasMesCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels_mes) ?>,
            datasets: [{
                label: 'Fallas Reportadas',
                data: <?= json_encode($fallas_por_mes) ?>,
                borderColor: '#003d2a',
                backgroundColor: 'rgba(0, 61, 42, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#003d2a'
                    },
                    grid: {
                        color: '#E2EFDA'
                    }
                },
                x: {
                    ticks: {
                        color: '#003d2a'
                    },
                    grid: {
                        display: false
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutQuad'
            }
        }
    });
});
</script>

<style>
.navbar {
    z-index: 1060 !important;
}

.navbar-toggler {
    padding: 8px !important;
    touch-action: manipulation;
}

.navbar-toggler:focus {
    outline: none;
}

.container {
    padding-top: 10px;
}

.table th,
.table td {
    vertical-align: middle;
    text-align: center;
    padding: 8px;
    font-size: 14px;
}

.table-responsive {
    border-radius: 10px;
    border: 1px solid #dee2e6;
}

.table thead {
    background-color: #003d2a;
    color: #fff;
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

.dashboard-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0, 61, 42, 0.2) !important;
}

.form-control:focus {
    border-color: #003d2a;
    box-shadow: 0 0 5px rgba(0, 61, 42, 0.5);
}

.btn:hover {
    background-color: #00261d !important;
}

@media (max-width: 576px) {
    .container {
        padding-top: 10px;
    }

    .mb-4.d-flex {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }

    .mb-4.d-flex .form-control,
    .mb-4.d-flex .btn {
        width: 100%;
    }

    .dashboard-card {
        margin-bottom: 15px;
    }

    .card-body {
        padding: 15px;
    }

    .display-6 {
        font-size: 2rem;
    }
}
</style>