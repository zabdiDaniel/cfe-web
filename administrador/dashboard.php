<?php
// dashboard.php
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

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

try {
    // Total registradores
    $stmt_registradores = $conexion->query("SELECT COUNT(*) as total FROM registradores");
    if (!$stmt_registradores) throw new Exception("Error en registradores: " . $conexion->error);
    $total_registradores = $stmt_registradores->fetch_assoc()['total'];

    // Total trabajadores
    $stmt_trabajadores = $conexion->query("SELECT COUNT(*) as total FROM trabajadores WHERE estatus = 1");
    if (!$stmt_trabajadores) throw new Exception("Error en trabajadores: " . $conexion->error);
    $total_trabajadores = $stmt_trabajadores->fetch_assoc()['total'];

    // Total tabletas y asignadas
    $stmt_tabletas = $conexion->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN rpe_trabajador IS NOT NULL THEN 1 ELSE 0 END) as asignadas
    FROM tabletas");
    if (!$stmt_tabletas) throw new Exception("Error en tabletas: " . $conexion->error);
    $tabletas_data = $stmt_tabletas->fetch_assoc();
    $total_tabletas = $tabletas_data['total'];
    $total_asignadas = $tabletas_data['asignadas'];
    $porcentaje_asignadas = $total_tabletas > 0 ? ($total_asignadas / $total_tabletas) * 100 : 0;

    // Total fallas
    $stmt_fallas = $conexion->query("SELECT COUNT(*) as total FROM fallas_historial");
    if (!$stmt_fallas) throw new Exception("Error en fallas: " . $conexion->error);
    $total_fallas = $stmt_fallas->fetch_assoc()['total'];

    // Centros activos
    $stmt_centros = $conexion->query("SELECT COUNT(DISTINCT t.centroTrabajo) as total 
    FROM trabajadores t 
    JOIN tabletas tab ON tab.rpe_trabajador = t.rpe 
    WHERE t.centroTrabajo IS NOT NULL");
    if (!$stmt_centros) throw new Exception("Error en centros: " . $conexion->error);
    $centros_activos = $stmt_centros->fetch_assoc()['total'];

    // Actividad reciente
    $stmt_actividad = $conexion->query("
        SELECT 'Asignación' as tipo, h.tipo_asignacion as descripcion, 
               t.nombre as trabajador_nombre, r.nombre as registrador_nombre, 
               h.fecha_inicio as fecha, h.activo as tableta_id
        FROM historial_asignaciones h
        JOIN trabajadores t ON h.rpe_trabajador = t.rpe
        JOIN registradores r ON h.asignada_por = r.rpe
        WHERE h.fecha_inicio IS NOT NULL
        ORDER BY h.fecha_inicio DESC
        LIMIT 5
    ");
    if (!$stmt_actividad) throw new Exception("Error en actividad: " . $conexion->error);
    $actividad = $stmt_actividad->fetch_all(MYSQLI_ASSOC);

    // Fallas por categoría (para pie chart)
    $stmt_categorias = $conexion->query("SELECT COALESCE(categoria, 'Sin Categoría') as categoria, COUNT(*) as total 
    FROM fallas_historial 
    GROUP BY categoria 
    ORDER BY total DESC 
    LIMIT 6");
    if (!$stmt_categorias) throw new Exception("Error en categorías: " . $conexion->error);
    $categorias = $stmt_categorias->fetch_all(MYSQLI_ASSOC);
    $labels = array_column($categorias, 'categoria');
    $data = array_column($categorias, 'total');
} catch (Exception $e) {
    die("Error en las consultas: " . $e->getMessage());
}
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

    <!-- Bienvenida -->
    <div class="text-center mb-5 animate__animated animate__fadeIn">
        <h1 class="display-4" style="color: #003d2a; font-weight: 700;">
            ¡Bienvenido, <?= htmlspecialchars($_SESSION['nombre'] ?? $_SESSION['rpe']) ?>!
        </h1>
        <p class="lead" style="color: #333;">
            Controla y optimiza SIMTLEC desde aquí. Tu impacto comienza ahora.
        </p>
    </div>

    <!-- Cards de Impacto -->
    <div class="row g-4 mb-5">
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm dashboard-card animate__animated animate__fadeIn" style="border-radius: 15px; border: 1px solid #dee2e6; background: #FFFFFF;">
                <div class="card-body text-center">
                    <i class="bi bi-person-check-fill" style="font-size: 3rem; color: #003d2a;"></i>
                    <h4 class="card-title mt-3" style="color: #003d2a;">Trabajadores Activos</h4>
                    <p class="card-text display-5" style="color: #003d2a; font-weight: 600;"><?= $total_trabajadores ?></p>
                    <a href="<?= BASE_URL ?>administrador/personal.php" class="btn btn-sm mt-2" style="background-color: #003d2a; color: #fff;">Ver Personal</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm dashboard-card animate__animated animate__fadeIn" style="border-radius: 15px; border: 1px solid #dee2e6; background: #FFFFFF;">
                <div class="card-body text-center">
                    <i class="bi bi-tablet-fill" style="font-size: 3rem; color: #003d2a;"></i>
                    <h4 class="card-title mt-3" style="color: #003d2a;">% Tabletas Asignadas</h4>
                    <p class="card-text display-5" style="color: #003d2a; font-weight: 600;"><?= number_format($porcentaje_asignadas, 1) ?>%</p>
                    <a href="<?= BASE_URL ?>administrador/gestionar_tabletas.php" class="btn btn-sm mt-2" style="background-color: #003d2a; color: #fff;">Gestionar</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm dashboard-card animate__animated animate__fadeIn" style="border-radius: 15px; border: 1px solid #dee2e6; background: #FFFFFF;">
                <div class="card-body text-center">
                    <i class="bi bi-building-fill" style="font-size: 3rem; color: #003d2a;"></i>
                    <h4 class="card-title mt-3" style="color: #003d2a;">Centros Activos</h4>
                    <p class="card-text display-5" style="color: #003d2a; font-weight: 600;"><?= $centros_activos ?></p>
                    <a href="<?= BASE_URL ?>administrador/gestionar.php" class="btn btn-sm mt-2" style="background-color: #003d2a; color: #fff;">Explorar</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm dashboard-card animate__animated animate__fadeIn" style="border-radius: 15px; border: 1px solid #dee2e6; background: #FFFFFF;">
                <div class="card-body text-center">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 3rem; color: #003d2a;"></i>
                    <h4 class="card-title mt-3" style="color: #003d2a;">Fallas Reportadas</h4>
                    <p class="card-text display-5" style="color: #003d2a; font-weight: 600;"><?= $total_fallas ?></p>
                    <a href="<?= BASE_URL ?>administrador/fallas_dashboard.php" class="btn btn-sm mt-2" style="background-color: #003d2a; color: #fff;">Analizar</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline de Actividad -->
    <div class="card shadow-sm mb-5 animate__animated animate__fadeIn" style="border-radius: 15px; border: none;">
        <div class="card-header" style="background-color: #003d2a; color: #fff; border-radius: 15px 15px 0 0;">
            <h3 class="card-title mb-0">Actividad Reciente</h3>
        </div>
        <div class="card-body">
            <?php if (empty($actividad)): ?>
                <p class="text-center" style="color: #003d2a;">No hay actividad reciente.</p>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($actividad as $index => $item): ?>
                        <div class="timeline-item animate__animated animate__fadeInUp" style="animation-delay: <?= $index * 0.2 ?>s;">
                            <div class="timeline-icon">
                                <i class="bi bi-arrow-right-circle-fill" style="font-size: 1.5rem; color: #003d2a;"></i>
                            </div>
                            <div class="timeline-content">
                                <h5 style="color: #003d2a;"><?= htmlspecialchars($item['tipo']) ?>: <?= htmlspecialchars($item['descripcion']) ?></h5>
                                <p style="color: #333;">
                                    Tableta <strong><?= htmlspecialchars($item['tableta_id']) ?></strong> asignada a 
                                    <strong><?= htmlspecialchars($item['trabajador_nombre']) ?></strong> por 
                                    <strong><?= htmlspecialchars($item['registrador_nombre']) ?></strong>
                                </p>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($item['fecha'])) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Gráfico de Pastel de Fallas -->
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="card shadow-sm animate__animated animate__fadeIn" style="border-radius: 15px; border: none;">
                <div class="card-header" style="background-color: #003d2a; color: #fff; border-radius: 15px 15px 0 0;">
                    <h3 class="card-title mb-0">Distribución de Fallas</h3>
                </div>
                <div class="card-body">
                    <canvas id="fallasChart" style="max-height: 350px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm animate__animated animate__fadeIn" style="border-radius: 15px; border: none;">
                <div class="card-header" style="background-color: #003d2a; color: #fff; border-radius: 15px 15px 0 0;">
                    <h3 class="card-title mb-0">Próximos Pasos</h3>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <?php if ($total_tabletas < 150): ?>
                            <li class="mb-3">
                                <i class="bi bi-check-circle-fill me-2" style="color: #003d2a;"></i>
                                Registra <strong><?= 150 - $total_tabletas ?> tabletas</strong> para alcanzar la meta.
                                <a href="<?= BASE_URL ?>administrador/gestionar_tabletas.php" class="text-decoration-none ms-1" style="color: #003d2a;">(Ir)</a>
                            </li>
                        <?php else: ?>
                            <li class="mb-3">
                                <i class="bi bi-trophy-fill me-2" style="color: #28a745;"></i>
                                ¡Meta de 150 tabletas alcanzada! Optimiza asignaciones.
                                <a href="<?= BASE_URL ?>administrador/gestionar.php" class="text-decoration-none ms-1" style="color: #003d2a;">(Ir)</a>
                            </li>
                        <?php endif; ?>
                        <?php if ($total_fallas > 0): ?>
                            <li class="mb-3">
                                <i class="bi bi-exclamation-triangle-fill me-2" style="color: #dc3545;"></i>
                                Hay <strong><?= $total_fallas ?> fallas</strong> reportadas. Analízalas.
                                <a href="<?= BASE_URL ?>administrador/fallas_dashboard.php" class="text-decoration-none ms-1" style="color: #003d2a;">(Ir)</a>
                            </li>
                        <?php endif; ?>
                        <li class="mb-3">
                            <i class="bi bi-person-plus-fill me-2" style="color: #003d2a;"></i>
                            Revisa el personal activo para nuevas asignaciones.
                            <a href="<?= BASE_URL ?>administrador/personal.php" class="text-decoration-none ms-1" style="color: #003d2a;">(Ir)</a>
                        </li>
                    </ul>
                </div>
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

    // Gráfico de Pastel de Fallas
    const ctx = document.getElementById('fallasChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                data: <?= json_encode($data) ?>,
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
                        color: '#003d2a',
                        font: {
                            size: 14
                        }
                    }
                },
                tooltip: {
                    backgroundColor: '#003d2a',
                    titleColor: '#FFFFFF',
                    bodyColor: '#FFFFFF'
                }
            },
            animation: {
                duration: 1500,
                easing: 'easeOutBounce'
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

.dashboard-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 61, 42, 0.3) !important;
}

.btn:hover {
    background-color: #00261d !important;
}

.form-control:focus {
    border-color: #003d2a;
    box-shadow: 0 0 5px rgba(0, 61, 42, 0.5);
}

/* Timeline */
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 20px;
    width: 4px;
    background: #E2EFDA;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-icon {
    position: absolute;
    left: 10px;
    top: 0;
    width: 24px;
    height: 24px;
    background: #FFFFFF;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.timeline-content {
    margin-left: 50px;
    background: #FFFFFF;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 61, 42, 0.1);
}

@media (max-width: 576px) {
    .container {
        padding-top: 10px;
    }

    .dashboard-card {
        margin-bottom: 15px;
    }

    .card-body {
        padding: 15px;
    }

    .display-5 {
        font-size: 1.8rem;
    }

    .col-md-3 {
        flex: 0 0 50%;
        max-width: 50%;
    }

    .timeline:before {
        left: 15px;
    }

    .timeline-icon {
        left: 5px;
    }

    .timeline-content {
        margin-left: 40px;
    }
}
</style>