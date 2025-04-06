<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['rpe'])) {
    header("Location: login.php");
    exit;
}

// Configuración básica
$basePath = '/cfe-web'; // Ajusta esto según tu estructura
$pageTitle = 'Panel de Control';

// Incluir configuración de la base de datos
require_once __DIR__ . '/app/config/database.php';

// Obtener datos del usuario (simplificado por ahora)
$rpe = $_SESSION['rpe'];

// Incluir vistas
require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3>Panel de Control</h3>
                </div>
                <div class="card-body">
                    <h4 class="text-center">Bienvenido, <?php echo htmlspecialchars($rpe); ?></h4>
                    <p class="text-center">Esta es tu área de trabajo.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/views/layouts/footer.php';
