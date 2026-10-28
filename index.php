<?php
session_start();

// Verificar inicio de sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

include 'conexion.php';

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo_ticket = "TICK-" . time();
    $descripcion = $_POST['descripcion'];

    // 1. Consumir la API de Python (Motor de IA) mediante cURL
    $url = "http://127.0.0.1:8000/predecir";
    $data = json_encode(array("descripcion" => $descripcion));

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
    $response = curl_exec($ch);
    curl_close($ch);

    $resultado_ia = json_decode($response, true);

    if ($resultado_ia) {
        $categoria_sugerida = $resultado_ia['categoria_sugerida'];
        $prioridad_sugerida = $resultado_ia['prioridad_sugerida'];
        $tiempo_ia = $resultado_ia['tiempo_procesamiento_ia'];

        // 2. Guardar en la base de datos MySQL (CUS-001 y CUS-002/003)
        $sql = "INSERT INTO incidencias (codigo_ticket, descripcion_texto, categoria_sugerida, prioridad_sugerida, tiempo_procesamiento_ia, estado) 
                VALUES ('$codigo_ticket', '$descripcion', '$categoria_sugerida', '$prioridad_sugerida', '$tiempo_ia', 'Pendiente')";

        if ($conexion->query($sql) === TRUE) {
            $mensaje = "<div class='alert alert-success'>¡Incidencia registrada y clasificada por la IA con éxito! Ticket: <strong>$codigo_ticket</strong> | Categoría: <strong>$categoria_sugerida</strong> | Prioridad: <strong>$prioridad_sugerida</strong></div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al guardar en la BD: " . $conexion->error . "</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>No se pudo conectar con el motor de IA en Python. Asegúrate de que Uvicorn esté encendido.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mesa de Ayuda - Concentrix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/jpeg" href="icono.jpg">
</head>
<body class="bg-light">

    <!-- Barra de navegación unificada con el Logotipo de Concentrix -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <!-- Logotipo incorporado -->
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="logo.jpg" alt="Concentrix" style="height: 32px;" class="me-2">
                <span style="font-size: 1rem; font-weight: 300; opacity: 0.8; color: #fff;">| Soporte IA</span>
            </a>

            <div class="d-flex text-white align-items-center">
                <span class="me-3">Hola, <strong><?php echo $_SESSION['nombre']; ?></strong> (<?php echo $_SESSION['rol']; ?>)</span>
                
                <?php if($_SESSION['rol'] == 'Analista'): ?>
                    <a href="historial.php" class="btn btn-outline-light btn-sm me-2">Ir a Historial</a>
                <?php elseif($_SESSION['rol'] == 'Administrador'): ?>
                    <a href="historial.php" class="btn btn-outline-light btn-sm me-2">Historial</a>
                    <a href="reportes.php" class="btn btn-outline-light btn-sm me-2">Reportes</a>
                    <a href="crear_usuario.php" class="btn btn-success btn-sm me-2">Crear Usuario</a>
                <?php endif; ?>

                <a href="logout.php" class="btn btn-danger btn-sm">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2 class="text-center mb-4">Registro Inteligente de Incidencias</h2>
        
        <?php echo $mensaje; ?>

        <div class="card shadow p-4">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción del Problema Técnico (Cliente):</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required placeholder="Ej. La computadora no enciende y se escucha un pitido de falla de hardware..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Registrar y Analizar con IA</button>
            </form>
        </div>
    </div>
</body>
</html>