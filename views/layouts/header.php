<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'CFE - Sistema de Control'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --cfe-blue: #005baa;
            --cfe-dark: #003865;
        }
        .navbar-cfe {
            background: linear-gradient(135deg, var(--cfe-blue) 0%, var(--cfe-dark) 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 0;
        }
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .navbar-brand img {
            height: 32px;
            margin-right: 10px;
        }
        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            transform: translateY(-2px);
        }
        .user-dropdown .dropdown-toggle::after {
            display: none;
        }
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
        }
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-cfe sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= $basePath ?? '' ?>/dashboard.php">
                <img src="<?= $basePath ?? '' ?>/assets/img/cfe-logo-white.png" alt="CFE Logo">
                <span>Control Interno</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?? '' ?>/dashboard.php">
                            <i class="fas fa-home me-1"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-file-alt me-1"></i> Reportes
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $basePath ?? '' ?>/reports/daily.php">Diarios</a></li>
                            <li><a class="dropdown-item" href="<?= $basePath ?? '' ?>/reports/weekly.php">Semanales</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= $basePath ?? '' ?>/reports/custom.php">Personalizados</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?? '' ?>/inventory.php">
                            <i class="fas fa-boxes me-1"></i> Inventario
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?? '' ?>/users.php">
                            <i class="fas fa-users me-1"></i> Usuarios
                        </a>
                    </li>
                </ul>
                
                <?php if(isset($_SESSION['rpe'])): ?>
                <div class="d-flex align-items-center user-dropdown">
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <span><?= htmlspecialchars($_SESSION['rpe']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?= $basePath ?? '' ?>/profile.php">
                                    <i class="fas fa-user-cog me-2"></i> Perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $basePath ?? '' ?>/settings.php">
                                    <i class="fas fa-cog me-2"></i> Configuración
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= $basePath ?? '' ?>/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Cerrar sesión
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <main class="container-fluid px-4 py-4">