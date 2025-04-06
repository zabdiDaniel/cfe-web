<div class="d-flex justify-content-center align-items-center vh-100 bg-light" style="position: relative;">
    <div class="card shadow-lg border-0" style="width: 100%; max-width: 380px; border-radius: 20px; position: absolute; top: 12%;">
        <div class="card-body p-4">
            <h3 class="text-center mb-4" style="font-weight: bold; color: #333; font-size: 1.5rem;">Bienvenido</h3>

            <!-- Mensaje de error -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger text-center mb-3"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form action="<?= $basePath ?? '' ?>/cfe-web/login.php" method="POST">
                <div class="mb-3">
                    <label for="rpe" class="form-label" style="font-weight: 500; color: #555;">RPE</label>
                    <input type="text" name="rpe" class="form-control form-control-lg" placeholder="Ingresa tu RPE" required style="border-radius: 10px; box-shadow: rgba(0, 0, 0, 0.1) 0px 4px 6px;">
                </div>
                <div class="mb-3">
                    <label for="contrasena" class="form-label" style="font-weight: 500; color: #555;">Contraseña</label>
                    <div class="input-group">
                        <input type="password" id="contrasena" name="contrasena" class="form-control form-control-lg" placeholder="Ingresa tu contraseña" required style="border-top-left-radius: 10px; border-bottom-left-radius: 10px;">
                        <button type="button" id="togglePassword" class="btn btn-outline-secondary" style="border-top-right-radius: 10px; border-bottom-right-radius: 10px;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100" style="border-radius: 15px; padding: 12px; font-size: 1.1rem; box-shadow: rgba(0, 0, 0, 0.1) 0px 4px 6px;">
                    Ingresar
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('contrasena');
        const icon = this.querySelector('i');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
</script>