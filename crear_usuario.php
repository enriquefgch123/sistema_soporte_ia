<?php
session_start();
include 'conexion.php';

// Validar que el usuario haya iniciado sesión y sea Administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Administrador') {
    header("Location: login.php");
    exit();
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $rol = $_POST['rol'];

    // Cifrar la contraseña de manera segura
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Verificar si el correo ya existe en la base de datos
    $check_sql = "SELECT id FROM usuarios WHERE email = '$email'";
    $check_result = $conexion->query($check_sql);

    if ($check_result->num_rows > 0) {
        $mensaje = "<div class='alert alert-danger'>El correo electrónico ya se encuentra registrado.</div>";
    } else {
        $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES ('$nombre', '$email', '$password_hash', '$rol')";
        if ($conexion->query($sql) === TRUE) {
            $mensaje = "<div class='alert alert-success'>¡Usuario registrado con éxito en el sistema!</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al registrar el usuario: " . $conexion->error . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario - Sistema de Soporte IA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/jpeg" href="icono.jpg">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Usuarios - Registro de Nuevos Accesos</h2>
            <div>
                <a href="reportes.php" class="btn btn-secondary me-2">Volver a Reportes</a>
                <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
            </div>
        </div>

        <?php echo $mensaje; ?>

        <div class="card shadow p-4 bg-white col-md-6 mx-auto">
            <h4 class="mb-3 text-center">Nuevo Usuario</h4>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre Completo:</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required placeholder="Ej. Ana Gómez">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electrónico:</label>
                    <input type="email" class="form-control" id="email" name="email" required placeholder="analista2@soporte.com">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña Temporal:</label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Mínimo 6 caracteres">
                </div>
                <div class="mb-3">
                    <label for="rol" class="form-label">Rol del Usuario:</label>
                    <select class="form-select" id="rol" name="rol" required>
                        <option value="Cliente">Cliente / Usuario Externo</option>
                        <option value="Analista">Analista de Soporte</option>
                        <option value="Administrador">Administrador</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">Registrar Usuario</button>
            </form>
        </div>
    </div>
</body>
</html>