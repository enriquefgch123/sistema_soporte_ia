<?php
session_start();
include 'conexion.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Por favor, ingresa tanto el correo como la contraseña.";
    } else {
        // Buscar el usuario en la base de datos
        $sql = "SELECT * FROM usuarios WHERE email = '$email'";
        $result = $conexion->query($sql);

        if ($result && $result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
            
            // Verificar la contraseña cifrada
            if (password_verify($password, $usuario['password_hash'])) {
                // Guardar datos en la sesión
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['rol'] = $usuario['rol'];

                // Redirigir según el rol
                if ($usuario['rol'] == 'Administrador') {
                    header("Location: reportes.php");
                } elseif ($usuario['rol'] == 'Analista') {
                    header("Location: historial.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = "Contraseña incorrecta.";
            }
        } else {
            $error = "El correo electrónico no está registrado en la base de datos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - Mesa de Ayuda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/jpeg" href="icono.jpg">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow p-4 bg-white">
                    <h3 class="text-center mb-4">Iniciar Sesión</h3>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" autocomplete="off">
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico:</label>
                            <input type="email" class="form-control" id="email" name="email" required value="admin@concentrix.com">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña:</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>