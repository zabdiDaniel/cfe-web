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

require_once $_SERVER['DOCUMENT_ROOT'] . '/app/config/database.php';

// Obtener lista de activos para el dropdown
$activos_query = "SELECT activo FROM tabletas ORDER BY activo ASC";
$activos_result = $conexion->query($activos_query);
$activos = $activos_result->fetch_all(MYSQLI_ASSOC);

// Cargar datos de la tableta seleccionada
$datos_tableta = null;
$fotos = [];
if (isset($_GET['activo']) && !empty($_GET['activo'])) {
    $activo = $_GET['activo'];
    $stmt = $conexion->prepare("SELECT * FROM tabletas WHERE activo = ?");
    $stmt->bind_param("s", $activo);
    $stmt->execute();
    $result = $stmt->get_result();
    $datos_tableta = $result->fetch_assoc();

    // Buscar las 4 fotos
    if ($datos_tableta) {
        for ($i = 1; $i <= 4; $i++) {
            $foto_path = $_SERVER['DOCUMENT_ROOT'] . "/cfe-api/uploads/tabletas/{$activo}_{$i}.jpg";
            if (file_exists($foto_path)) {
                $fotos[] = "{$activo}_{$i}.jpg";
            }
        }
    }
}
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/views/layouts/header-admin.php'; ?>

