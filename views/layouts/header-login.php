<!-- filepath: d:\xammp\htdocs\cfe-web\views\layouts\header-login.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'SIMTLEC'; ?></title>

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .header-login-title {
            text-align: center;
            margin-top: 40px;
            font-size: 2rem;
            font-weight: 600;
            color: #ffffff;
        }

        .header-login-title::after {
            content: "";
            display: block;
            width: 50px;
            height: 3px;
            margin: 15px auto 0;
            background-color: #ffffff;
            border-radius: 2px;
        }

        .header-login-container {
            background: linear-gradient(135deg, #4e73df, #2e59d9);
            padding: 20px 0;
        }
    </style>
</head>
<body class="header-login-container">
    <div class="header-login-title">
        SIMTLEC: SISTEMA DE MONITOREO DE TABLETAS
    </div>
</body>
</html>
