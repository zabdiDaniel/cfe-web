<?php
// gestionar.php
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
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$centro_trabajo = isset($_GET['centro_trabajo']) ? trim($_GET['centro_trabajo']) : '';

// Consulta: Total trabajadores activos
$trabajadores_query = "SELECT COUNT(*) as total FROM trabajadores WHERE estatus = 1";
if ($centro_trabajo) {
    $trabajadores_query .= " AND centroTrabajo = ?";
}
$stmt = $conexion->prepare($trabajadores_query);
if ($centro_trabajo) {
    $stmt->bind_param("s", $centro_trabajo);
}
$stmt->execute();
$total_trabajadores = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Consulta: Total tabletas
$tabletas_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN rpe_trabajador IS NOT NULL THEN 1 ELSE 0 END) as asignadas
FROM tabletas";
if ($centro_trabajo) {
    $tabletas_query .= " WHERE rpe_trabajador IN (SELECT rpe FROM trabajadores WHERE centroTrabajo = ?)";
}
$stmt = $conexion->prepare($tabletas_query);
if ($centro_trabajo) {
    $stmt->bind_param("s", $centro_trabajo);
}
$stmt->execute();
$tabletas_data = $stmt->get_result()->fetch_assoc();
$total_tabletas = $tabletas_data['total'];
$asignadas = $tabletas_data['asignadas'];
$no_asignadas = $total_tabletas - $asignadas;
$stmt->close();

// Consulta: Fallas este mes
$fallas_query = "SELECT COUNT(*) as total 
FROM fallas_historial fh
JOIN historial_asignaciones ha ON fh.historial_id = ha.id
WHERE ha.fecha_inicio BETWEEN ? AND ?";
$bind_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
if ($centro_trabajo) {
    $fallas_query .= " AND ha.rpe_trabajador IN (SELECT rpe FROM trabajadores WHERE centroTrabajo = ?)";
    $bind_params[] = $centro_trabajo;
}
$stmt = $conexion->prepare($fallas_query);
$stmt->bind_param(str_repeat("s", count($bind_params)), ...$bind_params);
$stmt->execute();
$total_fallas = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Consulta: Tabletas con fallas
$fallas_tabletas_query = "SELECT COUNT(DISTINCT ha.activo) as total
FROM fallas_historial fh
JOIN historial_asignaciones ha ON fh.historial_id = ha.id
WHERE ha.fecha_inicio BETWEEN ? AND ?";
$bind_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
if ($centro_trabajo) {
    $fallas_tabletas_query .= " AND ha.rpe_trabajador IN (SELECT rpe FROM trabajadores WHERE centroTrabajo = ?)";
    $bind_params[] = $centro_trabajo;
}
$stmt = $conexion->prepare($fallas_tabletas_query);
$stmt->bind_param(str_repeat("s", count($bind_params)), ...$bind_params);
$stmt->execute();
$fallas_tabletas = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Consulta: Últimas asignaciones
$asignaciones_query = "SELECT ha.activo, ha.rpe_trabajador, t.nombre, ha.fecha_inicio, ha.tipo_asignacion
FROM historial_asignaciones ha
LEFT JOIN trabajadores t ON ha.rpe_trabajador = t.rpe
WHERE ha.fecha_fin IS NULL";
if ($centro_trabajo) {
    $asignaciones_query .= " AND ha.rpe_trabajador IN (SELECT rpe FROM trabajadores WHERE centroTrabajo = ?)";
}
$asignaciones_query .= " ORDER BY ha.fecha_inicio DESC LIMIT 5";
$stmt = $conexion->prepare($asignaciones_query);
if ($centro_trabajo) {
    $stmt->bind_param("s", $centro_trabajo);
}
$stmt->execute();
$asignaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Consulta: Datos para gráfico de tabletas
$chart_tabletas_data = [
    'Asignadas' => $asignadas,
    'No Asignadas' => $no_asignadas,
    'Con Fallas' => $fallas_tabletas
];

