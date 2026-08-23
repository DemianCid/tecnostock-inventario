<?php
session_start();
require_once 'conexion.php';

// Si ya inició sesión, redirigir al inventario
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $clave  = trim($_POST['clave']);

    if (!empty($nombre) && !empty($correo) && !empty($clave)) {
        // Verificar si el correo ya existe
        $stmt_check = $conexion->prepare("SELECT id FROM usuarios WHERE correo = :correo");
        $stmt_check->execute([':correo' => $correo]);

        if ($stmt_check->fetch()) {
            $error = 'El correo electrónico ya está registrado.';
        } else {
            // Cifrar contraseña e insertar nuevo usuario
            $clave_hash = password_hash($clave, PASSWORD_DEFAULT);

            $stmt_insert = $conexion->prepare("
                INSERT INTO usuarios (nombre, correo, clave, estado) 
                VALUES (:nombre, :correo, :clave, 'activo')
            ");
            
            $stmt_insert->execute([
                ':nombre' => $nombre,
                ':correo' => $correo,
                ':clave'  => $clave_hash
            ]);

            // Redirigir al login con mensaje de éxito
            header('Location: login.php?registro=exito');
            exit;
        }
    } else {
        $error = 'Por favor completa todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center vh-100">
    <div class="container" style="max-width: 420px;">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0">TecnoStock</h4>
                <small>Crear Nueva Cuenta</small>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="registro.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Juan Pérez" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="correo" class="form-control" placeholder="juan@tecnostock.cl" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="clave" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-2">Crear Cuenta</button>
                </form>

                <div class="text-center mt-3">
                    <small>¿Ya tienes cuenta? <a href="login.php" class="text-decoration-none">Iniciar Sesión</a></small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>