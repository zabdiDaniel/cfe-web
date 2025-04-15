<?php
session_start();
define('BASE_URL', '/');

if (!isset($_SESSION['rpe']) || $_SESSION['tipo_usuario'] !== 'usuario') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}
?>

<?php require_once __DIR__ . '/../views/layouts/header-usuario.php'; ?>

<div class="container mt-5">
    <h1 class="mb-4" style="color: #009156;">Bienvenido, <?= htmlspecialchars($_SESSION['rpe']) ?></h1>
    <p>Esta es la página para usuarios normales.</p>
    <a href="<?= BASE_URL ?>logout.php" class="btn btn-danger">Cerrar Sesión</a>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer-usuario.php'; ?>