// Consulta: Datos para gráfico de fallas (por semana)
$fallas_por_semana = [];
$start = new DateTime($start_date);
$end = new DateTime($end_date);
$interval = new DateInterval('P1W');
$period = new DatePeriod($start, $interval, $end->modify('+1 day'));
$labels = [];
foreach ($period as $date) {
    $week_start = $date->format('Y-m-d');
    $week_end = $date->modify('+6 days')->format('Y-m-d');
    $labels[] = "Sem {$date->format('W')}";

    $query = "SELECT COUNT(*) as total 
    FROM fallas_historial fh
    JOIN historial_asignaciones ha ON fh.historial_id = ha.id
    WHERE ha.fecha_inicio BETWEEN ? AND ?";
    $bind_params = [$week_start . ' 00:00:00', $week_end . ' 23:59:59'];
    if ($centro_trabajo) {
        $query .= " AND ha.rpe_trabajador IN (SELECT rpe FROM trabajadores WHERE centroTrabajo = ?)";
        $bind_params[] = $centro_trabajo;
    }

    $stmt = $conexion->prepare($query);
    $stmt->bind_param(str_repeat("s", count($bind_params)), ...$bind_params);
    $stmt->execute();
    $fallas_por_semana[] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}

// Opciones de centro de trabajo
$centros_query = "SELECT DISTINCT centroTrabajo FROM trabajadores WHERE centroTrabajo IS NOT NULL ORDER BY centroTrabajo";
$centros = $conexion->query($centros_query)->fetch_all(MYSQLI_ASSOC);
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

    <h1 class="mb-4" style="color: #003d2a; font-weight: 700;">Gestión General</h1>

    <!-- Filtros -->
    <div class="mb-4 d-flex align-items-center gap-3 flex-wrap">
        <form method="GET" action="<?= BASE_URL ?>administrador/gestionar.php" class="d-flex gap-2 flex-wrap" style="max-width: 600px;">
            <div>
                <label for="start_date" class="form-label">Fecha Inicio</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>" style="border-radius: 8px; border-color: #003d2a;">
            </div>
            <div>
                <label for="end_date" class="form-label">Fecha Fin</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>" style="border-radius: 8px; border-color: #003d2a;">
            </div>
            <div>
                <label for="centro_trabajo" class="form-label">Centro de Trabajo</label>
                <select name="centro_trabajo" id="centro_trabajo" class="form-control" style="border-radius: 8px; border-color: #003d2a;">
                    <option value="">Todos</option>
                    <?php foreach ($centros as $centro): ?>
                        <option value="<?= htmlspecialchars($centro['centroTrabajo']) ?>" <?= $centro_trabajo === $centro['centroTrabajo'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($centro['centroTrabajo']) ?>
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
        <div class="col-md-2 col-sm-6">
            <div class="card shadow-sm dashboard-card animate__animated animate__fadeIn" style="border-radius: 10px; border: none;">
                <div class="card-body text-center" style="background-color: #E2EFDA;">
                    <h5 class="card-title" style="color: #003d2a;">Trabajadores Activos</h5>
                    <p class="card-text display-6" style="color: #003d2a;"><?= $total_trabajadores ?></p>
                    <a href="<?= BASE_URL ?>administrador/personal.php" class="btn btn-sm" style="background-color: #003d2a; color: #fff;">Ver Detalles</a>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="card shadow-sm dashboard-card animate__animated animate__fadeIn" style="border-radius: 10px; border: none;">
                <div class="card-body text-center" style="background-color: #FFFFFF;">
                    <h5 class="card-title" style="color: #003d2a;">Tabletas Registradas</h5>
                    <p class="card-text display-6" style="color: #003d2a;"><?= $total_tabletas ?></p>
                    <a href="<?= BASE_URL ?>administrador/gestionar_tabletas.php" class="btn btn-sm" style="background-color: #003d2a; color: #fff;">Ver Detalles</a>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="card shadow-sm dashboard-card animate__animated animate__fadeIn" style="border-radius: 10px; border: none;">
                <div class="card-body text-center" style="background-color: #E2EFDA;">
                    <h5 class="card-title" style="color: #003d2a;">Tabletas Asignadas</h5>
                    <p class="card-text display-6" style="color: #003d2a;"><?= $asignadas ?></p>
                    <a href="<?= BASE_URL ?>administrador/gestionar_tabletas.php" class="btn btn-sm" style="background-color: #003d2a; color: #fff;">Ver Detalles</a>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="card shadow-sm dashboard-card animate__animated animate__fadeIn" style="border-radius: 10px; border: none;">
                <div class="card-body text-center" style="background-color: #FFFFFF;">
                    <h5 class="card-title" style="color: #003d2a;">Fallas Reportadas</h5>
                    <p class="card-text display-6" style="color: #003d2a;"><?= $total_fallas ?></p>
                    <a href="<?= BASE_URL ?>administrador/fallas_dashboard.php" class="btn btn-sm" style="background-color: #003d2a; color: #fff;">Ver Detalles</a>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="card shadow-sm dashboard-card animate__animated animate__fadeIn" style="border-radius: 10px; border: none;">
                <div class="card-body text-center" style="background-color: #E2EFDA;">
                    <h5 class="card-title" style="color: #003d2a;">Progreso Meta Tabletas</h5>
                    <p class="card-text" style="color: #003d2a; font-size: 1.2rem;">
                        <?= $total_tabletas ?> de 150
                    </p>
                    <p class="card-text" style="color: <?= ($total_tabletas >= 150) ? '#28a745' : '#dc3545' ?>; font-weight: 600;">
                        <?= ($total_tabletas >= 150) ? '¡Meta alcanzada!' : (150 - $total_tabletas) . ' faltantes' ?>
                    </p>
                    <a href="<?= BASE_URL ?>administrador/gestionar_tabletas.php" class="btn btn-sm" style="background-color: #003d2a; color: #fff;">Gestionar Tabletas</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mb-4 g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm animate__animated animate__fadeIn" style="border-radius: 10px; border: none;">
                <div class="card-body">
                    <h5 class="card-title" style="color: #003d2a;">Estado de Tabletas</h5>
                    <canvas id="tabletasChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm animate__animated animate__fadeIn" style="border-radius: 10px; border: none;">
                <div class="card-body">
                    <h5 class="card-title" style="color: #003d2a;">Fallas por Semana</h5>
                    <canvas id="fallasChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Asignaciones -->
    <div class="card shadow-sm animate__animated animate__fadeIn" style="border-radius: 10px; border: none;">
        <div class="card-body">
            <h5 class="card-title" style="color: #003d2a;">Últimas Asignaciones</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead style="background-color: #003d2a; color: #fff;">
                        <tr>
                            <th>Activo</th>
                            <th>RPE</th>
                            <th>Nombre</th>
                            <th>Fecha Inicio</th>
                            <th>Tipo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($asignaciones)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No hay asignaciones recientes.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($asignaciones as $index => $asignacion): ?>
                                <tr class="<?= $index % 2 === 0 ? 'row-dark' : 'row-light' ?>">
                                    <td><?= htmlspecialchars($asignacion['activo']) ?></td>
                                    <td><?= htmlspecialchars($asignacion['rpe_trabajador']) ?></td>
                                    <td><?= htmlspecialchars($asignacion['nombre'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($asignacion['fecha_inicio'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($asignacion['tipo_asignacion']) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>administrador/gestionar_tabletas.php?search=<?= urlencode($asignacion['activo']) ?>" class="btn btn-sm" style="background-color: #003d2a; color: #fff;">
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
    // Toast para mensajes
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

    // Toggler debugging
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

        // Gráfico de tabletas
        const tabletasCtx = document.getElementById('tabletasChart').getContext('2d');
        new Chart(tabletasCtx, {
            type: 'doughnut',
            data: {
                labels: ['Asignadas', 'No Asignadas', 'Con Fallas'],
                datasets: [{
                    data: [<?= $chart_tabletas_data['Asignadas'] ?>, <?= $chart_tabletas_data['No Asignadas'] ?>, <?= $chart_tabletas_data['Con Fallas'] ?>],
                    backgroundColor: ['#003d2a', '#E2EFDA', '#dc3545'],
                    borderColor: ['#FFFFFF'],
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

        // Gráfico de fallas
        const fallasCtx = document.getElementById('fallasChart').getContext('2d');
        new Chart(fallasCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Fallas Reportadas',
                    data: <?= json_encode($fallas_por_semana) ?>,
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

        .col-md-2 {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }
</style>