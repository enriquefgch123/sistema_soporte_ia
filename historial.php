<?php
include 'conexion.php';

$mensaje = "";

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Procesar la validación o edición humana (CUS-004)
if (isset($_POST['accion']) && $_POST['accion'] == 'validar') {
    $id_ticket = $_POST['id_ticket'];
    $categoria_final = $_POST['categoria_final'];
    $prioridad_final = $_POST['prioridad_final'];

    $sql_update = "UPDATE incidencias SET categoria_final = '$categoria_final', prioridad_final = '$prioridad_final', estado = 'Validado' WHERE id = $id_ticket";
    
    if ($conexion->query($sql_update) === TRUE) {
        $mensaje = "<div class='alert alert-success'>¡Incidencia validada correctamente por el analista!</div>";
    } else {
        $mensaje = "<div class='alert alert-danger'>Error al actualizar: " . $conexion->error . "</div>";
    }
}

// Consultar el historial de incidencias (CUS-005)
$resultado = $conexion->query("SELECT * FROM incidencias ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial y Validación de Tickets</title>
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
                <span class="me-3">Hola, <strong><?php echo isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario'; ?></strong> (<?php echo isset($_SESSION['rol']) ? $_SESSION['rol'] : ''; ?>)</span>
                
                <a href="index.php" class="btn btn-outline-light btn-sm me-2">Nuevo Ticket</a>

                <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador'): ?>
                    <a href="reportes.php" class="btn btn-outline-light btn-sm me-2">Reportes</a>
                    <a href="crear_usuario.php" class="btn btn-success btn-sm me-2">Crear Usuario</a>
                <?php endif; ?>

                <a href="logout.php" class="btn btn-danger btn-sm">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión e Historial de Incidencias (Validación Humana)</h2>
        </div>

        <?php echo $mensaje; ?>

        <div class="card shadow p-3">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>IA (Cat / Prio)</th>
                            <th>Validación Final (Editar si es necesario)</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $resultado->fetch_assoc()) { ?>
                        <tr>
                            <form method="POST" action="">
                                <input type="hidden" name="id_ticket" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="accion" value="validar">
                                
                                <td><strong><?php echo $row['codigo_ticket']; ?></strong><br><small class="text-muted"><?php echo $row['fecha_registro']; ?></small></td>
                                <td><?php echo $row['descripcion_texto']; ?></td>
                                <td>
                                    <span class="badge bg-info text-dark"><?php echo $row['categoria_sugerida']; ?></span>
                                    <span class="badge bg-warning text-dark"><?php echo $row['prioridad_sugerida']; ?></span>
                                    <br><small class="text-muted">Tiempo IA: <?php echo $row['tiempo_procesamiento_ia']; ?>s</small>
                                </td>
                                <td>
                                    <!-- Selección de Categoría Final -->
                                    <select name="categoria_final" class="form-select form-select-sm mb-1">
                                        <?php 
                                        $cats = ['Hardware', 'Software', 'Redes', 'Accesos', 'Impresoras'];
                                        foreach($cats as $c) {
                                            $selected = ($row['categoria_final'] == $c || ($row['categoria_final'] == NULL && $row['categoria_sugerida'] == $c)) ? 'selected' : '';
                                            echo "<option value='$c' $selected>$c</option>";
                                        }
                                        ?>
                                    </select>
                                    <!-- Selección de Prioridad Final -->
                                    <select name="prioridad_final" class="form-select form-select-sm">
                                        <?php 
                                        $pris = ['Baja', 'Media', 'Alta', 'Crítica'];
                                        foreach($pris as $p) {
                                            $selected = ($row['prioridad_final'] == $p || ($row['prioridad_final'] == NULL && $row['prioridad_sugerida'] == $p)) ? 'selected' : '';
                                            echo "<option value='$p' $selected>$p</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo ($row['estado'] == 'Validado') ? 'success' : 'secondary'; ?>">
                                        <?php echo $row['estado']; ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="submit" class="btn btn-primary btn-sm w-100">Guardar</button>
                                </td>
                            </form>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>