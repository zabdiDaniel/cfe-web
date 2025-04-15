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

// Incluir la conexión a la base de datos
require_once $_SERVER['DOCUMENT_ROOT'] . '/app/config/database.php';

// Verificar la conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener datos para el dashboard
try {
    // Conteo de registradores, trabajadores y tabletas
    $stmt_registradores = $conexion->query("SELECT COUNT(*) as total FROM registradores");
    if (!$stmt_registradores) throw new Exception("Error en registradores: " . $conexion->error);
    $total_registradores = $stmt_registradores->fetch_assoc()['total'];

    $stmt_trabajadores = $conexion->query("SELECT COUNT(*) as total FROM trabajadores");
    if (!$stmt_trabajadores) throw new Exception("Error en trabajadores: " . $conexion->error);
    $total_trabajadores = $stmt_trabajadores->fetch_assoc()['total'];

    $stmt_tabletas = $conexion->query("SELECT COUNT(*) as total FROM tabletas");
    if (!$stmt_tabletas) throw new Exception("Error en tabletas: " . $conexion->error);
    $total_tabletas = $stmt_tabletas->fetch_assoc()['total'];

    // Conteo de fallas
    $stmt_fallas = $conexion->query("SELECT COUNT(*) as total FROM fallas_historial");
    if (!$stmt_fallas) throw new Exception("Error en fallas: " . $conexion->error);
    $total_fallas = $stmt_fallas->fetch_assoc()['total'];

    // Conteo de tabletas asignadas
    $stmt_asignadas = $conexion->query("SELECT COUNT(*) as total FROM historial_asignaciones WHERE fecha_fin IS NULL OR fecha_fin > NOW()");
    if (!$stmt_asignadas) throw new Exception("Error en asignaciones: " . $conexion->error);
    $total_asignadas = $stmt_asignadas->fetch_assoc()['total'];

    // Actividad reciente (asignaciones con trabajador y registrador)
    $stmt_actividad = $conexion->query("
        SELECT 'Asignación' as tipo, h.tipo_asignacion as descripcion, 
               t.nombre as trabajador_nombre, r.nombre as registrador_nombre, 
               h.fecha_inicio as fecha
        FROM historial_asignaciones h
        JOIN trabajadores t ON h.rpe_trabajador = t.rpe
        JOIN registradores r ON h.asignada_por = r.rpe
        WHERE h.fecha_inicio IS NOT NULL
        ORDER BY h.fecha_inicio DESC
        LIMIT 5
    ");
    if (!$stmt_actividad) throw new Exception("Error en actividad: " . $conexion->error);
    $actividad = $stmt_actividad->fetch_all(MYSQLI_ASSOC);

    // Datos para gráfico de fallas por categoría
    $stmt_categorias = $conexion->query("SELECT categoria, COUNT(*) as total FROM fallas_historial GROUP BY categoria");
    if (!$stmt_categorias) throw new Exception("Error en categorías: " . $conexion->error);
    $categorias = $stmt_categorias->fetch_all(MYSQLI_ASSOC);
    $labels = array_column($categorias, 'categoria');
    $data = array_column($categorias, 'total');
} catch (Exception $e) {
    die("Error en las consultas: " . $e->getMessage());
}
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/views/layouts/header-admin.php'; ?>

<div class="container mt-5">
    <h1 class="mb-4" style="color: var(--primary);">Bienvenido, <?= htmlspecialchars($_SESSION['rpe']) ?></h1>
    
    <!-- Tarjetas de Resumen -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card h-100 shadow-sm" style="background-color: var(--secondary); border: none;">
                <div class="card-body text-center">
                    <i class="bi bi-people-fill" style="font-size: 2.5rem; color: var(--primary);"></i>
                    <h3 class="card-title mt-3" style="color: var(--primary);">Registradores</h3>
                    <p class="card-text" style="font-size: 2rem; font-weight: 600; color: var(--text-dark);"><?= $total_registradores ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 shadow-sm" style="background-color: var(--secondary); border: none;">
                <div class="card-body text-center">
                    <i class="bi bi-person-fill" style="font-size: 2.5rem; color: var(--primary);"></i>
                    <h3 class="card-title mt-3" style="color: var(--primary);">Trabajadores</h3>
                    <p class="card-text" style="font-size: 2rem; font-weight: 600; color: var(--text-dark);"><?= $total_trabajadores ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 shadow-sm" style="background-color: var(--secondary); border: none;">
                <div class="card-body text-center">
                    <i class="bi bi-tablet-fill" style="font-size: 2.5rem; color: var(--primary);"></i>
                    <h3 class="card-title mt-3" style="color: var(--primary);">Tabletas</h3>
                    <p class="card-text" style="font-size: 2rem; font-weight: 600; color: var(--text-dark);"><?= $total_tabletas ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 shadow-sm" style="background-color: var(--secondary); border: none;">
                <div class="card-body text-center">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 2.5rem; color: var(--primary);"></i>
                    <h3 class="card-title mt-3" style="color: var(--primary);">Fallas Reportadas</h3>
                    <p class="card-text" style="font-size: 2rem; font-weight: 600; color: var(--text-dark);"><?= $total_fallas ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Actividad Reciente -->
    <div class="card shadow-sm mb-4">
        <div class="card-header" style="background-color: var(--primary); color: white;">
            <h3 class="card-title mb-0">Actividad Reciente</h3>
        </div>
        <div class="card-body">
            <?php if (empty($actividad)): ?>
                <p class="text-muted">No hay actividad reciente.</p>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($actividad as $item): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($item['tipo']) ?>:</strong>
                                    <?= htmlspecialchars($item['descripcion']) ?> 
                                    para <?= htmlspecialchars($item['trabajador_nombre']) ?> 
                                    por <?= htmlspecialchars($item['registrador_nombre']) ?>
                                </div>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($item['fecha'])) ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Gráfico de Fallas por Categoría -->
    <div class="card shadow-sm mb-4">
        <div class="card-header" style="background-color: var(--primary); color: white;">
            <h3 class="card-title mb-0">Fallas por Categoría</h3>
        </div>
        <div class="card-body">
            <canvas id="fallasChart" style="max-height: 300px;"></canvas>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/views/layouts/footer-admin.php'; ?>

<style>
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 61, 42, 0.3);
    }
    .list-group-item {
        border-left: 3px solid var(--primary);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Gráfico de Fallas por Categoría
    const ctx = document.getElementById('fallasChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Número de Fallas',
                data: <?= json_encode($data) ?>,
                backgroundColor: 'rgba(0, 61, 42, 0.8)',
                borderColor: 'rgba(0, 61, 42, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
</script>