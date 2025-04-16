<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMTLEC - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #003d2a;
            --secondary: #E6F3ED;
            --text-dark: #333333;
            --shadow: rgba(0, 61, 42, 0.2);
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background-color: var(--primary);
            box-shadow: 0 4px 10px var(--shadow);
        }
        .navbar-brand {
            font-weight: 700;
            transition: color 0.3s ease;
        }
        .navbar-brand:hover {
            color: #e6f3ed;
        }
        .nav-link {
            color: #fff;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            color: #e6f3ed;
            transform: translateY(-2px);
        }
        .dropdown-menu {
            background-color: #fff;
            border: none;
            box-shadow: 0 4px 10px var(--shadow);
            border-radius: 8px;
        }
        .dropdown-item {
            color: var(--text-dark);
            transition: background-color 0.3s ease;
        }
        .dropdown-item:hover {
            background-color: var(--secondary);
        }
        main {
            flex: 1;
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        .table-hover tbody tr:hover {
            background-color: var(--secondary);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= BASE_URL ?>administrador/dashboard.php">
                <span class="text-white">SIMTLEC </span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>administrador/dashboard.php">
                            <i class="bi bi-house-door-fill me-1"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="personalDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-people-fill me-1"></i> Personal
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="personalDropdown">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>administrador/personal.php">Gestión de Personal</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>administrador/registradores.php">Gestión de Registradores</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="tabletasDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-tablet-fill me-1"></i> Tabletas
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="tabletasDropdown">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>administrador/reportes_tabletas.php">Generar Reportes</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>administrador/gestionar_tabletas.php">Gestionar Tabletas</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>administrador/gestionar.php">
                            <i class="bi bi-gear-fill me-1"></i> Gestión
                        </a>
                    </li>
                </ul>
                <?php if (isset($_SESSION['rpe'])): ?>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['rpe']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main>