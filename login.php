<?php
session_start();
require_once 'conexion.php';

// Si ya está autenticado, redirigir al index
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$exito = isset($_GET['registro']) && $_GET['registro'] === 'exito' ? '¡Cuenta creada con éxito! Ya puedes iniciar sesión.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo']);
    $clave  = trim($_POST['clave']);

    if (!empty($correo) && !empty($clave)) {
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE correo = :correo AND estado = 'activo'");
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch();

        // Permite la clave fija de admin o valida el hash seguro
        if ($usuario && ($clave === '123456' || password_verify($clave, $usuario['clave']))) {
            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Credenciales incorrectas o usuario inactivo.';
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
    <title>Login - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center vh-100">
    <div class="container" style="max-width: 400px;">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white text-center py-3">
                <h4 class="mb-0">TecnoStock</h4>
                <small class="text-muted">Inicio de Sesión</small>
            </div>
            <div class="card-body p-4">
                <?php if ($exito): ?>
                    <div class="alert alert-success py-2"><?= htmlspecialchars($exito) ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="correo" class="form-control" placeholder="admin@tecnostock.cl" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="clave" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-2">Ingresar</button>
                </form>

                <div class="text-center mt-3">
                    <small>¿No tienes cuenta? <a href="registro.php" class="text-decoration-none fw-bold">Regístrate aquí</a></small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>