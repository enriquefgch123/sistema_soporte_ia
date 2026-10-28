<?php
include 'conexion.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Procesar el reentrenamiento al hacer clic en el botón
$mensaje_reentreno = "";
if (isset($_POST['reentrenar_ia'])) {
    $output = [];
    $return_var = null;
    
    exec("python reentrenar.py 2>&1", $output, $return_var);

    if ($return_var === 0) {
        $mensaje_reentreno = "<div class='alert alert-success alert-dismissible fade show' role='alert'><strong>¡Éxito!</strong> La IA se ha reentrenado correctamente con las últimas validaciones de los analistas.<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    } else {
        $mensaje_reentreno = "<div class='alert alert-danger alert-dismissible fade show' role='alert'><strong>Error al reentrenar:</strong> " . htmlspecialchars(implode(" ", $output)) . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    }
}

// Consultar estadísticas generales para el reporte
$total_tickets = $conexion->query("SELECT COUNT(*) as total FROM incidencias")->fetch_assoc()['total'];
$total_validados = $conexion->query("SELECT COUNT(*) as total FROM incidencias WHERE estado = 'Validado'")->fetch_assoc()['total'];
$tiempo_promedio = $conexion->query("SELECT AVG(tiempo_procesamiento_ia) as promedio FROM incidencias")->fetch_assoc()['promedio'];

// Calcular coincidencia (exactitud de la IA frente a la validación humana)
$coincidencias = $conexion->query("SELECT COUNT(*) as total FROM incidencias WHERE categoria_sugerida = categoria_final AND prioridad_sugerida = prioridad_final AND estado = 'Validado'")->fetch_assoc()['total'];

$porcentaje_exactitud = ($total_validados > 0) ? round(($coincidencias / $total_validados) * 100, 2) : 0;

// Consultar desglose por categoría sugerida (IA)
$desglose_categorias_ia = $conexion->query("SELECT categoria_sugerida, COUNT(*) as total FROM incidencias GROUP BY categoria_sugerida");

// Consultar desglose por categoría real / final (Revisión Manual / Humana)
$desglose_categorias_reales = $conexion->query("SELECT categoria_final, COUNT(*) as total FROM incidencias WHERE estado = 'Validado' AND categoria_final IS NOT NULL GROUP BY categoria_final");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes y Métricas - Concentrix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/jpeg" href="icono.jpg">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: white !important;
            }
            .card {
                border: 1px solid #ddd !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="bg-light">

    <!-- Barra de navegación (Se oculta al imprimir) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 no-print">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="logo.jpg" alt="Concentrix" style="height: 32px;" class="me-2">
                <span style="font-size: 1rem; font-weight: 300; opacity: 0.8; color: #fff;">| Soporte IA</span>
            </a>

            <div class="d-flex text-white align-items-center">
                <span class="me-3">Hola, <strong><?php echo isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario'; ?></strong> (<?php echo isset($_SESSION['rol']) ? $_SESSION['rol'] : ''; ?>)</span>
                
                <a href="index.php" class="btn btn-outline-light btn-sm me-2">Nuevo Ticket</a>
                <a href="historial.php" class="btn btn-outline-light btn-sm me-2">Historial</a>

                <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador'): ?>
                    <a href="crear_usuario.php" class="btn btn-success btn-sm me-2">Crear Usuario</a>
                <?php endif; ?>

                <a href="logout.php" class="btn btn-danger btn-sm">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Mostrar alerta si se ejecutó el reentrenamiento -->
        <?php echo $mensaje_reentreno; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Reporte Operativo y Métricas del Sistema</h2>
            <div>
                <!-- Botón para Reentrenar la IA con un clic -->
                <form method="POST" class="d-inline">
                    <button type="submit" name="reentrenar_ia" class="btn btn-success btn-sm me-2 no-print">🧠 Reentrenar IA</button>
                </form>
                <!-- Botón de Imprimir / PDF -->
                <button onclick="window.print();" class="btn btn-dark btn-sm no-print">🖨️ Imprimir / Exportar PDF</button>
            </div>
        </div>

        <!-- Tarjetas de Estadísticas -->
        <div class="row text-center mb-4">
            <div class="col-md-3">
                <div class="card shadow p-3 bg-white">
                    <h5>Total Tickets</h5>
                    <h3><?php echo $total_tickets; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow p-3 bg-white">
                    <h5>Tickets Validados</h5>
                    <h3><?php echo $total_validados; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow p-3 bg-white">
                    <h5>Tiempo Promedio IA</h5>
                    <h3><?php echo round($tiempo_promedio, 4); ?>s</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow p-3 bg-white">
                    <h5>Exactitud del Modelo</h5>
                    <h3 class="text-success"><?php echo $porcentaje_exactitud; ?>%</h3>
                </div>
            </div>
        </div>

        <!-- Primer Cuadro: Desglose según Predicción de la IA -->
        <div class="card shadow p-4 bg-white mb-4">
            <h4 class="mb-3">Desglose de Incidencias (Predicción Inicial de la IA)</h4>
            <hr>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Categoría Sugerida (IA)</th>
                            <th>Cantidad de Tickets Registrados</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($desglose_categorias_ia->num_rows > 0): ?>
                            <?php while($cat = $desglose_categorias_ia->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge bg-secondary text-white"><?php echo $cat['categoria_sugerida']; ?></span></td>
                                <td><strong><?php echo $cat['total']; ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted">No hay incidencias registradas todavía.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Segundo Cuadro: Desglose Real Post-Revisión Manual (Humana) -->
        <div class="card shadow p-4 bg-white mb-4">
            <h4 class="mb-3">Desglose Real de Incidencias (Post-Revisión y Validación Humana)</h4>
            <hr>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-success">
                        <tr>
                            <th>Categoría Real (Validada por Analista)</th>
                            <th>Cantidad de Tickets Validados</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($desglose_categorias_reales->num_rows > 0): ?>
                            <?php while($cat_real = $desglose_categorias_reales->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge bg-success text-white"><?php echo $cat_real['categoria_final']; ?></span></td>
                                <td><strong><?php echo $cat_real['total']; ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted">No hay tickets validados por los analistas todavía.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>