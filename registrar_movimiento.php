<?php
session_start();
require_once 'conexion.php';

// Verificar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener lista de productos activos
$stmt_prod = $conexion->query("SELECT id, codigo, nombre, stock_actual FROM productos WHERE estado = 'activo' ORDER BY nombre ASC");
$productos = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_id = (int)$_POST['producto_id'];
    $tipo        = $_POST['tipo'];
    $cantidad    = (int)$_POST['cantidad'];
    $observacion = trim($_POST['observacion']);
    $usuario_id  = $_SESSION['usuario_id'];

    if ($producto_id > 0 && in_array($tipo, ['entrada', 'salida']) && $cantidad > 0) {
        
        // Consultar el producto seleccionado para validar el stock disponible
        $stmt_check = $conexion->prepare("SELECT stock_actual FROM productos WHERE id = :id");
        $stmt_check->execute([':id' => $producto_id]);
        $producto = $stmt_check->fetch();

        if ($producto) {
            // Regla de negocio: Una salida no puede dejar el stock bajo cero
            if ($tipo === 'salida' && $producto['stock_actual'] < $cantidad) {
                $error = 'Error: La cantidad a retirar supera el stock actual disponible (' . $producto['stock_actual'] . ' unidades).';
            } else {
                try {
                    // 1. Registrar el movimiento
                    $stmt_mov = $conexion->prepare("
                        INSERT INTO movimientos (tipo, cantidad, observacion, producto_id, usuario_id) 
                        VALUES (:tipo, :cantidad, :observacion, :producto_id, :usuario_id)
                    ");
                    $stmt_mov->execute([
                        ':tipo'        => $tipo,
                        ':cantidad'    => $cantidad,
                        ':observacion' => $observacion,
                        ':producto_id' => $producto_id,
                        ':usuario_id'  => $usuario_id
                    ]);

                    // 2. Actualizar el stock del producto automáticamente
                    $nuevo_stock = ($tipo === 'entrada') 
                        ? $producto['stock_actual'] + $cantidad 
                        : $producto['stock_actual'] - $cantidad;

                    $stmt_update = $conexion->prepare("UPDATE productos SET stock_actual = :stock WHERE id = :id");
                    $stmt_update->execute([':stock' => $nuevo_stock, ':id' => $producto_id]);

                    header('Location: index.php');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Error en el servidor al registrar el movimiento.';
                }
            }
        } else {
            $error = 'El producto seleccionado no existe.';
        }
    } else {
        $error = 'Por favor completa todos los campos requeridos con valores válidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimiento de Inventario - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5" style="max-width: 600px;">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Registrar Entrada / Salida de Stock</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="registrar_movimiento.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Producto</label>
                        <select name="producto_id" class="form-select" required>
                            <option value="">-- Seleccionar Producto --</option>
                            <?php foreach ($productos as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= htmlspecialchars($p['nombre']) ?> (Código: <?= htmlspecialchars($p['codigo']) ?> | Stock actual: <?= $p['stock_actual'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo de Movimiento</label>
                        <select name="tipo" class="form-select" required>
                            <option value="entrada">Entrada (Aumenta stock)</option>
                            <option value="salida">Salida (Disminuye stock)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cantidad</label>
                        <input type="number" name="cantidad" class="form-control" min="1" placeholder="Ej: 10" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observación / Detalle</label>
                        <input type="text" name="observacion" class="form-control" placeholder="Ej: Compra a proveedor / Venta boleta #1024">
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar Movimiento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>