<div class="container mt-5">
    <h1 class="mb-4" style="color: #003d2a; font-weight: 700;">Reportes de Tabletas</h1>

    <div class="mb-4 d-flex align-items-center gap-3">
        <form method="GET" action="<?= BASE_URL ?>administrador/reportes_tabletas.php" class="d-flex" style="max-width: 400px; flex-grow: 1;">
            <select name="activo" id="activoSelect" class="form-control" style="border-radius: 8px 0 0 8px; border-color: #003d2a;" onchange="this.form.submit()">
                <option value="">Selecciona un activo</option>
                <?php foreach ($activos as $activo): ?>
                    <option value="<?= htmlspecialchars($activo['activo']) ?>" <?= isset($_GET['activo']) && $_GET['activo'] === $activo['activo'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($activo['activo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn" style="background-color: #003d2a; color: #fff; border-radius: 0 8px 8px 0;"><i class="bi bi-arrow-right"></i></button>
        </form>
        <?php if ($datos_tableta): ?>
            <button id="generarPdfBtn" class="btn btn-primary" style="background-color: #003d2a; border: none; padding: 10px 20px; border-radius: 8px;">
                <i class="bi bi-file-earmark-pdf"></i> Generar PDF
            </button>
        <?php endif; ?>
    </div>

    <?php if ($datos_tableta): ?>
        <!-- Contenedor del reporte -->
        <div id="reportePDF" class="reporte-container p-4" style="background-color: #fff; border: 1px solid #003d2a; border-radius: 10px;">
            <div class="d-flex justify-content-between mb-4">
                <div class="logo-placeholder" style="width: 100px; height: 100px; background-color: #e0e0e0; border: 2px dashed #003d2a;"></div>
                <h2 class="text-center flex-grow-1" style="color: #003d2a;">Reporte de Tableta</h2>
                <div class="logo-placeholder" style="width: 100px; height: 100px; background-color: #e0e0e0; border: 2px dashed #003d2a;"></div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong>Activo:</strong> <?= htmlspecialchars($datos_tableta['activo']) ?></p>
                    <p><strong>Marca:</strong> <?= htmlspecialchars($datos_tableta['marca']) ?></p>
                    <p><strong>Modelo:</strong> <?= htmlspecialchars($datos_tableta['modelo']) ?></p>
                    <p><strong>Inventario:</strong> <?= htmlspecialchars($datos_tableta['inventario'] ?? 'N/A') ?></p>
                    <p><strong>Número de Serie:</strong> <?= htmlspecialchars($datos_tableta['numero_serie']) ?></p>
                    <p><strong>Versión Android:</strong> <?= htmlspecialchars($datos_tableta['version_android'] ?? 'N/A') ?></p>
                    <p><strong>Año de Adquisición:</strong> <?= htmlspecialchars($datos_tableta['anio_adquisicion'] ?? 'N/A') ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Agencia:</strong> <?= htmlspecialchars($datos_tableta['agencia'] ?? 'N/A') ?></p>
                    <p><strong>Proceso:</strong> <?= htmlspecialchars($datos_tableta['proceso'] ?? 'N/A') ?></p>
                    <p><strong>RPE Trabajador:</strong> <?= htmlspecialchars($datos_tableta['rpe_trabajador']) ?></p>
                    <p><strong>Ubicación Registro:</strong> <?= htmlspecialchars($datos_tableta['ubicacion_registro'] ?? 'N/A') ?></p>
                    <p><strong>Fecha Registro:</strong> <?= htmlspecialchars($datos_tableta['fecha_registro'] ?? 'N/A') ?></p>
                    <p><strong>Marca Chip:</strong> <?= htmlspecialchars($datos_tableta['marca_chip'] ?? 'N/A') ?></p>
                    <p><strong>Número Serie Chip:</strong> <?= htmlspecialchars($datos_tableta['numero_serie_chip'] ?? 'N/A') ?></p>
                </div>
            </div>

            <div class="text-center mb-4">
                <h6>Fotos de la Tableta</h6>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <?php if (!empty($fotos)): ?>
                        <?php foreach ($fotos as $index => $foto): ?>
                            <img id="tabletaFoto<?= $index + 1 ?>" src="<?= BASE_URL ?>cfe-api/uploads/tabletas/<?= htmlspecialchars($foto) ?>" alt="Foto Tableta <?= $index + 1 ?>" class="img-fluid" style="max-width: 150px; border-radius: 8px; border: 2px solid #003d2a;" data-bs-toggle="modal" data-bs-target="#fotoModal" data-src="<?= BASE_URL ?>cfe-api/uploads/tabletas/<?= htmlspecialchars($foto) ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No hay fotos disponibles.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="text-center">
                <p><strong>Firma:</strong></p>
                <div style="width: 200px; height: 50px; border: 1px solid #003d2a; margin: 0 auto;"></div>
            </div>
        </div>

        <!-- Modal para ver fotos más grandes -->
        <div class="modal fade" id="fotoModal" tabindex="-1" aria-labelledby="fotoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="fotoModalLabel">Foto de la Tableta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img id="modalFoto" src="" alt="Foto Grande" style="max-width: 100%; max-height: 70vh;">
                    </div>
                </div>
            </div>
        </div>
    <?php elseif (isset($_GET['activo'])): ?>
        <p class="text-danger">No se encontró la tableta con el activo seleccionado.</p>
    <?php endif; ?>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/views/layouts/footer-admin.php'; ?>

<!-- Incluir jsPDF desde CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    // Mostrar foto en modal al hacer clic
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(img => {
        img.addEventListener('click', function() {
            const src = this.getAttribute('data-src');
            document.getElementById('modalFoto').src = src;
        });
    });

    // Generar PDF con jsPDF
    document.getElementById('generarPdfBtn').addEventListener('click', function() {
        const {
            jsPDF
        } = window.jspdf;
        const doc = new jsPDF({
            unit: 'mm',
            format: 'letter',
            orientation: 'portrait'
        });

        // Configuración inicial
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        const margin = 10;
        let yPosition = margin;

        // Logos
        doc.setFillColor(224, 224, 224);
        doc.rect(margin, yPosition, 30, 30, 'F'); // Logo izquierda
        doc.rect(pageWidth - margin - 30, yPosition, 30, 30, 'F'); // Logo derecha
0


        // Título
        yPosition += 15;
        doc.setFontSize(16);
        doc.text('Reporte de Tableta', pageWidth / 2, yPosition, {
            align: 'center'
        });

        yPosition += 30;
        doc.setFontSize(11); // Aumenta ligeramente el tamaño de letra
        const lineHeight = 6;
        const colWidth = (pageWidth - 3 * margin) / 2;

        const dataLeft = [
            ['Activo:', `<?= htmlspecialchars($datos_tableta['activo']) ?>`],
            ['Marca:', `<?= htmlspecialchars($datos_tableta['marca']) ?>`],
            ['Modelo:', `<?= htmlspecialchars($datos_tableta['modelo']) ?>`],
            ['Inventario:', `<?= htmlspecialchars($datos_tableta['inventario'] ?? 'N/A') ?>`],
            ['Número de Serie:', `<?= htmlspecialchars($datos_tableta['numero_serie']) ?>`],
            ['Versión Android:', `<?= htmlspecialchars($datos_tableta['version_android'] ?? 'N/A') ?>`],
            ['Año de Adquisición:', `<?= htmlspecialchars($datos_tableta['anio_adquisicion'] ?? 'N/A') ?>`]
        ];

        const dataRight = [
            ['Agencia:', `<?= htmlspecialchars($datos_tableta['agencia'] ?? 'N/A') ?>`],
            ['Proceso:', `<?= htmlspecialchars($datos_tableta['proceso'] ?? 'N/A') ?>`],
            ['RPE Trabajador:', `<?= htmlspecialchars($datos_tableta['rpe_trabajador']) ?>`],
            ['Ubicación Registro:', `<?= htmlspecialchars($datos_tableta['ubicacion_registro'] ?? 'N/A') ?>`],
            ['Fecha Registro:', `<?= htmlspecialchars($datos_tableta['fecha_registro'] ?? 'N/A') ?>`],
            ['Marca Chip:', `<?= htmlspecialchars($datos_tableta['marca_chip'] ?? 'N/A') ?>`],
            ['Número Serie Chip:', `<?= htmlspecialchars($datos_tableta['numero_serie_chip'] ?? 'N/A') ?>`]
        ];

        // Función para imprimir con negrita el título y normal el valor
        const printData = (dataArray, x, yStart) => {
            dataArray.forEach(([label, value], i) => {
                const y = yStart + i * lineHeight;

                doc.setFont('helvetica', 'bold');
                doc.text(label, x, y);

                const labelWidth = doc.getTextWidth(label) + 1;
                doc.setFont('helvetica', 'normal');
                doc.text(value, x + labelWidth, y);
            });
        };

        let yData = yPosition;
        printData(dataLeft, margin, yData);
        printData(dataRight, margin + colWidth + margin, yData);


        // Fotos
        yPosition = yData + 50;
        doc.setFontSize(12);
        doc.text('Fotos de la Tableta:', pageWidth / 2, yPosition, {
            align: 'center'
        });
        yPosition += 10;

        const fotos = [
            document.getElementById('tabletaFoto1'),
            document.getElementById('tabletaFoto2'),
            document.getElementById('tabletaFoto3'),
            document.getElementById('tabletaFoto4')
        ];

        const fotoSize = 55; // Aumentamos tamaño
        const espacioEntreFotos = 10;
        const fotosPorFila = 2;

        let xStart = (pageWidth - ((fotoSize * fotosPorFila) + espacioEntreFotos)) / 2;
        let x = xStart;
        let y = yPosition;

        fotos.forEach((foto, index) => {
            if (foto) {
                const imgData = getImageData(foto);
                if (imgData) {
                    doc.addImage(imgData, 'JPEG', x, y, fotoSize, fotoSize);
                    if ((index + 1) % fotosPorFila === 0) {
                        y += fotoSize + espacioEntreFotos;
                        x = xStart;
                    } else {
                        x += fotoSize + espacioEntreFotos;
                    }
                }
            }
        });



        // Firma (centrada)
        yPosition += 135;
        doc.setFontSize(12);

        const firmaLabel = 'Firma:';
        const firmaBoxWidth = 60;
        const firmaBoxHeight = 15;
        const firmaX = (pageWidth - firmaBoxWidth) / 2; // Centrar horizontalmente

        // Título centrado
        doc.text(firmaLabel, pageWidth / 2, yPosition, {
            align: 'center'
        });

        yPosition += 5;

        // Recuadro centrado
        doc.rect(firmaX, yPosition, firmaBoxWidth, firmaBoxHeight);


        // Guardar el PDF
        doc.save(`reporte_tableta_<?= htmlspecialchars($datos_tableta['activo']) ?>.pdf`);
    });

    // Función para obtener los datos de la imagen
    function getImageData(img) {
        const canvas = document.createElement('canvas');
        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, img.naturalWidth, img.naturalHeight);
        return canvas.toDataURL('image/jpeg');
    }
</script>

<style>
    .card-body p {
        margin-bottom: 0.5rem;
    }

    .btn-primary:hover {
        background-color: #00261d;
        transform: scale(1.05);
        box-shadow: 0 3px 6px rgba(0, 61, 42, 0.3);
    }

    .form-control:focus {
        border-color: #003d2a;
        box-shadow: 0 0 5px rgba(0, 61, 42, 0.5);
    }

    .reporte-container {
        width: 100%;
        max-width: 8.5in;
        background-color: #fff;
        border: 1px solid #003d2a;
        border-radius: 10px;
    }

    .reporte-container p {
        margin-bottom: 0.3rem;
        font-size: 14px;
    }

    .reporte-container h2 {
        font-size: 24px;
    }

    .reporte-container h6 {
        font-size: 16px;
    }
</style>