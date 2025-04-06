<div class="card shadow">
    <div class="card-header bg-primary text-white text-center">
        <h4>Iniciar Sesión</h4>
    </div>
    <div class="card-body">
        <!-- Mensaje de error (se usará después) -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form action="<?= $basePath ?? '' ?>/cfe-web/login.php" method="POST">
            <div class="mb-3">
                <label for="rpe" class="form-label">RPE</label>
                <input type="text" name="rpe" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="contrasena" class="form-label">Contraseña</label>
                <input type="password" name="contrasena" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
        </form>
    </div>
</div>