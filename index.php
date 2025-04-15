<?php
// Mostrar errores para depuración (quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('BASE_URL', '/');
require_once __DIR__ . '/app/config/database.php';

session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rpe = $_POST['rpe'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    if (empty($rpe) || empty($contrasena)) {
        $error = "Por favor ingrese RPE y contraseña";
    } else {
        $stmt = $conexion->prepare("SELECT rpe, contrasena, tipo_usuario FROM registradores WHERE rpe = ?");
        $stmt->bind_param("s", $rpe);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && $contrasena === $user['contrasena']) {
            $_SESSION['rpe'] = $user['rpe'];
            $_SESSION['tipo_usuario'] = $user['tipo_usuario'];

            if ($user['tipo_usuario'] === 'usuario') {
                header("Location: " . BASE_URL . "usuario/dashboard.php");
            } elseif ($user['tipo_usuario'] === 'administrador') {
                header("Location: " . BASE_URL . "administrador/dashboard.php");
            }
            exit();
        } else {
            $error = "Credenciales incorrectas";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CFE - Inicio de Sesión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --cfe-primary: #009156;
            --cfe-primary-dark: #006e42;
            --cfe-primary-light: #e6f3ed;
            --cfe-secondary: #003d2a;
            --cfe-gray: #f5f5f5;
            --cfe-gray-dark: #e0e0e0;
            --cfe-text: #333333;
            --cfe-text-light: #666666;
            --cfe-white: #ffffff;
            --cfe-error: #d32f2f;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', 'Roboto', sans-serif;
            background-color: var(--cfe-gray);
            color: var(--cfe-text);
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            line-height: 1.6;
        }
        
        .login-container {
            display: flex;
            flex: 1;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--cfe-white);
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }
        
        .login-header {
            background-color: var(--cfe-primary);
            color: var(--cfe-white);
            padding: 2rem;
            text-align: center;
        }
        
        .login-header h1 {
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--cfe-secondary);
            font-size: 0.9rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--cfe-gray-dark);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--cfe-primary);
            box-shadow: 0 0 0 3px rgba(0, 145, 86, 0.2);
        }
        
        .input-group {
            position: relative;
            display: flex;
        }
        
        .input-group .form-control {
            flex: 1;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        
        .toggle-password {
            background-color: var(--cfe-primary-light);
            border: 1px solid var(--cfe-gray-dark);
            border-left: none;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            cursor: pointer;
            color: var(--cfe-primary);
            transition: background-color 0.3s;
        }
        
        .toggle-password:hover {
            background-color: var(--cfe-gray-dark);
        }
        
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--cfe-primary);
            color: var(--cfe-white);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }
        
        .btn-login:hover {
            background-color: var(--cfe-primary-dark);
            transform: translateY(-1px);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .error-message {
            color: var(--cfe-error);
            font-size: 0.9rem;
            margin-top: 0.25rem;
            display: none;
        }
        
        .alert-error {
            background-color: rgba(211, 47, 47, 0.1);
            color: var(--cfe-error);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border-left: 4px solid var(--cfe-error);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-error i {
            font-size: 1.2rem;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: var(--cfe-text-light);
        }
        
        .login-footer a {
            color: var(--cfe-primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 1rem;
            }
            
            .login-card {
                border-radius: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>SIMTLEC</h1>
                <p>Acceso al gestor de tabletas</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>
                
                <form id="loginForm" action="<?= BASE_URL ?>index.php" method="POST">
                    <div class="form-group">
                        <label for="rpe" class="form-label">RPE</label>
                        <input type="text" id="rpe" name="rpe" class="form-control" placeholder="Ingrese su RPE" required>
                        <div class="error-message" id="rpe-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="contrasena" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <input type="password" id="contrasena" name="contrasena" class="form-control" placeholder="Ingrese su contraseña" required>
                            <span class="toggle-password" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <div class="error-message" id="password-error"></div>
                    </div>
                    
                    <button type="submit" class="btn-login">Iniciar Sesión</button>
                </form>
                
                <div class="login-footer">
                    <p>¿Problemas para acceder? <a href="#">Contacte al soporte técnico</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mostrar/ocultar contraseña
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
        
        // Validación de formulario
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            let valid = true;
            const rpe = document.getElementById('rpe');
            const password = document.getElementById('contrasena');
            const rpeError = document.getElementById('rpe-error');
            const passwordError = document.getElementById('password-error');
            
            // Reset errores
            rpeError.style.display = 'none';
            passwordError.style.display = 'none';
            
            if (!rpe.value.trim()) {
                rpeError.textContent = 'El RPE es requerido';
                rpeError.style.display = 'block';
                valid = false;
            }
            
            if (!password.value.trim()) {
                passwordError.textContent = 'La contraseña es requerida';
                passwordError.style.display = 'block';
                valid = false;
            }
            
            if (!valid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>